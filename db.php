<?php
require_once __DIR__ . '/lib/env.php';
require_once __DIR__ . '/lib/database.php';

if (!defined('CREDITLAB_SKIP_SESSION')) {
	session_start();
}

if (env_bool('APP_DEBUG', false)) {
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);
} else {
	ini_set('display_errors', '0');
	ini_set('display_startup_errors', '0');
	error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

if (!isset($GLOBALS['db']) || !($GLOBALS['db'] instanceof mysqli)) {
	$db = creditlab_db_connect();
	if (!$db) {
		if (defined('CREDITLAB_DB_BOOTSTRAP')) {
			error_log('creditlab db bootstrap: connection failed (webhook should pass mysqli)');
		} else {
			creditlab_db_connection_failed('Database connection failed. Check DB_* settings in .env');
		}
	}
} else {
	$db = $GLOBALS['db'];
}

// Helper function to ensure database connection is valid
function ensure_db_connection() {
	global $db;
	static $checking = false;
	
	// Prevent infinite recursion
	if ($checking) {
		return false;
	}
	$checking = true;
	
	$needs_reconnect = false;
	
	try {
		// Use gettype to check without accessing the object
		$db_type = @gettype($db);
		
		// If $db is not set or not an object, we need to reconnect
		if ($db_type !== 'object') {
			$needs_reconnect = true;
		} else {
			// Check class name without accessing object properties
			$class_name = @get_class($db);
			if ($class_name !== 'mysqli') {
				$needs_reconnect = true;
			}
		}
	} catch (Exception $e) {
		// If any error occurs, reconnect
		$needs_reconnect = true;
	} catch (Error $e) {
		// Catch PHP 7+ errors as well
		$needs_reconnect = true;
	}
	
	if ($needs_reconnect) {
		// Just unset and create new - don't try to access the old connection
		$db = null;
		unset($db);
		
		$db = creditlab_db_connect();
		if (!$db) {
			error_log("Database reconnection failed");
			$checking = false;
			return false;
		}
	}
	
	$checking = false;
	return true;
}

/**
 * Ensure a column exists on the user table (self-healing for manual migrations).
 *
 * @param string $column     Column name (alphanumeric + underscore only)
 * @param string $definition SQL column definition, e.g. "TINYINT(1) NOT NULL DEFAULT 0"
 * @return bool True if column exists or was created successfully
 */
function creditlab_ensure_user_column(string $column, string $definition): bool
{
	global $db;
	static $checked = [];

	if (isset($checked[$column])) {
		return $checked[$column];
	}

	if (!ensure_db_connection()) {
		$checked[$column] = false;
		return false;
	}

	$safe_column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
	if ($safe_column !== $column) {
		$checked[$column] = false;
		return false;
	}

	$result = mysqli_query($db, "SHOW COLUMNS FROM `user` LIKE '$safe_column'");
	if ($result && mysqli_num_rows($result) > 0) {
		$checked[$column] = true;
		return true;
	}

	$ok = mysqli_query($db, "ALTER TABLE `user` ADD COLUMN `$safe_column` $definition");
	if (!$ok) {
		error_log("Failed to add user column `$safe_column`: " . mysqli_error($db));
		$checked[$column] = false;
		return false;
	}

	$checked[$column] = true;
	return true;
}

/**
 * SQL fragment for in-flight / active loan_apply rows.
 */
function creditlab_active_loan_apply_status_sql(): string
{
	return "status='pending' OR status='disbursal' OR status='follow_up' OR status='follow up' OR status='account manager' OR status='recovery officer'";
}

/**
 * Whether the user has an in-flight or active loan_apply row.
 */
function creditlab_user_has_active_loan_apply(int $user_id): bool
{
	if ($user_id <= 0) {
		return false;
	}
	$active = towquery("SELECT id FROM `loan_apply` WHERE uid=$user_id AND (" . creditlab_active_loan_apply_status_sql() . ") LIMIT 1");
	return $active && townum($active) > 0;
}

/**
 * Whether the user has a running loan (disbursed loan under account manager or recovery officer).
 * loan_apply rows (pending / disbursal / etc.) are not running loans.
 */
function creditlab_user_has_running_loan(int $user_id): bool
{
	if ($user_id <= 0) {
		return false;
	}
	$running = towquery("SELECT id FROM `loan` WHERE uid=$user_id AND status_log IN ('account manager','recovery officer') LIMIT 1");
	return $running && townum($running) > 0;
}

/**
 * Whether validation log indicates an admin block_next_loan hold.
 */
function creditlab_is_admin_block_hold(?string $validation): bool
{
	return is_string($validation) && strpos($validation, 'admin block') !== false;
}

/**
 * User is flagged block_next_loan and all running loans have been cleared.
 */
function creditlab_user_is_blocked_for_next_loan(int $user_id): bool
{
	if ($user_id <= 0) {
		return false;
	}
	if (!creditlab_ensure_user_column('block_next_loan', 'TINYINT(1) NOT NULL DEFAULT 0')) {
		return false;
	}
	$row = towfetchassoc(towquery("SELECT `block_next_loan` FROM `user` WHERE id=$user_id LIMIT 1"));
	if (!$row || (int)$row['block_next_loan'] !== 1) {
		return false;
	}
	return !creditlab_user_has_running_loan($user_id);
}

/**
 * Whether the user should see the account-on-hold page (blocked, all loans cleared).
 */
function creditlab_user_should_see_account_on_hold(int $user_id, int $verify, ?string $validation): bool
{
	if (creditlab_user_is_blocked_for_next_loan($user_id)) {
		return true;
	}
	if ($verify === 3 && creditlab_is_admin_block_hold($validation) && !creditlab_user_has_running_loan($user_id)) {
		return true;
	}
	return false;
}

/**
 * Auto-hold user flagged with block_next_loan once all running loans are cleared.
 * Returns true if the user was placed on hold.
 */
function creditlab_enforce_block_next_loan(int $user_id): bool
{
	if ($user_id <= 0) {
		return false;
	}

	if (!creditlab_ensure_user_column('block_next_loan', 'TINYINT(1) NOT NULL DEFAULT 0')) {
		return false;
	}

	$row = towfetchassoc(towquery("SELECT `block_next_loan` FROM `user` WHERE id=$user_id LIMIT 1"));
	if (!$row || (int)$row['block_next_loan'] !== 1) {
		return false;
	}

	if (creditlab_user_has_running_loan($user_id)) {
		return false;
	}

	$holdDate = date('Y-m-d');
	$ok = towquery("UPDATE `user` SET `verify`=3, `status`='Hold', `block_next_loan`=0,
		`validation`=CONCAT(`validation`,'Auto-held on new application (admin block) on $holdDate\\n')
		WHERE id=$user_id");
	return (bool)$ok;
}

function towquery($query)
{
	global $db;
	
	// Ensure connection is valid
	if (!ensure_db_connection()) {
		return false;
	}
	
	$re = mysqli_query($db,$query);
	if (!$re) {
		error_log("SQL Error: " . mysqli_error($db) . " - Query: " . $query);
		// Return false instead of throwing fatal error
		return false;
	}
	return $re;
}
 function towquery2($query)
{
	global $db;
	
	// Ensure connection is valid
	if (!ensure_db_connection()) {
		return false;
	}
	
	$re = mysqli_query($db,$query);
	if (!$re) {
		error_log("SQL Error in towquery2: " . mysqli_error($db) . " - Query: " . $query);
		return false;
	}
	$re2 = mysqli_insert_id($db);
	return $re2;
}
 function townum($query)
{
	$re = mysqli_num_rows($query);
	return $re;
}
 function towfetch($query)
{
	$re = mysqli_fetch_array($query);
	return $re;
}
 function towfetchassoc($query)
{
	$re = mysqli_fetch_assoc($query);
	return $re;
}
 function towreal($query)
{
	global $db;
	
	// Ensure connection is valid
	if (!ensure_db_connection()) {
		// Fallback to basic escaping if connection fails
		$re = str_replace("<","&lt;",$query);
		$re = str_replace(">","&gt;",$re);
		return $re;
	}
	
	$re = str_replace("<","&lt;",$query);
	$re = str_replace(">","&gt;",$re);
	$re = mysqli_real_escape_string($db,$re);
	return $re;
}
 function towrealarray($query)
{
	global $db;
	
	// Ensure connection is valid
	if (!ensure_db_connection()) {
		return array();
	}
	
	$re = array();
	if (!is_array($query) || $query === null) {
		return $re;
	}
	foreach ($query as $key => $value) {
	    if(!is_array($value)){
		$$key = str_replace("<","&lt;",$value);
		$$key = str_replace(">","&gt;",$$key);
		$$key = mysqli_real_escape_string($db,$$key);

		$re[$key] = $$key;
	    }else{
	        $re[$key] = towrealarray($value);
	    }
   }
	return $re;
}
 function towrealarray2($query)
{
	global $db;
	
	// Ensure connection is valid
	if (!ensure_db_connection()) {
		return array();
	}
	
	$re = array();
	foreach ($query as $key => $value) {
	    if(!is_array($value)){
		$$key = str_replace("<","&lt;",$value);
		$$key = str_replace(">","&gt;",$$key);
		$$key = mysqli_real_escape_string($db,$$key);

		$re[$key] = $$key;
	    }else{
	        $re[$key] = towrealarray2($value);
	    }
   }
	return $re;
}
 
if (isset($_SESSION['user'])) {
    $user = towreal($_SESSION['user']);
}elseif(isset($_COOKIE['user'])){
    $user = towreal($_COOKIE['user']);
}

if (isset($_SESSION['admin'])) {
    $admin = towreal($_SESSION['admin']);
}elseif(isset($_COOKIE['admin'])){
    $cookie_admin = towreal($_COOKIE['admin']);
    $chk = towquery("SELECT id, active FROM user WHERE email='".$cookie_admin."' LIMIT 1");
    if ($chk && ($row = towfetchassoc($chk)) && isset($row['active']) && (string)$row['active'] === '2') {
        $_SESSION['admin'] = $cookie_admin;
        $admin = $cookie_admin;
    } else {
        setcookie('admin', '', time() - 3600, '/');
    }
}

if (isset($_SESSION['account_manager'])) {
    $account_manager = towreal($_SESSION['account_manager']);
}elseif(isset($_COOKIE['account_manager'])){
    $account_manager = towreal($_COOKIE['account_manager']);
}

if (isset($_SESSION['recovery_officer'])) {
    $recovery_officer = towreal($_SESSION['recovery_officer']);
}elseif(isset($_COOKIE['recovery_officer'])){
    $recovery_officer = towreal($_COOKIE['recovery_officer']);
}
if (isset($_SESSION['verify_user'])) {
    $verify_user = towreal($_SESSION['verify_user']);
}elseif(isset($_COOKIE['verify_user'])){
    $verify_user = towreal($_COOKIE['verify_user']);
}

if (isset($_SESSION['agency_admin'])) {
    $agency_admin = towreal($_SESSION['agency_admin']);
} elseif (isset($_COOKIE['agency_admin'])) {
    $agency_admin = towreal($_COOKIE['agency_admin']);
}

date_default_timezone_set('Asia/Kolkata');

function getDateTimeDiff($date){
 $now_timestamp = strtotime(date('Y-m-d H:i:s'));
 $diff_timestamp = $now_timestamp - strtotime($date);
 
 if($diff_timestamp<60){
  return 'few seconds ago';
 }
 else if($diff_timestamp>=60 && $diff_timestamp<3600){
  return round($diff_timestamp/60).' mins ago';
 }
 else if($diff_timestamp>=3600 && $diff_timestamp<86400){
  return round($diff_timestamp/3600).' hours ago';
 }
 else if($diff_timestamp>=86400 && $diff_timestamp<(86400*30)){
  return round($diff_timestamp/(86400)).' days ago';
 }
 else if($diff_timestamp>=(86400*30) && $diff_timestamp<(86400*365)){
  return round($diff_timestamp/(86400*30)).' months ago';
 }
 else{
  return round($diff_timestamp/(86400*365)).' years ago';
 }
}

/**
 * Calculate loan days based on salary date and applied date
 * Business rules:
 * - Minimum loan tenure is 16 days (inclusive)
 * - Find next salary date, if salary_day > last_day_of_month, use last_day_of_month
 * - If days < 16, move to next month's salary date
 * - If salary date is missing: default to 30 days
 * 
 * Examples:
 * - Apply Jan 28, Salary 3: Feb 3 = 7 days (<16), move to Mar 3 = 35 days ✓
 * - Apply Jan 28, Salary 30: Jan 30 = 3 days (<16), move to Feb 30→28 = 32 days ✓
 * - Apply Feb 25, Salary 30: Feb 30→28 = 4 days (<16), move to Mar 30 = 34 days ✓
 * 
 * @param string $applied_date Applied date in 'Y-m-d' format
 * @param int|null $salary_date Day of month (1-31) or null if not set
 * @return int Number of days (to store in days column)
 */
function calculateLoanDays($applied_date, $salary_date = null) {
    $applied = date_create($applied_date);
    $applied_day = (int)date_format($applied, 'j'); // Day of month (1-31)
    
    // If salary date is missing or invalid, default to 30 days
    if (empty($salary_date) || $salary_date === null || $salary_date === '' || !is_numeric($salary_date) || (int)$salary_date < 1 || (int)$salary_date > 31) {
        return 30; // Default 30 days
    }
    
    $salary_day = (int)$salary_date;
    
    // Determine starting year/month for search
    $year = (int)date_format($applied, 'Y');
    $month = (int)date_format($applied, 'n');
    
    // If salary date has passed or is today, start from next month
    if ($salary_day <= $applied_day) {
        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }
    }
    
    // Loop until we find a due date that's >= 16 days away (inclusive)
    while (true) {
        // Get last day of target month
        $last_day_of_month = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
        
        // Adjust salary day if it exceeds month's last day
        // e.g., Salary 30 in Feb (28 days) -> use 28
        $actual_salary_day = min($salary_day, $last_day_of_month);
        
        // Create due date
        $due_date = date_create(sprintf('%04d-%02d-%02d', $year, $month, $actual_salary_day));
        
        // Calculate days (exclusive)
        $days_exclusive = (int)date_diff($applied, $due_date)->format('%a');
        
        // Check if >= 16 days inclusive (15 days exclusive)
        if ($days_exclusive >= 15) {
            // Return inclusive days (applied date = day 1, due date = day N)
            return $days_exclusive + 1;
        }
        
        // Move to next month
        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }
    }
}

/**
 * Calculate loan due date based on salary date and applied date
 * Returns the actual due date (Y-m-d format) instead of just days
 * 
 * Business rules:
 * - Minimum loan tenure is 16 days (inclusive)
 * - Find next salary date, if salary_day > last_day_of_month, use last_day_of_month
 * - If days < 16, move to next month's salary date
 * - If salary date is missing: default to 30 days from applied date
 * 
 * Examples:
 * - Apply Jan 28, Salary 3: Feb 3 (<16 days), move to Mar 3
 * - Apply Jan 28, Salary 30: Jan 30 (<16 days), move to Feb 30→28
 * - Apply Feb 25, Salary 30: Feb 30→28 (<16 days), move to Mar 30
 * 
 * @param string $applied_date Applied date in 'Y-m-d' format
 * @param int|null $salary_date Day of month (1-31) or null if not set
 * @return string Due date in 'Y-m-d' format
 */
function calculateLoanDueDate($applied_date, $salary_date = null) {
    $applied = date_create($applied_date);
    $applied_day = (int)date_format($applied, 'j'); // Day of month (1-31)
    
    // If salary date is missing or invalid, default to 30 days from applied date
    if (empty($salary_date) || $salary_date === null || $salary_date === '' || !is_numeric($salary_date) || (int)$salary_date < 1 || (int)$salary_date > 31) {
        $due_date = clone $applied;
        $due_date->modify('+30 days');
        return date_format($due_date, 'Y-m-d');
    }
    
    $salary_day = (int)$salary_date;
    
    // Determine starting year/month for search
    $year = (int)date_format($applied, 'Y');
    $month = (int)date_format($applied, 'n');
    
    // If salary date has passed or is today, start from next month
    if ($salary_day <= $applied_day) {
        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }
    }
    
    // Loop until we find a due date that's >= 16 days away (inclusive)
    while (true) {
        // Get last day of target month
        $last_day_of_month = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
        
        // Adjust salary day if it exceeds month's last day
        // e.g., Salary 30 in Feb (28 days) -> use 28
        $actual_salary_day = min($salary_day, $last_day_of_month);
        
        // Create due date
        $due_date = date_create(sprintf('%04d-%02d-%02d', $year, $month, $actual_salary_day));
        
        // Calculate days (exclusive)
        $days_exclusive = (int)date_diff($applied, $due_date)->format('%a');
        
        // Check if >= 16 days inclusive (15 days exclusive)
        if ($days_exclusive >= 15) {
            return date_format($due_date, 'Y-m-d');
        }
        
        // Move to next month
        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }
    }
}
?>
<?php
// $date = date('Y-m-d');
// $cc = towfetch(towquery("SELECT * FROM `fetchdate` WHERE id=1"))['date'];
// // echo strtotime($date); echo "kk". strtotime($cc);exit;
// if(strtotime($date) > strtotime($cc)){
// towquery("UPDATE `fetchdate` SET `date`='$date' WHERE id=1");
// $loan_data = towquery("SELECT * FROM loan WHERE status_log='account manager' OR status_log='recovery officer' ORDER BY id DESC"); 
//     while($loan_fetch = towfetch($loan_data)){ 
//         extract($loan_fetch,EXTR_PREFIX_ALL,"users");
//         $user_star = towquery("SELECT * FROM `user` WHERE id=$users_uid");
//         $user_a = towfetch($user_star);
//         $user_star = $user_a;
//         $approvenew = $user_star['approvenew'];
//         $user_star = $user_star['star_member'];
//         $stop_date = date_create($users_processed_date);
//                     $sa = date_create(date('Y-m-d 23:59:59'));
//                     $aa = date_diff($stop_date,$sa);
//                     $days = $aa->format("%a");
//                     $t = $users_processed_amount + $users_p_fee + ($users_p_fee*0.18);
//                     $days++;
//                     $day =  $days;
//                     $service_charge = 0;
//                     if($days >= 3 ){
//                         $fee = $t * 3 / 100 * 0;
//                         $interest = "0%";
//                         $days = $days-3;
//                         $service_charge +=$fee;
//                     }else{
//                         $fee = $t * $days / 100 * 0;
//                         $interest = "0%";
//                         $days = 0;
//                         $service_charge +=$fee;
//                     }
//                     if(($days) >= 7){
//                         $fee = $t * 7 / 100 * 0.1;
//                         $interest = "0.1%";
//                         $days = $days-7;
//                         $service_charge +=$fee;
//                     }else{
//                         $fee = $t * $days / 100 * 0.1;
//                         $interest = "0.1%"; 
//                         $days = 0;
//                         $service_charge +=$fee;
//                     }
//                     if(($days) >= 20){
//                         $fee = $t * 20 / 100 * 0.115;
//                         $interest = "0.115%";
//                         $days = $days-20;
//                         $service_charge +=$fee;
//                     }else{
//                         $fee = $t * $days / 100 * 0.115;
//                         $interest = "0.115%"; 
//                         $days = 0;
//                         $service_charge +=$fee;
//                     }
//                     if(($days) >= 1){
//                         $fee = $t * $days / 100 * 0.1;
//                         $interest = "0.1%";
//                         $service_charge +=$fee;
//                         $days=0;
//                     }
//                     if($day > 30){
//                         $penalitydays = $day - 30;
//                         $penalitydays--;
//                         $penality = (($t)/100)*3;
//                         // if($penalitydays >= 29){
//                             $atnp = ((($t)/100) * 0.2)*$penalitydays;
//                             $penality = $penality + $atnp;
//                         // print_r($penality);exit;
//                         // }
//                     }else{$penality=0;}
//         towquery("UPDATE `loan` SET `exhausted_period` = '$day',`service_charge`=$service_charge,`penality_charge`=$penality WHERE `loan`.`id` = $users_id;");
//     }
// }
// exit;

/**
 * Get base URL from database configuration
 * Falls back to current server URL if not set in database
 * @return string Base URL (e.g., https://creditlab.in or https://testing.creditlab.in)
 */
function getAppUrl() {
    global $db;
    static $cached_url = null;
    
    // Return cached value if available
    if ($cached_url !== null) {
        return $cached_url;
    }
    
    // Try to get from database
    try {
        // Check if config table exists, if not create it
        $table_check = mysqli_query($db, "SHOW TABLES LIKE 'site_config'");
        if (mysqli_num_rows($table_check) == 0) {
            // Create config table
            mysqli_query($db, "CREATE TABLE IF NOT EXISTS `site_config` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `config_key` varchar(100) NOT NULL,
                `config_value` text NOT NULL,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `config_key` (`config_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            
            // Insert default value
            mysqli_query($db, "INSERT INTO `site_config` (`config_key`, `config_value`) VALUES ('base_url', 'https://creditlab.in') ON DUPLICATE KEY UPDATE `config_value` = 'https://creditlab.in'");
        }
        
        // Get base URL from database
        $result = mysqli_query($db, "SELECT `config_value` FROM `site_config` WHERE `config_key` = 'base_url' LIMIT 1");
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $cached_url = rtrim($row['config_value'], '/');
            return $cached_url;
        }
    } catch (Exception $e) {
        // If database query fails, fall back to auto-detection
    }
    
    // Fallback: Auto-detect from current request
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'creditlab.in';
    $cached_url = $protocol . $host;
    
    return $cached_url;
}

// Set app_url for backward compatibility
$app_url = getAppUrl();

/**
 * Get PDF URL from site_config table
 * @param string $pdf_type - Type of PDF (grievanceredressal, policy, fair_practice_code, it_policy, fees_policy, refund_cancellation)
 * @return string PDF URL
 */
function getPdfUrl($pdf_type) {
    global $db;
    static $cached_urls = [];
    
    // Return cached value if available
    if (isset($cached_urls[$pdf_type])) {
        return $cached_urls[$pdf_type];
    }
    
    // Allowed PDF types and their default filenames
    $allowed_pdfs = [
        'grievanceredressal' => 'grievanceredressal.pdf',
        'policy' => 'policy.pdf',
        'fair_practice_code' => 'FairPracticeCodeSMPL.pdf',
        'it_policy' => 'it_policy.pdf',
        'fees_policy' => 'fees_policy.pdf',
        'refund_cancellation' => 'RefundCancellationPolicy.pdf'
    ];
    
    if (!isset($allowed_pdfs[$pdf_type])) {
        return '';
    }
    
    // Try to get from database
    try {
        $result = mysqli_query($db, "SELECT `config_value` FROM `site_config` WHERE `config_key` = 'pdf_{$pdf_type}' LIMIT 1");
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $cached_urls[$pdf_type] = $row['config_value'];
            return $cached_urls[$pdf_type];
        }
    } catch (Exception $e) {
        // If database query fails, fall back to default
    }
    
    // Fallback: Return default URL
    $base_url = getAppUrl();
    $cached_urls[$pdf_type] = $base_url . '/' . $allowed_pdfs[$pdf_type];
    return $cached_urls[$pdf_type];
}
?>
<?php
// CSRF helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
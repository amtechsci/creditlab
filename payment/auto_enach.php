<!-- <?php
//  ini_set('display_startup_errors', 1);
//  ini_set('display_errors', 1);
//  error_reporting(-1);
// Set a longer execution time limit, essential for cron jobs that might process many records.
set_time_limit(0); 

// --- DATABASE CONNECTION ---
require_once __DIR__ . '/../lib/database.php';
$db = creditlab_db_connect();
if (!$db) {
    die('Database connection failed.');
}

if (mysqli_connect_errno()) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed.");
}
mysqli_set_charset($db, 'utf8');

// --- DATABASE FUNCTIONS ---
function towquery($db, $query) {
    $result = mysqli_query($db, $query);
    if (!$result) {
        error_log("SQL Error: " . mysqli_error($db) . " - Query: " . $query);
        return false;
    }
    return $result;
}
function townum($query_result) {
    return mysqli_num_rows($query_result);
}
function towfetch($query_result) {
    return mysqli_fetch_array($query_result);
}
function towreal($db, $query) {
    $re = str_replace("<","&lt;",$query);
    $re = str_replace(">","&gt;",$re);
    $re = mysqli_real_escape_string($db, $re);
    return $re;
}

/**
 * Get base URL from database configuration
 * Falls back to current server URL if not set in database
 */
function getAppUrl() {
    global $db;
    static $cached_url = null;
    
    if ($cached_url !== null) {
        return $cached_url;
    }
    
    try {
        $table_check = mysqli_query($db, "SHOW TABLES LIKE 'site_config'");
        if (mysqli_num_rows($table_check) == 0) {
            mysqli_query($db, "CREATE TABLE IF NOT EXISTS `site_config` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `config_key` varchar(100) NOT NULL,
                `config_value` text NOT NULL,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `config_key` (`config_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            mysqli_query($db, "INSERT INTO `site_config` (`config_key`, `config_value`) VALUES ('base_url', 'https://creditlab.in') ON DUPLICATE KEY UPDATE `config_value` = 'https://creditlab.in'");
        }
        
        $result = mysqli_query($db, "SELECT `config_value` FROM `site_config` WHERE `config_key` = 'base_url' LIMIT 1");
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $cached_url = rtrim($row['config_value'], '/');
            return $cached_url;
        }
    } catch (Exception $e) {
    }
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'creditlab.in';
    $cached_url = $protocol . $host;
    
    return $cached_url;
}

function initiateEasebuzzDirectDebit(array $postParams, array $easebuzz_row = []): string
{
    require_once __DIR__ . '/../lib/easebuzz_enach.php';
    return creditlab_easebuzz_initiate_direct_debit_json($postParams, $easebuzz_row);
}

/**
 * Calculate total amount for loan repayment (matching zzautoloanamountcalculator.php logic)
 * @param array $loan Loan data from database
 * @param array $loan_apply Loan application data
 * @return float Total amount including all charges
 */
function calculateTotalAmount($loan, $loan_apply) {
    // Get current date and calculate tday (days since processed_date)
    $stop_date = date_create($loan['processed_date']);
    $sa = date_create(date('Y-m-d 23:59:59'));
    $aa = date_diff($stop_date, $sa);
    $tday = (int)$aa->format("%a");
    
    // Get days from loan_apply and EMI flag strictly from DB (do NOT auto-derive EMI by days)
    $loan_days_raw = isset($loan_apply['days']) ? (int)$loan_apply['days'] : 30;
    $loan_is_emi = isset($loan['is_emi']) ? (int)$loan['is_emi'] : 0;
    $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
    
    // For amount calculation we need to charge interest for one extra day (old logic had +1 day)
    // Example: exhausted_period 30 → charge for 31 days
    $days = $tday + 1;
    
    // Calculate base amount with GST on processing fee (18% GST)
    $t = $loan['processed_amount'] + $loan['p_fee'] + ($loan['p_fee'] * 0.18);
    
    $service_charge = 0;
    $penality = 0;
    
    // Calculate service charge based on interest percentage
    if ($loan_apply['interest_percentage'] == 1) {
        // Special case for 1% interest - tiered calculation
        $remaining_days = $days;
        if ($remaining_days >= 3) {
            $fee = $t * 3 / 100 * 0;
            $remaining_days = $remaining_days - 3;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if ($remaining_days >= 7) {
            $fee = $t * 7 / 100 * 0.1;
            $remaining_days = $remaining_days - 7;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0.1;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if ($remaining_days >= 20) {
            $fee = $t * 20 / 100 * 0.115;
            $remaining_days = $remaining_days - 20;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0.115;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if ($remaining_days >= 1) {
            $fee = $t * $remaining_days / 100 * 0.1;
            $remaining_days = 0;
            $service_charge += $fee;
        }
    } else {
        // Standard interest calculation
        $fee = $t * $days / 100 * $loan_apply['interest_percentage'];
        $service_charge += $fee;
    }
    
    // Calculate penalty based on DPD (Days Past Due) = tday - loan_days
    // E-Nach triggers when DPD = 1, so penalty starts from DPD = 1
    $dpd = $tday - $loan_days; // DPD = Days Past Due
    if ($dpd > 0) {
        $penalitydays = $dpd - 1; // Penalty starts from DPD = 1, so subtract 1
        $penality = (($t) / 100) * 4; // First day penalty
        if ($penalitydays > 0) {
            $atnp = ((($t) / 100) * 0.2) * $penalitydays; // Additional penalty for remaining days
            $penality = $penality + $atnp;
        }
    } else {
        $penality = 0;
    }
    
    // Add 18% GST to penalty
    $penality = ($penality + ($penality * 0.18));
    
    // Calculate total amount (including GST on processing fee)
    $p_fee_gst = $loan['p_fee'] * 0.18;
    $totalamount = (float)$loan['processed_amount'] + (float)$loan['p_fee'] + $p_fee_gst + (float)$service_charge + (float)$penality;
    
    return $totalamount;
}

/**
 * Calculate detailed breakdown of loan amount components
 * @param array $loan Loan data from database
 * @param array $loan_apply Loan application data
 * @return array Breakdown of all amount components
 */
function calculateAmountBreakdown($loan, $loan_apply) {
    // Get current date and calculate tday (days since processed_date)
    $stop_date = date_create($loan['processed_date']);
    $sa = date_create(date('Y-m-d 23:59:59'));
    $aa = date_diff($stop_date, $sa);
    $tday = (int)$aa->format("%a");
    
    $loan_days_raw = isset($loan_apply['days']) ? (int)$loan_apply['days'] : 30;
    $loan_is_emi = isset($loan['is_emi']) ? (int)$loan['is_emi'] : 0;
    $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
    
    // For detailed breakdown also include the extra interest day
    $days = $tday + 1;
    
    // Calculate base amount with GST on processing fee (18% GST)
    $p_fee_gst = $loan['p_fee'] * 0.18;
    $t = $loan['processed_amount'] + $loan['p_fee'] + $p_fee_gst;
    
    $service_charge = 0;
    $penality = 0;
    
    // Calculate service charge based on interest percentage
    if ($loan_apply['interest_percentage'] == 1) {
        // Special case for 1% interest - tiered calculation
        $remaining_days = $days;
        if ($remaining_days >= 3) {
            $fee = $t * 3 / 100 * 0;
            $remaining_days = $remaining_days - 3;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if (($remaining_days) >= 7) {
            $fee = $t * 7 / 100 * 0.1;
            $remaining_days = $remaining_days - 7;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0.1;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if (($remaining_days) >= 20) {
            $fee = $t * 20 / 100 * 0.115;
            $remaining_days = $remaining_days - 20;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0.115;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if (($remaining_days) >= 1) {
            $fee = $t * $remaining_days / 100 * 0.1;
            $service_charge += $fee;
            $remaining_days = 0;
        }
    } else {
        // Standard interest calculation
        $fee = $t * $days / 100 * $loan_apply['interest_percentage'];
        $service_charge += $fee;
    }
    
    // Calculate penalty based on DPD (Days Past Due) = tday - loan_days
    // E-Nach triggers when DPD = 1, so penalty starts from DPD = 1
    $dpd = $tday - $loan_days; // DPD = Days Past Due
    if ($dpd > 0) {
        $penalitydays = $dpd - 1; // Penalty starts from DPD = 1, so subtract 1
        $penality = (($t) / 100) * 4; // First day penalty
        if ($penalitydays > 0) {
            $atnp = ((($t) / 100) * 0.2) * $penalitydays; // Additional penalty for remaining days
            $penality = $penality + $atnp;
        }
    } else {
        $penality = 0;
    }
    
    // Calculate penalty GST
    $penalty_gst = $penality * 0.18;
    $penality = ($penality + $penalty_gst);
    
    return [
        'days' => $days,
        'p_fee_gst' => $p_fee_gst,
        'service_charge' => $service_charge,
        'penalty_charge' => $penality - $penalty_gst, // Penalty before GST
        'penalty_gst' => $penalty_gst,
        'total_amount' => (float)$loan['processed_amount'] + (float)$loan['p_fee'] + (float)$service_charge + (float)$penality
    ];
}

// --- DRY RUN CONFIGURATION ---
// Set to true to enable dry-run mode (no actual API calls, just calculations and logging)
$dry_run = isset($_GET['dry_run']) ? (bool)$_GET['dry_run'] : false;

// --- CRON JOB LOGIC ---
$current_date = date('Y-m-d');
$current_time = date('Y-m-d H:i:s');
$current_day = date('j'); // Day of month without leading zeros
$gst = 0; // Define GST variable

// Initialize detailed logging arrays
$processed_loans = [];
$successful_loans = [];
$failed_loans = [];
$skipped_loans = [];
$sms_sent_count = 0;
$sms_failed_count = 0;

// Create log file with date
$log_file = "logs/enach_cron_" . $current_date . ".log";
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Function to write detailed logs
function writeLog($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    error_log($message); // Also log to system error log
}

// Function to send SMS
function sendSMS($mobile, $message, $template_id, $sender = "CREDLB") {
    require_once __DIR__ . '/../config/sms.php';
    $url = "https://sms.k7marketinghub.com/app/smsapi/index.php?key=" . urlencode(SMS_API_KEY) . "&campaign=16613&routeid=30&type=text&contacts=$mobile&senderid=$sender&msg=".urlencode($message)."&template_id=$template_id&pe_id=1401337620000065797";
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    return ['success' => !$error, 'response' => $response, 'error' => $error];
}

// Start cron job logging
writeLog("=== E-NACH CRON JOB STARTED ===", $log_file);
writeLog("Date: $current_date | Time: $current_time", $log_file);
writeLog("Dry Run Mode: " . ($dry_run ? 'YES' : 'NO'), $log_file);

// Log dry-run mode
if ($dry_run) {
    writeLog("DRY RUN MODE ENABLED - No actual API calls will be made", $log_file);
    echo "=== DRY RUN MODE ENABLED ===\n";
    echo "Date: $current_date\n";
    echo "No actual API calls will be made\n\n";
}

// 1. RESET FAILED E-NACH REQUESTS (3+ days old)
$reset_query = "UPDATE `loan` SET `enach_request` = 0, `enach_request_date` = NULL 
                WHERE `enach_request` = 1 
                AND `enach_request_date` IS NOT NULL 
                AND DATEDIFF('$current_date', `enach_request_date`) >= 3
                AND `status_log` != 'cleared'";
$reset_result = towquery($db, $reset_query);
$reset_count = mysqli_affected_rows($db);
writeLog("Reset $reset_count failed E-Nach requests (3+ days old)", $log_file);

// 1.1. RESET TEMPORARY SKIPPED E-NACH REQUESTS (past skip_until_date)
$reset_temporary_query = "UPDATE `loan` SET 
                `enach_request` = 0, 
                `enach_skip_date` = NULL,
                `enach_skip_reason` = NULL,
                `enach_skip_type` = NULL,
                `enach_skip_until_date` = NULL
                WHERE `enach_request` = 2 
                AND `enach_skip_type` = 'temporary'
                AND `enach_skip_until_date` IS NOT NULL 
                AND `enach_skip_until_date` <= '$current_date'
                AND `status_log` != 'cleared'";
$reset_temporary_result = towquery($db, $reset_temporary_query);
$reset_temporary_count = mysqli_affected_rows($db);
writeLog("Reset $reset_temporary_count temporary skipped E-Nach requests (past skip_until_date)", $log_file);

// 2. DETERMINE ELIGIBLE LOANS BASED ON CONDITIONS (NOW DPD-BASED)
$eligible_loans = [];

// Condition 1: Daily run for all active loans where DPD == 1 (exactly 1 day past due)
// tday = days since processed_date, loan_days from loan_apply / EMI logic, dpd = tday - loan_days
$sql1 = "SELECT l.*, la.days, la.apply_date 
         FROM `loan` l 
         INNER JOIN `loan_apply` la ON l.lid = la.id 
         WHERE l.`status_log` = 'account manager' 
         AND l.`action` != 'cleared' 
         AND l.`enach_request` = 0 
         AND (l.`enach_request` != 2 OR (l.`enach_skip_type` = 'temporary' AND l.`enach_skip_until_date` <= '$current_date'))
         AND la.`status` = 'account manager'";
$loans1 = towquery($db, $sql1);
$condition1_count = 0;
while ($loan = towfetch($loans1)) {
    // Calculate tday (days since processed_date, with -1 day alignment like other cron logic)
    $processed_date_str = date('Y-m-d', strtotime($loan['processed_date'] . " -1 day"));
    $tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
    
    // Derive loan_days using EMI flag strictly from DB
    $loan_days_raw = isset($loan['days']) ? (int)$loan['days'] : 30;
    $loan_is_emi = isset($loan['is_emi']) ? (int)$loan['is_emi'] : 0;
    $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
    
    // DPD = Days Past Due
    $dpd = $tday - $loan_days;
    
    // Trigger E-NACH when DPD == 1 (exactly 1 day past due)
    if ($dpd == 1) {
        $eligible_loans[] = $loan;
        $condition1_count++;
    }
}
writeLog("Condition 1 (DPD == 1, calculated per loan): Found $condition1_count eligible loans", $log_file);

// Check for loans that are skipped due to E-NACH skip flag (for logging only)
$skipped_enach_query = "SELECT COUNT(*) as skipped_count FROM `loan` l 
                        INNER JOIN `loan_apply` la ON l.lid = la.id 
                        WHERE l.`status_log` = 'account manager' 
                        AND l.`enach_request` = 2";
$skipped_result = towquery($db, $skipped_enach_query);
$skipped_count = towfetch($skipped_result)['skipped_count'];

// Check for permanent vs temporary skips
$permanent_skip_query = "SELECT COUNT(*) as permanent_count FROM `loan` l 
                         INNER JOIN `loan_apply` la ON l.lid = la.id 
                         WHERE l.`status_log` = 'account manager' 
                         AND l.`enach_request` = 2 
                         AND (l.`enach_skip_type` = 'permanent' OR l.`enach_skip_type` IS NULL)";
$permanent_result = towquery($db, $permanent_skip_query);
$permanent_count = towfetch($permanent_result)['permanent_count'];

$temporary_skip_query = "SELECT COUNT(*) as temporary_count FROM `loan` l 
                         INNER JOIN `loan_apply` la ON l.lid = la.id 
                         WHERE l.`status_log` = 'account manager' 
                         AND l.`enach_request` = 2 
                         AND l.`enach_skip_type` = 'temporary' 
                         AND l.`enach_skip_until_date` > '$current_date'";
$temporary_result = towquery($db, $temporary_skip_query);
$temporary_count = towfetch($temporary_result)['temporary_count'];

if($skipped_count > 0) {
    writeLog("Condition 1 (DPD == 1): $skipped_count loans skipped due to E-NACH skip flag (enach_request = 2) - Permanent: $permanent_count, Temporary: $temporary_count", $log_file);
}

// Get last day of current month for last day processing
$last_day_of_month = date('t'); // Returns the number of days in the current month

// Condition 2: On 3rd, 10th, and last day of month (30th/31st) for loans where DPD > 0
if ($current_day == 3 || $current_day == 10 || $current_day == $last_day_of_month) {
    $sql2 = "SELECT l.*, la.days, la.apply_date 
             FROM `loan` l 
             INNER JOIN `loan_apply` la ON l.lid = la.id 
             WHERE l.`status_log` = 'account manager' 
             AND l.`action` != 'cleared' 
             AND l.`enach_request` = 0 
             AND (l.`enach_request` != 2 OR (l.`enach_skip_type` = 'temporary' AND l.`enach_skip_until_date` <= '$current_date'))
             AND la.`status` = 'account manager'";
    $loans2 = towquery($db, $sql2);
    $condition2_count = 0;
    $duplicates_count = 0;
    while ($loan = towfetch($loans2)) {
        // Calculate tday (days since processed_date, with -1 day alignment like other cron logic)
        $processed_date_str = date('Y-m-d', strtotime($loan['processed_date'] . " -1 day"));
        $tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
        
        // Derive loan_days using EMI flag strictly from DB
        $loan_days_raw = isset($loan['days']) ? (int)$loan['days'] : 30;
        $loan_is_emi = isset($loan['is_emi']) ? (int)$loan['is_emi'] : 0;
        $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
        
        // DPD = Days Past Due
        $dpd = $tday - $loan_days;
        
        // Only add if DPD > 0 (any overdue days)
        if ($dpd > 0) {
            // Avoid duplicates
            $exists = false;
            foreach ($eligible_loans as $existing_loan) {
                if ($existing_loan['id'] == $loan['id']) {
                    $exists = true;
                    $duplicates_count++;
                    break;
                }
            }
            if (!$exists) {
                $eligible_loans[] = $loan;
                $condition2_count++;
            }
        }
    }
    $day_type = ($current_day == 3) ? "3rd" : (($current_day == 10) ? "10th" : "last day ($last_day_of_month)");
    writeLog("Condition 2 (DPD > 0, day $current_day - $day_type): Found $condition2_count new eligible loans, $duplicates_count duplicates skipped", $log_file);
    
    // Check for loans that are skipped due to E-NACH skip flag
    $skipped_enach_query2 = "SELECT COUNT(*) as skipped_count FROM `loan` l 
                             INNER JOIN `loan_apply` la ON l.lid = la.id 
                             WHERE l.`status_log` = 'account manager' 
                             AND l.`enach_request` = 2
                             AND la.`status` = 'account manager'";
    $skipped_result2 = towquery($db, $skipped_enach_query2);
    $skipped_count2 = towfetch($skipped_result2)['skipped_count'];
    if($skipped_count2 > 0) {
        writeLog("Condition 2: $skipped_count2 loans skipped due to E-NACH skip flag (enach_request = 2)", $log_file);
    }
} else {
    writeLog("Condition 2: Skipped (not 3rd, 10th, or last day of month, current day: $current_day, last day: $last_day_of_month)", $log_file);
}

// Condition 3: Salary date processing - loans where DPD > 0 when salary_date day equals today
$sql3 = "SELECT l.*, la.days, la.apply_date 
         FROM `loan` l 
         INNER JOIN `loan_apply` la ON l.lid = la.id 
         INNER JOIN `user` u ON l.uid = u.id 
         WHERE l.`status_log` = 'account manager' 
         AND l.`action` != 'cleared' 
         AND l.`enach_request` = 0 
         AND (l.`enach_request` != 2 OR (l.`enach_skip_type` = 'temporary' AND l.`enach_skip_until_date` <= '$current_date'))
         AND la.`status` = 'account manager'
         AND DAY(u.salary_date) = $current_day";
$loans3 = towquery($db, $sql3);
$condition3_count = 0;
$condition3_duplicates = 0;
while ($loan = towfetch($loans3)) {
    // Calculate tday (days since processed_date, with -1 day alignment like other cron logic)
    $processed_date_str = date('Y-m-d', strtotime($loan['processed_date'] . " -1 day"));
    $tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
    
    // Derive loan_days using EMI flag strictly from DB
    $loan_days_raw = isset($loan['days']) ? (int)$loan['days'] : 30;
    $loan_is_emi = isset($loan['is_emi']) ? (int)$loan['is_emi'] : 0;
    $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
    
    // DPD = Days Past Due
    $dpd = $tday - $loan_days;
    
    // Only add if DPD > 0 (any overdue days)
    if ($dpd > 0) {
        // Avoid duplicates
        $exists = false;
        foreach ($eligible_loans as $existing_loan) {
            if ($existing_loan['id'] == $loan['id']) {
                $exists = true;
                $condition3_duplicates++;
                break;
            }
        }
        if (!$exists) {
            $eligible_loans[] = $loan;
            $condition3_count++;
        }
    }
}
writeLog("Condition 3 (DPD > 0, salary date = $current_day): Found $condition3_count new eligible loans, $condition3_duplicates duplicates skipped", $log_file);

// Check for loans that are skipped due to E-NACH skip flag
$skipped_enach_query3 = "SELECT COUNT(*) as skipped_count FROM `loan` l 
                         INNER JOIN `loan_apply` la ON l.lid = la.id 
                         INNER JOIN `user` u ON l.uid = u.id 
                         WHERE l.`status_log` = 'account manager' 
                         AND l.`enach_request` = 2
                         AND la.`status` = 'account manager'
                         AND DAY(u.salary_date) = $current_day";
$skipped_result3 = towquery($db, $skipped_enach_query3);
$skipped_count3 = towfetch($skipped_result3)['skipped_count'];
if($skipped_count3 > 0) {
    writeLog("Condition 3: $skipped_count3 loans skipped due to E-NACH skip flag (enach_request = 2)", $log_file);
}

// 3. PROCESS ELIGIBLE LOANS
$processed_count = 0;
$success_count = 0;
$failed_count = 0;
$total_eligible = count($eligible_loans);

writeLog("=== PROCESSING ELIGIBLE LOANS ===", $log_file);
writeLog("Total eligible loans found: $total_eligible", $log_file);

// Log all eligible loan IDs
$loan_ids = array_column($eligible_loans, 'lid');
writeLog("Eligible Loan IDs: " . implode(', ', $loan_ids), $log_file);

foreach ($eligible_loans as $loan) {
    $lid = $loan['lid'];
    $uid = $loan['uid'];
    $processed_count++;

    writeLog("Processing Loan ID: CLL$lid | User ID: $uid | Progress: $processed_count/$total_eligible", $log_file);

    // Check if loan is already cleared to prevent duplicate autopay deduction
    if ($loan['status_log'] == 'cleared' || $loan['action'] == 'cleared') {
        writeLog("SKIPPED: Loan CLL$lid is already cleared. Preventing duplicate autopay deduction.", $log_file);
        $skipped_loans[] = "CLL$lid (Already Cleared)";
        if ($dry_run) {
            echo "SKIPPED: Loan CLL$lid is already cleared\n";
        }
        continue;
    }

    // Get user details
    $userdata = towquery($db, "SELECT * FROM `user` WHERE id='$uid'");
    $userdataff = towfetch($userdata);

    // Get loan application details for interest calculation
    $loan_apply_data = towquery($db, "SELECT * FROM `loan_apply` WHERE id='$lid'");
    $loan_apply = towfetch($loan_apply_data);

    // Get ALL E-Nach details for this user (multiple customer_authentication_id)
    // First, let's check what E-Nach records exist for this user
    $debug_query = towquery($db, "SELECT * FROM `easebuzz_adtd` WHERE uid='$uid'");
    $debug_count = townum($debug_query);
    writeLog("Loan CLL$lid: Found $debug_count total E-Nach records for user $uid", $log_file);
    
    if ($debug_count > 0) {
        while ($debug_record = towfetch($debug_query)) {
            writeLog("Loan CLL$lid: E-Nach record - Auth Status: '{$debug_record['authorization_status']}' | Customer Auth ID: '{$debug_record['customer_authentication_id']}' | TxnID: '{$debug_record['txnid']}'", $log_file);
        }
    }
    
    // Now check for authorized authorizations (case-insensitive)
    $easebuzz_adtd = towquery($db, "SELECT * FROM `easebuzz_adtd` WHERE uid='$uid' AND LOWER(authorization_status) IN ('authorized', 'accepted')");
    $enach_count = townum($easebuzz_adtd);

    if ($enach_count > 0) {
        writeLog("Loan CLL$lid: Found $enach_count E-Nach authorization(s) for user $uid", $log_file);
        // Process each customer_authentication_id
        $auth_count = 0;
        while ($easebuzz_adtdff = towfetch($easebuzz_adtd)) {
            $auth_count++;
            require_once __DIR__ . '/../lib/easebuzz_enach.php';
            $presentment_api = creditlab_easebuzz_presentment_api_for_row($easebuzz_adtdff);
            writeLog("Loan CLL$lid: Processing E-Nach authorization #$auth_count of $enach_count | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | API: $presentment_api", $log_file);
            
            // Calculate total amount with proper logic (matching zzautoloanamountcalculator.php)
            $totalamount = calculateTotalAmount($loan, $loan_apply);
            
            // Calculate breakdown for detailed logging
            $breakdown = calculateAmountBreakdown($loan, $loan_apply);
            
            $totalamount = number_format($totalamount, 2, '.', '');

            // Detailed logging for dry-run and regular mode
            $log_message = "LOAN ID: CLL$lid | User: {$userdataff['name']} | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']}\n";
            $log_message .= "  Processed Amount: ₹" . number_format($loan['processed_amount'], 2) . "\n";
            $log_message .= "  Processing Fee: ₹" . number_format($loan['p_fee'], 2) . "\n";
            $log_message .= "  Processing Fee GST (18%): ₹" . number_format($breakdown['p_fee_gst'], 2) . "\n";
            $log_message .= "  Service Charge: ₹" . number_format($breakdown['service_charge'], 2) . "\n";
            $log_message .= "  Penalty Charge: ₹" . number_format($breakdown['penalty_charge'], 2) . "\n";
            $log_message .= "  Penalty GST (18%): ₹" . number_format($breakdown['penalty_gst'], 2) . "\n";
            $log_message .= "  TOTAL AMOUNT: ₹$totalamount\n";
            $log_message .= "  Days Since Processed: " . $breakdown['days'] . " (including +1 extra day)\n";
            $log_message .= "  Interest Rate: {$loan_apply['interest_percentage']}%\n";
            $log_message .= "  Status: {$loan['status_log']}\n";
            $log_message .= "  Exhausted Period: {$loan['exhausted_period']}\n";
            $log_message .= "  Processed Date: {$loan['processed_date']}\n";
            $log_message .= "  ---\n";
            
            writeLog($log_message, $log_file);
            
            if ($dry_run) {
                echo $log_message;
            }

            if (!$dry_run) {
                writeLog("Loan CLL$lid: Calling Easebuzz API for amount ₹$totalamount", $log_file);
                
                // Prepare payment details
                $paymentDetails = [
                    "amount" => $totalamount,
                    "productinfo" => "Loan Repayment Cron",
                    "firstname" => $userdataff['name'],
                    "email" => $userdataff['email'],
                    "phone" => $userdataff['mobile'],
                    "customer_authentication_id" => $easebuzz_adtdff['customer_authentication_id'],
                    "merchant_debit_id" => "CLL_AUTO_" . $lid . "_" . time(),
                    "auto_debit_access_key" => $easebuzz_adtdff['auto_debit_access_key'],
                    "udf1" => "CREDITLAB_AUTO_ENACH",
                ];

                // Debug: Log the exact API call data and E-Nach details
                writeLog("Loan CLL$lid: E-Nach Details - Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | Auto Debit Access Key: {$easebuzz_adtdff['auto_debit_access_key']} | Authorization Status: {$easebuzz_adtdff['authorization_status']}", $log_file);
                writeLog("Loan CLL$lid: API Call Data - " . json_encode($paymentDetails), $log_file);
                
                // Call Easebuzz API (Autocollect for cai… mandates, legacy PG otherwise)
                $apiResponse = initiateEasebuzzDirectDebit($paymentDetails, $easebuzz_adtdff);
                writeLog("Loan CLL$lid: API Response - " . $apiResponse, $log_file);
                $res = json_decode($apiResponse, true);

                // Check response and update database
                if ($res && isset($res['status']) && $res['status']) {
                    // Update loan with enach_request = 1 and set enach_request_date
                    towquery($db, "UPDATE `loan` SET `enach_request` = 1, `enach_request_date` = '$current_date' WHERE lid = $lid");
                    $success_count++;
                    $successful_loans[] = "CLL$lid";
                    writeLog("SUCCESS: E-Nach request initiated for CLL$lid | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | Amount: ₹$totalamount", $log_file);
                    
                    // Send E-NACH Reminder SMS (Template: 1407175015994490488)
                    // Time: 6:50 PM - triggered when E-NACH is initiated
                    $mobile = $userdataff['mobile'];
                    $template_id = "1407175015994490488";
                    $outstanding_amount = number_format($totalamount, 2);
                    $enach_date = date('d-m-Y'); // Current date when E-NACH is triggered
                    
                    $sms_message = "Hi ! Your Creditlab.in loan of Rs. $outstanding_amount will auto-debit on $enach_date. Ensure sufficient balance to avoid chq bounce & legal action under Section 138 N.I. Act";
                    
                    $sms_result = sendSMS($mobile, $sms_message, $template_id, "CREDLB");
                    
                    if ($sms_result['success']) {
                        $sms_sent_count++;
                        writeLog("SMS SENT: E-NACH reminder sent to $mobile for CLL$lid | Amount: ₹$outstanding_amount | Date: $enach_date", $log_file);
                    } else {
                        $sms_failed_count++;
                        writeLog("SMS FAILED: E-NACH reminder failed for CLL$lid | Mobile: $mobile | Error: {$sms_result['error']}", $log_file);
                    }
                    
                } else {
                    $errorMessage = isset($res['error_desc']) ? $res['error_desc'] : 'Unknown API error';
                    $failed_count++;
                    $failed_loans[] = "CLL$lid";
                    writeLog("FAILED: E-Nach request for CLL$lid | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | Error: $errorMessage", $log_file);
                    
                    // Reset enach_request flag for failed loans so they can be retried
                    towquery($db, "UPDATE `loan` SET `enach_request` = 0 WHERE lid = $lid");
                }
            } else {
                // In dry-run mode, just count as would-be success
                $success_count++;
                $successful_loans[] = "CLL$lid (DRY RUN)";
                writeLog("DRY RUN: Would process CLL$lid for amount ₹$totalamount", $log_file);
                
                // Log would-be E-NACH Reminder SMS for dry run
                $mobile = $userdataff['mobile'];
                $template_id = "1407175015994490488";
                $outstanding_amount = number_format($totalamount, 2);
                $enach_date = date('d-m-Y');
                $sms_message = "Hi ! Your Creditlab.in loan of Rs. $outstanding_amount will auto-debit on $enach_date. Ensure sufficient balance to avoid chq bounce & legal action under Section 138 N.I. Act";
                
                writeLog("DRY RUN SMS: Would send E-NACH reminder to $mobile for CLL$lid | Amount: ₹$outstanding_amount | Date: $enach_date", $log_file);
                writeLog("DRY RUN SMS MESSAGE: $sms_message", $log_file);
            }
        }
    } else {
        $skipped_loans[] = "CLL$lid";
        writeLog("SKIPPED: No authorized E-Nach authorizations found for user uid: $uid, loan CLL$lid", $log_file);
        if ($dry_run) {
            echo "SKIPPED: No authorized E-Nach authorizations found for user uid: $uid, lid: $lid\n";
        }
    }
}

// 4. LOG CRON JOB SUMMARY
writeLog("=== CRON JOB SUMMARY ===", $log_file);
writeLog("Date: $current_date | Time: $current_time", $log_file);
writeLog("Total Eligible Loans: $total_eligible", $log_file);
writeLog("Processed Loans: $processed_count", $log_file);
writeLog("Successful: $success_count", $log_file);
writeLog("Failed: $failed_count", $log_file);
writeLog("Skipped: " . count($skipped_loans), $log_file);
writeLog("SMS Sent: $sms_sent_count", $log_file);
writeLog("SMS Failed: $sms_failed_count", $log_file);

// Log detailed loan lists
if (!empty($successful_loans)) {
    writeLog("Successful Loan IDs: " . implode(', ', $successful_loans), $log_file);
}
if (!empty($failed_loans)) {
    writeLog("Failed Loan IDs: " . implode(', ', $failed_loans), $log_file);
}
if (!empty($skipped_loans)) {
    writeLog("Skipped Loan IDs: " . implode(', ', $skipped_loans), $log_file);
}

$summary_message = "E-Nach Cron Job Completed - Date: $current_date, Processed: $processed_count, Success: $success_count, Failed: $failed_count, Skipped: " . count($skipped_loans) . ", SMS Sent: $sms_sent_count, SMS Failed: $sms_failed_count";
if ($dry_run) {
    $summary_message = "E-Nach DRY RUN Completed - Date: $current_date, Processed: $processed_count, Would-be Success: $success_count, Would-be Failed: $failed_count, Skipped: " . count($skipped_loans) . ", Would-be SMS Sent: $sms_sent_count";
    echo "\n=== SUMMARY ===\n";
    echo $summary_message . "\n";
    echo "No actual API calls were made.\n";
    echo "To run for real, remove ?dry_run=1 from URL\n";
    echo "Log file: $log_file\n";
}
writeLog($summary_message, $log_file);
writeLog("=== E-NACH CRON JOB ENDED ===", $log_file);

// Close database connection
// mysqli_close($db);
?> -->

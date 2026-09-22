<!-- <?php
//  ini_set('display_startup_errors', 1);
//  ini_set('display_errors', 1);
//  error_reporting(-1);
// Set a longer execution time limit, essential for cron jobs that might process many records.
set_time_limit(0);
date_default_timezone_set('Asia/Kolkata'); 

// --- DATABASE CONNECTION ---
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/enach_presentment_policy.php';
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
    require_once __DIR__ . '/../lib/loan_charge_calc.php';
    return creditlab_enach_presentment_breakdown($loan, $loan_apply)['total'];
}

function calculateAmountBreakdown($loan, $loan_apply) {
    require_once __DIR__ . '/../lib/loan_charge_calc.php';
    return creditlab_enach_amount_log_fields($loan, $loan_apply);
}

// --- DRY RUN CONFIGURATION ---
// Set to true to enable dry-run mode (no actual API calls, just calculations and logging)
$dry_run = isset($_GET['dry_run']) ? (bool)$_GET['dry_run'] : false;
// Opt-in catch-up: present all DPD>0 loans (same as 3rd/10th/month-end). CLI: php auto_enach.php all_overdue=1
$all_overdue = !empty($_GET['all_overdue']);
if (PHP_SAPI === 'cli' && !empty($argv)) {
    foreach ($argv as $arg) {
        if ($arg === 'all_overdue=1' || $arg === '--all-overdue') {
            $all_overdue = true;
        }
        if ($arg === 'dry_run=1' || $arg === '--dry-run') {
            $dry_run = true;
        }
    }
}

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
$sms_skip_remaining = false;

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
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    error_log($message);
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
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
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
writeLog("All overdue catch-up: " . ($all_overdue ? 'YES' : 'NO'), $log_file);

creditlab_enach_ensure_holiday_table();
creditlab_enach_ensure_presentment_run_table();
$last_working = creditlab_enach_last_working_day_of_month($current_date);
writeLog("Last working day this month: $last_working | Today is last working: " . ($current_date === $last_working ? 'YES' : 'NO'), $log_file);

// Log dry-run mode
if ($dry_run) {
    writeLog("DRY RUN MODE ENABLED - No actual API calls will be made", $log_file);
    echo "=== DRY RUN MODE ENABLED ===\n";
    echo "Date: $current_date\n";
    echo "No actual API calls will be made\n\n";
}

// 1. RESET FAILED E-NACH REQUESTS (4+ days old — matches min gap)
$reset_query = "UPDATE `loan` SET `enach_request` = 0, `enach_request_date` = NULL 
                WHERE `enach_request` = 1 
                AND `enach_request_date` IS NOT NULL 
                AND DATEDIFF('$current_date', `enach_request_date`) >= 4
                AND `status_log` != 'cleared'";
$reset_result = towquery($db, $reset_query);
$reset_count = mysqli_affected_rows($db);
writeLog("Reset $reset_count E-Nach request flags (4+ days old)", $log_file);

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

// 2. ELIGIBLE LOANS — DPD 1 daily, salary date, last working day, 7th (priority + once)
$eligible_loans = [];
$trigger_counts = ['dpd1' => 0, 'salary_date' => 0, 'last_working_day' => 0, 'seventh' => 0, 'catch_up' => 0];

$sql_pool = "SELECT l.*, la.days, la.apply_date, la.interest_percentage, u.salary_date
         FROM `loan` l
         INNER JOIN `loan_apply` la ON l.lid = la.id
         INNER JOIN `user` u ON l.uid = u.id
         WHERE l.`status_log` = 'account manager'
         AND l.`action` != 'cleared'
         AND la.`status` = 'account manager'";
$pool = towquery($db, $sql_pool);
$skipped_flag = 0;
while ($loan = towfetch($pool)) {
    if (creditlab_enach_loan_is_skipped($loan, $current_date)) {
        $skipped_flag++;
        continue;
    }
    $loan_apply = [
        'days' => $loan['days'] ?? 30,
        'interest_percentage' => $loan['interest_percentage'] ?? 1,
    ];
    $trigger = creditlab_enach_trigger_for_loan(
        $loan,
        $loan_apply,
        $loan['salary_date'] ?? '',
        $current_date,
        $all_overdue
    );
    if ($trigger === null) {
        continue;
    }
    $loan['_enach_trigger'] = $trigger;
    $eligible_loans[] = $loan;
    $trigger_counts[$trigger] = ($trigger_counts[$trigger] ?? 0) + 1;
}
writeLog('Triggers today: DPD1=' . $trigger_counts['dpd1']
    . ' salary=' . $trigger_counts['salary_date']
    . ' last_working=' . $trigger_counts['last_working_day']
    . ' seventh=' . $trigger_counts['seventh']
    . ($all_overdue ? (' catch_up=' . $trigger_counts['catch_up']) : ''), $log_file);
if ($skipped_flag > 0) {
    writeLog("Skipped $skipped_flag loans with eNACH skip flag (enach_request = 2)", $log_file);
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

    try {

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
            $presentment_trigger = $loan['_enach_trigger'] ?? 'dpd1';
            writeLog("Loan CLL$lid: Processing E-Nach authorization #$auth_count of $enach_count | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | API: $presentment_api | trigger: $presentment_trigger", $log_file);

            $mandate_key = creditlab_enach_mandate_key($easebuzz_adtdff, (int) $lid);
            $may = creditlab_enach_mandate_may_present($mandate_key, $current_date);
            if (!$may['ok']) {
                writeLog("SKIPPED: CLL$lid mandate $mandate_key ({$may['reason']})", $log_file);
                $skipped_loans[] = "CLL$lid ({$may['reason']})";
                continue;
            }
            
            // Calculate total amount with proper logic (matching zzautoloanamountcalculator.php)
            $totalamount = calculateTotalAmount($loan, $loan_apply);
            
            // Calculate breakdown for detailed logging
            $breakdown = calculateAmountBreakdown($loan, $loan_apply);
            
            $totalamount = number_format($totalamount, 2, '.', '');

            // Detailed logging for dry-run and regular mode
            $log_message = "LOAN ID: CLL$lid | User: {$userdataff['name']} | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']}\n";
            $log_message .= "  Trigger: $presentment_trigger | Mandate: $mandate_key\n";
            $log_message .= "  Disbursed: ₹" . number_format($breakdown['processed_amount'], 2) . "\n";
            $log_message .= "  Repayment base (disbursed+PF+GST): ₹" . number_format($breakdown['repayment_base'], 2) . "\n";
            $log_message .= "  KFS interest (to due date): ₹" . number_format($breakdown['kfs_interest'], 2) . "\n";
            $log_message .= "  Calendar DPD: {$breakdown['calendar_dpd']} | Presentment DPD (DPD+1 next-day debit): {$breakdown['presentment_dpd']}\n";
            $log_message .= "  Overdue interest: ₹" . number_format($breakdown['overdue_interest'], 2) . "\n";
            $log_message .= "  Penalty: ₹" . number_format($breakdown['penalty_charge'], 2) . "\n";
            $log_message .= "  TOTAL AMOUNT: ₹$totalamount\n";
            $log_message .= "  Status: {$loan['status_log']}\n";
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
                    $merchant_ref = trim((string) ($res['merchant_request_number'] ?? $paymentDetails['merchant_debit_id'] ?? ''));
                    creditlab_enach_record_presentment(
                        (int) $lid,
                        (int) $uid,
                        $mandate_key,
                        $presentment_trigger,
                        $totalamount,
                        'success',
                        $current_date,
                        [
                            'merchant_ref' => $merchant_ref,
                            'api' => (string) ($res['api'] ?? 'autocollect'),
                        ]
                    );
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

                    if ($sms_skip_remaining) {
                        $sms_failed_count++;
                    } else {
                        $sms_result = sendSMS($mobile, $sms_message, $template_id, "CREDLB");

                        if ($sms_result['success']) {
                            $sms_sent_count++;
                            writeLog("SMS SENT: E-NACH reminder sent to $mobile for CLL$lid | Amount: ₹$outstanding_amount | Date: $enach_date", $log_file);
                        } else {
                            $sms_failed_count++;
                            writeLog("SMS FAILED: E-NACH reminder failed for CLL$lid | Mobile: $mobile | Error: {$sms_result['error']}", $log_file);
                            $err = strtolower((string) ($sms_result['error'] ?? ''));
                            if (strpos($err, "couldn't connect") !== false || strpos($err, 'timed out') !== false) {
                                $sms_skip_remaining = true;
                                writeLog("SMS skipped for remaining loans this run (gateway unreachable)", $log_file);
                            }
                        }
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
                writeLog("DRY RUN: Would process CLL$lid trigger=$presentment_trigger mandate=$mandate_key amount ₹$totalamount", $log_file);
                
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
    } catch (Throwable $e) {
        $failed_count++;
        $failed_loans[] = "CLL$lid";
        writeLog("ERROR: Loan CLL$lid aborted (continuing batch): " . $e->getMessage(), $log_file);
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

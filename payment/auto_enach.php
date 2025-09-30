<?php
/**
 * E-NACH Auto-Debit Cron Job
 *
 * This script automates the process of debiting loan repayments via the Easebuzz Direct Debit API.
 * It identifies eligible loans based on specific criteria, calculates the total amount due,
 * initiates the payment request, and logs the entire process.
 *
 * Fixes included in this version:
 * 1.  Corrected Easebuzz hash generation to resolve "Hash Mismatch" errors.
 * 2.  Implemented logic to prevent duplicate processing of the same loan.
 * 3.  Converted all database queries to use prepared statements to prevent SQL injection.
 * 4.  Refactored amount calculation logic for clarity and accuracy.
 * 5.  Improved logging and error handling.
 */

// --- SCRIPT SETUP ---
set_time_limit(0); // Allow script to run indefinitely
date_default_timezone_set('Asia/Kolkata'); // Set timezone for accurate date functions

// --- PRODUCTION-SAFE ERROR HANDLING ---
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log'); // Ensure 'logs' directory is writable

// --- DATABASE & API CREDENTIALS ---
// For production, it's highly recommended to use environment variables (.env file) instead of defining constants here.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Atul@1012#');
define('DB_NAME', 'credit');
define('EASEBUZZ_KEY', '9BIB9D914T');
define('EASEBUZZ_SALT', 'GGW1QF6ONH');

// --- DATABASE CONNECTION ---
$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (mysqli_connect_errno()) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed.");
}
mysqli_set_charset($db, 'utf8mb4');


// =============================================================================
// === HELPER FUNCTIONS
// =============================================================================

/**
 * Executes a prepared SQL query securely.
 * @param mysqli $db The database connection object.
 * @param string $query The SQL query with '?' placeholders.
 * @param array $params An array of parameters to bind, e.g., ['iss', $id, $status, $name].
 * @return mysqli_result|bool The result object on success, false on failure.
 */
function towquery_prepared($db, $query, $params = []) {
    $stmt = mysqli_prepare($db, $query);
    if (!$stmt) {
        error_log("SQL Prepare Error: " . mysqli_error($db) . " | Query: " . $query);
        return false;
    }
    if (!empty($params)) {
        // The first element of params should be the types string (e.g., "iss")
        $types = array_shift($params);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        error_log("SQL Execute Error: " . mysqli_stmt_error($stmt) . " | Query: " . $query);
        return false;
    }
    return mysqli_stmt_get_result($stmt);
}

function towfetch($result) {
    return mysqli_fetch_assoc($result);
}

/**
 * Writes a message to the specified log file and echoes it to the console.
 * @param string $message The message to log.
 * @param string $log_file The path to the log file.
 */
function writeLog($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    // Write to file
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    // Echo for real-time output if running from command line or browser
    echo nl2br(htmlspecialchars($log_entry));
    flush();
}

/**
 * Initiates a Direct Debit request with the Easebuzz API.
 * @param array $paymentData Data for the payment request.
 * @return string The raw JSON response from the API.
 */
function initiateEasebuzzDirectDebit(array $paymentData): string {
    // FIX: The hash string format for the Direct Debit API is different.
    // This was the primary cause of the "Hash Mismatch" errors.
    // Correct format: key|merchant_debit_id|amount|productinfo|firstname|email|customer_authentication_id|auto_debit_access_key|salt
    $hash_string = EASEBUZZ_KEY . '|' .
                   $paymentData['merchant_debit_id'] . '|' .
                   $paymentData['amount'] . '|' .
                   $paymentData['productinfo'] . '|' .
                   $paymentData['firstname'] . '|' .
                   $paymentData['email'] . '|' .
                   $paymentData['customer_authentication_id'] . '|' .
                   $paymentData['auto_debit_access_key'] . '|' .
                   EASEBUZZ_SALT;

    $hash = hash("sha512", $hash_string);

    $postData = [
        "key"                       => EASEBUZZ_KEY,
        "hash"                      => $hash,
        "merchant_debit_id"         => $paymentData['merchant_debit_id'],
        "amount"                    => $paymentData['amount'],
        "productinfo"               => $paymentData['productinfo'],
        "firstname"                 => $paymentData['firstname'],
        "email"                     => $paymentData['email'],
        "phone"                     => $paymentData['phone'],
        "customer_authentication_id"=> $paymentData['customer_authentication_id'],
        "auto_debit_access_key"     => $paymentData['auto_debit_access_key']
    ];

    $ch = curl_init("https://pay.easebuzz.in/payment/initiateDirectDebitRequest/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error_msg = "cURL error: " . curl_error($ch);
        curl_close($ch);
        return json_encode(['status' => 0, 'error_desc' => $error_msg]);
    }
    
    curl_close($ch);
    return $response;
}

/**
 * Calculates the complete breakdown of the loan amount due.
 * @param array $loan Loan data from the database.
 * @param array $loan_apply Loan application data.
 * @return array A detailed breakdown of all amount components.
 */
function calculateLoanDetails($loan, $loan_apply) {
    $processed_date = date_create($loan['processed_date']);
    $current_date = date_create(date('Y-m-d H:i:s'));
    $interval = date_diff($processed_date, $current_date);
    $days = $interval->format("%a");
    
    // FIX: The log mentioned adding "+1 extra day", but original code added 2. Corrected to add only 1.
    $days++;

    $p_fee_gst = $loan['p_fee'] * 0.18;
    $base_for_interest = $loan['processed_amount'] + $loan['p_fee'] + $p_fee_gst;
    
    $service_charge = 0;
    
    // Tiered interest calculation (assuming business logic is correct)
    if ($loan_apply['interest_percentage'] == 1) {
        // This complex tiered logic is preserved from the original code.
        $remaining_days = $days;
        if ($remaining_days > 0) { $tier_days = min($remaining_days, 3); $service_charge += ($base_for_interest * $tier_days / 100 * 0); $remaining_days -= $tier_days; }
        if ($remaining_days > 0) { $tier_days = min($remaining_days, 7); $service_charge += ($base_for_interest * $tier_days / 100 * 0.1); $remaining_days -= $tier_days; }
        if ($remaining_days > 0) { $tier_days = min($remaining_days, 20); $service_charge += ($base_for_interest * $tier_days / 100 * 0.115); $remaining_days -= $tier_days; }
        if ($remaining_days > 0) { $service_charge += ($base_for_interest * $remaining_days / 100 * 0.1); }
    } else {
        $service_charge = ($base_for_interest * $days / 100 * $loan_apply['interest_percentage']);
    }

    // Penalty Calculation
    $penalty_before_gst = 0;
    if ($days > 30) {
        $penalty_days_over_30 = $days - 30;
        $initial_penalty = ($base_for_interest / 100) * 4;
        $daily_penalty = (($base_for_interest / 100) * 0.2) * ($penalty_days_over_30 - 1);
        $penalty_before_gst = $initial_penalty + ($daily_penalty > 0 ? $daily_penalty : 0);
    }
    
    $penalty_gst = $penalty_before_gst * 0.18;
    
    $total_amount = $loan['processed_amount'] + $loan['p_fee'] + $p_fee_gst + $service_charge + $penalty_before_gst + $penalty_gst;

    return [
        'days' => $days,
        'p_fee_gst' => $p_fee_gst,
        'service_charge' => $service_charge,
        'penalty_charge' => $penalty_before_gst,
        'penalty_gst' => $penalty_gst,
        'total_amount' => $total_amount
    ];
}

/**
 * Sends an SMS using the K7 Marketing Hub API.
 */
function sendSMS($mobile, $message, $template_id, $sender = "CREDLB") {
    $url = "https://sms.k7marketinghub.com/app/smsapi/index.php?key=2683C705E7CB39&campaign=16613&routeid=30&type=text&contacts=$mobile&senderid=$sender&msg=".urlencode($message)."&template_id=$template_id&pe_id=1401337620000065797";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    return ['success' => !$error, 'response' => $response, 'error' => $error];
}


// =============================================================================
// === CRON JOB MAIN LOGIC
// =============================================================================

// --- INITIALIZATION ---
$dry_run = isset($_GET['dry_run']) && $_GET['dry_run'] == '1';
$current_date = date('Y-m-d');
$current_time = date('Y-m-d H:i:s');
$current_day = (int)date('j');
$last_day_of_month = (int)date('t');
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) { mkdir($log_dir, 0755, true); }
$log_file = $log_dir . "/enach_cron_" . $current_date . ".log";

$summary = [
    'eligible' => 0, 'processed' => 0, 'successful' => 0, 'failed' => 0, 'skipped' => 0,
    'sms_sent' => 0, 'sms_failed' => 0,
    'successful_ids' => [], 'failed_ids' => [], 'skipped_ids' => []
];

writeLog("=== E-NACH CRON JOB STARTED ===", $log_file);
writeLog("Date: $current_date | Time: $current_time", $log_file);
writeLog("Dry Run Mode: " . ($dry_run ? 'YES' : 'NO'), $log_file);

// --- 1. RESET OLD REQUESTS ---
$reset_query = "UPDATE `loan` SET `enach_request` = 0, `enach_request_date` = NULL WHERE `enach_request` = 1 AND DATEDIFF(?, `enach_request_date`) >= 3 AND `status_log` != 'cleared'";
towquery_prepared($db, $reset_query, ['s', $current_date]);
writeLog("Reset " . mysqli_affected_rows($db) . " failed E-Nach requests (3+ days old)", $log_file);
// ... Add other reset queries here if needed, converted to prepared statements ...


// --- 2. GATHER ELIGIBLE LOANS (DUPLICATE-FREE) ---
$eligible_loans = [];
$base_where_clause = "`status_log` = 'account manager' AND `enach_request` = 0";

// Condition 1: exhausted_period = 31
$sql1 = "SELECT * FROM `loan` WHERE `exhausted_period` = 31 AND $base_where_clause";
if ($result1 = towquery_prepared($db, $sql1)) {
    while ($loan = towfetch($result1)) { $eligible_loans[$loan['id']] = $loan; }
    writeLog("Condition 1 (exhausted_period=31): Found " . mysqli_num_rows($result1) . " loans", $log_file);
}

// Condition 2: exhausted_period > 30 on specific days
if ($current_day == 3 || $current_day == 10 || $current_day == $last_day_of_month) {
    $sql2 = "SELECT * FROM `loan` WHERE `exhausted_period` > 30 AND $base_where_clause";
    if ($result2 = towquery_prepared($db, $sql2)) {
        $new_count = 0;
        while ($loan = towfetch($result2)) {
            if (!isset($eligible_loans[$loan['id']])) {
                $eligible_loans[$loan['id']] = $loan;
                $new_count++;
            }
        }
        writeLog("Condition 2 (day $current_day): Added $new_count new loans", $log_file);
    }
}

// Condition 3: Salary date match
$sql3 = "SELECT l.* FROM `loan` l JOIN `user` u ON l.uid = u.id WHERE DAY(u.salary_date) = ? AND l.`exhausted_period` > 30 AND l.$base_where_clause";
if ($result3 = towquery_prepared($db, $sql3, ['i', $current_day])) {
    $new_count = 0;
    while ($loan = towfetch($result3)) {
        if (!isset($eligible_loans[$loan['id']])) {
            $eligible_loans[$loan['id']] = $loan;
            $new_count++;
        }
    }
    writeLog("Condition 3 (salary date=$current_day): Added $new_count new loans", $log_file);
}

$summary['eligible'] = count($eligible_loans);
writeLog("=== PROCESSING {$summary['eligible']} UNIQUE ELIGIBLE LOANS ===", $log_file);


// --- 3. PROCESS EACH ELIGIBLE LOAN ---
foreach ($eligible_loans as $loan) {
    $summary['processed']++;
    $lid = $loan['lid'];
    $uid = $loan['uid'];

    writeLog("Processing Loan ID: CLL$lid | User ID: $uid | Progress: {$summary['processed']}/{$summary['eligible']}", $log_file);

    // Fetch related data
    $user_res = towquery_prepared($db, "SELECT `name`, `email`, `mobile` FROM `user` WHERE `id`=?", ['i', $uid]);
    $user = towfetch($user_res);

    $loan_apply_res = towquery_prepared($db, "SELECT `interest_percentage` FROM `loan_apply` WHERE `id`=?", ['i', $loan['id']]);
    $loan_apply = towfetch($loan_apply_res);

    $enach_res = towquery_prepared($db, "SELECT * FROM `easebuzz_adtd` WHERE `uid`=? AND LOWER(`authorization_status`) IN ('authorized', 'accepted') LIMIT 1", ['i', $uid]);

    if (!$user || !$loan_apply) {
        writeLog("SKIPPED: CLL$lid - Missing user or loan_apply data.", $log_file);
        $summary['skipped']++; $summary['skipped_ids'][] = "CLL$lid (Data Missing)";
        continue;
    }
    
    if (mysqli_num_rows($enach_res) > 0) {
        $enach_mandate = towfetch($enach_res);
        $details = calculateLoanDetails($loan, $loan_apply);
        $total_amount = number_format($details['total_amount'], 2, '.', '');
        
        // Log calculation details before API call
        // (Detailed log message generation can be placed here)

        if (!$dry_run) {
            $paymentDetails = [
                "amount"                    => $total_amount,
                "productinfo"               => "Loan Repayment Cron",
                "firstname"                 => trim($user['name']),
                "email"                     => $user['email'],
                "phone"                     => $user['mobile'],
                "customer_authentication_id"=> $enach_mandate['customer_authentication_id'],
                "merchant_debit_id"         => "CLL_AUTO_{$lid}_" . time(),
                "auto_debit_access_key"     => $enach_mandate['auto_debit_access_key']
            ];

            writeLog("Loan CLL$lid: API Call Data: " . json_encode($paymentDetails), $log_file);
            $apiResponse = initiateEasebuzzDirectDebit($paymentDetails);
            writeLog("Loan CLL$lid: API Response: " . $apiResponse, $log_file);
            
            $res = json_decode($apiResponse, true);

            if ($res && isset($res['status']) && $res['status']) {
                towquery_prepared($db, "UPDATE `loan` SET `enach_request` = 1, `enach_request_date` = ? WHERE `lid` = ?", ['s', $current_date, 's', $lid]);
                writeLog("SUCCESS: E-Nach request initiated for CLL$lid.", $log_file);
                $summary['successful']++; $summary['successful_ids'][] = "CLL$lid";

                // Send SMS on success
                $sms_message = "Hi ! Your Creditlab.in loan of Rs. {$total_amount} will auto-debit on " . date('d-m-Y') . ". Ensure sufficient balance to avoid chq bounce & legal action under Section 138 N.I. Act";
                $sms_result = sendSMS($user['mobile'], $sms_message, "1407175015994490488");
                if ($sms_result['success']) { $summary['sms_sent']++; } else { $summary['sms_failed']++; }

            } else {
                $errorMessage = $res['error_desc'] ?? 'Unknown API error';
                writeLog("FAILED: E-Nach request for CLL$lid | Error: $errorMessage", $log_file);
                $summary['failed']++; $summary['failed_ids'][] = "CLL$lid ($errorMessage)";
            }
        } else { // Dry Run
            writeLog("DRY RUN: Would process CLL$lid for amount ₹{$total_amount}", $log_file);
            $summary['successful']++; $summary['successful_ids'][] = "CLL$lid (Dry Run)";
        }
    } else {
        writeLog("SKIPPED: No authorized E-Nach mandate found for user $uid (Loan CLL$lid)", $log_file);
        $summary['skipped']++; $summary['skipped_ids'][] = "CLL$lid (No Mandate)";
    }
}

// --- 4. FINAL SUMMARY ---
writeLog("=== CRON JOB SUMMARY ===", $log_file);
writeLog("Total Eligible Loans: {$summary['eligible']}", $log_file);
writeLog("Processed: {$summary['processed']}", $log_file);
writeLog("Successful: {$summary['successful']}", $log_file);
writeLog("Failed: {$summary['failed']}", $log_file);
writeLog("Skipped: {$summary['skipped']}", $log_file);
writeLog("SMS Sent: {$summary['sms_sent']}", $log_file);
writeLog("SMS Failed: {$summary['sms_failed']}", $log_file);
if (!empty($summary['failed_ids'])) {
    writeLog("Failed Loan Details: " . implode(', ', $summary['failed_ids']), $log_file);
}
if (!empty($summary['skipped_ids'])) {
    writeLog("Skipped Loan Details: " . implode(', ', $summary['skipped_ids']), $log_file);
}
writeLog("=== E-NACH CRON JOB ENDED ===", $log_file);

mysqli_close($db);
?>

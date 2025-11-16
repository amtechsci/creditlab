<!-- <?php
//  ini_set('display_startup_errors', 1);
//  ini_set('display_errors', 1);
//  error_reporting(-1);
// Set a longer execution time limit, essential for cron jobs that might process many records.
set_time_limit(0); 

// --- DATABASE CONNECTION ---
$db = mysqli_connect("localhost", "root", "Atul@1012#", "testing_credit");

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
function initiateEasebuzzDirectDebit(array $postParams): string
{
    // --- Credentials ---
    // IMPORTANT: Store these securely. Do not hardcode them in a production environment.
    // Consider using environment variables (.env file) or a secure configuration management system.
    $key = '9BIB9D914T';
    $salt = 'GGW1QF6ONH';

    // --- Static & Required Data ---
    $txnid = uniqid("txn_"); // Generate a unique transaction ID for each request
    $surl = "https://creditlab.in/payment/cb_auto.php"; // Your success URL
    $furl = "https://creditlab.in/payment/cb_auto.php"; // Your failure URL

    // --- Map and Sanitize Input Parameters ---
    // This ensures that only expected keys are used and provides default empty values.
    $requiredKeys = [
        "amount" => "",
        "productinfo" => "",
        "firstname" => "",
        "email" => "",
        "phone" => "",
        "customer_authentication_id" => "",
        "merchant_debit_id" => "",
        "auto_debit_access_key" => ""
    ];

    // Add User Defined Fields (udf) to the mapping
    for ($i = 1; $i <= 10; $i++) {
        $requiredKeys["udf{$i}"] = "";
    }

    // Merge the user-provided data with our safe key structure.
    // This ensures all keys for the hash string exist.
    $data = array_merge($requiredKeys, $postParams);


    // --- Generate Hash ---
    // The order of fields is critical for the hash to be valid.
    // --- Generate Hash ---
    // The order of fields is critical for the hash to be valid.
    $hash_string = $key . '|' . $txnid . '|' . $data['amount'] . '|' . $data['productinfo'] . '|' . $data['firstname'] . '|' . $data['email'] . '|' .
                   $data['udf1'] . '|' . $data['udf2'] . '|' . $data['udf3'] . '|' . $data['udf4'] . '|' . $data['udf5'] . '|' .
                   $data['udf6'] . '|' . $data['udf7'] . '|' . $data['udf8'] . '|' . $data['udf9'] . '|' . $data['udf10'] . '|' . $salt;

    $hash = hash("sha512", $hash_string);


    // --- Prepare Data for POST Request ---
    // This array will be sent as the body of the cURL request.
    $postData = [
        "key" => $key,
        "txnid" => $txnid,
        "hash" => $hash,
        "amount" => $data['amount'],
        "productinfo" => $data['productinfo'],
        "firstname" => $data['firstname'],
        "email" => $data['email'],
        "phone" => $data['phone'],
        "surl" => $surl,
        "furl" => $furl,
        "customer_authentication_id" => $data['customer_authentication_id'],
        "merchant_debit_id" => $data['merchant_debit_id'],
        "auto_debit_access_key" => $data['auto_debit_access_key']
    ];
    
    // Add all udf fields to the post data
    for ($i = 1; $i <= 10; $i++) {
        $postData["udf{$i}"] = $data["udf{$i}"];
    }


    // --- Initialize and Execute cURL ---
    $ch = curl_init("https://pay.easebuzz.in/payment/initiateDirectDebitRequest/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json", // Request JSON response
        "Content-Type: application/x-www-form-urlencoded"
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        // If cURL itself fails, return a cURL error message.
        $error_msg = "cURL error: " . curl_error($ch);
        curl_close($ch);
        return json_encode(['status' => 0, 'error' => $error_msg]);
    }
    
    curl_close($ch);

    // Return the response from the API.
    return $response;
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
    
    // Get days from loan_apply
    // For one-time loans (days > 30): Use calculated days
    // For EMI loans (days <= 30): Always use 30 days (original logic)
    $loan_days_raw = isset($loan_apply['days']) ? (int)$loan_apply['days'] : 30;
    $loan_days = ($loan_days_raw > 30) ? $loan_days_raw : 30; // EMI loans always use 30
    
    // E-Nach triggers on tday = days + 1 (DPD = 1), so use tday for calculations
    $days = $tday; // Use tday for service charge calculation
    
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
    
    // Get days from loan_apply
    // For one-time loans (days > 30): Use calculated days
    // For EMI loans (days <= 30): Always use 30 days (original logic)
    $loan_days_raw = isset($loan_apply['days']) ? (int)$loan_apply['days'] : 30;
    $loan_days = ($loan_days_raw > 30) ? $loan_days_raw : 30; // EMI loans always use 30
    
    // E-Nach triggers on tday = days + 1 (DPD = 1), so use tday for calculations
    $days = $tday; // Use tday for service charge calculation
    
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
    $url = "https://sms.k7marketinghub.com/app/smsapi/index.php?key=2683C705E7CB39&campaign=16613&routeid=30&type=text&contacts=$mobile&senderid=$sender&msg=".urlencode($message)."&template_id=$template_id&pe_id=1401337620000065797";
    
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

// 2. DETERMINE ELIGIBLE LOANS BASED ON CONDITIONS
// New logic: Trigger E-Nach when tday = days + 1 (one day after due date, DPD = 1)
$eligible_loans = [];

// Condition 1: Daily run for loans where tday = days + 1 (DPD = 1)
// tday = days since processed_date
// E-Nach triggers when tday = days + 1 (one day after due date)
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
    // Calculate tday (days since processed_date)
    $processed_date_str = date('Y-m-d', strtotime($loan['processed_date'] . " -1 day"));
    $tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
    
    // Get days from loan_apply
    // For one-time loans (days > 30): Use calculated days
    // For EMI loans (days <= 30): Always use 30 days (original logic)
    $loan_days_raw = isset($loan['days']) ? (int)$loan['days'] : 30;
    $loan_days = ($loan_days_raw > 30) ? $loan_days_raw : 30; // EMI loans always use 30
    
    // Trigger E-Nach when tday = days + 1 (one day after due date)
    if ($tday == ($loan_days + 1)) {
        $eligible_loans[] = $loan;
        $condition1_count++;
    }
}
writeLog("Condition 1 (tday = days + 1, current tday calculated for each loan): Found $condition1_count eligible loans", $log_file);

// Check for loans that are skipped due to E-NACH skip flag (for logging only)
// Note: Skip checking is now based on loan status, not due_date matching
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
    writeLog("Condition 1: $skipped_count loans skipped due to E-NACH skip flag (enach_request = 2) - Permanent: $permanent_count, Temporary: $temporary_count", $log_file);
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
            writeLog("Loan CLL$lid: Processing E-Nach authorization #$auth_count of $enach_count | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']}", $log_file);
            
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
                    "auto_debit_access_key" => $easebuzz_adtdff['auto_debit_access_key']
                ];

                // Debug: Log the exact API call data and E-Nach details
                writeLog("Loan CLL$lid: E-Nach Details - Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | Auto Debit Access Key: {$easebuzz_adtdff['auto_debit_access_key']} | Authorization Status: {$easebuzz_adtdff['authorization_status']}", $log_file);
                writeLog("Loan CLL$lid: API Call Data - " . json_encode($paymentDetails), $log_file);
                
                // Call Easebuzz API
                $apiResponse = initiateEasebuzzDirectDebit($paymentDetails);
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

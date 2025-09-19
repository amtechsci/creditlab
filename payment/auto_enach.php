<?php
// Set a longer execution time limit, essential for cron jobs that might process many records.
set_time_limit(0); 

// --- DATABASE CONNECTION ---
$db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");

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
        "auto_debit_access_key" => "",
        "sub_merchant_id" => ""
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
        "auto_debit_access_key" => $data['auto_debit_access_key'],
        "sub_merchant_id" => $data['sub_merchant_id']
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
    // Get current date and calculate days since processed_date
    $stop_date = date_create($loan['processed_date']);
    $sa = date_create(date('Y-m-d 23:59:59'));
    $aa = date_diff($stop_date, $sa);
    $days = $aa->format("%a");
    
    // Add +1 day as requested (if exhausted_period is 30, calculate for 31)
    $days++;
    
    // Calculate base amount with GST on processing fee (18% GST)
    $t = $loan['processed_amount'] + $loan['p_fee'] + ($loan['p_fee'] * 0.18);
    
    $service_charge = 0;
    $penality = 0;
    
    // Calculate service charge based on interest percentage
    if ($loan_apply['interest_percentage'] == 1) {
        // Special case for 1% interest - tiered calculation
        if ($days >= 3) {
            $fee = $t * 3 / 100 * 0;
            $days = $days - 3;
            $service_charge += $fee;
        } else {
            $fee = $t * $days / 100 * 0;
            $days = 0;
            $service_charge += $fee;
        }
        if (($days) >= 7) {
            $fee = $t * 7 / 100 * 0.1;
            $days = $days - 7;
            $service_charge += $fee;
        } else {
            $fee = $t * $days / 100 * 0.1;
            $days = 0;
            $service_charge += $fee;
        }
        if (($days) >= 20) {
            $fee = $t * 20 / 100 * 0.115;
            $days = $days - 20;
            $service_charge += $fee;
        } else {
            $fee = $t * $days / 100 * 0.115;
            $days = 0;
            $service_charge += $fee;
        }
        if (($days) >= 1) {
            $fee = $t * $days / 100 * 0.1;
            $service_charge += $fee;
            $days = 0;
        }
    } else {
        // Standard interest calculation
        $fee = $t * $days / 100 * $loan_apply['interest_percentage'];
        $service_charge += $fee;
    }
    
    // Calculate penalty for days > 30
    if ($days > 30) {
        $penalitydays = $days - 30;
        $penalitydays--;
        $penality = (($t) / 100) * 4;
        $atnp = ((($t) / 100) * 0.2) * $penalitydays;
        $penality = $penality + $atnp;
    } else {
        $penality = 0;
    }
    
    // Add 18% GST to penalty
    $penality = ($penality + ($penality * 0.18));
    
    // Calculate total amount
    $totalamount = (float)$loan['processed_amount'] + (float)$loan['p_fee'] + (float)$service_charge + (float)$penality;
    
    return $totalamount;
}

/**
 * Calculate detailed breakdown of loan amount components
 * @param array $loan Loan data from database
 * @param array $loan_apply Loan application data
 * @return array Breakdown of all amount components
 */
function calculateAmountBreakdown($loan, $loan_apply) {
    // Get current date and calculate days since processed_date
    $stop_date = date_create($loan['processed_date']);
    $sa = date_create(date('Y-m-d 23:59:59'));
    $aa = date_diff($stop_date, $sa);
    $days = $aa->format("%a");
    
    // Add +1 day as requested (if exhausted_period is 30, calculate for 31)
    $days++;
    
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
    
    // Calculate penalty for days > 30
    if ($days > 30) {
        $penalitydays = $days - 30;
        $penalitydays--;
        $penality = (($t) / 100) * 4;
        $atnp = ((($t) / 100) * 0.2) * $penalitydays;
        $penality = $penality + $atnp;
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
$current_day = date('j'); // Day of month without leading zeros
$gst = 0; // Define GST variable

// Log dry-run mode
if ($dry_run) {
    error_log("DRY RUN MODE ENABLED - No actual API calls will be made");
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
towquery($db, $reset_query);

// 2. DETERMINE ELIGIBLE LOANS BASED ON CONDITIONS
$eligible_loans = [];

// Condition 1: Daily run for exhausted_period = 31
$sql1 = "SELECT * FROM `loan` WHERE `exhausted_period` = 31 AND `status_log` = 'account manager' AND `enach_request` = 0";
$loans1 = towquery($db, $sql1);
while ($loan = towfetch($loans1)) {
    $eligible_loans[] = $loan;
}

// Condition 2: On 3rd and 10th of month for exhausted_period > 30
if ($current_day == 3 || $current_day == 10) {
    $sql2 = "SELECT * FROM `loan` WHERE `exhausted_period` > 30 AND `status_log` = 'account manager' AND `enach_request` = 0";
    $loans2 = towquery($db, $sql2);
    while ($loan = towfetch($loans2)) {
        // Avoid duplicates
        $exists = false;
        foreach ($eligible_loans as $existing_loan) {
            if ($existing_loan['id'] == $loan['id']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $eligible_loans[] = $loan;
        }
    }
}

// Condition 3: Salary date processing
$sql3 = "SELECT l.* FROM `loan` l 
         INNER JOIN `user` u ON l.uid = u.id 
         WHERE l.`exhausted_period` > 30 
         AND l.`status_log` = 'account manager' 
         AND l.`enach_request` = 0 
         AND DAY(u.salary_date) = $current_day";
$loans3 = towquery($db, $sql3);
while ($loan = towfetch($loans3)) {
    // Avoid duplicates
    $exists = false;
    foreach ($eligible_loans as $existing_loan) {
        if ($existing_loan['id'] == $loan['id']) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $eligible_loans[] = $loan;
    }
}

// 3. PROCESS ELIGIBLE LOANS
$processed_count = 0;
$success_count = 0;
$failed_count = 0;

foreach ($eligible_loans as $loan) {
    $lid = $loan['lid'];
    $uid = $loan['uid'];
    $processed_count++;

    // Get user details
    $userdata = towquery($db, "SELECT * FROM `user` WHERE id='$uid'");
    $userdataff = towfetch($userdata);

    // Get loan application details for interest calculation
    $loan_apply_data = towquery($db, "SELECT * FROM `loan_apply` WHERE id='$lid'");
    $loan_apply = towfetch($loan_apply_data);

    // Get ALL E-Nach details for this user (multiple customer_authentication_id)
    $easebuzz_adtd = towquery($db, "SELECT * FROM `easebuzz_adtd` WHERE uid='$uid' AND authorization_status = 'accepted'");

    if (townum($easebuzz_adtd) > 0) {
        // Process each customer_authentication_id
        while ($easebuzz_adtdff = towfetch($easebuzz_adtd)) {
            // Calculate total amount with proper logic (matching zzautoloanamountcalculator.php)
            $totalamount = calculateTotalAmount($loan, $loan_apply);
            
            // Calculate breakdown for detailed logging
            $breakdown = calculateAmountBreakdown($loan, $loan_apply);
            
            $totalamount = number_format($totalamount, 2, '.', '');

            // Detailed logging for dry-run and regular mode
            $log_message = "LOAN ID: $lid | User: {$userdataff['name']} | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']}\n";
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
            
            if ($dry_run) {
                echo $log_message;
            }
            error_log($log_message);

            if (!$dry_run) {
                // Prepare payment details
                $paymentDetails = [
                    "amount" => $totalamount,
                    "productinfo" => "Loan Repayment Cron",
                    "firstname" => $userdataff['name'],
                    "email" => $userdataff['email'],
                    "phone" => $userdataff['mobile'],
                    "customer_authentication_id" => $easebuzz_adtdff['customer_authentication_id'],
                    "merchant_debit_id" => "CLL_AUTO_" . $lid,
                    "auto_debit_access_key" => $easebuzz_adtdff['auto_debit_access_key']
                ];

                // Call Easebuzz API
                $apiResponse = initiateEasebuzzDirectDebit($paymentDetails);
                $res = json_decode($apiResponse, true);

                // Check response and update database
                if ($res && isset($res['status']) && $res['status']) {
                    // Update loan with enach_request = 1 and set enach_request_date
                    towquery($db, "UPDATE `loan` SET `enach_request` = 1, `enach_request_date` = '$current_date' WHERE lid = $lid");
                    $success_count++;
                    error_log("SUCCESS: E-Nach request initiated for lid: $lid, customer_auth_id: " . $easebuzz_adtdff['customer_authentication_id']);
                } else {
                    $errorMessage = isset($res['error_desc']) ? $res['error_desc'] : 'Unknown API error';
                    $failed_count++;
                    error_log("FAILED: E-Nach request for lid: $lid, customer_auth_id: " . $easebuzz_adtdff['customer_authentication_id'] . ", Error: $errorMessage");
                }
            } else {
                // In dry-run mode, just count as would-be success
                $success_count++;
            }
        }
    } else {
        $log_message = "SKIPPED: No E-Nach details found for user uid: $uid, lid: $lid\n";
        if ($dry_run) {
            echo $log_message;
        }
        error_log($log_message);
    }
}

// 4. LOG CRON JOB SUMMARY
$summary_message = "E-Nach Cron Job Completed - Date: $current_date, Processed: $processed_count, Success: $success_count, Failed: $failed_count";
if ($dry_run) {
    $summary_message = "E-Nach DRY RUN Completed - Date: $current_date, Processed: $processed_count, Would-be Success: $success_count, Would-be Failed: $failed_count";
    echo "\n=== SUMMARY ===\n";
    echo $summary_message . "\n";
    echo "No actual API calls were made.\n";
    echo "To run for real, remove ?dry_run=1 from URL\n";
}
error_log($summary_message);

// Close database connection
mysqli_close($db);

?>

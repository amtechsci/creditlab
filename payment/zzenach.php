<?php
include '../db.php';

// --- ENHANCED LOGGING ---
$current_date = date('Y-m-d');
$current_time = date('Y-m-d H:i:s');

// Create daily log file
$log_file = "logs/zzenach_" . $current_date . ".log";
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Function to write detailed logs
function writeZzenachLog($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    error_log($message); // Also log to system error log
}

// Start logging
writeZzenachLog("=== ZZENACH PROCESSING STARTED ===", $log_file);
writeZzenachLog("Date: $current_date | Time: $current_time", $log_file);

/**
 * Calculate total amount with dynamic calculation including +1 day extra
 * @param array $loan Loan data from database
 * @param array $loan_apply Loan application data
 * @return float Total calculated amount
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
    
    return [
        'processed_amount' => (float)$loan['processed_amount'],
        'p_fee' => (float)$loan['p_fee'],
        'p_fee_gst' => $p_fee_gst,
        'service_charge' => $service_charge,
        'penalty_charge' => $penality,
        'days' => $days
    ];
}

/**
 * Initiates a direct debit payment request with the Easebuzz API.
 *
 * @param array $postData An associative array containing the payment details.
 * Expected keys: 'amount', 'productinfo', 'firstname', 'email', 'phone',
 * 'customer_authentication_id', 'merchant_debit_id', 'auto_debit_access_key'.
 * Optional keys: 'udf1' through 'udf10', 'sub_merchant_id'.
 * @return string The JSON response from the Easebuzz API or an error string.
 */
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


// --- Main Logic ---

$lid = towreal($_GET['lid']);
writeZzenachLog("Processing loan CLL$lid", $log_file);

$userdata = towquery("SELECT * FROM `loan` INNER JOIN user ON user.id=loan.uid WHERE lid=$lid");
$userdataff = towfetch($userdata);

if (!$userdataff) {
    writeZzenachLog("ERROR: Loan CLL$lid not found", $log_file);
    echo "<script>alert('Loan not found!');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
    exit;
}

$loan_data = towquery("SELECT * FROM loan_apply WHERE id='$lid'");
$loan_fetch = towfetch($loan_data);

if (!$loan_fetch) {
    writeZzenachLog("ERROR: Loan application not found for CLL$lid", $log_file);
    echo "<script>alert('Loan application not found!');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
    exit;
}

// Use dynamic calculation with +1 day extra
$totalamount = calculateTotalAmount($userdataff, $loan_fetch);
$totalamount = number_format($totalamount, 2, '.', '');

// Calculate breakdown for logging
$breakdown = calculateAmountBreakdown($userdataff, $loan_fetch);

writeZzenachLog("Loan CLL$lid: Dynamic calculation completed - Amount: ₹$totalamount", $log_file);
writeZzenachLog("Loan CLL$lid: Breakdown - Processed: ₹" . number_format($breakdown['processed_amount'], 2) . " | Processing Fee: ₹" . number_format($breakdown['p_fee'], 2) . " | Service Charge: ₹" . number_format($breakdown['service_charge'], 2) . " | Penalty: ₹" . number_format($breakdown['penalty_charge'], 2), $log_file);

// Get ALL E-Nach details for this user (multiple customer_authentication_id)
$easebuzz_adtd = towquery("SELECT * FROM `easebuzz_adtd` WHERE uid='{$userdataff['uid']}' AND (LOWER(authorization_status) = 'accepted' OR LOWER(authorization_status) = 'authorized')");
$enach_count = townum($easebuzz_adtd);

if($enach_count > 0){
    writeZzenachLog("Loan CLL$lid: Found $enach_count E-Nach authorization(s) for user {$userdataff['uid']}", $log_file);
    
    $success_count = 0;
    $failed_count = 0;
    $success_messages = [];
    $error_messages = [];
    
    // Process each E-Nach authorization
    $auth_count = 0;
    while ($easebuzz_adtdff = towfetch($easebuzz_adtd)) {
        $auth_count++;
        writeZzenachLog("Loan CLL$lid: Processing E-Nach authorization #$auth_count of $enach_count | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']}", $log_file);
        
        $paymentDetails = [
            "amount" => "$totalamount",
            "productinfo" => "Loan Repayment Manual",
            "firstname" => $userdataff['name'],
            "email" => $userdataff['email'],
            "phone" => $userdataff['mobile'],
            "customer_authentication_id" => $easebuzz_adtdff['customer_authentication_id'],
            "merchant_debit_id" => "CLL_AUTO_".$lid,
            "auto_debit_access_key" => $easebuzz_adtdff['auto_debit_access_key']
        ];
        
        // Debug: Log the exact API call data
        writeZzenachLog("Loan CLL$lid: API Call Data - " . json_encode($paymentDetails), $log_file);
        
        $apiResponse = initiateEasebuzzDirectDebit($paymentDetails);
        writeZzenachLog("Loan CLL$lid: API Response - " . $apiResponse, $log_file);
        
        $res = json_decode($apiResponse, true);
        
        // Check if the response was successfully decoded and if the status key exists and is true
        if($res && isset($res['status']) && $res['status']){
            towquery("UPDATE `loan` SET `enach_request`=1, `enach_request_date`='".date('Y-m-d')."' WHERE lid=$lid");
            $success_count++;
            $success_messages[] = "E-Nach #$auth_count (Auth ID: {$easebuzz_adtdff['customer_authentication_id']}) - SUCCESS";
            writeZzenachLog("SUCCESS: E-Nach request initiated for CLL$lid | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | Amount: ₹$totalamount", $log_file);
        } else {
            $failed_count++;
            $errorMessage = isset($res['error_desc']) ? $res['error_desc'] : 'Unknown API error';
            $error_messages[] = "E-Nach #$auth_count (Auth ID: {$easebuzz_adtdff['customer_authentication_id']}) - FAILED: $errorMessage";
            writeZzenachLog("FAILED: E-Nach request for CLL$lid | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | Error: $errorMessage", $log_file);
        }
    }
    
    // Prepare final message
    $final_message = "E-Nach Processing Complete for CLL$lid:\n\n";
    $final_message .= "Total E-Nach Authorizations: $enach_count\n";
    $final_message .= "Successful: $success_count\n";
    $final_message .= "Failed: $failed_count\n\n";
    
    if (!empty($success_messages)) {
        $final_message .= "SUCCESS DETAILS:\n";
        foreach ($success_messages as $msg) {
            $final_message .= "✓ $msg\n";
        }
        $final_message .= "\n";
    }
    
    if (!empty($error_messages)) {
        $final_message .= "FAILED DETAILS:\n";
        foreach ($error_messages as $msg) {
            $final_message .= "✗ $msg\n";
        }
    }
    
    writeZzenachLog("=== ZZENACH PROCESSING COMPLETED ===", $log_file);
    writeZzenachLog("Final Result: $success_count successful, $failed_count failed out of $enach_count total", $log_file);
    
    echo "<script>alert('$final_message');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
    exit;
    
} else {
    writeZzenachLog("ERROR: No E-Nach details found for user {$userdataff['uid']}, loan CLL$lid", $log_file);
    echo "<script>alert('E-Nach details not found for this user.');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
    exit;
}
?>
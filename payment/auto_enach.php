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

// --- CRON JOB LOGIC ---
$current_date = date('Y-m-d');
$current_day = date('j'); // Day of month without leading zeros
$gst = 0; // Define GST variable

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

    // Get ALL E-Nach details for this user (multiple customer_authentication_id)
    $easebuzz_adtd = towquery($db, "SELECT * FROM `easebuzz_adtd` WHERE uid='$uid' AND authorization_status = 'accepted'");

    if (townum($easebuzz_adtd) > 0) {
        // Process each customer_authentication_id
        while ($easebuzz_adtdff = towfetch($easebuzz_adtd)) {
            $totalamount = (float)$loan['processed_amount'] + (float)$loan['p_fee'] + (float)$loan['service_charge'] + $gst + (float)$loan['penality_charge'];
            $totalamount = number_format($totalamount, 2, '.', '');

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
        }
    } else {
        error_log("SKIPPED: No E-Nach details found for user uid: $uid, lid: $lid");
    }
}

// 4. LOG CRON JOB SUMMARY
error_log("E-Nach Cron Job Completed - Date: $current_date, Processed: $processed_count, Success: $success_count, Failed: $failed_count");

// Close database connection
mysqli_close($db);

?>

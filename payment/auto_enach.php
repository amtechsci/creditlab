<?php
// Set a longer execution time limit, essential for cron jobs that might process many records.
set_time_limit(0); 

// Include your database connection and the payment function file.
// Adjust the path as necessary.
include '../db.php'; 
function initiateEasebuzzDirectDebit(array $postParams): string
{
    // --- Credentials ---
    // IMPORTANT: Store these securely. Do not hardcode them in a production environment.
    // Consider using environment variables (.env file) or a secure configuration management system.
    $key = '9BIB9D914T';
    $salt = 'GGW1QF6ONH';

    // --- Static & Required Data ---
    $txnid = uniqid("txn_"); // Generate a unique transaction ID for each request
    $surl = "https://creditlab.in/payment/cb.php"; // Your success URL
    $furl = "https://creditlab.in/payment/cb.php"; // Your failure URL

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

echo "Cron Job Started: " . date('Y-m-d H:i:s') . "";

// 1. Select all loans that are ready for automatic E-Nach debit.
$sql = "SELECT * FROM `loan` WHERE `exhausted_period` = 31   AND `status_log` = 'account manager'   AND `enach_request` = 0";
$eligible_loans = towquery($sql);

if (townum($eligible_loans) == 0) {
    echo "No eligible loans found for E-Nach processing.";
    exit; // Exit cleanly if there's nothing to do.
}

echo "Found " . townum($eligible_loans) . " loans to process.";

// 2. Loop through each eligible loan.
while ($loan = towfetch($eligible_loans)) {
    $lid = $loan['lid'];
    $uid = $loan['uid'];

    echo "---------------------------------";
    echo "Processing Loan ID (lid): $lid for User ID (uid): $uid";

    // 3. Fetch associated user and E-Nach data for the current loan.
    $userdata = towquery("SELECT * FROM `user` WHERE id='$uid'");
    $userdataff = towfetch($userdata);

    $easebuzz_adtd = towquery("SELECT * FROM `easebuzz_adtd` WHERE uid='$uid'");

    // Check if E-Nach details exist for the user.
    if (townum($easebuzz_adtd) > 0) {
        $easebuzz_adtdff = towfetch($easebuzz_adtd);

        $totalamount = $loan['processed_amount'] + $loan['p_fee'] + $loan['service_charge'] + $gst + $loan['penality_charge'];
        $totalamount = number_format($totalamount, 2, '.', '');

        // 5. Prepare the payment details for the API call.
        $paymentDetails = [
            "amount" => $totalamount,
            "productinfo" => "Loan Repayment Cron",
            "firstname" => $userdataff['name'],
            "email" => $userdataff['email'],
            "phone" => $userdataff['mobile'],
            "customer_authentication_id" => $easebuzz_adtdff['customer_authentication_id'],
            "merchant_debit_id" => "CLL_AUTO_" . $lid, // Use a unique ID for cron transactions
            "auto_debit_access_key" => $easebuzz_adtdff['auto_debit_access_key']
        ];

        // 6. Call the payment function.
        $apiResponse = initiateEasebuzzDirectDebit($paymentDetails);
        $res = json_decode($apiResponse, true);

        // 7. Check the response and update the database.
        if ($res && isset($res['status']) && $res['status']) {
            towquery("UPDATE `loan` SET `enach_request` = 1 WHERE lid = $lid");
            echo "SUCCESS: E-Nach request initiated for lid: $lid. Response: $apiResponse";
        } else {
            // Log the failure for investigation.
            $errorMessage = isset($res['error_desc']) ? $res['error_desc'] : 'Unknown API error.';
            echo "FAILED: E-Nach request for lid: $lid. Error: $errorMessage";
        }
    } else {
        echo "SKIPPED: No E-Nach details found for user uid: $uid.";
    }
}

echo "---------------------------------";
echo "Cron Job Finished: " . date('Y-m-d H:i:s') . "";

?>

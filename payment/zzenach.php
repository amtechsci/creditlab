<?php
include '../db.php';
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


// --- Main Logic ---

$lid = towreal($_GET['lid']);
$userdata = towquery("SELECT * FROM `loan` INNER JOIN user ON user.id=loan.uid WHERE lid=$lid");
$userdataff = towfetch($userdata);

$loan_data = towquery("SELECT * FROM loan_apply WHERE id='$lid'");
$loan_fetch = towfetch($loan_data);
extract($loan_fetch,EXTR_PREFIX_ALL,"users");
    $gst = ($users_processing_fees*0.18);
    $totalamount = $users_amount + $users_processing_fees + $users_service_charge + $gst + $users_penality_charge;
    $totalamount = number_format($totalamount, 2, '.', '');
    
$easebuzz_adtd = towquery("SELECT * FROM `easebuzz_adtd` WHERE uid='{$userdataff['uid']}'");
if(townum($easebuzz_adtd) > 0){
    $easebuzz_adtdff = towfetch($easebuzz_adtd);
    $paymentDetails = [
        "amount" => "$totalamount",
        "productinfo" => "Loan repayment",
        "firstname" => $userdataff['name'],
        "email" => $userdataff['email'],
        "phone" => $userdataff['mobile'],
        "customer_authentication_id" => $easebuzz_adtdff['customer_authentication_id'],
        "merchant_debit_id" => "CLL".$lid,
        "auto_debit_access_key" => $easebuzz_adtdff['auto_debit_access_key']
    ];
    $apiResponse = initiateEasebuzzDirectDebit($paymentDetails);
    
    $res = json_decode($apiResponse,true);
    
    // Check if the response was successfully decoded and if the status key exists and is true
    if($res && isset($res['status']) && $res['status']){
        towquery("UPDATE `loan` SET `enach_request`=1 WHERE lid=$lid");
        echo "<script>alert('E-Nach successfully set!');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
        exit;
    } else {
        // Handle API error. You might want to log the actual error from $res for debugging.
        $errorMessage = isset($res['error_desc']) ? $res['error_desc'] : 'An unknown API error occurred.';
        echo $errorMessage;
        // echo "<script>alert('Failed to set E-Nach: " . addslashes($errorMessage) . "');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
        exit;
    }
    
}
echo "<script>alert('E-Nach details not found for this user.');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>"; exit;
?>
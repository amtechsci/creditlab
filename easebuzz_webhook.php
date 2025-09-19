<?php
$filename = 'webhook_data.txt';
date_default_timezone_set('Asia/Kolkata');

// --- LOGGING ---
$headers = getallheaders();
$headersFormatted = "Headers:\n" . serialize($headers);
$getData = "GET Data:\n" . serialize($_GET);
$postData = "POST Data:\n" . serialize($_POST);
$rawBody = "Raw Body:\n" . file_get_contents('php://input');
$logData = "\n=== New Request at ".date('Y-m-d H:i:s')." ===\n";
$logData .= $headersFormatted . "\n";
$logData .= $getData . "\n";
$logData .= $postData . "\n";
$logData .= $rawBody . "\n";
file_put_contents($filename, $logData, FILE_APPEND);


// --- 1. ESTABLISH ONE DATABASE CONNECTION ---
// This is more efficient and allows us to use the connection variable everywhere.
$db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");

// Always check for connection errors
if (mysqli_connect_errno()) {
    error_log("Database connection failed: " . mysqli_connect_error());
    http_response_code(500);
    die("Database connection failed."); // Stop execution if DB is down
}
mysqli_set_charset($db, 'utf8');


// --- 2. REVISED DATABASE FUNCTIONS ---
// These now require the $db connection to be passed in.
function towquery($db, $query) {
    return mysqli_query($db, $query);
}
function townum($query_result) {
    return mysqli_num_rows($query_result);
}
function towfetch($query_result) {
    return mysqli_fetch_array($query_result);
}


// --- 3. MAIN LOGIC ---
$data = $_POST;

// Make sure the required fields are available in the callback
if ($data['furl'] == 'https://creditlab.in/payment/cb_auto.php') {
    // Handle auto-debit execution results
    if (isset($data['auto_debit_request_state']) && $data['auto_debit_request_state'] == 'success') {
        $merchant_debit_id = $data['merchant_debit_id'];
        $amount = $data['amount'];
        $bank_ref_num = $data['bank_ref_num'];
        $txnid = $data['txnid'];
        
        // Extract loan.lid from merchant_debit_id (remove CLL_AUTO_ prefix)
        if (strpos($merchant_debit_id, 'CLL_AUTO_') === 0) {
            $loan_lid = substr($merchant_debit_id, 9); // Remove 'CLL_AUTO_' (9 characters)
            
            
            // Get loan details
            $loan_data = towquery($db, "SELECT * FROM loan WHERE lid='$loan_lid'");
            if (townum($loan_data) > 0) {
                $loan_details = towfetch($loan_data);
                $uid = $loan_details['uid'];
                
                // Get user details
                $user_data = towquery($db, "SELECT * FROM user WHERE id='$uid'");
                $user_details = towfetch($user_data);
                
                // Calculate credit score points (same logic as admin/profile.php)
                $dpd = $loan_details['exhausted_period'] - 30;
                if ($dpd > 0) {
                    if ($dpd > 30) {
                        $point = -50;
                    } elseif ($dpd > 10) {
                        $point = -8;
                    } else {
                        $point = 2;
                    }
                } else {
                    $point = 8;
                }
                
                // Check if it's EMI
                $chf_data = towquery($db, "SELECT * FROM pay_ref WHERE loan_id='$loan_lid'");
                $chf = towfetch($chf_data);
                if ($chf && $chf['is_emi'] == 1) {
                    towquery($db, "UPDATE `loan` SET `semi`=1,`femi`=1 WHERE lid=$loan_lid");
                }
                
                // Update user credit score and loan count
                towquery($db, "UPDATE `user` SET `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point WHERE id=".$uid);
                
                // Clear the loan
                towquery($db, "UPDATE `loan` SET `action`='cleared',`status_log`='cleared',`cleard_date`='".date('Y-m-d')."' WHERE lid=$loan_lid");
                towquery($db, "UPDATE `user` SET `status`='cleared' WHERE id=".$uid);
                towquery($db, "UPDATE `loan_apply` SET `status`='cleared' WHERE id=".$loan_lid);
                towquery($db, "DELETE FROM `pay_ref` WHERE `loan_id`='$loan_lid'");
                
                // Insert transaction details
                towquery($db, "INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES (".$uid.", '".$loan_lid."', '$bank_ref_num', '".date('Y-m-d H:i:s')."', '$amount', 'full')");
                
                // Generate no-due certificate
                file_get_contents("https://creditlab.in/zxc/?url3=https://creditlab.in/no-due-certificate2.php?id=".$loan_lid."&email=".$user_details['email']);
                
                // Send SMS notification
                $template_id = '1107165683325768963';
                $mobile = $user_details['mobile'];
                include 'send_sms.php';
                
            }
        }
    }
}
elseif ($data['furl'] == 'https://creditlab.in/easebuzz_callback.php') {
    // Using prepared statements to prevent SQL Injection
    $stmt1 = mysqli_prepare($db, "UPDATE easebuzz_adtd SET authorization_status = ?, net_amount_debit = ?, bank_ref_num = ?, easepayid = ?, addedon = ?, cash_back_percentage = ?, status = ?, auto_debit_access_key = ? WHERE txnid = ?");

    $txnid = $data['txnid'];
    $authorization_status = strtolower($data['authorization_status']);
    $net_amount_debit = $data['net_amount_debit'];
    $bank_ref_num = $data['bank_ref_num'];
    $easepayid = $data['easepayid'];
    $addedon = $data['addedon'];
    $cash_back_percentage = $data['cash_back_percentage'];
    $status = $data['status'];
    $error_message = $data['error_Message'];
    $auto_debit_access_key = $data['auto_debit_access_key'];

    $update_status = $authorization_status;
    $user_easebuzz_status = 1;
    if ($authorization_status === 'rejected') {
        $update_status = 'rejected';
        $user_easebuzz_status = 2;
    } elseif ($status === 'failure') {
        $user_easebuzz_status = 0;
    }

    mysqli_stmt_bind_param($stmt1, "sssssssss", $update_status, $net_amount_debit, $bank_ref_num, $easepayid, $addedon, $cash_back_percentage, $status, $auto_debit_access_key, $txnid);

    if (mysqli_stmt_execute($stmt1)) {

        // Get the `uid` for the txnid to update the corresponding user table
        $stmt2 = mysqli_prepare($db, "SELECT uid FROM easebuzz_adtd WHERE txnid = ?");
        mysqli_stmt_bind_param($stmt2, "s", $txnid);
        mysqli_stmt_execute($stmt2);
        $result = mysqli_stmt_get_result($stmt2);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $uid = $row['uid'];

            $stmt3 = mysqli_prepare($db, "UPDATE user SET easebuzz = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt3, "is", $user_easebuzz_status, $uid);

            if (mysqli_stmt_execute($stmt3)) {
                // User table updated successfully
            }
        }
    }

} elseif (isset($data['furl']) && $data['furl'] == 'https://creditlab.in/payeasebuzz/response.php') {
    $result = $_POST;
    $txnid = $result['txnid'];
    $status = $result['status'];
    $amount = $result['amount'];
    $bank_ref_num = $result['bank_ref_num'];
    // FIX: Define $payment_method. Change 'payment_mode' to the actual key from your payment provider.
    $payment_method = isset($result['payment_mode']) ? $result['payment_mode'] : 'N/A';
    
    if (isset($status) && $status == "success") {
        $pg_transaction = towquery($db, "SELECT * FROM pg_transaction WHERE txnid='$txnid' AND `status`!='success'");
        if (townum($pg_transaction) > 0) {
            $pg_data = towfetch($pg_transaction);
            $cllid = $pg_data['loan_id'];
            
            $loan_data = towquery($db, "SELECT * FROM loan WHERE id='$cllid'");
            $loan_details = towfetch($loan_data);
            $uid = $loan_details['uid'];

            // FIX: Fetch user details so $user_details is defined
            $user_data_result = towquery($db, "SELECT * FROM user WHERE id='$uid'");
            $user_details = towfetch($user_data_result);

            $dpd = $loan_details['exhausted_period'] - 30;
            $point = ($dpd > 0) ? (($dpd > 30) ? -50 : (($dpd > 10) ? -8 : 2)) : 8;

            towquery($db, "UPDATE `user` SET `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point WHERE id=".$uid);
            towquery($db, "UPDATE `loan` SET `action`='cleared', `status_log`='cleared', `cleard_date`='".date('Y-m-d')."' WHERE id=".$loan_details['id']);
            towquery($db, "UPDATE `user` SET `status`='cleared' WHERE id=".$uid);
            towquery($db, "UPDATE `loan_apply` SET `status`='cleared' WHERE id=".$loan_details['lid']);
            towquery($db, "INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES (".$uid.", '".$loan_details['lid']."', '$bank_ref_num', '".date('Y-m-d H:i:s')."', '$amount', 'full')");

            // FIX: $user_details is now defined and can be used here
            file_get_contents("https://creditlab.in/zxc/?url3=https://creditlab.in/no-due-certificate2.php?id=".$loan_details['lid']."&email=".$user_details['email']);
            
            $template_id='1107165683325768963';
            // FIX: $user_details is now defined and can be used here
            $mobile = $user_details['mobile'];
            include '../send_sms.php';

            // FIX: The query now includes the defined $payment_method variable
            towquery($db, "UPDATE `pg_transaction` SET `status`='success', `amount`='$amount', `payment_method`='$payment_method', `bank_reference_number`='$bank_ref_num' WHERE txnid='$txnid'");
        }
    } else {
        $error_msg = isset($result['error_Message']) ? $result['error_Message'] : "Unknown error.";
        towquery($db, "UPDATE `pg_transaction` SET `status`='failure' WHERE txnid='$txnid'");
    }
}

http_response_code(200);

// Close the single database connection at the end
mysqli_close($db);
?>
<?php
$filename = 'webhook_data.txt';
date_default_timezone_set('Asia/Kolkata');

// --- LOGGING (No changes here) ---
$headers = getallheaders();
$headersFormatted = "Headers:\n" . print_r($headers, true);
$getData = "GET Data:\n" . print_r($_GET, true);
$postData = "POST Data:\n" . print_r($_POST, true);
$rawBody = "Raw Body:\n" . file_get_contents('php://input');
$logData = "\n=== New Request at ".date('Y-m-d H:i:s')." ===\n";
$logData .= $headersFormatted . "\n";
$logData .= $getData . "\n";
$logData .= $postData . "\n";
$logData .= $rawBody . "\n";
file_put_contents($filename, $logData, FILE_APPEND);


// --- 1. ESTABLISH ONE DATABASE CONNECTION ---
// This is more efficient and allows us to use the connection variable everywhere.
$db = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");

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
if (isset($data['txnid'], $data['authorization_status']) && $data['furl'] == 'https://creditlab.in/payment/cb.php') {
    // Using prepared statements to prevent SQL Injection
    $stmt1 = mysqli_prepare($db, "UPDATE easebuzz_adtd SET authorization_status = ?, net_amount_debit = ?, bank_ref_num = ?, easepayid = ?, addedon = ?, cash_back_percentage = ?, status = ?, error_message = ?, auto_debit_access_key = ? WHERE txnid = ?");

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

    mysqli_stmt_bind_param($stmt1, "ssssssssss", $update_status, $net_amount_debit, $bank_ref_num, $easepayid, $addedon, $cash_back_percentage, $status, $error_message, $auto_debit_access_key, $txnid);

    if (mysqli_stmt_execute($stmt1)) {
        echo "Authorization status updated in easebuzz_adtd.\n";

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
                echo "User table updated successfully.\n";
            } else {
                // FIX: Correctly using $db for the error message
                echo "Error updating user table: " . mysqli_error($db);
            }
        } else {
            echo "User ID not found for txnid: $txnid.";
        }
    } else {
        // FIX: Correctly using $db for the error message
        echo "Error updating easebuzz_adtd: " . mysqli_error($db);
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
            towquery($db, "INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES (".$uid.",'".$loan_details['lid']."','$bank_ref_num','".date('Y-m-d H:i:s')."','$amount','full')");

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
        echo "Payment Failed: " . $error_msg;
        towquery($db, "UPDATE `pg_transaction` SET `status`='failure', `error_message`='".$error_msg."' WHERE txnid='$txnid'");
    }
} else {
    echo "Request could not be processed.";
}

http_response_code(200);
echo "Webhook processed.";

// Close the single database connection at the end
mysqli_close($db);
?>
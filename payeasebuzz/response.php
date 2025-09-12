<?php
// Include database connection
include '../db.php';

// Include Easebuzz library (same as before)
include_once('easebuzz-lib/easebuzz_payment_gateway.php');

// Merchant Credentials
$MERCHANT_KEY = "9BIB9D914T"; // Replace with live Merchant Key
$SALT = "GGW1QF6ONH";         // Replace with live Salt Key
$ENV = "prod";                // Use "prod" for production

// Initialize Easebuzz Object
$easebuzzObj = new Easebuzz($MERCHANT_KEY, $SALT, $ENV);

// Handle Response
if ($_POST) {
    // Validate the response
    $result = $easebuzzObj->easebuzzResponse($_POST);
    $result = json_decode($result, true);

    // Extract transaction details from the response (available for both success and failure)
    $txnid = isset($result['data']['txnid']) ? $result['data']['txnid'] : '';
    $amount = isset($result['data']['amount']) ? $result['data']['amount'] : '';
    $payment_method = isset($result['data']['mode']) ? $result['data']['mode'] : '';
    $bank_reference_number = isset($result['data']['bank_ref_num']) ? $result['data']['bank_ref_num'] : '';
    
    // Check if payment was successful
    if (isset($result['status']) && $result['status'] == 1 && isset($result['data']['status']) && $result['data']['status'] === "success") {

        // Fetch loan_id from pg_transaction table
        $pg_transaction = towquery("SELECT * FROM pg_transaction WHERE txnid='$txnid' AND `status`!='success'");
        if(townum($pg_transaction) > 0){
            $pg_data = towfetch($pg_transaction);
            $cllid = $pg_data['loan_id'];  // Retrieve loan_id from pg_transaction table
    
            // Fetch loan details using the loan_id
            $loan_data = towquery("SELECT * FROM loan WHERE id='$cllid'");
            $loan_details = towfetch($loan_data);
            
            $user_data = towquery("SELECT * FROM user WHERE id='".$loan_details['uid']."'");
            $user_details = towfetch($user_data);
    
            // Handle exhausted period and calculate the points for loan
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
    
            // Update user’s loan count and credit score
            $a['user'] = towquery("UPDATE `user` SET `status`='cleared', `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point WHERE id=".$loan_details['uid']);
    
            // Update loan status to 'cleared'
            $a['loan'] = towquery("UPDATE `loan` SET `action`='cleared', `status_log`='cleared', `cleard_date`='".date('Y-m-d')."' WHERE id=".$loan_details['id']);
    
            // Send No Due certificate email
            file_get_contents("https://creditlab.in/zxc/?url3=https://creditlab.in/no-due-certificate2.php?id=".$loan_details['lid']."&email=".$user_details['email']);
    
            // Update loan application status
            $a['loan_apply'] = towquery("UPDATE `loan_apply` SET `status`='cleared' WHERE id=".$loan_details['lid']);
    
            $a['transaction_details'] = towquery("INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES (".$loan_details['uid'].",'".$loan_details['lid']."','$bank_reference_number','".date('Y-m-d H:i:s')."','$amount','full')");
            $template_id='1107165683325768963';
            $mobile = $user_details['mobile'];
            include '../send_sms.php';
            // Update pg_transaction with success status and other details
            towquery("UPDATE `pg_transaction` SET `status`='success', `amount`='$amount', `payment_method`='$payment_method', `bank_reference_number`='$bank_reference_number' WHERE txnid='$txnid'");
    
            // Optionally, you can use a service to send this message (e.g., an SMS gateway)
    
            // Show the success message and delay the redirect
            echo '<html><body>';
            echo '<h3>Payment Successful!</h3>';
            echo '<p>Your payment has been successfully processed. You will be redirected shortly.</p>';
            // print_r($a);
            // echo "UPDATE `pg_transaction` SET `status`='success', `amount`='$amount', `payment_method`='$payment_method', `bank_reference_number`='$bank_reference_number', `payment_date`='".date('Y-m-d H:i:s')."' WHERE txnid='$txnid'";
            echo '<script>';
            echo 'setTimeout(function() { window.location.href = "/user/"; }, 2000);';
            echo '</script>';
            echo '</body></html>';
        }
    } else {
        // Payment Failed: Handle accordingly
        echo "Payment Failed: ";
        echo isset($result['data']['error_Message']) ? $result['data']['error_Message'] : "Unknown error.";
        
        // Update pg_transaction with failure status
        towquery("UPDATE `pg_transaction` SET `status`='failure' WHERE txnid='$txnid'");
    }
} else {
    echo "No response received!";
}
?>
<?php
// Include database connection
include '../db.php';

// Include Easebuzz library (same as before)
include_once('easebuzz-lib/easebuzz_payment_gateway.php');

// Merchant Credentials
require_once __DIR__ . '/../config/easebuzz.php';
$MERCHANT_KEY = EASEBUZZ_MERCHANT_KEY;
$SALT = EASEBUZZ_SALT;
$ENV = EASEBUZZ_ENV;

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
            
            // Check if loan is already cleared to prevent duplicate processing
            if ($loan_details['status_log'] == 'cleared') {
                // Update pg_transaction but skip other processing
                towquery("UPDATE `pg_transaction` SET `status`='success', `amount`='$amount', `payment_method`='$payment_method', `bank_reference_number`='$bank_reference_number' WHERE txnid='$txnid'");
                echo '<html><body>';
                echo '<h3>Payment Already Processed!</h3>';
                echo '<p>This payment has already been processed. You will be redirected shortly.</p>';
                echo '<script>';
                echo 'setTimeout(function() { window.location.href = "/user/"; }, 2000);';
                echo '</script>';
                echo '</body></html>';
                exit;
            }
            
            $user_data = towquery("SELECT * FROM user WHERE id='".$loan_details['uid']."'");
            $user_details = towfetch($user_data);
    
            // Fetch loan days from loan_apply table
            $loan_apply_data = towfetch(towquery("SELECT days FROM loan_apply WHERE id='".$loan_details['lid']."'"));
            $loan_days = isset($loan_apply_data['days']) && $loan_apply_data['days'] > 0 ? (int)$loan_apply_data['days'] : 30;
    
            // Handle exhausted period and calculate the points for loan
            $dpd = $loan_details['exhausted_period'] - $loan_days;
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
    
            // Start database transaction
            mysqli_autocommit($db, false);
            
            // Log the start of processing
            error_log("PAYMENT PROCESSING START: txnid=$txnid, loan_id={$loan_details['lid']}, user_id={$loan_details['uid']}");
            
            // Diagnostic: Check current state of all tables
            $pg_check = towquery("SELECT * FROM pg_transaction WHERE txnid='$txnid'");
            $pg_status = towfetch($pg_check);
            error_log("DIAGNOSTIC - pg_transaction status: " . ($pg_status['status'] ?? 'NULL'));
            
            $loan_check = towquery("SELECT status_log, action FROM loan WHERE id='{$loan_details['id']}'");
            $loan_status = towfetch($loan_check);
            error_log("DIAGNOSTIC - loan status_log: " . ($loan_status['status_log'] ?? 'NULL') . ", action: " . ($loan_status['action'] ?? 'NULL'));
            
            $user_check = towquery("SELECT status, sloan FROM user WHERE id='{$loan_details['uid']}'");
            $user_status = towfetch($user_check);
            error_log("DIAGNOSTIC - user status: " . ($user_status['status'] ?? 'NULL') . ", sloan: " . ($user_status['sloan'] ?? 'NULL'));
            
            $loan_apply_check = towquery("SELECT status FROM loan_apply WHERE id='{$loan_details['lid']}'");
            $loan_apply_status = towfetch($loan_apply_check);
            error_log("DIAGNOSTIC - loan_apply status: " . ($loan_apply_status['status'] ?? 'NULL'));
            
            $transaction_check = towquery("SELECT COUNT(*) as count FROM transaction_details WHERE cllid='{$loan_details['lid']}' AND transaction_number='$bank_reference_number'");
            $transaction_count = towfetch($transaction_check);
            error_log("DIAGNOSTIC - transaction_details count: " . ($transaction_count['count'] ?? 'NULL'));
            
            try {
                // Update user's loan count and credit score
                error_log("STEP 1: Updating user table for user_id={$loan_details['uid']}");
                $a['user'] = towquery("UPDATE `user` SET `status`='cleared', `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point WHERE id=".$loan_details['uid']);
                if (!$a['user']) throw new Exception("Failed to update user table");
                error_log("STEP 1 SUCCESS: User table updated");

                // Update loan status to 'cleared'
                error_log("STEP 2: Updating loan table for loan_id={$loan_details['id']}");
                $a['loan'] = towquery("UPDATE `loan` SET `action`='cleared', `status_log`='cleared', `cleard_date`='".date('Y-m-d')."' WHERE id=".$loan_details['id']);
                if (!$a['loan']) throw new Exception("Failed to update loan table");
                error_log("STEP 2 SUCCESS: Loan table updated");

                // Update loan application status
                error_log("STEP 3: Updating loan_apply table for loan_id={$loan_details['lid']}");
                $a['loan_apply'] = towquery("UPDATE `loan_apply` SET `status`='cleared' WHERE id=".$loan_details['lid']);
                if (!$a['loan_apply']) throw new Exception("Failed to update loan_apply table");
                error_log("STEP 3 SUCCESS: Loan_apply table updated");

                // Insert transaction details
                error_log("STEP 4: Inserting transaction_details for loan_id={$loan_details['lid']}");
                $a['transaction_details'] = towquery("INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES (".$loan_details['uid'].",'".$loan_details['lid']."','$bank_reference_number','".date('Y-m-d H:i:s')."','$amount','full')");
                if (!$a['transaction_details']) throw new Exception("Failed to insert transaction_details");
                error_log("STEP 4 SUCCESS: Transaction_details inserted");

                // Update pg_transaction with success status and other details
                error_log("STEP 5: Updating pg_transaction for txnid=$txnid");
                $pg_update = towquery("UPDATE `pg_transaction` SET `status`='success', `amount`='$amount', `payment_method`='$payment_method', `bank_reference_number`='$bank_reference_number' WHERE txnid='$txnid'");
                if (!$pg_update) throw new Exception("Failed to update pg_transaction");
                error_log("STEP 5 SUCCESS: pg_transaction updated");

                // Commit transaction
                error_log("COMMITTING TRANSACTION for txnid=$txnid");
                mysqli_commit($db);
                error_log("TRANSACTION COMMITTED SUCCESSFULLY for txnid=$txnid");
                
                // Send No Due certificate email
                require_once __DIR__ . '/../lib/zxc_mail.php';
                file_get_contents(creditlab_zxc_mail_url('https://creditlab.in', $user_details['email'], null, null, 'https://creditlab.in/no-due-certificate2.php?id=' . $loan_details['lid']));

                $template_id='1107165683325768963';
                $mobile = $user_details['mobile'];
                $message = "Dear {$user_details['name']}, we acknowledge the repayment of your loan CLL{$loan_details['lid']} & it's cleared. You can apply again. https://creditlab.in/ -Creditlab";
                define('CREDITLAB_SMS_INCLUDE', true);
                include '../send_sms.php';
                
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($db);
                error_log("Transaction failed for txnid $txnid: " . $e->getMessage());
                echo '<html><body>';
                echo '<h3>Payment Processing Error!</h3>';
                echo '<p>There was an error processing your payment. Please contact support.</p>';
                echo '<script>';
                echo 'setTimeout(function() { window.location.href = "/user/"; }, 3000);';
                echo '</script>';
                echo '</body></html>';
                exit;
            } finally {
                // Re-enable autocommit
                mysqli_autocommit($db, true);
            }
    
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
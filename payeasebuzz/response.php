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
    if ($bank_reference_number === '' || $bank_reference_number === 'NA') {
        $bank_reference_number = $result['data']['easepayid'] ?? ('RESPONSE_' . $txnid);
    }
    
    // Check if payment was successful
    if (isset($result['status']) && $result['status'] == 1 && isset($result['data']['status']) && $result['data']['status'] === "success") {

        require_once __DIR__ . '/response_settle.php';
        $txnidEsc = towreal($txnid);
        $needsSettle = false;
        $pgRow = null;
        $pgQ = towquery("SELECT p.*, loan.status_log AS loan_status_log FROM pg_transaction p INNER JOIN loan ON loan.id = p.loan_id WHERE p.txnid='$txnidEsc' LIMIT 1");
        if ($pgQ && townum($pgQ) > 0) {
            $pgRow = towfetch($pgQ);
            $needsSettle = ($pgRow['loan_status_log'] ?? '') !== 'cleared';
        } else {
            $needsSettle = true;
        }

        if ($needsSettle) {
            $settle = creditlab_payeasebuzz_handle_success($txnid, (float) $amount, (string) $bank_reference_number, (string) $payment_method);
            if (!$settle['ok'] && ($settle['message'] ?? '') !== 'Not found') {
                echo '<html><body><h3>Payment Processing Error!</h3><p>Please contact support.</p>';
                echo '<script>setTimeout(function() { window.location.href = "/user/"; }, 3000);</script></body></html>';
                exit;
            }
            echo '<html><body>';
            echo '<h3>Payment Successful!</h3>';
            echo '<p>Your payment has been successfully processed. You will be redirected shortly.</p>';
            echo '<script>setTimeout(function() { window.location.href = "/user/"; }, 2000);</script>';
            echo '</body></html>';
        } else {
            echo '<html><body>';
            echo '<h3>Payment Successful!</h3>';
            echo '<p>Your loan is already cleared. You will be redirected shortly.</p>';
            echo '<script>setTimeout(function() { window.location.href = "/user/"; }, 2000);</script>';
            echo '</body></html>';
        }
    } else {
        // Payment Failed: Handle accordingly
        echo "Payment Failed: ";
        echo isset($result['data']['error_Message']) ? $result['data']['error_Message'] : "Unknown error.";
        
        require_once __DIR__ . '/../lib/pg_link_settlement.php';
        creditlab_pg_mark_tx_failure($db, $txnid);
    }
} else {
    echo "No response received!";
}
?>

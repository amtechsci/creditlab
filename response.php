<?php
// Include the Easebuzz library
include_once('payeasebuzz/easebuzz-lib/easebuzz_payment_gateway.php');

// Set your Easebuzz credentials
$SALT = "G151INEFT";  // Replace with your actual salt

$easebuzzObj = new Easebuzz($MERCHANT_KEY = null, $SALT, $ENV = null);

// Get the response from Easebuzz
$response = $easebuzzObj->easebuzzResponse($_POST);

if (!empty($_POST)) {
    $status = $_POST['status'] ?? 'failed';
    $txnid = $_POST['txnid'] ?? 'N/A';
    $customer_authentication_id = $_POST['customer_authentication_id'] ?? null;

    if ($status == 'success' && $customer_authentication_id) {
        // Save this `customer_authentication_id` for future auto debit requests
        echo "Auto Debit Authorization Successful!<br>";
        echo "Transaction ID: " . $txnid . "<br>";
        echo "Customer Authentication ID: " . $customer_authentication_id . "<br>";

        // Save this ID for later use (e.g., in a database)
        // Example: saveCustomerAuthId($txnid, $customer_authentication_id);
    } else {
        // Authorization failed or was canceled
        echo "Authorization Failed: " . ($_POST['error_Message'] ?? 'Unknown error') . "<br>";
    }
} else {
    echo "No response data received.";
}
?>

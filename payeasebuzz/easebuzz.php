<?php
// Include Easebuzz library
include_once('easebuzz-lib/easebuzz_payment_gateway.php');

if (!empty($_POST) && sizeof($_POST) > 0) {
    // Get the API name
    $apiname = trim(htmlentities($_GET['api_name'], ENT_QUOTES));

    // Merchant Credentials
    $MERCHANT_KEY = "9BIB9D914T"; // Replace with your Merchant Key
    $SALT = "GGW1QF6ONH";         // Replace with your Salt Key
    $ENV = "prod";                // Use "prod" for production

    // Initialize Easebuzz Object
    $easebuzzObj = new Easebuzz($MERCHANT_KEY, $SALT, $ENV);

    // Call Initiate Payment API
    if ($apiname === "initiate_payment") {
        // Process the payment
        $result = $easebuzzObj->initiatePaymentAPI($_POST);
        easebuzzAPIResponse($result);
    } else {
        echo "<h1>Invalid API Name</h1>";
    }
} else {
    echo "<h1>Please fill all mandatory fields.</h1>";
}

// Handle API Response
function easebuzzAPIResponse($data)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";

    // Log or save response for further processing
}
?>

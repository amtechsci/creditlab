<?php
// Include Easebuzz library
include_once('easebuzz-lib/easebuzz_payment_gateway.php');

if (!empty($_POST) && sizeof($_POST) > 0) {
    // Get the API name
    $apiname = trim(htmlentities($_GET['api_name'], ENT_QUOTES));

    require_once __DIR__ . '/../config/easebuzz.php';
    $MERCHANT_KEY = EASEBUZZ_MERCHANT_KEY;
    $SALT = EASEBUZZ_SALT;
    $ENV = EASEBUZZ_ENV;

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

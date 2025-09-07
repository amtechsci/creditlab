<?php
// Include the Easebuzz library
include_once('payeasebuzz/easebuzz-lib/easebuzz_payment_gateway.php');

// Set your Easebuzz credentials
$MERCHANT_KEY = "53LFWVJQH";  // Replace with your actual merchant key
$SALT = "G151INEFT";                  // Replace with your actual salt
$ENV = "test";                        // Set to "test" for testing or "prod" for production

// Create an instance of the Easebuzz class
$easebuzzObj = new Easebuzz($MERCHANT_KEY, $SALT, $ENV);

// Prepare the data for the payment
$postData = array (
  "txnid" => "T3SAT0B56G",          // Unique transaction ID
  "amount" => "100.00",              // Payment amount
  "firstname" => "John",            // Customer's first name
  "email" => "john.doe@example.com",// Customer's email
  "phone" => "9876543210",          // Customer's phone number
  "productinfo" => "Laptop",        // Product description
  "surl" => "https://creditlab.in/response.php", // Success URL
  "furl" => "https://creditlab.in/response.php", // Failure URL
  "udf1" => "",                     // Optional user-defined field 1
  "udf2" => "",                     // Optional user-defined field 2
  "udf3" => "",                     // Optional user-defined field 3
  "udf4" => "",                     // Optional user-defined field 4
  "udf5" => "",                     // Optional user-defined field 5
  "address1" => "123 Street",       // Customer's address line 1
  "address2" => "Apt 456",          // Customer's address line 2
  "city" => "CityName",             // Customer's city
  "state" => "StateName",           // Customer's state
  "country" => "CountryName",       // Customer's country
  "zipcode" => "123456"             // Customer's zip code
);

// Call the initiatePaymentAPI function
$result = $easebuzzObj->initiatePaymentAPI($postData);

// Redirect or handle the result based on the response
if ($result->status === 1) {
    // If status is 1, redirect to the payment page
    header('Location: ' . $result->data);
    exit();
} else {
    // If there's an error, print the error message
    echo 'Error: ' . $result->error_desc;
}
?>

<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

$MERCHANT_KEY = "53LFWVJQH";
$SALT = "G151INEFT";
$ENV = "test";

// Helper function to generate the hash
function generateHash($data, $salt) {
    $hashSequence = $data['key'] . '|' . $data['txnid'] . '|' . $data['amount'] . '|' . $data['productinfo'] . '|' . $data['firstname'] . '|' . $data['email'] . '|' . $data['udf1'] . '|' . $data['udf2'] . '|' . $data['udf3'] . '|' . $data['udf4'] . '|' . $data['udf5'] . '|' . $data['udf6'] . '|' . $data['udf7'] . '|' . $data['udf8'] . '|' . $data['udf9'] . '|' . $data['udf10'] . '|' . $salt;
    return hash('sha512', $hashSequence);
}

// Helper function to send cURL requests with detailed logging
function sendCurlRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);  // Include headers in the output
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);  // Track request headers

    $response = curl_exec($ch);
    $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT); // The request header

    // Separate headers and body
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    curl_close($ch);

    return [
        'headers' => $header,
        'body' => json_decode($body, true),
        'sent_headers' => $headerSent
    ];
}

// Step 1: Auto Debit API - Authorization
$uid = uniqid();
$cai = uniqid();
$authData = array(
    "key" => $MERCHANT_KEY,
    "txnid" => $uid, // Unique transaction ID
    "amount" => "1.0", // Amount to be debited for mandate registration
    "productinfo" => "Loan Payment", // Product description
    "firstname" => "John", // Customer's first name
    "phone" => "9876543210", // Customer's phone number
    "email" => "john.doe@example.com", // Customer's email
    "surl" => "https://creditlab.in/zpp.php", // Success URL
    "furl" => "https://creditlab.in/zpp.php", // Failure URL
    "udf1" => "", "udf2" => "", "udf3" => "", "udf4" => "", "udf5" => "500.0", // Max debit amount per request
    "udf6" => "", "udf7" => "", "udf8" => "", "udf9" => "", "udf10" => "",
    "request_flow" => "SEAMLESS", // Mandatory for seamless flow
    "customer_authentication_id" => $cai, // Use a unique identifier
    "final_collection_date" => date('d/m/Y', strtotime('+1 year')),
);

$authData['hash'] = generateHash($authData, $SALT);
$authUrl = "https://testpay.easebuzz.in/payment/initiateLink";

$authResponse = sendCurlRequest($authUrl, $authData);
echo "<h3>Auto Debit Authorization Response:</h3><pre>";
print_r($authResponse);
echo "</pre>";

if ($authResponse['body']['status'] == 1) {
    $access_key = $authResponse['body']['data']; // Store this for future use
    // print_r($access_key);exit;
    // Step 2: Generate and auto-submit the form for ENACH - Authorization
    echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <meta http-equiv='X-UA-Compatible' content='ie=edge'>
            <style>
                input, button {
                    width: 35%;
                    padding: 5px;
                    margin: 5px;
                }
            </style>
        </head>
        <body>
            <form id='seamless_auto_submit_upi_form' method='POST' action='https://testpay.easebuzz.in/initiate_seamless_payment/'>
                <input type='hidden' name='access_key' value='".$access_key."'></input><br>
                <input type='hidden' name='payment_mode' value='EN'></input><br>
                <input type='hidden' name='ifsc' value='EBZS0001987'></input><br>
                <input type='hidden' name='account_type' value='SAVINGS'></input><br>
                <input type='hidden' name='account_no' value='198765412358'></input><br>
                <input type='hidden' name='auth_mode' value='NetBanking'></input><br>
                <input type='hidden' name='bank_code' value='HDFCB'></input><br>
            </form>
            <script type='text/javascript'>
                document.getElementById('seamless_auto_submit_upi_form').submit();
            </script>
        </body>
        </html>
    ";

} else {
    die('Error in Auto Debit Authorization: ' . $authResponse['body']['error_desc']);
}

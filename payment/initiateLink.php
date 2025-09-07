<?php
// Auto Debit Authorization API Implementation

// API credentials
$key = '9BIB9D914T';
$salt = 'GGW1QF6ONH';

// API Endpoint
$url = 'https://pay.easebuzz.in/payment/initiateLink';

// Required Parameters
$params = [
    'key' => $key,
    'txnid' => uniqid('txn_'),
    'amount' => 1.0,
    'productinfo' => 'Test Product2',
    'firstname' => 'prnay',
    'email' => 'pranay@gmail.com',
    'phone' => '8328350247',
    'surl' => 'https://creditlab.in/payment/cb.php',
    'furl' => 'https://creditlab.in/payment/cb.php',
    'udf1' => '',
    'udf2' => '',
    'udf3' => '',
    'udf4' => '',
    'udf5' => '100.0',
    'udf6' => '',
    'udf7' => '',
    'customer_authentication_id' => 'xxxx',
    // 'show_payment_mode' => 'UPI',
    'request_flow' => 'SEAMLESS',
];

// Generate hash
$hash_sequence = implode('|', [
    $params['key'],
    $params['txnid'],
    $params['amount'],
    $params['productinfo'],
    $params['firstname'],
    $params['email'],
    $params['udf1'], $params['udf2'], $params['udf3'], $params['udf4'],
    $params['udf5'], $params['udf6'], $params['udf7'], '', '', '', // udf8-udf10 as empty
    $salt,
]);
$params['hash'] = strtolower(hash('sha512', $hash_sequence));

// Debug: Log the hash and parameters
error_log("Generated Hash: {$params['hash']}");
error_log("Parameters: " . print_r($params, true));

// Make the POST request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// Handle response
if ($error) {
    die("cURL Error: $error");
}

$response_data = json_decode($response, true);

if (!$response_data || $response_data['status'] === 0) {
    die("API Error: " . print_r($response_data, true));
}

echo "API Success: " . print_r($response_data, true);
?>

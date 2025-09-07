<?php
// UPI 2.0 - Authorization API Implementation

// API Endpoint
$url = 'https://pay.easebuzz.in/initiate_seamless_payment/';

// Required Parameters
$params = [
    'access_key' => 'f1bc7574fb52440da046b37b7a0ce309009b89daa72568a4b92d980bca839904', // Use the access key from the previous API response
    'payment_mode' => 'UPIAD', // UPI Auto Debit
    'upi_va' => '8800899875@kotak', // Replace with the Virtual Payment Address (VPA)
    'request_mode' => 'SUVA', // Choose request mode (e.g., seamless_vpa or SUVA)
];

// Debug: Log the parameters being sent
error_log("Parameters Sent to API: " . print_r($params, true));

// Make the POST request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// Handle response
if ($error) {
    error_log("cURL Error: $error");
    die("cURL Error: $error");
}

$response_data = json_decode($response, true);

// Log the raw response
error_log("API Response: $response");

// Parse the response
if (!$response_data || !$response_data['status']) {
    die("API Error: " . print_r($response_data, true));
}

echo "API Success: " . print_r($response_data, true);
?>
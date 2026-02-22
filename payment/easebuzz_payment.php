<?php
// UPI 2.0 - Authorization API Implementation
// NPCI: From 28 Feb 2026, UPI Collect requires is_ios when using upi_va (Seamless/Merchant Hosted).
// To pass is_ios from a form: <input type="hidden" name="is_ios" id="is_ios">
// Set via JS before submit: document.getElementById('is_ios').value = /iPhone|iPad|iPod/i.test(navigator.userAgent);

/**
 * Detect device type from User-Agent. Used when is_ios is not passed from client.
 * @return array ['is_ios' => bool, 'is_android' => bool, 'is_desktop' => bool]
 */
function easebuzz_detect_device() {
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $is_ios = (bool) preg_match('/iPhone|iPad|iPod/i', $ua);
    $is_android = (bool) preg_match('/Android/i', $ua);
    $is_desktop = !$is_ios && !$is_android;
    return ['is_ios' => $is_ios, 'is_android' => $is_android, 'is_desktop' => $is_desktop];
}

// API Endpoint
$url = 'https://pay.easebuzz.in/initiate_seamless_payment/';

// Required Parameters - use $_REQUEST if provided, else defaults
$params = [
    'access_key' => isset($_REQUEST['access_key']) ? trim($_REQUEST['access_key']) : 'f1bc7574fb52440da046b37b7a0ce309009b89daa72568a4b92d980bca839904',
    'payment_mode' => isset($_REQUEST['payment_mode']) ? trim($_REQUEST['payment_mode']) : 'UPIAD',
    'upi_va' => isset($_REQUEST['upi_va']) ? trim($_REQUEST['upi_va']) : '',
    'request_mode' => isset($_REQUEST['request_mode']) ? trim($_REQUEST['request_mode']) : 'SUVA',
];

// NPCI/Easebuzz: When payment mode is UPI (upi_va present), pass is_ios (required from 28 Feb 2026).
// Prefer value from request (set by your app after device detection); fallback to server-side User-Agent.
if (!empty($params['upi_va'])) {
    $device = easebuzz_detect_device();
    // Per NPCI: Do not allow UPI Collect on desktop.
    if ($device['is_desktop']) {
        header('Content-Type: application/json');
        http_response_code(400);
        die(json_encode(['status' => 0, 'error' => 'UPI Collect is not allowed on desktop devices. Please use a mobile device.']));
    }
    if (isset($_REQUEST['is_ios'])) {
        $params['is_ios'] = filter_var($_REQUEST['is_ios'], FILTER_VALIDATE_BOOLEAN);
    } else {
        $params['is_ios'] = $device['is_ios'];
    }
}

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
<?php
/**
 * Create Easebuzz payment link (initiate payment API).
 */
require_once __DIR__ . '/../config/easebuzz.php';
require_once __DIR__ . '/app_url.php';
require_once __DIR__ . '/../payeasebuzz/easebuzz-lib/easebuzz_payment_gateway.php';

function creditlab_easebuzz_initiate_link(array $params): array
{
    $merchantKey = EASEBUZZ_MERCHANT_KEY;
    $salt = EASEBUZZ_SALT;
    $env = EASEBUZZ_ENV;

    if ($merchantKey === '' || $salt === '') {
        return ['ok' => false, 'error' => 'Easebuzz credentials not configured (EASEBUZZ_MERCHANT_KEY / EASEBUZZ_SALT).'];
    }

    $base = creditlab_get_base_url();
    $postData = [
        'txnid' => $params['txnid'],
        'amount' => number_format((float) $params['amount'], 2, '.', ''),
        'firstname' => $params['firstname'],
        'email' => $params['email'],
        'phone' => $params['phone'],
        'productinfo' => $params['productinfo'] ?? 'Loan Payment',
        'surl' => $base . '/payeasebuzz/response.php',
        'furl' => $base . '/payeasebuzz/response.php',
        'udf1' => (string) ($params['udf1'] ?? ''),
        'udf2' => (string) ($params['udf2'] ?? ''),
        'udf3' => (string) ($params['udf3'] ?? ''),
        'udf4' => (string) ($params['udf4'] ?? ''),
        'udf5' => (string) ($params['udf5'] ?? ''),
    ];

    $easebuzzObj = new Easebuzz($merchantKey, $salt, $env);
    $raw = $easebuzzObj->initiatePaymentAPI($postData, false);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $obj = json_decode($raw);
        if (is_object($obj) && isset($obj->status)) {
            $decoded = json_decode(json_encode($obj), true);
        }
    }

    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'Invalid response from payment gateway.'];
    }

    $status = (int) ($decoded['status'] ?? 0);
    if ($status !== 1) {
        $err = $decoded['error_desc'] ?? $decoded['data'] ?? 'Payment initiation failed';
        return ['ok' => false, 'error' => is_string($err) ? $err : json_encode($err)];
    }

    $url = $decoded['data'] ?? '';
    if ($url === '' && !empty($decoded['access_key'])) {
        $envLower = strtolower($env);
        $host = ($envLower === 'test') ? 'https://testpay.easebuzz.in/' : 'https://pay.easebuzz.in/';
        $url = $host . 'pay/' . $decoded['access_key'];
    }

    if ($url === '') {
        return ['ok' => false, 'error' => 'Payment URL not returned by gateway.'];
    }

    return ['ok' => true, 'payment_url' => $url, 'raw' => $decoded];
}

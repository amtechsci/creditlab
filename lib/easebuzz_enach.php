<?php

require_once __DIR__ . '/../config/easebuzz.php';
require_once __DIR__ . '/app_url.php';

function creditlab_easebuzz_enach_log_path() {
    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    return $log_dir . '/easebuzz_enach_' . date('Y-m-d') . '.log';
}

function creditlab_easebuzz_enach_log($title, array $payload = []) {
    $log_file = creditlab_easebuzz_enach_log_path();
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $title . PHP_EOL;
    if (!empty($payload)) {
        $entry .= json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    $entry .= str_repeat('-', 80) . PHP_EOL;
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
    error_log('Easebuzz ENACH: ' . $title);
    return basename($log_file);
}

function creditlab_easebuzz_normalize_phone($phone) {
    $phone = preg_replace('/\D+/', '', (string) $phone);
    if (strlen($phone) > 10) {
        $phone = substr($phone, -10);
    }
    return $phone;
}

/**
 * Max e-NACH mandate amount (Autocollect access-key `amount`, amount_rule MAX).
 *
 * Default: max(60% of salary, loan_limit), floor ₹10,000.
 * Prod test override: set EASEBUZZ_ENACH_TEST_MOBILES + EASEBUZZ_ENACH_TEST_MAX_AMOUNT in .env.
 */
function creditlab_easebuzz_max_debit_amount($salary, $loan_limit, $phone = '') {
    require_once __DIR__ . '/env.php';

    $phone = creditlab_easebuzz_normalize_phone($phone);
    $test_max = trim(env('EASEBUZZ_ENACH_TEST_MAX_AMOUNT', ''));
    $test_phones_raw = trim(env('EASEBUZZ_ENACH_TEST_MOBILES', ''));

    if ($phone !== '' && $test_max !== '' && $test_phones_raw !== '') {
        $test_phones = array_filter(array_map(function ($entry) {
            $normalized = creditlab_easebuzz_normalize_phone($entry);
            return $normalized !== '' ? $normalized : null;
        }, explode(',', $test_phones_raw)));

        if (in_array($phone, $test_phones, true)) {
            return max(1, (int) round((float) $test_max));
        }
    }

    $amount = round((float) $salary * 0.6);
    if ((float) $loan_limit > $amount) {
        $amount = (int) round((float) $loan_limit);
    }
    if ($amount < 10000) {
        $amount = 10000;
    }
    return $amount;
}

/** @deprecated Legacy PG 5-char codes — use creditlab_autocollect_resolve_bank_code() for Autocollect. */
function creditlab_resolve_easebuzz_bank_code($ifsc, $db_code) {
    require_once __DIR__ . '/easebuzz_autocollect.php';
    return creditlab_autocollect_resolve_bank_code($ifsc, $db_code);
}

function creditlab_resolve_easebuzz_account_type($ac_type) {
    $normalized = strtolower(trim((string)$ac_type));
    if (strpos($normalized, 'curr') !== false) {
        return 'CURRENT';
    }
    return 'SAVINGS';
}

function creditlab_is_valid_ifsc($ifsc) {
    return (bool)preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper(trim((string)$ifsc)));
}

function creditlab_easebuzz_clean_field($value) {
    return trim(strip_tags((string)$value));
}

/**
 * Start customer e-NACH via Easebuzz Autocollect (replaces legacy initiateLink flow).
 *
 * @return array{ok:bool, html?:string, error?:string, transaction_id?:string, log_file?:string}
 */
function creditlab_start_easebuzz_enach($user_id, array $post, array $context = []) {
    require_once __DIR__ . '/easebuzz_autocollect.php';
    return creditlab_autocollect_start_user_enach($user_id, $post, $context);
}

/**
 * Easebuzz Payment Gateway base URL (legacy initiateLink / initiateDirectDebitRequest).
 */
function creditlab_easebuzz_pg_base_url()
{
    return strtolower((string) EASEBUZZ_ENV) === 'test'
        ? 'https://testpay.easebuzz.in'
        : 'https://pay.easebuzz.in';
}

/**
 * True for Autocollect-registered mandates (new cai… signups or AUTOCOLLECT request_flow).
 *
 * Legacy PG rows may also have customer_authentication_id starting with cai… but txnid is en… — not Autocollect.
 */
function creditlab_easebuzz_is_autocollect_mandate_row(array $row)
{
    $flow = strtoupper(trim((string) ($row['request_flow'] ?? '')));
    if ($flow !== '' && strpos($flow, 'AUTOCOLLECT') === 0) {
        return true;
    }

    $customer_auth_id = trim((string) ($row['customer_authentication_id'] ?? ''));
    $txnid = trim((string) ($row['txnid'] ?? ''));
    if ($customer_auth_id !== '' && preg_match('/^cai/i', $customer_auth_id)
        && $txnid !== '' && strcasecmp($customer_auth_id, $txnid) === 0) {
        return true;
    }

    return (bool) preg_match('/^clac/i', $customer_auth_id);
}

function creditlab_easebuzz_mandate_authorization_ok(array $row)
{
    $auth = strtolower(trim((string) ($row['authorization_status'] ?? '')));

    return in_array($auth, ['authorized', 'accepted'], true);
}

/**
 * Autocollect presentment transaction_id — always customer_authentication_id when set (new cai… or migrated legacy).
 */
function creditlab_easebuzz_autocollect_transaction_id(array $row)
{
    $customer_auth_id = trim((string) ($row['customer_authentication_id'] ?? ''));
    if ($customer_auth_id !== '') {
        return $customer_auth_id;
    }

    return trim((string) ($row['txnid'] ?? ''));
}

/**
 * When true, pre-Autocollect mandates use legacy PG initiateDirectDebitRequest (rollback only).
 */
function creditlab_easebuzz_legacy_presentment_pg_enabled()
{
    return env_bool('EASEBUZZ_LEGACY_PRESENTMENT_PG', false);
}

/**
 * Presentment API to use for an easebuzz_adtd row.
 *
 * Default: Autocollect POST /v1/mandate/presentment/ with customer_authentication_id as transaction_id
 * (new cai… signups and Easebuzz-migrated legacy mandates). Legacy PG only if EASEBUZZ_LEGACY_PRESENTMENT_PG=1.
 *
 * @return 'autocollect'|'legacy_pg'
 */
function creditlab_easebuzz_presentment_api_for_row(array $row)
{
    if (creditlab_easebuzz_is_autocollect_mandate_row($row)) {
        return 'autocollect';
    }

    if (creditlab_easebuzz_legacy_presentment_pg_enabled()) {
        return 'legacy_pg';
    }

    if (creditlab_easebuzz_mandate_authorization_ok($row)
        && creditlab_easebuzz_autocollect_transaction_id($row) !== '') {
        return 'autocollect';
    }

    return 'legacy_pg';
}

/**
 * Legacy PG eNACH debit — POST initiateDirectDebitRequest (pre-Autocollect mandates).
 *
 * @return array{ok:bool, status?:int, error_desc?:string, data?:array, raw?:string, api?:string}
 */
function creditlab_easebuzz_legacy_initiate_direct_debit(array $params)
{
    $key = (string) EASEBUZZ_MERCHANT_KEY;
    $salt = (string) EASEBUZZ_SALT;
    if ($key === '' || $salt === '') {
        return ['ok' => false, 'error_desc' => 'Easebuzz credentials are not configured.', 'api' => 'legacy_pg'];
    }

    $txnid = trim((string) ($params['txnid'] ?? ''));
    if ($txnid === '') {
        $txnid = 'txn_' . str_replace('.', '', uniqid('', true));
    }

    $amount = creditlab_easebuzz_clean_field($params['amount'] ?? '0');
    $productinfo = creditlab_easebuzz_clean_field($params['productinfo'] ?? 'Loan Repayment');
    $firstname = creditlab_easebuzz_clean_field($params['firstname'] ?? '');
    $email = creditlab_easebuzz_clean_field($params['email'] ?? '');
    $phone = creditlab_easebuzz_normalize_phone($params['phone'] ?? '');
    $customer_authentication_id = trim((string) ($params['customer_authentication_id'] ?? ''));
    $merchant_debit_id = trim((string) ($params['merchant_debit_id'] ?? ''));
    if ($merchant_debit_id === '') {
        $merchant_debit_id = 'CLDR_' . str_replace('.', '', uniqid('', true));
    }
    $auto_debit_access_key = trim((string) ($params['auto_debit_access_key'] ?? ''));
    $sub_merchant_id = trim((string) ($params['sub_merchant_id'] ?? ''));

    if ($customer_authentication_id === '') {
        return ['ok' => false, 'error_desc' => 'Missing customer_authentication_id.', 'api' => 'legacy_pg'];
    }

    $base = creditlab_get_base_url();
    $surl = trim((string) ($params['surl'] ?? ($base . '/payment/cb_auto.php')));
    $furl = trim((string) ($params['furl'] ?? ($base . '/payment/cb_auto.php')));

    $udf = [];
    for ($i = 1; $i <= 10; $i++) {
        $udf[$i] = (string) ($params['udf' . $i] ?? '');
    }

    $hash_string = $key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|'
        . $udf[1] . '|' . $udf[2] . '|' . $udf[3] . '|' . $udf[4] . '|' . $udf[5] . '|'
        . $udf[6] . '|' . $udf[7] . '|' . $udf[8] . '|' . $udf[9] . '|' . $udf[10] . '|' . $salt;
    $hash = hash('sha512', $hash_string);

    $postData = [
        'key' => $key,
        'txnid' => $txnid,
        'hash' => $hash,
        'amount' => $amount,
        'productinfo' => $productinfo,
        'firstname' => $firstname,
        'email' => $email,
        'phone' => $phone,
        'surl' => $surl,
        'furl' => $furl,
        'customer_authentication_id' => $customer_authentication_id,
        'merchant_debit_id' => $merchant_debit_id,
        'auto_debit_access_key' => $auto_debit_access_key,
        'sub_merchant_id' => $sub_merchant_id,
    ];
    for ($i = 1; $i <= 10; $i++) {
        $postData['udf' . $i] = $udf[$i];
    }

    $url = rtrim(creditlab_easebuzz_pg_base_url(), '/') . '/payment/initiateDirectDebitRequest/';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    $raw = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    creditlab_easebuzz_enach_log('LEGACY PG DIRECT DEBIT', [
        'url' => $url,
        'customer_authentication_id' => $customer_authentication_id,
        'merchant_debit_id' => $merchant_debit_id,
        'amount' => $amount,
        'curl_error' => $curl_error !== '' ? $curl_error : null,
        'response' => $raw,
    ]);

    if ($curl_error !== '') {
        return ['ok' => false, 'error_desc' => 'cURL error: ' . $curl_error, 'api' => 'legacy_pg', 'raw' => (string) $raw];
    }

    $decoded = json_decode((string) $raw, true);
    $status = is_array($decoded) && isset($decoded['status']) ? (int) $decoded['status'] : 0;
    $error_desc = is_array($decoded) ? (string) ($decoded['error_desc'] ?? $decoded['error'] ?? '') : 'Invalid API response';

    return [
        'ok' => $status === 1,
        'status' => $status,
        'error_desc' => $status === 1 ? '' : ($error_desc !== '' ? $error_desc : 'Legacy PG presentment failed.'),
        'data' => is_array($decoded) ? $decoded : null,
        'raw' => (string) $raw,
        'merchant_debit_id' => $merchant_debit_id,
        'api' => 'legacy_pg',
    ];
}

/**
 * Route loan eNACH presentment to Autocollect or legacy PG based on easebuzz_adtd row.
 *
 * @return array{ok:bool, api:string, error_desc?:string, data?:array, merchant_request_number?:string, merchant_debit_id?:string}
 */
function creditlab_easebuzz_initiate_loan_debit(array $easebuzz_row, array $payment_details)
{
    require_once __DIR__ . '/easebuzz_autocollect.php';

    $api = creditlab_easebuzz_presentment_api_for_row($easebuzz_row);
    $customer_auth_id = trim((string) ($easebuzz_row['customer_authentication_id'] ?? ''));
    $amount = (string) ($payment_details['amount'] ?? '0');
    $merchant_ref = trim((string) ($payment_details['merchant_debit_id'] ?? $payment_details['merchant_request_number'] ?? ''));

    if ($api === 'autocollect') {
        $transaction_id = creditlab_easebuzz_autocollect_transaction_id($easebuzz_row);
        $result = creditlab_autocollect_initiate_loan_debit($transaction_id, $amount, $merchant_ref, [
            'udf1' => $payment_details['udf1'] ?? 'CREDITLAB_ENACH',
        ]);

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $error = $result['error'] ?? '';
        if (!$result['ok'] && is_array($data) && !empty($data['message'])) {
            $error = (string) $data['message'];
        }

        $autocollect_result = [
            'ok' => !empty($result['ok']),
            'api' => 'autocollect',
            'error_desc' => !empty($result['ok']) ? '' : ($error !== '' ? $error : 'Autocollect presentment failed.'),
            'data' => $data,
            'merchant_request_number' => $result['merchant_request_number'] ?? $merchant_ref,
            'transaction_id' => $transaction_id,
        ];

        creditlab_easebuzz_log_user_event([
            'uid' => (int) ($easebuzz_row['uid'] ?? 0),
            'mobile' => (string) ($payment_details['phone'] ?? ''),
            'transaction_id' => $transaction_id,
            'stage' => 'presentment',
            'outcome' => !empty($autocollect_result['ok']) ? 'success' : 'failure',
            'api' => 'autocollect',
            'amount' => $amount,
            'message' => !empty($autocollect_result['ok']) ? 'Autocollect presentment initiated.' : ($autocollect_result['error_desc'] ?: 'Autocollect presentment failed.'),
            'meta' => ['merchant_request_number' => $autocollect_result['merchant_request_number'] ?? $merchant_ref],
        ]);

        return $autocollect_result;
    }

    $legacy = creditlab_easebuzz_legacy_initiate_direct_debit([
        'amount' => $amount,
        'productinfo' => $payment_details['productinfo'] ?? 'Loan Repayment',
        'firstname' => $payment_details['firstname'] ?? '',
        'email' => $payment_details['email'] ?? '',
        'phone' => $payment_details['phone'] ?? '',
        'customer_authentication_id' => $customer_auth_id,
        'merchant_debit_id' => $merchant_ref,
        'auto_debit_access_key' => trim((string) ($easebuzz_row['auto_debit_access_key'] ?? '')),
        'sub_merchant_id' => trim((string) ($payment_details['sub_merchant_id'] ?? '')),
        'udf1' => $payment_details['udf1'] ?? 'CREDITLAB_ENACH',
    ]);

    $legacy_result = [
        'ok' => !empty($legacy['ok']),
        'api' => 'legacy_pg',
        'error_desc' => $legacy['error_desc'] ?? '',
        'data' => $legacy['data'] ?? null,
        'merchant_debit_id' => $legacy['merchant_debit_id'] ?? $merchant_ref,
        'transaction_id' => $customer_auth_id,
    ];

    creditlab_easebuzz_log_user_event([
        'uid' => (int) ($easebuzz_row['uid'] ?? 0),
        'mobile' => (string) ($payment_details['phone'] ?? ''),
        'transaction_id' => $customer_auth_id,
        'stage' => 'presentment',
        'outcome' => !empty($legacy_result['ok']) ? 'success' : 'failure',
        'api' => 'legacy_pg',
        'amount' => $amount,
        'message' => !empty($legacy_result['ok']) ? 'Legacy PG presentment initiated.' : ($legacy_result['error_desc'] ?: 'Legacy PG presentment failed.'),
        'meta' => ['merchant_debit_id' => $legacy_result['merchant_debit_id'] ?? $merchant_ref],
    ]);

    return $legacy_result;
}

/**
 * Initiates eNACH presentment and returns JSON ({status:1|0, error_desc, api, ...}).
 * Used by zzenach, auto_enach cron, and manual_enach batch.
 */
function creditlab_easebuzz_initiate_direct_debit_json(array $postParams, array $easebuzz_row = [])
{
    if (!$easebuzz_row) {
        $easebuzz_row = [
            'customer_authentication_id' => trim((string) ($postParams['customer_authentication_id'] ?? '')),
            'auto_debit_access_key' => trim((string) ($postParams['auto_debit_access_key'] ?? '')),
            'request_flow' => trim((string) ($postParams['request_flow'] ?? '')),
            'txnid' => trim((string) ($postParams['txnid'] ?? '')),
        ];
    }

    $result = creditlab_easebuzz_initiate_loan_debit($easebuzz_row, $postParams);

    return json_encode([
        'status' => !empty($result['ok']) ? 1 : 0,
        'error_desc' => $result['error_desc'] ?? '',
        'data' => $result['data'] ?? null,
        'api' => $result['api'] ?? '',
        'merchant_request_number' => $result['merchant_request_number'] ?? ($result['merchant_debit_id'] ?? ''),
        'transaction_id' => $result['transaction_id'] ?? ($easebuzz_row['customer_authentication_id'] ?? ''),
    ]);
}

require_once __DIR__ . '/easebuzz_enach_user_log.php';

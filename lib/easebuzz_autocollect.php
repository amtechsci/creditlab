<?php
/**
 * Easebuzz Autocollect (Recurring Payment) API client.
 * Parallel to the legacy PG auto-debit helpers in easebuzz_enach.php — do not mix the two.
 */

require_once __DIR__ . '/../config/easebuzz.php';
require_once __DIR__ . '/app_url.php';

/** UAT credentials — used only when CREDITLAB_AUTOCOLLECT_FORCE_UAT is set (playground/log pages). */
define('CREDITLAB_AUTOCOLLECT_UAT_MERCHANT_KEY', '53LFWVJQH');
define('CREDITLAB_AUTOCOLLECT_UAT_SALT', 'G151INEFT');

function creditlab_autocollect_is_uat()
{
    return defined('CREDITLAB_AUTOCOLLECT_FORCE_UAT') && CREDITLAB_AUTOCOLLECT_FORCE_UAT;
}

function creditlab_autocollect_env_label()
{
    if (creditlab_autocollect_is_uat()) {
        return 'uat';
    }
    if (creditlab_autocollect_is_sandbox()) {
        return 'test';
    }
    return 'prod';
}

function creditlab_autocollect_merchant_key()
{
    if (creditlab_autocollect_is_uat()) {
        return CREDITLAB_AUTOCOLLECT_UAT_MERCHANT_KEY;
    }
    return (string) EASEBUZZ_MERCHANT_KEY;
}

function creditlab_autocollect_salt()
{
    if (creditlab_autocollect_is_uat()) {
        return CREDITLAB_AUTOCOLLECT_UAT_SALT;
    }
    return (string) EASEBUZZ_SALT;
}

function creditlab_autocollect_log_dir()
{
    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    return $log_dir;
}

function creditlab_autocollect_log_path()
{
    return creditlab_autocollect_log_dir() . '/easebuzz_autocollect_' . date('Y-m-d') . '.log';
}

function creditlab_autocollect_web_log_path()
{
    return creditlab_autocollect_log_dir() . '/autocollect_api_web.log';
}

function creditlab_autocollect_web_log($title, array $payload = [])
{
    $entry = [
        'ts' => date('Y-m-d H:i:s'),
        'env' => creditlab_autocollect_env_label(),
        'title' => (string) $title,
        'payload' => $payload,
    ];
    file_put_contents(
        creditlab_autocollect_web_log_path(),
        json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
        FILE_APPEND | LOCK_EX
    );
    creditlab_autocollect_log($title, $payload);
}

/**
 * @return array<int, array{ts:string,env:string,title:string,payload:array}>
 */
function creditlab_autocollect_read_web_logs($limit = 100)
{
    $path = creditlab_autocollect_web_log_path();
    if (!is_readable($path)) {
        return [];
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }
    $entries = [];
    foreach (array_slice($lines, -1 * max(1, (int) $limit)) as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            $entries[] = $decoded;
        }
    }
    return array_reverse($entries);
}

function creditlab_autocollect_clear_web_logs()
{
    file_put_contents(creditlab_autocollect_web_log_path(), '');
    return true;
}

function creditlab_autocollect_render_web_logs_html($limit = 50)
{
    $entries = creditlab_autocollect_read_web_logs($limit);
    if (!$entries) {
        return '<p class="hint">No API log entries yet.</p>';
    }
    $html = '';
    foreach ($entries as $entry) {
        $title = htmlspecialchars((string) ($entry['title'] ?? ''), ENT_QUOTES);
        $ts = htmlspecialchars((string) ($entry['ts'] ?? ''), ENT_QUOTES);
        $env = htmlspecialchars((string) ($entry['env'] ?? ''), ENT_QUOTES);
        $json = htmlspecialchars(
            json_encode($entry['payload'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ENT_QUOTES
        );
        $html .= '<div class="log-entry">'
            . '<div class="log-meta"><strong>' . $title . '</strong>'
            . ' · <code>' . $ts . '</code> · env <code>' . $env . '</code></div>'
            . '<pre class="log-body">' . $json . '</pre></div>';
    }
    return $html;
}

function creditlab_autocollect_log($title, array $payload = [])
{
    $log_file = creditlab_autocollect_log_path();
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $title . PHP_EOL;
    if (!empty($payload)) {
        $entry .= json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    $entry .= str_repeat('-', 80) . PHP_EOL;
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
    return basename($log_file);
}

function creditlab_autocollect_is_sandbox()
{
    if (creditlab_autocollect_is_uat()) {
        return true;
    }
    return strtolower((string) EASEBUZZ_ENV) === 'test';
}

function creditlab_autocollect_base_url()
{
    return creditlab_autocollect_is_sandbox()
        ? 'https://sandboxapi.easebuzz.in/autocollect'
        : 'https://api.easebuzz.in/autocollect';
}

function creditlab_autocollect_checkout_base_url()
{
    return creditlab_autocollect_is_sandbox()
        ? 'https://testpay.easebuzz.in'
        : 'https://pay.easebuzz.in';
}

function creditlab_autocollect_checkout_url($access_key)
{
    return rtrim(creditlab_autocollect_checkout_base_url(), '/') . '/pay/' . ltrim((string) $access_key, '/');
}

/**
 * Easebuzz sandbox eNACH test account fields (for SEAMLESS API payload tests only).
 * Note: bank_code EBZS is rejected by NPCI on mandate register — use DEFAULT checkout on sandbox.
 *
 * @return array<string, string>|null
 */
function creditlab_autocollect_sandbox_bank_defaults()
{
    if (!creditlab_autocollect_is_sandbox()) {
        return null;
    }
    return [
        'account_holder_name' => 'Sandbox Testing',
        'account_number' => '282800002828',
        'account_type' => 'savings',
        'ifsc' => 'EBZS0001987',
        'bank_code' => 'EBZS',
        'auth_mode' => 'netbanking',
    ];
}

function creditlab_autocollect_apply_sandbox_bank_defaults(array $fields)
{
    $defaults = creditlab_autocollect_sandbox_bank_defaults();
    if (!$defaults) {
        return $fields;
    }
    foreach ($defaults as $key => $value) {
        if (trim((string) ($fields[$key] ?? '')) === '') {
            $fields[$key] = $value;
        }
    }
    return $fields;
}

function creditlab_autocollect_mandate_api_url()
{
    return rtrim(creditlab_autocollect_base_url(), '/') . '/v1/mandate';
}

/**
 * SHA-512 of pipe-joined parts (Autocollect Authorization header).
 */
function creditlab_autocollect_hash(array $parts)
{
    return hash('sha512', implode('|', $parts));
}

/**
 * AES-256-CBC encrypt for seamless mandate fields.
 * Matches Easebuzz docs (Python sample): SHA-256 hex digest, first 32/16 chars as key/IV bytes.
 */
function creditlab_autocollect_aes_encrypt($plain)
{
    $plain = (string) $plain;
    $key = substr(hash('sha256', creditlab_autocollect_merchant_key()), 0, 32);
    $iv = substr(hash('sha256', creditlab_autocollect_salt()), 0, 16);
    $encrypted = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false) {
        return '';
    }
    return base64_encode($encrypted);
}

function creditlab_autocollect_format_amount($amount)
{
    return number_format((float) $amount, 2, '.', '');
}

function creditlab_autocollect_credentials_ok()
{
    return creditlab_autocollect_merchant_key() !== '' && creditlab_autocollect_salt() !== '';
}

/**
 * Low-level HTTP helper. Returns array with success, http_code, data, raw, error, request meta.
 *
 * @param string $method GET|POST
 * @param string $path Path under base URL (e.g. /v1/access-key/generate/)
 * @param array $headers Extra headers (Authorization, etc.)
 * @param mixed $body null | array (JSON) | string (raw body)
 * @param string $content_type application/json | application/x-www-form-urlencoded
 */
function creditlab_autocollect_request($method, $path, array $headers = [], $body = null, $content_type = 'application/json')
{
    $url = rtrim(creditlab_autocollect_base_url(), '/') . '/' . ltrim($path, '/');
    $method = strtoupper($method);

    $default_headers = [
        'Accept: application/json',
        'X-EB-MERCHANT-KEY: ' . creditlab_autocollect_merchant_key(),
    ];
    foreach ($headers as $name => $value) {
        if (is_int($name)) {
            $default_headers[] = $value;
        } else {
            $default_headers[] = $name . ': ' . $value;
        }
    }

    $payload = null;
    if ($body !== null) {
        if ($content_type === 'application/x-www-form-urlencoded' && is_array($body)) {
            $payload = http_build_query($body);
            $default_headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } elseif (is_array($body)) {
            $payload = json_encode($body);
            $default_headers[] = 'Content-Type: application/json';
        } else {
            $payload = (string) $body;
            $default_headers[] = 'Content-Type: ' . $content_type;
        }
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $default_headers);
    if ($method !== 'GET' && $payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = null;
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
    }

    $result = [
        'ok' => ($errno === 0 && $http_code >= 200 && $http_code < 300),
        'http_code' => $http_code,
        'data' => is_array($decoded) ? $decoded : null,
        'raw' => $raw,
        'error' => $errno ? $curl_error : null,
        'meta' => [
            'url' => $url,
            'method' => $method,
            'curl_errno' => $errno,
        ],
    ];

    creditlab_autocollect_web_log('HTTP ' . $method . ' ' . $path, [
        'http_code' => $http_code,
        'url' => $url,
        'method' => $method,
        'request_headers' => [
            'X-EB-MERCHANT-KEY' => creditlab_autocollect_merchant_key(),
            'Authorization' => isset($headers['Authorization']) ? substr((string) $headers['Authorization'], 0, 16) . '…' : null,
        ],
        'request_body' => is_array($body) ? $body : $payload,
        'response' => is_array($decoded) ? $decoded : $raw,
        'curl_error' => $result['error'],
    ]);

    return $result;
}

/**
 * POST /v1/access-key/generate/
 *
 * @param array $params transaction_id, amount, email, phone, success_url, failure_url, start_date, end_date, ...
 */
function creditlab_autocollect_generate_access_key(array $params)
{
    if (!creditlab_autocollect_credentials_ok()) {
        return ['ok' => false, 'error' => 'Easebuzz credentials are not configured.', 'data' => null, 'raw' => null];
    }

    $key = creditlab_autocollect_merchant_key();
    $transaction_id = trim((string) ($params['transaction_id'] ?? ''));
    if ($transaction_id === '') {
        $transaction_id = 'CLAC_' . str_replace('.', '', uniqid('', true));
    }

    $amount = creditlab_autocollect_format_amount($params['amount'] ?? 1);
    $email = trim((string) ($params['email'] ?? ''));
    $phone = preg_replace('/\D+/', '', (string) ($params['phone'] ?? ''));
    if (strlen($phone) > 10) {
        $phone = substr($phone, -10);
    }

    $base = creditlab_get_base_url();
    $success_url = trim((string) ($params['success_url'] ?? ($base . '/payment/autocollect_playground.php?cb=success')));
    $failure_url = trim((string) ($params['failure_url'] ?? ($base . '/payment/autocollect_playground.php?cb=failure')));

    $start_date = trim((string) ($params['start_date'] ?? date('Y-m-d')));
    $end_date = trim((string) ($params['end_date'] ?? date('Y-m-d', strtotime('+3 years'))));

    $body = [
        'key' => $key,
        'transaction_id' => $transaction_id,
        'success_url' => $success_url,
        'failure_url' => $failure_url,
        'request_type' => $params['request_type'] ?? 'DEFAULT',
        'amount' => (float) $amount,
        'email' => $email,
        'phone' => $phone,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'frequency' => $params['frequency'] ?? 'as_presented',
        'amount_rule' => $params['amount_rule'] ?? 'MAX',
        'payment_modes' => $params['payment_modes'] ?? ['EN'],
        'udf1' => (string) ($params['udf1'] ?? 'AUTOCOLLECT_PLAYGROUND'),
        'udf2' => (string) ($params['udf2'] ?? ''),
        'udf3' => (string) ($params['udf3'] ?? ''),
        'udf4' => (string) ($params['udf4'] ?? ''),
        'udf5' => (string) ($params['udf5'] ?? ''),
        'udf6' => (string) ($params['udf6'] ?? ''),
        'udf7' => (string) ($params['udf7'] ?? ''),
    ];

    if (isset($params['upfront_presentment_amount']) && $params['upfront_presentment_amount'] !== '') {
        $body['upfront_presentment_amount'] = (float) $params['upfront_presentment_amount'];
    }
    if (!empty($params['submerchant_id'])) {
        $body['submerchant_id'] = $params['submerchant_id'];
    }

    $auth = creditlab_autocollect_hash([$key, $amount, $transaction_id, creditlab_autocollect_salt()]);

    $response = creditlab_autocollect_request(
        'POST',
        '/v1/access-key/generate/',
        ['Authorization' => $auth],
        $body
    );

    $access_key = null;
    if (is_array($response['data']) && !empty($response['data']['access_key'])) {
        $access_key = $response['data']['access_key'];
    }

    return [
        'ok' => $response['ok'] && $access_key !== null,
        'http_code' => $response['http_code'],
        'data' => $response['data'],
        'raw' => $response['raw'],
        'error' => $response['error'],
        'transaction_id' => $transaction_id,
        'access_key' => $access_key,
        'checkout_url' => $access_key ? creditlab_autocollect_checkout_url($access_key) : null,
        'request_body' => $body,
        'authorization_hash' => $auth,
    ];
}

function creditlab_autocollect_mandate_redirect_url($access_key)
{
    return creditlab_autocollect_checkout_url($access_key);
}

/**
 * Autocollect ENACH bank_code — exactly 4 uppercase letters (IFSC prefix).
 * Legacy PG eNACH uses 5-char codes (e.g. HDFCB); Autocollect rejects those (ADVA00001).
 */
function creditlab_autocollect_resolve_bank_code($ifsc, $bank_code = '')
{
    $ifsc = strtoupper(trim((string) $ifsc));
    $prefix = strlen($ifsc) >= 4 ? substr($ifsc, 0, 4) : '';

    $bank_code = strtoupper(trim((string) $bank_code));
    if ($bank_code !== '' && preg_match('/^[A-Z]{4}$/', $bank_code)) {
        return $bank_code;
    }
    if ($bank_code !== '' && preg_match('/^[A-Z]{5}$/', $bank_code) && preg_match('/^[A-Z]{4}$/', $prefix)) {
        return $prefix;
    }
    if (preg_match('/^[A-Z]{4}$/', $prefix)) {
        return $prefix;
    }
    return '';
}

/**
 * Build auto-submit HTML form for seamless eNACH mandate creation (POST /v1/mandate).
 *
 * @param string $access_key
 * @param array $fields account_holder_name, account_number, account_type, ifsc, bank_code, auth_mode
 */
function creditlab_autocollect_build_seamless_mandate_form($access_key, array $fields)
{
    $key = creditlab_autocollect_merchant_key();
    $account_number = (string) ($fields['account_number'] ?? '');
    $account_holder_name = (string) ($fields['account_holder_name'] ?? '');
    $account_type = strtolower(trim((string) ($fields['account_type'] ?? 'savings')));
    if (strpos($account_type, 'curr') !== false) {
        $account_type = 'current';
    } else {
        $account_type = 'savings';
    }
    $ifsc = strtoupper(trim((string) ($fields['ifsc'] ?? '')));
    $bank_code = creditlab_autocollect_resolve_bank_code(
        $ifsc,
        (string) ($fields['bank_code'] ?? '')
    );
    $auth_mode = strtolower(trim((string) ($fields['auth_mode'] ?? 'netbanking')));

    $enc_account_number = creditlab_autocollect_aes_encrypt($account_number);
    $enc_account_holder = creditlab_autocollect_aes_encrypt($account_holder_name);
    $enc_account_type = creditlab_autocollect_aes_encrypt($account_type);
    $enc_upi_handle = ''; // ENACH — empty

    // Docs: UPI & ENACH Authorization = SHA-512(key|enc_account_number|ifsc|enc_upi_handle|salt)
    $authorization = creditlab_autocollect_hash([
        $key,
        $enc_account_number,
        $ifsc,
        $enc_upi_handle,
        creditlab_autocollect_salt(),
    ]);

    $form_fields = [
        'key' => $key,
        'access_key' => $access_key,
        'mandate_type' => 'ENACH',
        'auth_mode' => $auth_mode,
        'Authorization' => $authorization,
        'account_holder_name' => $enc_account_holder,
        'account_number' => $enc_account_number,
        'account_type' => $enc_account_type,
        'ifsc' => $ifsc,
        'bank_code' => $bank_code,
    ];

    $action = htmlspecialchars(creditlab_autocollect_mandate_api_url(), ENT_QUOTES);
    $inputs = '';
    foreach ($form_fields as $name => $value) {
        $inputs .= '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES) . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '">' . "\n";
    }

    creditlab_autocollect_web_log('SEAMLESS MANDATE FORM POST', [
        'method' => 'POST',
        'url' => creditlab_autocollect_mandate_api_url(),
        'access_key' => $access_key,
        'auth_mode' => $auth_mode,
        'bank_code' => $bank_code,
        'ifsc' => $ifsc,
        'account_type' => $account_type,
        'hash_pattern' => 'key|enc_account_number|ifsc|enc_upi_handle|salt',
        'authorization_hash_prefix' => substr($authorization, 0, 16) . '…',
        'form_fields' => array_keys($form_fields),
    ]);

    return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Redirecting to Easebuzz Autocollect</title></head><body>'
        . '<p>Redirecting to Easebuzz for eNACH mandate registration...</p>'
        . '<form id="autocollect_mandate_form" method="POST" action="' . $action . '">'
        . $inputs
        . '</form><script>document.getElementById("autocollect_mandate_form").submit();</script></body></html>';
}

/**
 * GET /v1/mandate/{transaction_id}
 */
function creditlab_autocollect_retrieve_mandate($transaction_id)
{
    if (!creditlab_autocollect_credentials_ok()) {
        return ['ok' => false, 'error' => 'Easebuzz credentials are not configured.', 'data' => null];
    }

    $transaction_id = trim((string) $transaction_id);
    $key = creditlab_autocollect_merchant_key();
    $auth = creditlab_autocollect_hash([$key, $transaction_id, creditlab_autocollect_salt()]);
    $path = '/v1/mandate/' . rawurlencode($transaction_id) . '?key=' . rawurlencode($key);

    $response = creditlab_autocollect_request('GET', $path, ['Authorization' => $auth]);

    return [
        'ok' => $response['ok'],
        'http_code' => $response['http_code'],
        'data' => $response['data'],
        'raw' => $response['raw'],
        'error' => $response['error'],
        'authorization_hash' => $auth,
    ];
}

/**
 * POST /v1/mandate/presentment/ — eNACH debit / presentment (not for UPI/SI).
 *
 * @param array $params transaction_id, amount, merchant_request_number, presentment_date (optional)
 */
function creditlab_autocollect_initiate_enach_debit(array $params)
{
    if (!creditlab_autocollect_credentials_ok()) {
        return ['ok' => false, 'error' => 'Easebuzz credentials are not configured.', 'data' => null];
    }

    $key = creditlab_autocollect_merchant_key();
    $transaction_id = trim((string) ($params['transaction_id'] ?? ''));
    $amount = creditlab_autocollect_format_amount($params['amount'] ?? 0);
    $merchant_request_number = trim((string) ($params['merchant_request_number'] ?? ''));
    if ($merchant_request_number === '') {
        $merchant_request_number = 'CLDR_' . str_replace('.', '', uniqid('', true));
    }

    $body = [
        'key' => $key,
        'transaction_id' => $transaction_id,
        'amount' => (float) $amount,
        'merchant_request_number' => $merchant_request_number,
    ];

    if (!empty($params['presentment_date'])) {
        $body['presentment_date'] = trim((string) $params['presentment_date']);
    }
    for ($i = 1; $i <= 7; $i++) {
        $udf = 'udf' . $i;
        if (isset($params[$udf]) && $params[$udf] !== '') {
            $body[$udf] = (string) $params[$udf];
        }
    }

    $auth = creditlab_autocollect_hash([
        $key,
        $transaction_id,
        $merchant_request_number,
        $amount,
        creditlab_autocollect_salt(),
    ]);

    $response = creditlab_autocollect_request(
        'POST',
        '/v1/mandate/presentment/',
        ['Authorization' => $auth],
        $body
    );

    return [
        'ok' => $response['ok'],
        'http_code' => $response['http_code'],
        'data' => $response['data'],
        'raw' => $response['raw'],
        'error' => $response['error'],
        'merchant_request_number' => $merchant_request_number,
        'request_body' => $body,
        'authorization_hash' => $auth,
    ];
}

/**
 * Whether the Autocollect playground page may be used.
 *
 * Sandbox (EASEBUZZ_ENV=test): always allowed.
 * Production: set EASEBUZZ_AUTOCOLLECT_PLAYGROUND=1 in .env (server-side gate only).
 *
 * This helper is only loaded by payment/autocollect_playground.php — never wire it
 * into user/easebuzz.php, zzenach, or easebuzz_webhook (legacy flow stays separate).
 */
function creditlab_autocollect_playground_allowed()
{
    if (creditlab_autocollect_is_sandbox()) {
        return true;
    }
    return (string) EASEBUZZ_AUTOCOLLECT_PLAYGROUND === '1';
}

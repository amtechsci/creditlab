<?php
/**
 * Easebuzz Autocollect (Recurring Payment) API client.
 * Parallel to the legacy PG auto-debit helpers in easebuzz_enach.php — do not mix the two.
 */

require_once __DIR__ . '/../config/easebuzz.php';
require_once __DIR__ . '/app_url.php';
require_once __DIR__ . '/easebuzz_enach_user_log.php';

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
    $channel = '';
    if (defined('CREDITLAB_AUTOCOLLECT_WEB_LOG_CHANNEL')) {
        $channel = preg_replace('/[^a-z0-9_]/', '', strtolower((string) CREDITLAB_AUTOCOLLECT_WEB_LOG_CHANNEL));
    }
    $suffix = $channel !== '' ? '_' . $channel : '';

    return creditlab_autocollect_log_dir() . '/autocollect_api_web' . $suffix . '.log';
}

function creditlab_autocollect_mask_secret($value, $visible = 4)
{
    $value = (string) $value;
    if ($value === '') {
        return '—';
    }
    if (strlen($value) <= $visible) {
        return str_repeat('*', strlen($value));
    }

    return substr($value, 0, $visible) . '…';
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

/** @see https://docs.easebuzz.in/docs/8-autocollect-recurring-payment/426w7nkqcqoai-sandbox-testing-credentials */
function creditlab_autocollect_sandbox_docs_url()
{
    return 'https://docs.easebuzz.in/docs/8-autocollect-recurring-payment/426w7nkqcqoai-sandbox-testing-credentials';
}

/**
 * Official Autocollect sandbox eNACH test accounts (Easebuzz docs §A).
 *
 * @return array<string, array{label:string,account_holder_name:string,account_number:string,ifsc:string,presentment:string}>
 */
function creditlab_autocollect_sandbox_enach_accounts()
{
    return [
        'success_1' => [
            'label' => 'Success presentment — 282800002828',
            'account_holder_name' => 'Sandbox Testing',
            'account_number' => '282800002828',
            'ifsc' => 'EBZS0001987',
            'presentment' => 'success',
        ],
        'success_2' => [
            'label' => 'Success presentment — 454545454545',
            'account_holder_name' => 'Sandbox Testing',
            'account_number' => '454545454545',
            'ifsc' => 'EBZS0001987',
            'presentment' => 'success',
        ],
        'fail_presentment' => [
            'label' => 'Fail presentment — 198765412358',
            'account_holder_name' => 'Sandbox Testing',
            'account_number' => '198765412358',
            'ifsc' => 'EBZS0001987',
            'presentment' => 'fail',
        ],
    ];
}

/**
 * Default sandbox bank fields (first success account from Easebuzz docs).
 *
 * @return array<string, string>|null
 */
function creditlab_autocollect_sandbox_bank_defaults()
{
    if (!creditlab_autocollect_is_sandbox()) {
        return null;
    }
    $accounts = creditlab_autocollect_sandbox_enach_accounts();
    $first = reset($accounts);
    if (!$first) {
        return null;
    }
    return [
        'account_holder_name' => $first['account_holder_name'],
        'account_number' => $first['account_number'],
        'account_type' => 'savings',
        'ifsc' => $first['ifsc'],
        'bank_code' => 'HDFC',
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
 * Stored in easebuzz_adtd.request_flow (column is VARCHAR(15) on prod — do not use longer values).
 */
function creditlab_autocollect_request_flow_label()
{
    return 'AUTOCOLLECT';
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
 * Legacy PG eNACH uses 5-char codes (e.g. HDFCB, SBOI); Autocollect/NPCI expects IFSC prefix (e.g. SBIN).
 */
function creditlab_autocollect_resolve_bank_code($ifsc, $bank_code = '')
{
    $ifsc = strtoupper(trim((string) $ifsc));
    $prefix = strlen($ifsc) >= 4 ? substr($ifsc, 0, 4) : '';

    // Easebuzz sandbox test IFSC (EBZS…) — NPCI bank_code is HDFC (matches DEFAULT checkout mapping).
    if (creditlab_autocollect_is_sandbox() && $prefix === 'EBZS') {
        return 'HDFC';
    }

    // Valid IFSC → always use first 4 chars (SBIN0020488 → SBIN, not legacy PG code SBOI).
    if (preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
        return $prefix;
    }

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
    $auth_mode = creditlab_autocollect_normalize_auth_mode($fields['auth_mode'] ?? 'netbanking');

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
 * Normalize mandate object from GET /v1/mandate/{transaction_id} response.
 */
function creditlab_autocollect_parse_mandate_retrieve_data($retrieve)
{
    if (!is_array($retrieve['data'] ?? null)) {
        return [];
    }
    $root = $retrieve['data'];
    if (is_array($root['data'] ?? null)) {
        $inner = $root['data'];
        if (isset($inner['status']) || isset($inner['sub_status']) || isset($inner['umrn'])) {
            return $inner;
        }
    }
    if (isset($root['status']) || isset($root['sub_status']) || isset($root['umrn'])) {
        return $root;
    }

    return [];
}

/**
 * True when NPCI/Easebuzz has accepted the e-NACH registration (success SMS / UMRN issued).
 * Prod often returns status=initiated, sub_status=accepted — not status=authorized immediately.
 */
function creditlab_autocollect_mandate_registration_succeeded($status, $sub_status, $umrn = '')
{
    $status = strtolower(trim((string) $status));
    $sub_status = strtolower(trim((string) $sub_status));
    $umrn = trim((string) $umrn);

    if ($status === 'failed' || $sub_status === 'failed') {
        return false;
    }
    if ($status === 'authorized' || $sub_status === 'authorized') {
        return true;
    }
    if ($sub_status === 'accepted' && $umrn !== '') {
        return true;
    }
    if ($status === 'initiated' && $sub_status === 'accepted' && $umrn !== '') {
        return true;
    }

    return false;
}

/**
 * Mandate statuses that will not change without a new registration attempt.
 */
function creditlab_autocollect_mandate_status_is_terminal($status, $sub_status = '', $umrn = '')
{
    $status = strtolower(trim((string) $status));
    $sub_status = strtolower(trim((string) $sub_status));

    if (creditlab_autocollect_mandate_registration_succeeded($status, $sub_status, $umrn)) {
        return true;
    }

    return in_array($status, ['authorized', 'failed'], true)
        || in_array($sub_status, ['authorized', 'failed'], true);
}

/**
 * Poll retrieve until mandate reaches a useful state (authorized/failed/in_process) or attempts exhaust.
 *
 * @return array{retrieve:array, attempts:int, status:string, sub_status:string, umrn:string}
 */
function creditlab_autocollect_poll_mandate_retrieve($transaction_id, $max_attempts = 5, $delay_seconds = 2)
{
    $max_attempts = max(1, (int) $max_attempts);
    $delay_seconds = max(0, (int) $delay_seconds);
    $retrieve = ['ok' => false, 'data' => null];
    $status = '';
    $sub_status = '';
    $umrn = '';
    $attempt = 0;

    for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
        $retrieve = creditlab_autocollect_retrieve_mandate($transaction_id);
        $data = creditlab_autocollect_parse_mandate_retrieve_data($retrieve);
        $status = strtolower((string) ($data['status'] ?? ''));
        $sub_status = strtolower((string) ($data['sub_status'] ?? ''));
        $umrn = trim((string) ($data['umrn'] ?? ''));

        if (creditlab_autocollect_mandate_status_is_terminal($status, $sub_status, $umrn)) {
            break;
        }
        if ($status === 'in_process' || $sub_status === 'in_process') {
            break;
        }
        if ($attempt < $max_attempts && $delay_seconds > 0) {
            sleep($delay_seconds);
        }
    }

    return [
        'retrieve' => $retrieve,
        'attempts' => $attempt,
        'status' => $status,
        'sub_status' => $sub_status,
        'umrn' => $umrn,
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
 * Initiate eNACH presentment (Autocollect) for loan auto-debit.
 * transaction_id = easebuzz_adtd.customer_authentication_id (new + migrated mandates).
 *
 * @return array{ok:bool, data?:array, error?:string, merchant_request_number?:string}
 */
function creditlab_autocollect_initiate_loan_debit($transaction_id, $amount, $merchant_request_number, array $extra = [])
{
    $result = creditlab_autocollect_initiate_enach_debit([
        'transaction_id' => trim((string) $transaction_id),
        'amount' => $amount,
        'merchant_request_number' => $merchant_request_number,
        'presentment_date' => $extra['presentment_date'] ?? '',
        'udf1' => $extra['udf1'] ?? 'CREDITLAB_ZZENACH',
    ]);

    creditlab_autocollect_log('LOAN ENACH PRESENTMENT', [
        'transaction_id' => $transaction_id,
        'amount' => $amount,
        'merchant_request_number' => $result['merchant_request_number'] ?? $merchant_request_number,
        'http_code' => $result['http_code'] ?? null,
        'ok' => $result['ok'] ?? false,
    ]);

    return $result;
}

/**
 * Whether the Autocollect playground page may be used.
 *
 * Sandbox (EASEBUZZ_ENV=test): always allowed.
 * Production: set EASEBUZZ_AUTOCOLLECT_PLAYGROUND=1 in .env (server-side gate only).
 *
 * Playground gate only — customer flow uses user/easebuzz.php + zzenach.php directly.
 */
function creditlab_autocollect_playground_allowed()
{
    if (creditlab_autocollect_is_sandbox()) {
        return true;
    }
    return (string) EASEBUZZ_AUTOCOLLECT_PLAYGROUND === '1';
}

/**
 * Live Autocollect playground (prod API + .env merchant key/salt).
 * Requires EASEBUZZ_AUTOCOLLECT_PLAYGROUND=1 and EASEBUZZ_ENV=prod (not sandbox/UAT forced).
 */
function creditlab_autocollect_playground_prod_allowed()
{
    if (creditlab_autocollect_is_uat()) {
        return false;
    }
    if (creditlab_autocollect_is_sandbox()) {
        return false;
    }

    return creditlab_autocollect_playground_allowed();
}

/**
 * Autocollect mandate auth_mode values (POST /v1/mandate).
 * API expects: netbanking | debitcard | aadhaar (not legacy NetBanking / debit_card).
 */
function creditlab_autocollect_normalize_auth_mode($auth_mode)
{
    $normalized = preg_replace('/[^a-z]/', '', strtolower((string) $auth_mode));
    if ($normalized === 'debitcard') {
        return 'debitcard';
    }
    if ($normalized === 'aadhaar') {
        return 'aadhaar';
    }

    return 'netbanking';
}

/** @deprecated Use creditlab_autocollect_normalize_auth_mode() */
function creditlab_autocollect_map_auth_mode($auth_mode)
{
    return creditlab_autocollect_normalize_auth_mode($auth_mode);
}

/**
 * Customer e-NACH signup via Autocollect (generate access key + SEAMLESS mandate).
 * Stores transaction_id in easebuzz_adtd.customer_authentication_id for retrieve/presentment.
 *
 * @return array{ok:bool, html?:string, error?:string, transaction_id?:string, log_file?:string}
 */
function creditlab_autocollect_start_user_enach($user_id, array $post, array $context = [])
{
    global $db;

    require_once __DIR__ . '/easebuzz_enach.php';

    $log_context = [
        'uid' => (int) $user_id,
        'rcid' => isset($context['rcid']) ? (string) $context['rcid'] : '',
        'mobile' => isset($context['mobile']) ? (string) $context['mobile'] : '',
        'env' => creditlab_autocollect_env_label(),
        'flow' => 'user_enach',
    ];

    creditlab_autocollect_log('USER ENACH START', array_merge($log_context, ['post' => $post]));

    $required_fields = ['firstname', 'phone', 'email', 'account_no', 'account_type', 'ifsc', 'bank_code'];
    foreach ($required_fields as $field) {
        if (!isset($post[$field]) || trim((string) $post[$field]) === '') {
            $error = 'Missing required field: ' . $field;
            creditlab_autocollect_log('USER ENACH VALIDATION FAILED', array_merge($log_context, ['error' => $error]));
            creditlab_easebuzz_log_user_event([
                'uid' => (int) $user_id,
                'mobile' => (string) ($log_context['mobile'] ?? ''),
                'stage' => 'mandate_start',
                'outcome' => 'failure',
                'api' => 'autocollect',
                'message' => $error,
            ]);

            return ['ok' => false, 'error' => $error, 'log_file' => creditlab_autocollect_log_path()];
        }
    }

    $firstname = creditlab_easebuzz_clean_field($post['firstname']);
    $phone = preg_replace('/\D+/', '', creditlab_easebuzz_clean_field($post['phone']));
    if (strlen($phone) > 10) {
        $phone = substr($phone, -10);
    }
    $email = creditlab_easebuzz_clean_field($post['email']);
    $bank_code = creditlab_easebuzz_clean_field($post['bank_code']);
    $account_no = preg_replace('/\D+/', '', creditlab_easebuzz_clean_field($post['account_no']));
    $auth_mode = creditlab_autocollect_normalize_auth_mode($post['auth_mode'] ?? 'netbanking');
    $account_type = creditlab_easebuzz_clean_field($post['account_type']);
    $ifsc = strtoupper(trim(creditlab_easebuzz_clean_field($post['ifsc'])));
    $account_name = creditlab_easebuzz_clean_field($post['account_name'] ?? $firstname);

    if (strlen($phone) !== 10) {
        creditlab_easebuzz_log_user_event([
            'uid' => (int) $user_id, 'mobile' => $phone, 'stage' => 'mandate_start', 'outcome' => 'failure',
            'api' => 'autocollect', 'message' => 'Invalid mobile number on profile.',
        ]);
        return ['ok' => false, 'error' => 'Invalid mobile number on profile. Please contact support.', 'log_file' => creditlab_autocollect_log_path()];
    }
    if ($account_no === '') {
        creditlab_easebuzz_log_user_event([
            'uid' => (int) $user_id, 'mobile' => $phone, 'stage' => 'mandate_start', 'outcome' => 'failure',
            'api' => 'autocollect', 'message' => 'Invalid bank account number.',
        ]);
        return ['ok' => false, 'error' => 'Invalid bank account number. Please contact support.', 'log_file' => creditlab_autocollect_log_path()];
    }
    if (!creditlab_is_valid_ifsc($ifsc)) {
        creditlab_easebuzz_log_user_event([
            'uid' => (int) $user_id, 'mobile' => $phone, 'stage' => 'mandate_start', 'outcome' => 'failure',
            'api' => 'autocollect', 'auth_mode' => $auth_mode, 'message' => 'Invalid IFSC code.',
        ]);
        return ['ok' => false, 'error' => 'Invalid IFSC code. Please ask support to update your bank IFSC.', 'log_file' => creditlab_autocollect_log_path()];
    }

    $bank_code = creditlab_autocollect_resolve_bank_code($ifsc, $bank_code);
    if ($bank_code === '') {
        creditlab_easebuzz_log_user_event([
            'uid' => (int) $user_id, 'mobile' => $phone, 'stage' => 'mandate_start', 'outcome' => 'failure',
            'api' => 'autocollect', 'auth_mode' => $auth_mode, 'message' => 'Unable to resolve Easebuzz bank code.',
        ]);
        return ['ok' => false, 'error' => 'Unable to resolve Easebuzz bank code for this IFSC.', 'log_file' => creditlab_autocollect_log_path()];
    }

    if (!creditlab_autocollect_credentials_ok()) {
        creditlab_easebuzz_log_user_event([
            'uid' => (int) $user_id, 'mobile' => $phone, 'stage' => 'mandate_start', 'outcome' => 'failure',
            'api' => 'autocollect', 'message' => 'Easebuzz credentials not configured.',
        ]);
        return ['ok' => false, 'error' => 'Easebuzz credentials are not configured on the server.', 'log_file' => creditlab_autocollect_log_path()];
    }

    $salary = isset($context['salary']) ? (float) $context['salary'] : 0;
    $loan_limit = isset($context['loan_limit']) ? (float) $context['loan_limit'] : 0;
    $max_amount = creditlab_easebuzz_max_debit_amount(
        $salary,
        $loan_limit,
        isset($context['mobile']) ? (string) $context['mobile'] : ($phone ?? '')
    );

    // Autocollect transaction_id — also stored as customer_authentication_id for presentment/retrieve.
    $transaction_id = str_replace('.', '', uniqid('cai', true));
    $log_context['transaction_id'] = $transaction_id;

    $callback_base = creditlab_get_base_url();
    $txn_q = rawurlencode($transaction_id);
    $success_url = $callback_base . '/user/autocollect_callback.php?cb=success&transaction_id=' . $txn_q;
    $failure_url = $callback_base . '/user/autocollect_callback.php?cb=failure&transaction_id=' . $txn_q;

    $result = creditlab_autocollect_generate_access_key([
        'transaction_id' => $transaction_id,
        'amount' => $max_amount,
        'email' => $email,
        'phone' => $phone,
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+3 years')),
        'success_url' => $success_url,
        'failure_url' => $failure_url,
        'request_type' => 'SEAMLESS',
        'udf1' => 'CREDITLAB_USER',
        'udf5' => (string) $max_amount,
    ]);

    creditlab_autocollect_log('USER ENACH generate access key', array_merge($log_context, [
        'http_code' => $result['http_code'] ?? null,
        'ok' => $result['ok'] ?? false,
        'error' => $result['error'] ?? null,
    ]));

    if (empty($result['access_key'])) {
        $err = $result['error'] ?? 'Easebuzz rejected the e-NACH request.';
        if (is_array($result['data']) && !empty($result['data']['message'])) {
            $err = (string) $result['data']['message'];
        }
        $err_msg = is_string($err) ? $err : 'Easebuzz rejected the e-NACH request.';
        creditlab_easebuzz_log_user_event([
            'uid' => (int) $user_id,
            'mobile' => $phone,
            'transaction_id' => $transaction_id,
            'stage' => 'mandate_start',
            'outcome' => 'failure',
            'api' => 'autocollect',
            'auth_mode' => $auth_mode,
            'amount' => $max_amount,
            'message' => $err_msg,
            'meta' => ['http_code' => $result['http_code'] ?? null],
        ]);

        return [
            'ok' => false,
            'error' => $err_msg,
            'transaction_id' => $transaction_id,
            'log_file' => creditlab_autocollect_log_path(),
        ];
    }

    $access_key = $result['access_key'];
    $account_type_db = creditlab_resolve_easebuzz_account_type($account_type);

    $firstname_sql = mysqli_real_escape_string($db, $firstname);
    $phone_sql = mysqli_real_escape_string($db, $phone);
    $email_sql = mysqli_real_escape_string($db, $email);
    $access_key_sql = mysqli_real_escape_string($db, $access_key);
    $ifsc_sql = mysqli_real_escape_string($db, $ifsc);
    $account_type_sql = mysqli_real_escape_string($db, $account_type_db);
    $account_no_sql = mysqli_real_escape_string($db, $account_no);
    $auth_mode_sql = mysqli_real_escape_string($db, $auth_mode);
    $bank_code_sql = mysqli_real_escape_string($db, $bank_code);
    $transaction_id_sql = mysqli_real_escape_string($db, $transaction_id);
    $udf5_sql = mysqli_real_escape_string($db, $max_amount . '.0');
    $final_collection_date = date('d/m/Y', strtotime('+3 years'));

    towquery("DELETE FROM `easebuzz_adtd` WHERE `uid` = " . (int) $user_id);
    $flow_label = creditlab_autocollect_request_flow_label();
    $flow_sql = mysqli_real_escape_string($db, $flow_label);
    $insert_query = "INSERT INTO `easebuzz_adtd` (`uid`, `txnid`, `firstname`, `phone`, `email`, `udf5`, `request_flow`, `customer_authentication_id`, `final_collection_date`, `hash`, `access_key`, `payment_mode`, `ifsc`, `account_type`, `account_no`, `auth_mode`, `bank_code`)
        VALUES (" . (int) $user_id . ", '$transaction_id_sql', '$firstname_sql', '$phone_sql', '$email_sql', '$udf5_sql', '$flow_sql', '$transaction_id_sql', '$final_collection_date', '', '$access_key_sql', 'EN', '$ifsc_sql', '$account_type_sql', '$account_no_sql', '$auth_mode_sql', '$bank_code_sql')";

    if (!towquery($insert_query)) {
        creditlab_autocollect_log('USER ENACH DB INSERT FAILED', array_merge($log_context, ['sql_error' => mysqli_error($db)]));
        creditlab_easebuzz_log_user_event([
            'uid' => (int) $user_id,
            'mobile' => $phone,
            'transaction_id' => $transaction_id,
            'stage' => 'mandate_start',
            'outcome' => 'failure',
            'api' => 'autocollect',
            'auth_mode' => $auth_mode,
            'amount' => $max_amount,
            'message' => 'Could not save e-NACH request to database.',
        ]);

        return ['ok' => false, 'error' => 'Could not save e-NACH request. Please try again.', 'transaction_id' => $transaction_id, 'log_file' => creditlab_autocollect_log_path()];
    }

    $seamless_fields = [
        'account_holder_name' => $account_name !== '' ? $account_name : $firstname,
        'account_number' => $account_no,
        'account_type' => $account_type_db,
        'ifsc' => $ifsc,
        'bank_code' => $bank_code,
        'auth_mode' => $auth_mode,
    ];

    creditlab_autocollect_log('USER ENACH seamless mandate redirect', array_merge($log_context, [
        'access_key' => $access_key,
        'bank_code' => $bank_code,
        'auth_mode' => $auth_mode,
    ]));

    creditlab_easebuzz_log_user_event([
        'uid' => (int) $user_id,
        'mobile' => $phone,
        'transaction_id' => $transaction_id,
        'stage' => 'mandate_start',
        'outcome' => 'pending',
        'api' => 'autocollect',
        'auth_mode' => $auth_mode,
        'amount' => $max_amount,
        'message' => 'Redirected to Easebuzz for bank authentication.',
        'meta' => ['bank_code' => $bank_code, 'ifsc' => $ifsc],
    ]);

    return [
        'ok' => true,
        'html' => creditlab_autocollect_build_seamless_mandate_form($access_key, $seamless_fields),
        'transaction_id' => $transaction_id,
        'log_file' => creditlab_autocollect_log_path(),
    ];
}

/**
 * Apply Autocollect mandate retrieve result to easebuzz_adtd + user.easebuzz.
 *
 * @param string $transaction_id
 * @param array{cb?:string} $options cb=success|failure from Easebuzz redirect URL
 * @return array{ok:bool, status:string, message:string, uid?:int}
 */
function creditlab_autocollect_finalize_user_mandate($transaction_id, array $options = [])
{
    global $db;

    $transaction_id = trim((string) $transaction_id);
    if ($transaction_id === '') {
        return ['ok' => false, 'status' => 'error', 'message' => 'Missing transaction reference.'];
    }

    $callback_type = strtolower(trim((string) ($options['cb'] ?? '')));
    if ($callback_type !== 'success' && $callback_type !== 'failure') {
        $callback_type = '';
    }

    $txn_sql = mysqli_real_escape_string($db, $transaction_id);
    $row_q = towquery("SELECT uid, auth_mode, phone FROM easebuzz_adtd WHERE customer_authentication_id='$txn_sql' OR txnid='$txn_sql' LIMIT 1");
    if (!$row_q || townum($row_q) === 0) {
        return ['ok' => false, 'status' => 'error', 'message' => 'e-NACH registration record not found.'];
    }
    $row = towfetch($row_q);
    $uid = (int) $row['uid'];
    $stored_auth_mode = (string) ($row['auth_mode'] ?? '');
    $stored_phone = (string) ($row['phone'] ?? '');
    $skip_poll = !empty($options['skip_poll']);
    $skip_user_log = !empty($options['skip_user_log']);

    if ($skip_poll) {
        $retrieve = creditlab_autocollect_retrieve_mandate($transaction_id);
        $data = creditlab_autocollect_parse_mandate_retrieve_data($retrieve);
        $poll = [
            'retrieve' => $retrieve,
            'attempts' => 1,
            'status' => strtolower((string) ($data['status'] ?? '')),
            'sub_status' => strtolower((string) ($data['sub_status'] ?? '')),
            'umrn' => trim((string) ($data['umrn'] ?? '')),
        ];
    } else {
        $poll = creditlab_autocollect_poll_mandate_retrieve(
            $transaction_id,
            $callback_type === 'success' ? 8 : 5,
            2
        );
    }
    $retrieve = $poll['retrieve'];
    $data = creditlab_autocollect_parse_mandate_retrieve_data($retrieve);
    $status = strtolower((string) ($data['status'] ?? $poll['status'] ?? ''));
    $sub_status = strtolower((string) ($data['sub_status'] ?? $poll['sub_status'] ?? ''));
    $meta = is_array($data['response_meta'] ?? null) ? $data['response_meta'] : [];
    $meta_desc = (string) ($meta['description'] ?? '');
    $meta_code = (string) ($meta['code'] ?? '');
    $umrn = trim((string) ($data['umrn'] ?? $poll['umrn'] ?? ''));
    $bank_ref = (string) ($data['bank_reference_number'] ?? '');

    creditlab_autocollect_log('USER ENACH callback retrieve', [
        'transaction_id' => $transaction_id,
        'uid' => $uid,
        'cb' => $callback_type,
        'auth_mode' => $stored_auth_mode,
        'poll_attempts' => $poll['attempts'] ?? 1,
        'http_code' => $retrieve['http_code'] ?? null,
        'status' => $status,
        'sub_status' => $sub_status,
        'umrn' => $umrn !== '' ? $umrn : null,
        'meta_code' => $meta_code,
        'meta_description' => $meta_desc,
        'retrieve_auth_mode' => (string) ($data['auth_mode'] ?? ''),
    ]);

    $authorization_status = 'pending';
    $db_status = 'pending';
    $user_easebuzz = 0;
    $message = 'e-NACH registration is being processed. Please check again in a few minutes.';
    $is_terminal = creditlab_autocollect_mandate_status_is_terminal($status, $sub_status, $umrn);
    $registration_ok = creditlab_autocollect_mandate_registration_succeeded($status, $sub_status, $umrn);

    if ($registration_ok) {
        $authorization_status = ($status === 'authorized' || $sub_status === 'authorized') ? 'authorized' : 'accepted';
        $db_status = 'success';
        $user_easebuzz = 1;
        $message = 'e-NACH registration completed successfully.';
    } elseif ($status === 'failed' || $sub_status === 'failed') {
        $authorization_status = 'rejected';
        $db_status = 'failure';
        $user_easebuzz = 2;
        $message = $meta_desc !== '' ? $meta_desc : 'e-NACH registration failed. Please try again.';
    } elseif ($status === 'in_process' || $sub_status === 'in_process') {
        $authorization_status = 'pending';
        $db_status = 'pending';
        $user_easebuzz = 0;
        $message = 'e-NACH registration submitted. NPCI confirmation may take a few minutes — refresh your dashboard shortly.';
    } elseif (in_array($status, ['requested', 'initiated', 'pending', 'created'], true)
        || in_array($sub_status, ['requested', 'initiated', 'pending', 'created'], true)) {
        $authorization_status = 'pending';
        $db_status = 'pending';
        $user_easebuzz = 0;
        if ($callback_type === 'failure') {
            $user_easebuzz = 2;
            $authorization_status = 'rejected';
            $db_status = 'failure';
            $message = $meta_desc !== ''
                ? $meta_desc
                : 'e-NACH registration was not completed. Your bank may not support the selected authentication mode — try Net Banking.';
        } else {
            $message = 'e-NACH registration submitted. Complete bank authentication on Easebuzz if you have not already, then check your dashboard in a few minutes.';
        }
    } elseif ($callback_type === 'failure') {
        $authorization_status = 'rejected';
        $db_status = 'failure';
        $user_easebuzz = 2;
        $message = $meta_desc !== ''
            ? $meta_desc
            : 'e-NACH registration could not be completed. Please try again using Net Banking.';
    } elseif (!$retrieve['ok']) {
        return ['ok' => false, 'status' => 'error', 'message' => 'Could not verify mandate status. Please try again.', 'uid' => $uid];
    } elseif ($callback_type === 'success') {
        $authorization_status = 'pending';
        $db_status = 'pending';
        $user_easebuzz = 0;
        $message = 'e-NACH registration submitted. Final confirmation from NPCI may take up to 5 minutes — refresh your dashboard shortly.';
    }

    $auth_sql = mysqli_real_escape_string($db, $authorization_status);
    $status_sql = mysqli_real_escape_string($db, $db_status);
    $err_sql = mysqli_real_escape_string($db, $meta_desc);
    $umrn_sql = mysqli_real_escape_string($db, $umrn);
    $bank_ref_sql = mysqli_real_escape_string($db, $bank_ref);
    $flow_sql = preg_match('/^(cai|clac)/i', $transaction_id)
        ? ", request_flow='" . mysqli_real_escape_string($db, creditlab_autocollect_request_flow_label()) . "'"
        : '';

    $update = "UPDATE easebuzz_adtd SET
        authorization_status='$auth_sql',
        status='$status_sql',
        error_message='$err_sql',
        bank_ref_num='$bank_ref_sql',
        auto_debit_access_key='$txn_sql'
        $flow_sql
        WHERE customer_authentication_id='$txn_sql' OR txnid='$txn_sql'";

    if ($umrn !== '') {
        $update = "UPDATE easebuzz_adtd SET
            authorization_status='$auth_sql',
            status='$status_sql',
            error_message='$err_sql',
            bank_ref_num='$bank_ref_sql',
            auto_debit_access_key='$txn_sql',
            easepayid='$umrn_sql'
            $flow_sql
            WHERE customer_authentication_id='$txn_sql' OR txnid='$txn_sql'";
    }

    towquery($update);
    towquery("UPDATE user SET easebuzz=$user_easebuzz WHERE id=$uid");

    if (!$skip_user_log) {
        creditlab_easebuzz_log_user_event([
            'uid' => $uid,
            'mobile' => $stored_phone,
            'transaction_id' => $transaction_id,
            'stage' => 'mandate_callback',
            'outcome' => creditlab_easebuzz_outcome_from_user_easebuzz($user_easebuzz),
            'api' => 'autocollect',
            'auth_mode' => $stored_auth_mode,
            'message' => $message,
            'meta' => [
                'cb' => $callback_type,
                'status' => $status,
                'sub_status' => $sub_status,
                'umrn' => $umrn !== '' ? $umrn : null,
                'meta_code' => $meta_code !== '' ? $meta_code : null,
            ],
        ]);
    }

    return [
        'ok' => true,
        'status' => $status !== '' ? $status : $authorization_status,
        'message' => $message,
        'uid' => $uid,
        'user_easebuzz' => $user_easebuzz,
        'transaction_id' => $transaction_id,
        'is_terminal' => $is_terminal,
        'callback_type' => $callback_type,
        'umrn' => $umrn,
    ];
}

/**
 * Re-check latest Autocollect mandate for a user (single retrieve — for dashboard refresh).
 *
 * @return array{ok:bool, synced:bool, user_easebuzz?:int, message?:string}
 */
function creditlab_autocollect_refresh_user_enach_status($uid)
{
    global $db;

    $uid = (int) $uid;
    if ($uid <= 0) {
        return ['ok' => false, 'synced' => false];
    }

    $row_q = towquery("SELECT customer_authentication_id, txnid FROM easebuzz_adtd WHERE uid=$uid ORDER BY id DESC LIMIT 1");
    if (!$row_q || townum($row_q) === 0) {
        return ['ok' => false, 'synced' => false];
    }
    $row = towfetch($row_q);
    $transaction_id = trim((string) ($row['customer_authentication_id'] ?? ''));
    if ($transaction_id === '') {
        $transaction_id = trim((string) ($row['txnid'] ?? ''));
    }
    if ($transaction_id === '') {
        return ['ok' => false, 'synced' => false];
    }

    $result = creditlab_autocollect_finalize_user_mandate($transaction_id, ['cb' => 'success', 'skip_poll' => true, 'skip_user_log' => true]);

    return array_merge($result, [
        'synced' => !empty($result['ok']) && (int) ($result['user_easebuzz'] ?? 0) === 1,
    ]);
}

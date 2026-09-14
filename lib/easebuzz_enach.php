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
 * True when the user has registered eNACH (accepted/authorized).
 * Do not wait for mandate "active" — that can take ~2 days. Recheck live status on the next loan.
 */
function creditlab_easebuzz_mandate_is_complete(array $row): bool
{
    return creditlab_easebuzz_mandate_authorization_ok($row);
}

function creditlab_easebuzz_auth_is_cancelled($auth): bool
{
    $auth = strtolower(trim((string) $auth));

    return in_array($auth, ['rejected', 'cancelled', 'canceled', 'revoked', 'expired', 'inactive'], true);
}

/**
 * Live Easebuzz/NPCI statuses that mean the customer cancelled or the mandate cannot be used.
 * Do not include initiated/accepted/in_process — those are the 2-day wait to become active.
 */
function creditlab_enach_live_status_is_cancelled($status, $sub_status = '', $mandate_status = ''): bool
{
    $cancelled = [
        'cancelled', 'canceled', 'cancelled_by_customer', 'cancelled_by_user',
        'revoked', 'expired', 'failed', 'rejected',
        'dropped', 'deregistered', 'terminated', 'closed',
    ];
    foreach ([$status, $sub_status, $mandate_status] as $value) {
        $value = strtolower(trim((string) $value));
        $value = str_replace([' ', '-'], '_', $value);
        if ($value !== '' && in_array($value, $cancelled, true)) {
            return true;
        }
    }

    return false;
}

function creditlab_user_enach_latest_row(int $uid): ?array
{
    global $db;
    if ($uid <= 0 || !isset($db) || !($db instanceof mysqli)) {
        return null;
    }

    $res = mysqli_query($db, 'SELECT * FROM easebuzz_adtd WHERE uid=' . $uid . ' ORDER BY id DESC LIMIT 1');
    if (!$res || mysqli_num_rows($res) === 0) {
        return null;
    }
    $row = mysqli_fetch_assoc($res);

    return is_array($row) ? $row : null;
}

function creditlab_user_enach_is_complete(int $uid): bool
{
    $row = creditlab_user_enach_latest_row($uid);

    return $row ? creditlab_easebuzz_mandate_is_complete($row) : false;
}

function creditlab_easebuzz_user_flag_from_row(array $row): int
{
    if (creditlab_easebuzz_mandate_is_complete($row)) {
        return 1;
    }
    $auth = strtolower(trim((string) ($row['authorization_status'] ?? '')));
    $status = strtolower(trim((string) ($row['status'] ?? '')));
    if (creditlab_easebuzz_auth_is_cancelled($auth)) {
        return 2;
    }
    if ($status === 'failure') {
        return 0;
    }

    return 0;
}

/**
 * Profile / dashboard e-NACH state from the latest mandate, not user.easebuzz alone.
 *
 * @return array{complete:bool,label:string,row:?array,easebuzz:int}
 */
function creditlab_user_enach_profile_state(int $uid, $user_easebuzz = 0): array
{
    $user_easebuzz = (int) $user_easebuzz;
    $row = creditlab_user_enach_latest_row($uid);
    $complete = $row ? creditlab_easebuzz_mandate_is_complete($row) : false;
    $auth = strtolower(trim((string) ($row['authorization_status'] ?? '')));

    if ($complete) {
        return ['complete' => true, 'label' => 'Yes', 'row' => $row, 'easebuzz' => 1];
    }
    if ($row && creditlab_easebuzz_auth_is_cancelled($auth)) {
        return ['complete' => false, 'label' => 'Cancel', 'row' => $row, 'easebuzz' => 2];
    }
    if ($row) {
        return ['complete' => false, 'label' => 'Pending', 'row' => $row, 'easebuzz' => 0];
    }
    if ($user_easebuzz === 2) {
        return ['complete' => false, 'label' => 'Cancel', 'row' => null, 'easebuzz' => 2];
    }

    return ['complete' => false, 'label' => 'No', 'row' => null, 'easebuzz' => 0];
}

/**
 * Correct user.easebuzz when it disagrees with the stored mandate.
 *
 * @return array{complete:bool,label:string,row:?array,easebuzz:int,synced:bool}
 */
function creditlab_user_enach_sync_flag(int $uid, $current_easebuzz = null): array
{
    global $db;
    if ($current_easebuzz === null && isset($db) && $db instanceof mysqli) {
        $q = mysqli_query($db, 'SELECT easebuzz FROM user WHERE id=' . $uid . ' LIMIT 1');
        $r = ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : null;
        $current_easebuzz = (int) ($r['easebuzz'] ?? 0);
    }
    $state = creditlab_user_enach_profile_state($uid, (int) $current_easebuzz);
    $synced = false;
    if (isset($db) && $db instanceof mysqli && (int) $current_easebuzz !== (int) $state['easebuzz']) {
        mysqli_query($db, 'UPDATE `user` SET easebuzz=' . (int) $state['easebuzz'] . ' WHERE id=' . $uid);
        $synced = true;
    }
    $state['synced'] = $synced;

    return $state;
}

function creditlab_user_enach_loan_is_after_mandate(array $loan, ?array $row): bool
{
    if (!$row) {
        return false;
    }
    $apply_ts = strtotime((string) ($loan['apply_date'] ?? ''));
    $mandate_at = trim((string) ($row['created_at'] ?? ''));
    if ($mandate_at === '') {
        $mandate_at = trim((string) ($row['addedon'] ?? ''));
    }
    $mandate_ts = strtotime($mandate_at);

    return $apply_ts > 0 && $mandate_ts > 0 && $apply_ts > ($mandate_ts + 60);
}

function creditlab_user_enach_needs_live_recheck(array $loan, ?array $row): bool
{
    if (!creditlab_user_enach_loan_is_after_mandate($loan, $row)) {
        return false;
    }
    if (!$row || !creditlab_easebuzz_mandate_is_complete($row)) {
        return false;
    }
    $mandate_status = trim((string) ($row['mandate_status'] ?? ''));
    $updated_ts = strtotime((string) ($row['updated_at'] ?? ''));
    $apply_ts = strtotime((string) ($loan['apply_date'] ?? ''));
    if ($mandate_status !== '' && $updated_ts > 0 && $apply_ts > 0 && $updated_ts >= $apply_ts) {
        return false;
    }

    return true;
}

function creditlab_user_enach_mark_inactive(int $uid, array $row, string $reason, string $live_status = ''): void
{
    global $db;
    if (!isset($db) || !($db instanceof mysqli) || $uid <= 0) {
        return;
    }

    $id = (int) ($row['id'] ?? 0);
    $reason_sql = mysqli_real_escape_string($db, substr($reason, 0, 255));
    $live_sql = mysqli_real_escape_string($db, substr($live_status, 0, 64));
    if ($id > 0) {
        mysqli_query(
            $db,
            "UPDATE easebuzz_adtd SET authorization_status='cancelled', mandate_status='$live_sql', cancellation_reason='$reason_sql' WHERE id=$id"
        );
    }
    mysqli_query($db, "UPDATE `user` SET easebuzz=0 WHERE id=$uid");

    if (function_exists('creditlab_easebuzz_log_user_event')) {
        creditlab_easebuzz_log_user_event([
            'uid' => $uid,
            'transaction_id' => (string) ($row['customer_authentication_id'] ?? $row['txnid'] ?? ''),
            'stage' => 'mandate_recheck',
            'outcome' => 'cancelled',
            'api' => 'autocollect',
            'message' => $reason,
            'meta' => ['live_status' => $live_status],
        ]);
    }
}

/**
 * On a new loan, GET Autocollect /v1/mandate/{transaction_id} and re-ask eNACH
 * only if the live mandate was cancelled. New flow has no auto_debit_access_key —
 * identity is customer_authentication_id. initiated/accepted/in_process is still valid
 * (active can take ~2 days).
 *
 * @return array{still_active:bool,checked:bool,reason:string,live_status:string}
 */
function creditlab_user_enach_recheck_for_new_loan(int $uid): array
{
    $empty = ['still_active' => false, 'checked' => false, 'reason' => 'no_mandate', 'live_status' => ''];
    $uid = (int) $uid;
    if ($uid <= 0) {
        return $empty;
    }

    $row = creditlab_user_enach_latest_row($uid);
    if (!$row) {
        return $empty;
    }
    if (!creditlab_easebuzz_mandate_is_complete($row)) {
        return [
            'still_active' => false,
            'checked' => false,
            'reason' => 'not_registered',
            'live_status' => (string) ($row['authorization_status'] ?? ''),
        ];
    }

    require_once __DIR__ . '/easebuzz_autocollect.php';
    $transaction_id = creditlab_easebuzz_autocollect_transaction_id($row);
    $live_status = '';
    $sub_status = '';
    $mandate_status = '';
    $umrn = '';

    if ($transaction_id === '') {
        return [
            'still_active' => true,
            'checked' => true,
            'reason' => 'no_transaction_id_keep',
            'live_status' => '',
        ];
    }

    $retrieve = creditlab_autocollect_retrieve_mandate($transaction_id);
    $data = creditlab_autocollect_parse_mandate_retrieve_data($retrieve);
    $live_status = strtolower(trim((string) ($data['status'] ?? '')));
    $sub_status = strtolower(trim((string) ($data['sub_status'] ?? '')));
    $mandate_status = strtolower(trim((string) ($data['mandate_status'] ?? $data['npci_status'] ?? '')));
    $umrn = trim((string) ($data['umrn'] ?? ''));

    if (empty($retrieve['ok']) || ($live_status === '' && $sub_status === '' && $mandate_status === '')) {
        return [
            'still_active' => true,
            'checked' => true,
            'reason' => 'retrieve_failed_keep',
            'live_status' => $live_status,
        ];
    }

    $combined = trim($live_status . '/' . $sub_status . '/' . $mandate_status, '/');
    if (creditlab_enach_live_status_is_cancelled($live_status, $sub_status, $mandate_status)) {
        creditlab_user_enach_mark_inactive($uid, $row, 'Mandate cancelled on new loan Autocollect retrieve', $combined);

        return [
            'still_active' => false,
            'checked' => true,
            'reason' => 'cancelled',
            'live_status' => $combined,
        ];
    }

    global $db;
    if (isset($db) && $db instanceof mysqli && !empty($row['id'])) {
        $live_sql = mysqli_real_escape_string($db, substr($combined, 0, 64));
        $umrn_sql = $umrn !== '' ? ", easepayid='" . mysqli_real_escape_string($db, substr($umrn, 0, 64)) . "'" : '';
        mysqli_query($db, 'UPDATE easebuzz_adtd SET mandate_status=\'' . $live_sql . '\'' . $umrn_sql . ' WHERE id=' . (int) $row['id']);
    }

    return [
        'still_active' => true,
        'checked' => true,
        'reason' => 'active',
        'live_status' => $combined,
    ];
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

<?php
/**
 * Autocollect / e-NACH presentment webhook parsing and loan settlement.
 *
 * Register URL with Easebuzz: {base_url}/payment/autocollect_webhook.php
 * (Legacy PG auto-debit still uses /easebuzz_webhook.php with furl=cb_auto.php.)
 */

require_once __DIR__ . '/../config/easebuzz.php';
require_once __DIR__ . '/loan_enach_settlement.php';
require_once __DIR__ . '/easebuzz_enach.php';
require_once __DIR__ . '/easebuzz_enach_user_log.php';

function creditlab_enach_webhook_log_path()
{
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir . '/autocollect_webhook_' . date('Y-m-d') . '.log';
}

function creditlab_enach_webhook_log($message, array $context = [])
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($context) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    $line .= PHP_EOL;
    file_put_contents(creditlab_enach_webhook_log_path(), $line, FILE_APPEND | LOCK_EX);
}

function creditlab_enach_webhook_log_raw($headers, $get, $post, $rawBody)
{
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $entry = "\n=== Autocollect webhook " . date('Y-m-d H:i:s') . " ===\n";
    $entry .= "Headers:\n" . serialize($headers) . "\n";
    $entry .= "GET:\n" . serialize($get) . "\n";
    $entry .= "POST:\n" . serialize($post) . "\n";
    $entry .= "Raw:\n" . $rawBody . "\n";
    file_put_contents($dir . '/autocollect_webhook_raw.txt', $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Parse CLL_AUTO_{lid}_{timestamp} or legacy CLL_AUTO_{lid}.
 *
 * @return array{loan_lid:string,timestamp:?string,prefix:string}|null
 */
function creditlab_enach_parse_merchant_ref($merchant_ref)
{
    $merchant_ref = trim((string) $merchant_ref);
    if ($merchant_ref === '') {
        return null;
    }

    if (strpos($merchant_ref, 'CLL_AUTO_') === 0) {
        $parts = explode('_', $merchant_ref);
        if (count($parts) >= 3) {
            return [
                'prefix' => 'CLL_AUTO_',
                'loan_lid' => $parts[2],
                'timestamp' => $parts[3] ?? null,
            ];
        }
        return [
            'prefix' => 'CLL_AUTO_',
            'loan_lid' => substr($merchant_ref, 9),
            'timestamp' => null,
        ];
    }

    if (strpos($merchant_ref, 'CLTEST_') === 0) {
        return [
            'prefix' => 'CLTEST_',
            'loan_lid' => '',
            'timestamp' => null,
        ];
    }

    return null;
}

function creditlab_enach_webhook_pick(array $sources, array $keys, $default = '')
{
    foreach ($sources as $source) {
        if (!is_array($source)) {
            continue;
        }
        foreach ($keys as $key) {
            if (isset($source[$key]) && $source[$key] !== '' && $source[$key] !== null) {
                return $source[$key];
            }
        }
    }
    return $default;
}

/**
 * Normalize Autocollect JSON or form webhook into a presentment event.
 *
 * @return array|null
 */
function creditlab_enach_webhook_parse_presentment(array $post, $rawBody = '')
{
    $rawBody = (string) $rawBody;
    $json = null;
    $trim = ltrim($rawBody);
    if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $json = $decoded;
        }
    }

    $roots = array_filter([$json, $post], 'is_array');
    if (!$roots) {
        return null;
    }

    $layers = [];
    foreach ($roots as $root) {
        $layers[] = $root;
        if (isset($root['data']) && is_array($root['data'])) {
            $layers[] = $root['data'];
            if (isset($root['data']['data']) && is_array($root['data']['data'])) {
                $layers[] = $root['data']['data'];
            }
        }
    }

    $merchant_ref = trim((string) creditlab_enach_webhook_pick($layers, [
        'merchant_request_number',
        'merchant_debit_id',
    ]));

    if ($merchant_ref === '') {
        return null;
    }

    $status_raw = strtolower(trim((string) creditlab_enach_webhook_pick($layers, [
        'status',
        'status_at_bank',
        'auto_debit_request_state',
        'presentment_status',
    ])));

    $auto_state = strtolower(trim((string) creditlab_enach_webhook_pick($layers, ['auto_debit_request_state'])));

    if ($auto_state === 'success' || in_array($status_raw, ['success', 'successful', 'completed', 'captured', 'paid'], true)) {
        $outcome = 'success';
    } elseif ($auto_state === 'failure' || in_array($status_raw, ['failure', 'failed', 'bounced', 'rejected', 'cancelled'], true)) {
        $outcome = 'failure';
    } elseif (in_array($status_raw, ['in_process', 'pending', 'initiated', 'accepted', 'processing'], true)) {
        $outcome = 'pending';
    } else {
        $outcome = 'pending';
    }

    $amount = creditlab_enach_webhook_pick($layers, ['amount', 'net_amount', 'net_amount_debit'], '0');
    $txnid = trim((string) creditlab_enach_webhook_pick($layers, [
        'pg_transaction_id',
        'txnid',
        'easepayid',
        'transaction_id',
    ]));
    $presentment_id = trim((string) creditlab_enach_webhook_pick($layers, ['id', 'presentment_id']));
    $bank_ref = trim((string) creditlab_enach_webhook_pick($layers, [
        'bank_reference_number',
        'bank_ref_num',
        'transaction_reference_number',
        'auth_ref_num',
    ]));
    if ($bank_ref === '' || strtoupper($bank_ref) === 'NA') {
        $bank_ref = $txnid !== '' ? $txnid : ('ACWH_' . time());
    }

    $mandate_txn = trim((string) creditlab_enach_webhook_pick($layers, [
        'transaction_id',
    ]));
    $mandate = creditlab_enach_webhook_pick($layers, ['mandate'], null);
    if ($mandate_txn === '' && is_array($mandate) && !empty($mandate['transaction_id'])) {
        $mandate_txn = trim((string) $mandate['transaction_id']);
    }

    $error_message = trim((string) creditlab_enach_webhook_pick($layers, [
        'error_message',
        'error_Message',
        'message',
        'description',
    ]));
    if ($error_message === '' && is_array($mandate)) {
        $meta = creditlab_enach_webhook_pick([$layers[0] ?? [], $layers[1] ?? []], ['response_meta'], null);
        if (is_array($meta) && !empty($meta['description'])) {
            $error_message = (string) $meta['description'];
        }
    }

    return [
        'merchant_ref' => $merchant_ref,
        'outcome' => $outcome,
        'amount' => $amount,
        'txnid' => $txnid,
        'presentment_id' => $presentment_id,
        'bank_ref_num' => $bank_ref,
        'mandate_transaction_id' => $mandate_txn,
        'error_message' => $error_message,
        'raw_status' => $status_raw,
        'source' => $json ? 'json' : 'form',
    ];
}

/**
 * Verify Autocollect presentment webhook (shared secret and/or PG reverse hash on flat POST).
 */
function creditlab_enach_webhook_verify(array $post, $rawBody = '')
{
    require_once __DIR__ . '/easebuzz_verify.php';

    if (!empty($post['hash']) && creditlab_easebuzz_validate_callback($post)) {
        return true;
    }

    $headerSecret = trim(env('EASEBUZZ_WEBHOOK_SECRET', ''));
    if ($headerSecret !== '') {
        $provided = $_SERVER['HTTP_X_EASEBUZZ_WEBHOOK_SECRET'] ?? ($post['webhook_secret'] ?? '');
        if ($provided !== '' && hash_equals($headerSecret, (string) $provided)) {
            return true;
        }
    }

    // Autocollect JSON webhooks: require shared secret when no PG hash present.
    $trim = ltrim((string) $rawBody);
    if ($trim !== '' && $trim[0] === '{') {
        if ($headerSecret !== '') {
            return false;
        }
        // No secret configured — allow but log (merchant should set EASEBUZZ_WEBHOOK_SECRET).
        creditlab_enach_webhook_log('WARNING: Autocollect JSON webhook accepted without EASEBUZZ_WEBHOOK_SECRET');
        return true;
    }

    return !empty($post) && creditlab_easebuzz_validate_callback($post);
}

/**
 * Handle presentment webhook — clear loan on success, log all outcomes.
 *
 * @return array{ok:bool,action:string,message:string,loan_lid?:string}
 */
function creditlab_enach_webhook_handle_presentment($db, array $event, $base_url, ?callable $write_log = null)
{
    $log = function ($msg, $ctx = []) use ($write_log) {
        creditlab_enach_webhook_log($msg, $ctx);
        if ($write_log) {
            $write_log($msg);
        }
    };

    $merchant_ref = $event['merchant_ref'] ?? '';
    $parsed_ref = creditlab_enach_parse_merchant_ref($merchant_ref);
    $outcome = $event['outcome'] ?? 'pending';
    $amount = $event['amount'] ?? 0;
    $bank_ref = $event['bank_ref_num'] ?? '';
    $mandate_txn = $event['mandate_transaction_id'] ?? '';
    $txnid = $event['txnid'] ?? '';

    creditlab_easebuzz_log_user_event([
        'uid' => 0,
        'transaction_id' => $mandate_txn !== '' ? $mandate_txn : $merchant_ref,
        'stage' => 'presentment_webhook',
        'outcome' => in_array($outcome, ['success', 'failure', 'pending'], true) ? $outcome : 'pending',
        'api' => 'autocollect',
        'amount' => $amount,
        'message' => $outcome === 'success'
            ? 'Presentment settled successfully.'
            : ($outcome === 'failure'
                ? ('Presentment failed: ' . ($event['error_message'] ?? 'unknown'))
                : 'Presentment update: ' . ($event['raw_status'] ?? 'pending')),
        'meta' => [
            'merchant_ref' => $merchant_ref,
            'presentment_id' => $event['presentment_id'] ?? '',
            'pg_transaction_id' => $txnid,
            'bank_ref_num' => $bank_ref,
        ],
    ]);

    if ($parsed_ref && $parsed_ref['prefix'] === 'CLTEST_') {
        $log('Test presentment webhook logged (no loan action)', ['merchant_ref' => $merchant_ref, 'outcome' => $outcome]);
        return ['ok' => true, 'action' => 'test_logged', 'message' => 'Test ref — event logged only.'];
    }

    if (!$parsed_ref || $parsed_ref['prefix'] !== 'CLL_AUTO_' || $parsed_ref['loan_lid'] === '') {
        $log('Unknown merchant_ref — no loan action', ['merchant_ref' => $merchant_ref, 'outcome' => $outcome]);
        return ['ok' => true, 'action' => 'ignored', 'message' => 'Unknown merchant reference.'];
    }

    $loan_lid = $parsed_ref['loan_lid'];

    if ($outcome === 'pending') {
        $log("Presentment pending for CLL$loan_lid", ['merchant_ref' => $merchant_ref, 'status' => $event['raw_status'] ?? '']);
        return ['ok' => true, 'action' => 'pending', 'message' => 'Presentment still in progress.', 'loan_lid' => $loan_lid];
    }

    if ($outcome === 'failure') {
        $log("Presentment FAILED for CLL$loan_lid", ['merchant_ref' => $merchant_ref, 'error' => $event['error_message'] ?? '']);
        return ['ok' => true, 'action' => 'failure_logged', 'message' => 'Failure logged.', 'loan_lid' => $loan_lid];
    }

    $loan_lid_esc = mysqli_real_escape_string($db, $loan_lid);
    $loan_data = creditlab_enach_settlement_query($db, "SELECT * FROM loan WHERE lid='$loan_lid_esc' LIMIT 1");
    if (!$loan_data || creditlab_enach_settlement_num($loan_data) === 0) {
        $log("Loan CLL$loan_lid not found for presentment webhook", ['merchant_ref' => $merchant_ref]);
        return ['ok' => false, 'action' => 'error', 'message' => "Loan CLL$loan_lid not found.", 'loan_lid' => $loan_lid];
    }

    $loan_details = creditlab_enach_settlement_fetch($loan_data);
    $uid = (int) $loan_details['uid'];

    if (($loan_details['status_log'] ?? '') === 'cleared' || ($loan_details['action'] ?? '') === 'cleared') {
        $log("SKIPPED: CLL$loan_lid already cleared (duplicate webhook)", ['merchant_ref' => $merchant_ref]);
        return ['ok' => true, 'action' => 'already_cleared', 'message' => 'Loan already cleared.', 'loan_lid' => $loan_lid];
    }

    $user_data = creditlab_enach_settlement_query($db, "SELECT * FROM user WHERE id='$uid' LIMIT 1");
    if (!$user_data || creditlab_enach_settlement_num($user_data) === 0) {
        $log("User not found for CLL$loan_lid", ['uid' => $uid]);
        return ['ok' => false, 'action' => 'error', 'message' => 'User not found.', 'loan_lid' => $loan_lid];
    }
    $user_details = creditlab_enach_settlement_fetch($user_data);

    if (!creditlab_enach_process_loan_clearance($db, $loan_lid, $uid, $amount, $bank_ref, 'full')) {
        $log("Loan clearance failed for CLL$loan_lid", ['merchant_ref' => $merchant_ref]);
        return ['ok' => false, 'action' => 'clearance_failed', 'message' => 'Loan clearance failed.', 'loan_lid' => $loan_lid];
    }

    require_once __DIR__ . '/zxc_mail.php';
    require_once __DIR__ . '/http_fetch.php';
    require_once __DIR__ . '/sms_loan_cleared.php';

    creditlab_zxc_mail_trigger(creditlab_zxc_mail_url(
        $base_url,
        $user_details['email'],
        null,
        null,
        $base_url . '/no-due-certificate2.php?id=' . $loan_lid
    ));

    $sms_ok = creditlab_send_loan_cleared_sms(
        (string) ($user_details['mobile'] ?? ''),
        (string) ($user_details['name'] ?? 'Customer'),
        (int) $loan_lid,
        $base_url
    );

    $log("SUCCESS: CLL$loan_lid cleared via presentment webhook | amount=₹$amount | bank_ref=$bank_ref | sms=" . ($sms_ok ? 'ok' : 'fail'), [
        'merchant_ref' => $merchant_ref,
        'pg_txn' => $txnid,
    ]);

    return [
        'ok' => true,
        'action' => 'cleared',
        'message' => "Loan CLL$loan_lid cleared.",
        'loan_lid' => $loan_lid,
    ];
}

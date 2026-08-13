<?php
/**
 * Shared Autocollect eNACH playground UI + handlers.
 *
 * Entry points set constants before including this file:
 * - autocollect_playground.php — UAT sandbox (CREDITLAB_AUTOCOLLECT_FORCE_UAT)
 * - autocollect_playground_prod.php — live prod API (.env merchant key/salt)
 */

if (!defined('CREDITLAB_AUTOCOLLECT_PLAYGROUND_MODE')) {
    define('CREDITLAB_AUTOCOLLECT_PLAYGROUND_MODE', 'uat');
}
if (!defined('CREDITLAB_AUTOCOLLECT_SESSION_KEY')) {
    define('CREDITLAB_AUTOCOLLECT_SESSION_KEY', 'autocollect_playground');
}
if (!defined('CREDITLAB_AUTOCOLLECT_PLAYGROUND_SELF')) {
    define('CREDITLAB_AUTOCOLLECT_PLAYGROUND_SELF', 'autocollect_playground.php');
}
if (!defined('CREDITLAB_AUTOCOLLECT_LOGS_SELF')) {
    define('CREDITLAB_AUTOCOLLECT_LOGS_SELF', 'autocollect_logs.php');
}
if (!defined('CREDITLAB_AUTOCOLLECT_WEB_LOG_CHANNEL')) {
    define('CREDITLAB_AUTOCOLLECT_WEB_LOG_CHANNEL', CREDITLAB_AUTOCOLLECT_PLAYGROUND_MODE);
}

$playground_is_uat = (CREDITLAB_AUTOCOLLECT_PLAYGROUND_MODE === 'uat');
$playground_is_prod = !$playground_is_uat;
$playground_session_key = CREDITLAB_AUTOCOLLECT_SESSION_KEY;
$playground_self = CREDITLAB_AUTOCOLLECT_PLAYGROUND_SELF;
$playground_logs = CREDITLAB_AUTOCOLLECT_LOGS_SELF;

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/auth.php';

require_once __DIR__ . '/../lib/easebuzz_autocollect.php';
require_once __DIR__ . '/../lib/easebuzz_enach.php';

if ($playground_is_prod) {
    if (!creditlab_autocollect_playground_prod_allowed()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die(
            "Autocollect prod playground is disabled.\n"
            . "Set EASEBUZZ_AUTOCOLLECT_PLAYGROUND=1 in .env on production.\n"
            . "Requires EASEBUZZ_ENV=prod and prod merchant key/salt in .env.\n"
            . "Use autocollect_playground.php for UAT sandbox testing.\n"
        );
    }
    if (!creditlab_autocollect_credentials_ok()) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        die("Prod Easebuzz credentials missing. Set EASEBUZZ_MERCHANT_KEY and EASEBUZZ_SALT in .env.\n");
    }
} elseif (!creditlab_autocollect_playground_allowed()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    die(
        "Autocollect playground is disabled.\n"
        . "Production: set EASEBUZZ_AUTOCOLLECT_PLAYGROUND=1 in .env\n"
        . "Sandbox: set EASEBUZZ_ENV=test in .env\n"
    );
}

$flash = $_SESSION[$playground_session_key] ?? [];
$result_block = null;
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

$seamless_bank_fields = [
    'account_holder_name' => $_POST['account_holder_name'] ?? '',
    'account_number' => $_POST['account_number'] ?? '',
    'account_type' => $_POST['account_type'] ?? 'savings',
    'ifsc' => $_POST['ifsc'] ?? '',
    'bank_code' => $_POST['bank_code'] ?? '',
    'auth_mode' => $_POST['auth_mode'] ?? 'netbanking',
];
if ($playground_is_uat) {
    $seamless_bank_fields = creditlab_autocollect_apply_sandbox_bank_defaults($seamless_bank_fields);
    $sandbox_bank = creditlab_autocollect_sandbox_bank_defaults() ?? [];
} else {
    $sandbox_bank = [];
}

// Seamless mandate: emit HTML form and exit (browser posts to Easebuzz)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'seamless_mandate' || $action === 'generate_key_and_seamless')) {
    $access_key = trim((string) ($_POST['access_key'] ?? ($flash['access_key'] ?? '')));

    if ($action === 'generate_key_and_seamless') {
        $result = creditlab_autocollect_generate_access_key([
            'transaction_id' => $_POST['transaction_id'] ?? '',
            'amount' => $_POST['amount'] ?? '10000',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => $_POST['end_date'] ?? date('Y-m-d', strtotime('+3 years')),
            'success_url' => $_POST['success_url'] ?? '',
            'failure_url' => $_POST['failure_url'] ?? '',
            'request_type' => 'SEAMLESS',
        ]);
        if (empty($result['access_key'])) {
            $result_block = ['title' => 'Generate Access Key + Seamless Mandate', 'result' => $result];
        } else {
            $_SESSION[$playground_session_key] = [
                'access_key' => $result['access_key'],
                'transaction_id' => $result['transaction_id'],
                'checkout_url' => $result['checkout_url'],
                'request_type' => 'SEAMLESS',
            ];
            $seamless_bank_fields['bank_code'] = creditlab_autocollect_resolve_bank_code(
                $seamless_bank_fields['ifsc'],
                $seamless_bank_fields['bank_code']
            );
            $missing = [];
            foreach (['account_holder_name', 'account_number', 'ifsc'] as $req_field) {
                if (trim((string) ($seamless_bank_fields[$req_field] ?? '')) === '') {
                    $missing[] = $req_field;
                }
            }
            if ($missing) {
                $result_block = [
                    'title' => 'Generate Access Key + Seamless Mandate',
                    'ok' => false,
                    'error' => 'Access key created, but bank details missing: ' . implode(', ', $missing)
                        . '. Fill bank details and use Section B2, or retry with all fields.',
                    'result' => $result,
                ];
                $flash = $_SESSION[$playground_session_key];
            } else {
                echo creditlab_autocollect_build_seamless_mandate_form($result['access_key'], $seamless_bank_fields);
                exit;
            }
        }
    } elseif ($access_key === '') {
        $result_block = ['title' => 'Seamless mandate', 'ok' => false, 'error' => 'access_key is required'];
    } else {
        $seamless_bank_fields['bank_code'] = creditlab_autocollect_resolve_bank_code(
            $seamless_bank_fields['ifsc'],
            $seamless_bank_fields['bank_code']
        );
        echo creditlab_autocollect_build_seamless_mandate_form($access_key, $seamless_bank_fields);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $result_block === null) {
    switch ($action) {
        case 'generate_key':
            $request_type = strtoupper(trim((string) ($_POST['request_type'] ?? 'DEFAULT')));
            if ($request_type === 'SEAMLESS') {
                // Prefer the combined flow when SEAMLESS is selected with bank fields present.
                $has_bank = trim((string) ($_POST['account_number'] ?? '')) !== ''
                    && trim((string) ($_POST['account_holder_name'] ?? '')) !== '';
                if ($has_bank) {
                    $result = creditlab_autocollect_generate_access_key([
                        'transaction_id' => $_POST['transaction_id'] ?? '',
                        'amount' => $_POST['amount'] ?? '10000',
                        'email' => $_POST['email'] ?? '',
                        'phone' => $_POST['phone'] ?? '',
                        'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                        'end_date' => $_POST['end_date'] ?? date('Y-m-d', strtotime('+3 years')),
                        'success_url' => $_POST['success_url'] ?? '',
                        'failure_url' => $_POST['failure_url'] ?? '',
                        'request_type' => 'SEAMLESS',
                    ]);
                    if (!empty($result['access_key'])) {
                        $_SESSION[$playground_session_key] = [
                            'access_key' => $result['access_key'],
                            'transaction_id' => $result['transaction_id'],
                            'checkout_url' => $result['checkout_url'],
                            'request_type' => 'SEAMLESS',
                        ];
                        echo creditlab_autocollect_build_seamless_mandate_form($result['access_key'], $seamless_bank_fields);
                        exit;
                    }
                    $result_block = ['title' => 'Generate Access Key', 'result' => $result];
                    break;
                }
            }
            $result = creditlab_autocollect_generate_access_key([
                'transaction_id' => $_POST['transaction_id'] ?? '',
                'amount' => $_POST['amount'] ?? '10000',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'end_date' => $_POST['end_date'] ?? date('Y-m-d', strtotime('+3 years')),
                'success_url' => $_POST['success_url'] ?? '',
                'failure_url' => $_POST['failure_url'] ?? '',
                'request_type' => $request_type === 'SEAMLESS' ? 'SEAMLESS' : 'DEFAULT',
            ]);
            if (!empty($result['access_key'])) {
                $_SESSION[$playground_session_key] = [
                    'access_key' => $result['access_key'],
                    'transaction_id' => $result['transaction_id'],
                    'checkout_url' => $result['checkout_url'],
                    'request_type' => $request_type === 'SEAMLESS' ? 'SEAMLESS' : 'DEFAULT',
                ];
                $flash = $_SESSION[$playground_session_key];
            }
            $result_block = ['title' => 'Generate Access Key', 'result' => $result];
            break;

        case 'retrieve_mandate':
            $txn = trim((string) ($_POST['transaction_id'] ?? ($flash['transaction_id'] ?? '')));
            $result = creditlab_autocollect_retrieve_mandate($txn);
            if ($txn !== '') {
                if (!isset($_SESSION[$playground_session_key]) || !is_array($_SESSION[$playground_session_key])) {
                    $_SESSION[$playground_session_key] = [];
                }
                $_SESSION[$playground_session_key]['transaction_id'] = $txn;
                $flash = $_SESSION[$playground_session_key];
            }
            $result_block = ['title' => 'Mandate Retrieve', 'result' => $result];
            break;

        case 'request_debit':
            $txn = trim((string) ($_POST['transaction_id'] ?? ($flash['transaction_id'] ?? '')));
            $result = creditlab_autocollect_initiate_enach_debit([
                'transaction_id' => $txn,
                'amount' => $_POST['amount'] ?? '1.00',
                'merchant_request_number' => $_POST['merchant_request_number'] ?? '',
                'presentment_date' => $_POST['presentment_date'] ?? '',
            ]);
            if ($txn !== '') {
                if (!isset($_SESSION[$playground_session_key]) || !is_array($_SESSION[$playground_session_key])) {
                    $_SESSION[$playground_session_key] = [];
                }
                $_SESSION[$playground_session_key]['transaction_id'] = $txn;
                $flash = $_SESSION[$playground_session_key];
            }
            $result_block = ['title' => 'Autocollect Presentment (new cai… / CLAC…)', 'result' => $result];
            break;

        case 'legacy_request_debit':
            require_once __DIR__ . '/../lib/easebuzz_enach.php';
            $result = creditlab_easebuzz_legacy_initiate_direct_debit([
                'amount' => $_POST['amount'] ?? '1.00',
                'productinfo' => $_POST['productinfo'] ?? 'Playground Legacy Debit',
                'firstname' => $_POST['firstname'] ?? 'Test User',
                'email' => $_POST['email'] ?? 'test@example.com',
                'phone' => $_POST['phone'] ?? '9999999999',
                'customer_authentication_id' => $_POST['customer_authentication_id'] ?? '',
                'auto_debit_access_key' => $_POST['auto_debit_access_key'] ?? '',
                'merchant_debit_id' => $_POST['merchant_debit_id'] ?? '',
                'udf1' => 'AUTOCOLLECT_PLAYGROUND_LEGACY',
            ]);
            $result_block = ['title' => 'Legacy PG Presentment (old customer_authentication_id)', 'result' => $result];
            break;

        case 'lookup_mandate':
            require_once __DIR__ . '/../lib/easebuzz_enach.php';
            $txn = trim((string) ($_POST['transaction_id'] ?? ''));
            $customer_auth = trim((string) ($_POST['customer_authentication_id'] ?? ''));
            $lookup = [
                'transaction_id' => $txn,
                'customer_authentication_id' => $customer_auth,
            ];
            if ($txn !== '') {
                $retrieve = creditlab_autocollect_retrieve_mandate($txn);
                $parsed = creditlab_autocollect_parse_mandate_retrieve_data($retrieve);
                $lookup['autocollect_retrieve'] = [
                    'http_code' => $retrieve['http_code'] ?? null,
                    'ok' => $retrieve['ok'] ?? false,
                    'status' => $parsed['status'] ?? null,
                    'sub_status' => $parsed['sub_status'] ?? null,
                    'umrn' => $parsed['umrn'] ?? null,
                    'registration_ok' => creditlab_autocollect_mandate_registration_succeeded(
                        (string) ($parsed['status'] ?? ''),
                        (string) ($parsed['sub_status'] ?? ''),
                        (string) ($parsed['umrn'] ?? '')
                    ),
                ];
            }
            if ($customer_auth !== '') {
                $row = [
                    'customer_authentication_id' => $customer_auth,
                    'request_flow' => trim((string) ($_POST['request_flow'] ?? '')),
                    'auto_debit_access_key' => trim((string) ($_POST['auto_debit_access_key'] ?? '')),
                    'txnid' => $txn,
                ];
                $lookup['presentment_api'] = creditlab_easebuzz_presentment_api_for_row($row);
                $lookup['is_autocollect_mandate'] = creditlab_easebuzz_is_autocollect_mandate_row($row);
            }
            $result_block = ['title' => 'Mandate lookup (new vs old)', 'result' => $lookup];
            break;

        case 'open_checkout':
            $access_key = trim((string) ($_POST['access_key'] ?? ($flash['access_key'] ?? '')));
            if ($access_key === '') {
                $result_block = ['title' => 'Open checkout', 'ok' => false, 'error' => 'access_key is required'];
            } else {
                $url = creditlab_autocollect_checkout_url($access_key);
                $_SESSION[$playground_session_key]['access_key'] = $access_key;
                $_SESSION[$playground_session_key]['checkout_url'] = $url;
                header('Location: ' . $url);
                exit;
            }
            break;
    }
}

$cb = $_GET['cb'] ?? '';
$cb_retrieve = null;
if (($cb === 'success' || $cb === 'failure') && !empty($flash['transaction_id'])) {
    $cb_retrieve = creditlab_autocollect_retrieve_mandate($flash['transaction_id']);
}
$base = creditlab_get_base_url();
$default_success = $base . '/payment/' . $playground_self . '?cb=success';
$default_failure = $base . '/payment/' . $playground_self . '?cb=failure';

$v_access_key = htmlspecialchars((string) ($flash['access_key'] ?? ''), ENT_QUOTES);
$v_txn = htmlspecialchars((string) ($flash['transaction_id'] ?? ''), ENT_QUOTES);
$v_checkout = htmlspecialchars((string) ($flash['checkout_url'] ?? ''), ENT_QUOTES);

function playground_dump($data)
{
    return htmlspecialchars(
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ENT_QUOTES
    );
}

function playground_bank_value($field, array $post_data, array $sandbox_defaults)
{
    $v = trim((string) ($post_data[$field] ?? ''));
    if ($v === '' && isset($sandbox_defaults[$field])) {
        $v = (string) $sandbox_defaults[$field];
    }
    return htmlspecialchars($v, ENT_QUOTES);
}

function playground_option_selected($field, $option, array $post_data, array $sandbox_defaults)
{
    $current = trim((string) ($post_data[$field] ?? ''));
    if ($current === '' && isset($sandbox_defaults[$field])) {
        $current = (string) $sandbox_defaults[$field];
    }
    return $current === (string) $option ? ' selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $playground_is_prod ? 'Autocollect eNACH Playground (PROD)' : 'Autocollect eNACH Playground' ?></title>
    <style>
        body { font-family: sans-serif; margin: 2em; background: #f4f4f9; color: #333; }
        .container { max-width: 960px; margin: auto; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 20px; }
        h1, h2 { color: #444; margin-top: 0; }
        label { display: block; font-weight: bold; margin: 10px 0 4px; }
        input, select, button { padding: 8px 10px; font-size: 1rem; border-radius: 5px; border: 1px solid #ccc; }
        input[type=text], input[type=email], input[type=number], input[type=date], input[type=url] { width: 100%; max-width: 480px; box-sizing: border-box; }
        button { background: #007bff; color: #fff; border-color: #007bff; cursor: pointer; margin-top: 12px; margin-right: 8px; }
        button.secondary { background: #6c757d; border-color: #6c757d; }
        .results { margin-top: 16px; padding: 12px; background: #e9ecef; border-left: 4px solid #007bff; white-space: pre-wrap; word-wrap: break-word; font-family: monospace; font-size: 13px; }
        .results.err { border-left-color: #dc3545; }
        .hint { color: #666; font-size: 13px; margin: 6px 0 0; }
        .banner { background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .ok-banner { background: #d4edda; border-color: #28a745; }
        .row { display: flex; flex-wrap: wrap; gap: 16px; }
        .row > div { flex: 1 1 280px; }
        code { background: #eee; padding: 1px 4px; border-radius: 3px; }
        a.checkout { word-break: break-all; }
        ol.smoke { line-height: 1.5; }
        .log-entry { margin-bottom: 12px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; }
        .log-meta { padding: 8px 12px; background: #f8f9fa; border-bottom: 1px solid #eee; font-size: 13px; }
        .log-body { margin: 0; padding: 10px 12px; font-size: 12px; overflow-x: auto; max-height: 320px; white-space: pre-wrap; word-wrap: break-word; }
        .nav-links { margin-bottom: 16px; }
        .nav-links a { margin-right: 16px; }
        .uat-banner { background: #cfe2ff; border: 1px solid #0d6efd; }
        .prod-banner { background: #f8d7da; border: 1px solid #dc3545; color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <div class="nav-links">
        <?php if ($playground_is_uat): ?>
        <a href="autocollect_playground.php"><strong>UAT playground</strong></a>
        <a href="autocollect_logs.php">UAT logs</a>
        <a href="autocollect_playground_prod.php">Prod playground</a>
        <?php else: ?>
        <a href="autocollect_playground.php">UAT playground</a>
        <a href="autocollect_playground_prod.php"><strong>Prod playground</strong></a>
        <a href="autocollect_logs_prod.php">Prod logs</a>
        <?php endif; ?>
    </div>

    <h1>Autocollect eNACH Playground<?= $playground_is_prod ? ' <span style="color:#dc3545;">(PROD — live API)</span>' : '' ?></h1>
    <p class="hint">
        Mode: <code><?= htmlspecialchars(creditlab_autocollect_env_label(), ENT_QUOTES) ?><?= $playground_is_uat ? ' (forced UAT)' : '' ?></code>
        · API: <code><?= htmlspecialchars(creditlab_autocollect_base_url(), ENT_QUOTES) ?></code>
        · Checkout: <code><?= htmlspecialchars(creditlab_autocollect_checkout_base_url(), ENT_QUOTES) ?></code>
        · Merchant key: <code><?= htmlspecialchars(creditlab_autocollect_merchant_key(), ENT_QUOTES) ?></code>
        · Salt: <code><?= htmlspecialchars($playground_is_prod ? creditlab_autocollect_mask_secret(creditlab_autocollect_salt()) : creditlab_autocollect_salt(), ENT_QUOTES) ?></code>
    </p>

    <?php if ($playground_is_uat): ?>
    <div class="banner uat-banner">
        <strong>UAT sandbox.</strong> Hardcoded UAT credentials (<code>53LFWVJQH</code> / <code>G151INEFT</code>)
        and sandbox API URLs. Logs: <a href="<?= htmlspecialchars($playground_logs, ENT_QUOTES) ?>"><?= htmlspecialchars($playground_logs, ENT_QUOTES) ?></a>.
    </div>
    <?php else: ?>
    <div class="banner prod-banner">
        <strong>Live production Autocollect.</strong> Uses <code>EASEBUZZ_MERCHANT_KEY</code> / <code>EASEBUZZ_SALT</code> from .env
        and <code>https://api.easebuzz.in/autocollect</code>. Real bank accounts and real debits — test with small amounts only.
        Logs: <a href="<?= htmlspecialchars($playground_logs, ENT_QUOTES) ?>"><?= htmlspecialchars($playground_logs, ENT_QUOTES) ?></a>.
    </div>
    <?php endif; ?>

    <div class="banner">
        <strong>Internal test page.</strong> Live customers use <code>user/easebuzz.php</code> (Autocollect SEAMLESS)
        and <code>payment/zzenach.php</code> (Autocollect or legacy PG presentment, auto-routed).
    </div>

    <?php if ($cb === 'success'): ?>
        <div class="banner ok-banner">Mandate callback: <strong>success</strong> URL hit. Use Section D to retrieve mandate status.</div>
    <?php elseif ($cb === 'failure'): ?>
        <div class="banner">Mandate callback: <strong>failure</strong> URL hit. Check retrieve / logs below.</div>
    <?php endif; ?>

    <?php if ($cb_retrieve): ?>
        <?php
        $cb_data = creditlab_autocollect_parse_mandate_retrieve_data($cb_retrieve);
        $cb_status = (string) ($cb_data['status'] ?? 'unknown');
        $cb_sub = (string) ($cb_data['sub_status'] ?? '');
        $cb_umrn = (string) ($cb_data['umrn'] ?? '');
        $cb_meta = $cb_data['response_meta'] ?? [];
        $cb_err = is_array($cb_meta) && !empty($cb_meta['description']) ? $cb_meta['description'] : null;
        $cb_reg_ok = creditlab_autocollect_mandate_registration_succeeded($cb_status, $cb_sub, $cb_umrn);
        ?>
        <div class="card">
            <h2>Callback retrieve: <?= htmlspecialchars((string) ($flash['transaction_id'] ?? ''), ENT_QUOTES) ?></h2>
            <p>Status: <code><?= htmlspecialchars($cb_status, ENT_QUOTES) ?></code>
                <?php if ($cb_sub !== ''): ?>
                · sub_status: <code><?= htmlspecialchars($cb_sub, ENT_QUOTES) ?></code>
                <?php endif; ?>
                <?php if ($cb_umrn !== ''): ?>
                · UMRN: <code><?= htmlspecialchars($cb_umrn, ENT_QUOTES) ?></code>
                <?php endif; ?>
            </p>
            <?php if ($cb_reg_ok): ?>
                <div class="banner ok-banner">Registration accepted<?= $cb_status !== 'authorized' ? ' (NPCI accepted — may show initiated/accepted before authorized)' : '' ?>.</div>
            <?php endif; ?>
            <?php if ($cb_err): ?>
                <div class="results err"><?= htmlspecialchars($cb_err, ENT_QUOTES) ?>
                    <?php if (!empty($cb_meta['code'])): ?> (<code><?= htmlspecialchars((string) $cb_meta['code'], ENT_QUOTES) ?></code>)<?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($cb_err && stripos($cb_err, 'bank_code') !== false): ?>
                <p class="hint"><strong>Fix:</strong> For sandbox IFSC <code>EBZS0001987</code>, use <code>bank_code=HDFC</code> (not <code>EBZS</code>). The playground auto-maps this on SEAMLESS submit.</p>
            <?php endif; ?>
            <?php if ($cb_status === 'failed'): ?>
                <p class="hint"><strong>Do not reuse this transaction_id.</strong> Leave it blank in Section A for a new <code>CLAC_…</code> ID and retry.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Mandate / presentment ID guide (new vs old)</h2>
        <table style="width:100%;border-collapse:collapse;font-size:13px;margin:12px 0;">
            <tr style="background:#f8f9fa;">
                <th style="text-align:left;padding:8px;border:1px solid #dee2e6;">Mandate type</th>
                <th style="text-align:left;padding:8px;border:1px solid #dee2e6;">ID to use</th>
                <th style="text-align:left;padding:8px;border:1px solid #dee2e6;">Retrieve (Section D)</th>
                <th style="text-align:left;padding:8px;border:1px solid #dee2e6;">Presentment</th>
            </tr>
            <tr>
                <td style="padding:8px;border:1px solid #dee2e6;"><strong>New Autocollect</strong><br><code>request_flow=AUTOCOLLECT_SEAMLESS</code></td>
                <td style="padding:8px;border:1px solid #dee2e6;"><code>cai…</code> (same as <code>customer_authentication_id</code>)</td>
                <td style="padding:8px;border:1px solid #dee2e6;">Autocollect GET <code>/v1/mandate/{id}</code></td>
                <td style="padding:8px;border:1px solid #dee2e6;">Section <strong>C</strong> — Autocollect POST presentment</td>
            </tr>
            <tr>
                <td style="padding:8px;border:1px solid #dee2e6;"><strong>Legacy PG eNACH</strong><br>pre-Autocollect signups</td>
                <td style="padding:8px;border:1px solid #dee2e6;"><code>customer_authentication_id</code> + <code>auto_debit_access_key</code></td>
                <td style="padding:8px;border:1px solid #dee2e6;">Not on Autocollect — check <code>easebuzz_adtd.authorization_status</code></td>
                <td style="padding:8px;border:1px solid #dee2e6;">Section <strong>E</strong> — legacy <code>initiateDirectDebitRequest</code></td>
            </tr>
            <tr>
                <td style="padding:8px;border:1px solid #dee2e6;"><strong>Migrated on Autocollect</strong></td>
                <td style="padding:8px;border:1px solid #dee2e6;">Old <code>customer_authentication_id</code> as Autocollect <code>transaction_id</code></td>
                <td style="padding:8px;border:1px solid #dee2e6;">Section <strong>D</strong> (if Easebuzz migrated the mandate)</td>
                <td style="padding:8px;border:1px solid #dee2e6;">Section <strong>C</strong></td>
            </tr>
        </table>
        <p class="hint"><code>payment/zzenach.php</code> auto-routes: <code>cai…</code> / <code>AUTOCOLLECT_*</code> → Autocollect; otherwise → legacy PG.</p>
        <ul class="smoke">
            <li><strong>New mandate</strong> (playground): leave blank in Section A for auto <code>CLAC_…</code>; customer flow uses <code>cai…</code>.</li>
            <li><strong>Retrieve / presentment (Autocollect)</strong>: same ID as at mandate creation.</li>
            <li><strong>Legacy presentment</strong>: needs <code>customer_authentication_id</code> and <code>auto_debit_access_key</code> from <code>easebuzz_adtd</code>.</li>
        </ul>
        <?php if ($v_txn && $cb_retrieve && ($cb_data['status'] ?? '') === 'failed'): ?>
        <p class="hint"><code><?= $v_txn ?></code> is <code>failed</code> — leave transaction_id blank in Section A for a new mandate.</p>
        <?php endif; ?>
    </div>

    <?php if ($playground_is_uat): ?>
    <div class="card">
        <h2>Sandbox credentials (Easebuzz official)</h2>
        <p class="hint">Source: <a href="<?= htmlspecialchars(creditlab_autocollect_sandbox_docs_url(), ENT_QUOTES) ?>" target="_blank" rel="noopener">Autocollect sandbox testing credentials</a></p>
        <table style="width:100%;border-collapse:collapse;font-size:13px;margin:12px 0;">
            <tr style="background:#f8f9fa;"><th style="text-align:left;padding:8px;border:1px solid #dee2e6;">Scenario</th><th style="text-align:left;padding:8px;border:1px solid #dee2e6;">Name</th><th style="text-align:left;padding:8px;border:1px solid #dee2e6;">Account</th><th style="text-align:left;padding:8px;border:1px solid #dee2e6;">IFSC</th></tr>
            <?php foreach (creditlab_autocollect_sandbox_enach_accounts() as $preset): ?>
            <tr>
                <td style="padding:8px;border:1px solid #dee2e6;"><?= htmlspecialchars($preset['label'], ENT_QUOTES) ?></td>
                <td style="padding:8px;border:1px solid #dee2e6;"><code><?= htmlspecialchars($preset['account_holder_name'], ENT_QUOTES) ?></code></td>
                <td style="padding:8px;border:1px solid #dee2e6;"><code><?= htmlspecialchars($preset['account_number'], ENT_QUOTES) ?></code></td>
                <td style="padding:8px;border:1px solid #dee2e6;"><code><?= htmlspecialchars($preset['ifsc'], ENT_QUOTES) ?></code></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p class="hint">Sandbox IFSC <code>EBZS0001987</code> → send <code>bank_code=HDFC</code> on SEAMLESS (same as successful DEFAULT checkout).</p>
        <p class="hint"><strong>Mandate timing:</strong> after checkout, status is <code>in_process</code> first; final success/failed may take <strong>~5 minutes</strong> (poll Section D or webhook).</p>
        <p class="hint"><strong>Presentment mock:</strong> <code>merchant_request_number</code> must contain <code>suc</code> for success (e.g. <code>enachReq99suc01</code>).</p>
    </div>

    <div class="card">
        <h2>Smoke test (sandbox)</h2>
        <div class="banner">
            <strong>Use DEFAULT or SEAMLESS on sandbox.</strong> Official account on testpay or in Section A; for IFSC
            <code>EBZS0001987</code> use <code>bank_code=HDFC</code> on SEAMLESS (auto-filled).
        </div>
        <ol class="smoke">
            <li><strong>A.</strong> <code>DEFAULT</code>, leave <code>transaction_id</code> blank → <strong>Generate Access Key</strong>.</li>
            <li><strong>B1.</strong> Open checkout → enter <code>Sandbox Testing</code> / <code>282800002828</code> / <code>EBZS0001987</code>.</li>
            <li><strong>D.</strong> Retrieve — expect <code>in_process</code>, then wait ~5 min and retrieve again until ready for debit.</li>
            <li><strong>C.</strong> Presentment with MRN like <code>enachReq99suc01</code> (must contain <code>suc</code>).</li>
        </ol>
        <p class="hint">Full API log: <a href="<?= htmlspecialchars($playground_logs, ENT_QUOTES) ?>"><?= htmlspecialchars($playground_logs, ENT_QUOTES) ?></a></p>
    </div>
    <?php else: ?>
    <div class="card">
        <h2>Smoke test (production)</h2>
        <ol class="smoke">
            <li><strong>A.</strong> <code>SEAMLESS</code> (or <code>DEFAULT</code> checkout) with a <strong>real customer bank account</strong>.</li>
            <li><strong>IFSC → bank_code:</strong> first 4 letters of IFSC (e.g. <code>HDFC0004171</code> → <code>HDFC</code>).</li>
            <li><strong>D.</strong> Retrieve until <code>authorized</code> (may take a few minutes).</li>
            <li><strong>C.</strong> Presentment with a unique <code>merchant_request_number</code> and a small test amount.</li>
        </ol>
        <p class="hint">Full API log: <a href="<?= htmlspecialchars($playground_logs, ENT_QUOTES) ?>"><?= htmlspecialchars($playground_logs, ENT_QUOTES) ?></a></p>
    </div>
    <?php endif; ?>

    <?php if ($result_block): ?>
        <div class="card">
            <h2>Last result: <?= htmlspecialchars($result_block['title'], ENT_QUOTES) ?></h2>
            <?php if (!empty($result_block['error'])): ?>
                <div class="results err"><?= htmlspecialchars($result_block['error'], ENT_QUOTES) ?></div>
            <?php else: ?>
                <?php
                $r = $result_block['result'];
                $ok = !empty($r['ok']);
                ?>
                <div class="results <?= $ok ? '' : 'err' ?>"><?= playground_dump($r) ?></div>
                <?php if (!empty($r['checkout_url'])): ?>
                    <p>Checkout:
                        <a class="checkout" href="<?= htmlspecialchars($r['checkout_url'], ENT_QUOTES) ?>" target="_blank" rel="noopener">
                            <?= htmlspecialchars($r['checkout_url'], ENT_QUOTES) ?>
                        </a>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($v_access_key || $v_txn): ?>
        <div class="card">
            <h2>Session context</h2>
            <p><strong>transaction_id:</strong> <code><?= $v_txn ?: '—' ?></code></p>
            <p><strong>access_key:</strong> <code style="word-break:break-all;"><?= $v_access_key ?: '—' ?></code></p>
            <?php if ($v_checkout): ?>
                <p><strong>checkout:</strong> <a class="checkout" href="<?= $v_checkout ?>" target="_blank" rel="noopener"><?= $v_checkout ?></a></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- A. Generate Access Key (+ bank details when SEAMLESS) -->
    <div class="card">
        <h2>A. Generate Access Key</h2>
        <p class="hint">POST <code>/v1/access-key/generate/</code> — eNACH, <code>payment_modes=["EN"]</code>. Choose <strong>SEAMLESS</strong> to fill bank details here and create the mandate in one step.</p>
        <form method="POST" id="form_generate">
            <input type="hidden" name="action" id="generate_action" value="generate_key">
            <div class="row">
                <div>
                    <label>transaction_id (optional — leave blank for new CLAC_… ID)</label>
                    <input type="text" name="transaction_id" placeholder="blank = auto CLAC_… · migrated eNACH = customer_authentication_id" value="<?= $cb === 'failure' ? '' : $v_txn ?>">
                </div>
                <div>
                    <label>amount (max mandate)</label>
                    <input type="text" name="amount" value="10000.00" required>
                </div>
            </div>
            <div class="row">
                <div>
                    <label>email</label>
                    <input type="email" name="email" required placeholder="test@example.com">
                </div>
                <div>
                    <label>phone (10 digits)</label>
                    <input type="text" name="phone" required pattern="[2-9][0-9]{9}" placeholder="98XXXXXXXX">
                </div>
            </div>
            <div class="row">
                <div>
                    <label>start_date</label>
                    <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label>end_date</label>
                    <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+3 years')) ?>" required>
                </div>
            </div>
            <div class="row">
                <div>
                    <label>success_url</label>
                    <input type="url" name="success_url" value="<?= htmlspecialchars($default_success, ENT_QUOTES) ?>">
                </div>
                <div>
                    <label>failure_url</label>
                    <input type="url" name="failure_url" value="<?= htmlspecialchars($default_failure, ENT_QUOTES) ?>">
                </div>
            </div>
            <label>request_type</label>
            <select name="request_type" id="request_type">
                <?php if ($playground_is_uat): ?>
                <option value="DEFAULT" selected>DEFAULT (non-seamless checkout) — recommended on sandbox</option>
                <option value="SEAMLESS">SEAMLESS (bank details below — sandbox EBZS IFSC uses bank_code HDFC)</option>
                <?php else: ?>
                <option value="SEAMLESS" selected>SEAMLESS (bank details below — recommended for prod)</option>
                <option value="DEFAULT">DEFAULT (redirect to pay.easebuzz.in checkout)</option>
                <?php endif; ?>
            </select>

            <div id="seamless_bank_box" style="margin-top:16px;padding:14px;background:#f8f9fa;border-radius:6px;border:1px solid #dee2e6;">
                <h3 style="margin-top:0;">Bank details (required for SEAMLESS)</h3>
                <?php if ($playground_is_uat): ?>
                <label>Sandbox account preset</label>
                <select id="sandbox_account_preset">
                    <?php foreach (creditlab_autocollect_sandbox_enach_accounts() as $key => $preset): ?>
                    <option value="<?= htmlspecialchars($key, ENT_QUOTES) ?>"<?= ($sandbox_bank['account_number'] ?? '') === $preset['account_number'] ? ' selected' : '' ?>>
                        <?= htmlspecialchars($preset['label'], ENT_QUOTES) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="hint">Sandbox: IFSC <code>EBZS0001987</code> → <code>bank_code=HDFC</code> (not EBZS). Prod: IFSC prefix → 4-char code (e.g. HDFC000… → <code>HDFC</code>).</p>
                <?php else: ?>
                <p class="hint">Enter the customer's real account. <code>bank_code</code> = first 4 letters of IFSC (auto-filled on blur).</p>
                <?php endif; ?>
                <div class="row">
                    <div>
                        <label>account_holder_name</label>
                        <input type="text" name="account_holder_name" id="account_holder_name" value="<?= playground_bank_value('account_holder_name', $seamless_bank_fields, $sandbox_bank) ?>" placeholder="Name as on bank account">
                    </div>
                    <div>
                        <label>account_number</label>
                        <input type="text" name="account_number" id="account_number" value="<?= playground_bank_value('account_number', $seamless_bank_fields, $sandbox_bank) ?>" placeholder="Your account number">
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label>account_type</label>
                        <select name="account_type" id="account_type">
                            <option value="savings"<?= playground_option_selected('account_type', 'savings', $seamless_bank_fields, $sandbox_bank) ?>>savings</option>
                            <option value="current"<?= playground_option_selected('account_type', 'current', $seamless_bank_fields, $sandbox_bank) ?>>current</option>
                        </select>
                    </div>
                    <div>
                        <label>ifsc</label>
                        <input type="text" name="ifsc" id="ifsc" value="<?= playground_bank_value('ifsc', $seamless_bank_fields, $sandbox_bank) ?>" placeholder="<?= $playground_is_uat ? 'e.g. EBZS0001987' : 'e.g. HDFC0004171' ?>" style="text-transform:uppercase;">
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label>bank_code (4 letters<?= $playground_is_uat ? ' — sandbox EBZS IFSC → HDFC' : '' ?>)</label>
                        <input type="text" name="bank_code" id="bank_code" value="<?= playground_bank_value('bank_code', $seamless_bank_fields, $sandbox_bank) ?>" placeholder="e.g. HDFC" pattern="[A-Za-z]{4}" title="Exactly 4 uppercase letters" style="text-transform:uppercase;">
                        <span class="hint">Auto-filled from IFSC on blur.</span>
                    </div>
                    <div>
                        <label>auth_mode</label>
                        <select name="auth_mode" id="auth_mode">
                            <option value="netbanking"<?= playground_option_selected('auth_mode', 'netbanking', $seamless_bank_fields, $sandbox_bank) ?>>netbanking</option>
                            <option value="debitcard"<?= playground_option_selected('auth_mode', 'debitcard', $seamless_bank_fields, $sandbox_bank) ?>>debitcard</option>
                            <option value="aadhaar"<?= playground_option_selected('auth_mode', 'aadhaar', $seamless_bank_fields, $sandbox_bank) ?>>aadhaar</option>
                        </select>
                    </div>
                </div>
            </div>

            <br>
            <button type="submit" id="generate_btn">Generate key &amp; create mandate</button>
        </form>
    </div>

    <!-- B. Create Mandate (fallback / advanced) -->
    <div class="card">
        <h2>B. Create eNACH Mandate (fallback)</h2>
        <p class="hint">Only needed if you already have an access key, or if you used DEFAULT and need checkout.</p>

        <div id="b1_box">
            <h3>B1. Non-seamless redirect</h3>
            <p class="hint">Opens <code>pay.easebuzz.in/pay/{access_key}</code> (or testpay in sandbox).</p>
            <form method="POST">
                <input type="hidden" name="action" value="open_checkout">
                <label>access_key</label>
                <input type="text" name="access_key" value="<?= $v_access_key ?>" style="max-width:100%;">
                <br>
                <button type="submit">Open checkout</button>
            </form>
        </div>

        <hr style="margin: 24px 0; border: 0; border-top: 1px solid #ddd;">

        <div id="b2_box">
            <h3>B2. Seamless form submit (with existing access_key)</h3>
            <p class="hint">Use when the key was already generated and you only need to submit bank details.</p>
            <form method="POST">
                <input type="hidden" name="action" value="seamless_mandate">
                <label>access_key</label>
                <input type="text" name="access_key" value="<?= $v_access_key ?>" required style="max-width:100%;">
                <div class="row">
                    <div>
                        <label>account_holder_name</label>
                        <input type="text" name="account_holder_name" value="<?= playground_bank_value('account_holder_name', $seamless_bank_fields, $sandbox_bank) ?>" placeholder="Name as on bank account" required>
                    </div>
                    <div>
                        <label>account_number</label>
                        <input type="text" name="account_number" value="<?= playground_bank_value('account_number', $seamless_bank_fields, $sandbox_bank) ?>" placeholder="Your account number" required>
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label>account_type</label>
                        <select name="account_type">
                            <option value="savings"<?= playground_option_selected('account_type', 'savings', $seamless_bank_fields, $sandbox_bank) ?>>savings</option>
                            <option value="current"<?= playground_option_selected('account_type', 'current', $seamless_bank_fields, $sandbox_bank) ?>>current</option>
                        </select>
                    </div>
                    <div>
                        <label>ifsc</label>
                        <input type="text" name="ifsc" value="<?= playground_bank_value('ifsc', $seamless_bank_fields, $sandbox_bank) ?>" placeholder="e.g. EBZS0001987" required style="text-transform:uppercase;">
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label>bank_code (4 letters<?= $playground_is_uat ? ' — sandbox EBZS IFSC → HDFC' : '' ?>)</label>
                        <input type="text" name="bank_code" value="<?= playground_bank_value('bank_code', $seamless_bank_fields, $sandbox_bank) ?>" placeholder="e.g. HDFC" pattern="[A-Za-z]{4}" title="Exactly 4 uppercase letters" style="text-transform:uppercase;">
                    </div>
                    <div>
                        <label>auth_mode</label>
                        <select name="auth_mode">
                            <option value="netbanking"<?= playground_option_selected('auth_mode', 'netbanking', $seamless_bank_fields, $sandbox_bank) ?>>netbanking</option>
                            <option value="debitcard"<?= playground_option_selected('auth_mode', 'debitcard', $seamless_bank_fields, $sandbox_bank) ?>>debitcard</option>
                            <option value="aadhaar"<?= playground_option_selected('auth_mode', 'aadhaar', $seamless_bank_fields, $sandbox_bank) ?>>aadhaar</option>
                        </select>
                    </div>
                </div>
                <button type="submit">Submit seamless mandate</button>
            </form>
        </div>
    </div>

    <!-- D. Retrieve -->
    <div class="card">
        <h2>D. Mandate Retrieve (Autocollect — new cai… / migrated ID)</h2>
        <p class="hint">GET <code>/v1/mandate/{transaction_id}</code>. Use <strong>cai…</strong> for new signups or a migrated legacy <code>customer_authentication_id</code> if Easebuzz moved it to Autocollect.<?= $playground_is_uat ? ' Sandbox: status may stay <code>initiated</code>/<code>accepted</code> before <code>authorized</code>.' : '' ?></p>
        <form method="POST">
            <input type="hidden" name="action" value="retrieve_mandate">
            <label>transaction_id</label>
            <input type="text" name="transaction_id" value="<?= $v_txn ?>" placeholder="cai… or CLAC_… or migrated customer_authentication_id" required>
            <br>
            <button type="submit">Retrieve mandate</button>
        </form>
    </div>

    <!-- D2. Lookup which API -->
    <div class="card">
        <h2>D2. Lookup — which presentment API?</h2>
        <p class="hint">Paste IDs from <code>easebuzz_adtd</code> to see whether zzenach will use Autocollect or legacy PG.</p>
        <form method="POST">
            <input type="hidden" name="action" value="lookup_mandate">
            <label>transaction_id (optional — runs Autocollect retrieve if set)</label>
            <input type="text" name="transaction_id" value="<?= $v_txn ?>" placeholder="cai… or migrated ID">
            <label>customer_authentication_id</label>
            <input type="text" name="customer_authentication_id" placeholder="from easebuzz_adtd">
            <label>request_flow (optional)</label>
            <input type="text" name="request_flow" placeholder="AUTOCOLLECT_SEAMLESS or blank for legacy">
            <label>auto_debit_access_key (optional — legacy)</label>
            <input type="text" name="auto_debit_access_key" placeholder="legacy PG access key">
            <br>
            <button type="submit">Lookup mandate</button>
        </form>
    </div>

    <!-- C. Autocollect Debit -->
    <div class="card">
        <h2>C. Autocollect Presentment (new transaction_id / cai…)</h2>
        <p class="hint">POST <code>/v1/mandate/presentment/</code> — for <strong>new Autocollect</strong> mandates and migrated IDs on Autocollect.<?= $playground_is_uat ? ' Sandbox: <code>merchant_request_number</code> must contain <code>suc</code>.' : ' Use a unique merchant_request_number. Mandate should be <code>authorized</code> (not just accepted).' ?></p>
        <form method="POST">
            <input type="hidden" name="action" value="request_debit">
            <label>transaction_id</label>
            <input type="text" name="transaction_id" value="<?= $v_txn ?>" placeholder="cai… or CLAC_…" required>
            <div class="row">
                <div>
                    <label>amount</label>
                    <input type="text" name="amount" value="1.00" required>
                </div>
                <div>
                    <label>merchant_request_number (optional)</label>
                    <input type="text" name="merchant_request_number" value="<?= $playground_is_uat ? 'enachReq99suc01' : '' ?>" placeholder="<?= $playground_is_uat ? 'must contain suc for sandbox success' : 'unique per debit, e.g. CLDR_…' ?>">
                </div>
            </div>
            <label>presentment_date (optional YYYY-MM-DD)</label>
            <input type="date" name="presentment_date">
            <br>
            <button type="submit">Initiate Autocollect presentment</button>
        </form>
    </div>

    <!-- E. Legacy PG Debit -->
    <div class="card">
        <h2>E. Legacy PG Presentment (old customer_authentication_id)</h2>
        <p class="hint">POST <code><?= htmlspecialchars(creditlab_easebuzz_pg_base_url(), ENT_QUOTES) ?>/payment/initiateDirectDebitRequest/</code> — for mandates registered via legacy <code>initiateLink</code> (before Autocollect). Requires <code>customer_authentication_id</code> and <code>auto_debit_access_key</code> from <code>easebuzz_adtd</code>.</p>
        <form method="POST">
            <input type="hidden" name="action" value="legacy_request_debit">
            <label>customer_authentication_id</label>
            <input type="text" name="customer_authentication_id" placeholder="legacy ID from easebuzz_adtd" required>
            <label>auto_debit_access_key</label>
            <input type="text" name="auto_debit_access_key" placeholder="from easebuzz_adtd (legacy PG)">
            <div class="row">
                <div>
                    <label>amount</label>
                    <input type="text" name="amount" value="1.00" required>
                </div>
                <div>
                    <label>merchant_debit_id (optional)</label>
                    <input type="text" name="merchant_debit_id" placeholder="unique, e.g. CLDR_test_…">
                </div>
            </div>
            <div class="row">
                <div>
                    <label>firstname</label>
                    <input type="text" name="firstname" value="Test User">
                </div>
                <div>
                    <label>email</label>
                    <input type="text" name="email" value="test@example.com">
                </div>
                <div>
                    <label>phone</label>
                    <input type="text" name="phone" value="9999999999">
                </div>
            </div>
            <br>
            <button type="submit">Initiate legacy PG presentment</button>
        </form>
    </div>

    <div class="card">
        <h2>API log (recent)</h2>
        <p class="hint">Last 15 calls from this playground session. <a href="<?= htmlspecialchars($playground_logs, ENT_QUOTES) ?>">View all / clear logs</a></p>
        <?= creditlab_autocollect_render_web_logs_html(15) ?>
    </div>
</div>
<script>
(function () {
    var sel = document.getElementById('request_type');
    var bankBox = document.getElementById('seamless_bank_box');
    var actionInput = document.getElementById('generate_action');
    var btn = document.getElementById('generate_btn');
    var bankRequiredIds = ['account_holder_name', 'account_number', 'ifsc'];

    var playgroundIsUat = <?= $playground_is_uat ? 'true' : 'false' ?>;

    function resolveBankCodeFromIfsc(ifsc) {
        ifsc = (ifsc || '').toUpperCase().trim();
        if (playgroundIsUat && ifsc.indexOf('EBZS') === 0) {
            return 'HDFC';
        }
        return ifsc.length >= 4 ? ifsc.substring(0, 4) : '';
    }

    function syncRequestType() {
        var seamless = sel && sel.value === 'SEAMLESS';
        if (bankBox) bankBox.style.display = seamless ? 'block' : 'none';
        if (actionInput) actionInput.value = seamless ? 'generate_key_and_seamless' : 'generate_key';
        if (btn) btn.textContent = seamless ? 'Generate key & create mandate' : 'Generate Access Key';
        bankRequiredIds.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.required = !!seamless;
        });
    }

    document.querySelectorAll('input[name="ifsc"]').forEach(function (ifscInput) {
        ifscInput.addEventListener('blur', function () {
            var form = ifscInput.closest('form');
            if (!form) return;
            var bankInput = form.querySelector('input[name="bank_code"]');
            if (!bankInput) return;
            var code = resolveBankCodeFromIfsc(ifscInput.value);
            // Always sync from IFSC — sandbox EBZS IFSC → HDFC; else 4-char IFSC prefix.
            if (/^[A-Za-z]{4}$/.test(code)) {
                bankInput.value = code.toUpperCase();
            }
        });
    });

    if (sel) {
        sel.addEventListener('change', syncRequestType);
        syncRequestType();
    }

    if (playgroundIsUat) {
        var sandboxPresets = <?= json_encode(creditlab_autocollect_sandbox_enach_accounts(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var presetSel = document.getElementById('sandbox_account_preset');
        function applySandboxPreset() {
            if (!presetSel || !sandboxPresets[presetSel.value]) return;
            var p = sandboxPresets[presetSel.value];
            ['account_holder_name', 'account_number', 'ifsc'].forEach(function (field) {
                document.querySelectorAll('[name="' + field + '"]').forEach(function (el) {
                    if (field === 'account_holder_name') el.value = p.account_holder_name;
                    if (field === 'account_number') el.value = p.account_number;
                    if (field === 'ifsc') el.value = p.ifsc;
                });
            });
            var bc = resolveBankCodeFromIfsc(p.ifsc);
            document.querySelectorAll('input[name="bank_code"]').forEach(function (el) {
                el.value = bc;
            });
        }
        if (presetSel) {
            presetSel.addEventListener('change', applySandboxPreset);
            applySandboxPreset();
        }
    }
})();
</script>
</body>
</html>

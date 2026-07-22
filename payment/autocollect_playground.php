<?php
/**
 * Internal Autocollect eNACH playground (parallel to legacy eNACH — does not replace it).
 *
 * Production: set EASEBUZZ_AUTOCOLLECT_PLAYGROUND=1 in server .env.
 * Sandbox: EASEBUZZ_ENV=test (no extra flag needed).
 *
 * Isolation guarantees:
 * - Does not write to easebuzz_adtd or user.easebuzz
 * - Does not call initiateLink / initiateDirectDebitRequest
 * - Callback URLs point only to this page (not easebuzz_callback.php)
 * - Mandate transaction_ids use CLAC_ prefix; udf1=AUTOCOLLECT_PLAYGROUND
 *
 * Smoke test: A generate key → B mandate → D retrieve (authorized) → C presentment.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/auth.php';

/** Playground always uses UAT sandbox API + hardcoded merchant credentials. */
define('CREDITLAB_AUTOCOLLECT_FORCE_UAT', true);

require_once __DIR__ . '/../lib/easebuzz_autocollect.php';

if (!creditlab_autocollect_playground_allowed()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    die(
        "Autocollect playground is disabled.\n"
        . "Production: set EASEBUZZ_AUTOCOLLECT_PLAYGROUND=1 in .env\n"
        . "Sandbox: set EASEBUZZ_ENV=test in .env\n"
    );
}

$flash = $_SESSION['autocollect_playground'] ?? [];
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
            $_SESSION['autocollect_playground'] = [
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
                $flash = $_SESSION['autocollect_playground'];
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
                        $_SESSION['autocollect_playground'] = [
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
                $_SESSION['autocollect_playground'] = [
                    'access_key' => $result['access_key'],
                    'transaction_id' => $result['transaction_id'],
                    'checkout_url' => $result['checkout_url'],
                    'request_type' => $request_type === 'SEAMLESS' ? 'SEAMLESS' : 'DEFAULT',
                ];
                $flash = $_SESSION['autocollect_playground'];
            }
            $result_block = ['title' => 'Generate Access Key', 'result' => $result];
            break;

        case 'retrieve_mandate':
            $txn = trim((string) ($_POST['transaction_id'] ?? ($flash['transaction_id'] ?? '')));
            $result = creditlab_autocollect_retrieve_mandate($txn);
            if ($txn !== '') {
                if (!isset($_SESSION['autocollect_playground']) || !is_array($_SESSION['autocollect_playground'])) {
                    $_SESSION['autocollect_playground'] = [];
                }
                $_SESSION['autocollect_playground']['transaction_id'] = $txn;
                $flash = $_SESSION['autocollect_playground'];
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
                if (!isset($_SESSION['autocollect_playground']) || !is_array($_SESSION['autocollect_playground'])) {
                    $_SESSION['autocollect_playground'] = [];
                }
                $_SESSION['autocollect_playground']['transaction_id'] = $txn;
                $flash = $_SESSION['autocollect_playground'];
            }
            $result_block = ['title' => 'Request eNACH Debit', 'result' => $result];
            break;

        case 'open_checkout':
            $access_key = trim((string) ($_POST['access_key'] ?? ($flash['access_key'] ?? '')));
            if ($access_key === '') {
                $result_block = ['title' => 'Open checkout', 'ok' => false, 'error' => 'access_key is required'];
            } else {
                $url = creditlab_autocollect_checkout_url($access_key);
                $_SESSION['autocollect_playground']['access_key'] = $access_key;
                $_SESSION['autocollect_playground']['checkout_url'] = $url;
                header('Location: ' . $url);
                exit;
            }
            break;
    }
}

$cb = $_GET['cb'] ?? '';
$base = creditlab_get_base_url();
$default_success = $base . '/payment/autocollect_playground.php?cb=success';
$default_failure = $base . '/payment/autocollect_playground.php?cb=failure';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autocollect eNACH Playground</title>
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
    </style>
</head>
<body>
<div class="container">
    <div class="nav-links">
        <a href="autocollect_playground.php"><strong>Playground</strong></a>
        <a href="autocollect_logs.php">API logs</a>
    </div>

    <h1>Autocollect eNACH Playground</h1>
    <p class="hint">
        Mode: <code>UAT (forced)</code>
        · API: <code><?= htmlspecialchars(creditlab_autocollect_base_url(), ENT_QUOTES) ?></code>
        · Checkout: <code><?= htmlspecialchars(creditlab_autocollect_checkout_base_url(), ENT_QUOTES) ?></code>
        · Merchant key: <code><?= htmlspecialchars(creditlab_autocollect_merchant_key(), ENT_QUOTES) ?></code>
        · Salt: <code><?= htmlspecialchars(creditlab_autocollect_salt(), ENT_QUOTES) ?></code>
    </p>

    <div class="banner uat-banner">
        <strong>UAT sandbox.</strong> This page uses hardcoded UAT credentials (<code>53LFWVJQH</code> / <code>G151INEFT</code>)
        and sandbox API URLs only. All API requests and responses are logged below and on
        <a href="autocollect_logs.php">API logs</a>.
    </div>

    <div class="banner">
        <strong>Legacy eNACH is unchanged.</strong> Customer mandate setup still uses
        <code>user/easebuzz.php</code> → <code>initiateLink</code>; auto-debit still uses
        <code>zzenach.php</code> / <code>initiateDirectDebitRequest</code>.
        This page only talks to Autocollect APIs and does not update <code>easebuzz_adtd</code>.
    </div>

    <?php if ($cb === 'success'): ?>
        <div class="banner ok-banner">Mandate callback: <strong>success</strong> URL hit. Use Section D to retrieve mandate status.</div>
    <?php elseif ($cb === 'failure'): ?>
        <div class="banner">Mandate callback: <strong>failure</strong> URL hit. Check retrieve / logs.</div>
    <?php endif; ?>

    <div class="card">
        <h2>Smoke test (sandbox)</h2>
        <ol class="smoke">
            <li><strong>A.</strong> Choose <code>SEAMLESS</code>, fill customer + bank details, click <strong>Generate key &amp; create mandate</strong>.</li>
            <li>Complete netbanking / debit card auth on the bank page.</li>
            <li><strong>D.</strong> Retrieve until <code>authorized</code>.</li>
            <li><strong>C.</strong> Debit with a unique merchant_request_number (prod: real ₹1–2; sandbox: include <code>suc</code>).</li>
        </ol>
        <p class="hint">Sandbox test account: <code>282800002828</code> / IFSC <code>EBZS0001987</code>.
        Full API log: <a href="autocollect_logs.php">autocollect_logs.php</a></p>
    </div>

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
                    <label>transaction_id (optional)</label>
                    <input type="text" name="transaction_id" placeholder="CLAC_… auto if blank" value="<?= $v_txn ?>">
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
                <option value="SEAMLESS" selected>SEAMLESS (fill bank details below)</option>
                <option value="DEFAULT">DEFAULT (non-seamless checkout)</option>
            </select>

            <div id="seamless_bank_box" style="margin-top:16px;padding:14px;background:#f8f9fa;border-radius:6px;border:1px solid #dee2e6;">
                <h3 style="margin-top:0;">Bank details (required for SEAMLESS)</h3>
                <p class="hint">We send these to Easebuzz encrypted. Customer only does netbanking / debit card auth — no bank form on Easebuzz.</p>
                <div class="row">
                    <div>
                        <label>account_holder_name</label>
                        <input type="text" name="account_holder_name" id="account_holder_name" placeholder="Name as on bank account">
                    </div>
                    <div>
                        <label>account_number</label>
                        <input type="text" name="account_number" id="account_number" placeholder="Your account number">
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label>account_type</label>
                        <select name="account_type" id="account_type">
                            <option value="savings" selected>savings</option>
                            <option value="current">current</option>
                        </select>
                    </div>
                    <div>
                        <label>ifsc</label>
                        <input type="text" name="ifsc" id="ifsc" placeholder="e.g. HDFC0001234" style="text-transform:uppercase;">
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label>bank_code (4 letters — Autocollect; HDFC IFSC → HDFC)</label>
                        <input type="text" name="bank_code" id="bank_code" placeholder="e.g. HDFC" pattern="[A-Za-z]{4}" title="Exactly 4 uppercase letters" style="text-transform:uppercase;">
                        <span class="hint">Optional if IFSC is filled — auto-filled from first 4 chars of IFSC.</span>
                    </div>
                    <div>
                        <label>auth_mode</label>
                        <select name="auth_mode" id="auth_mode">
                            <option value="netbanking" selected>netbanking</option>
                            <option value="debit_card">debit_card</option>
                            <option value="aadhaar">aadhaar</option>
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
                        <input type="text" name="account_holder_name" placeholder="Name as on bank account" required>
                    </div>
                    <div>
                        <label>account_number</label>
                        <input type="text" name="account_number" placeholder="Your account number" required>
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label>account_type</label>
                        <select name="account_type">
                            <option value="savings" selected>savings</option>
                            <option value="current">current</option>
                        </select>
                    </div>
                    <div>
                        <label>ifsc</label>
                        <input type="text" name="ifsc" placeholder="e.g. HDFC0001234" required style="text-transform:uppercase;">
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label>bank_code (4 letters — Autocollect; HDFC IFSC → HDFC)</label>
                        <input type="text" name="bank_code" placeholder="e.g. HDFC" pattern="[A-Za-z]{4}" title="Exactly 4 uppercase letters" style="text-transform:uppercase;">
                    </div>
                    <div>
                        <label>auth_mode</label>
                        <select name="auth_mode">
                            <option value="netbanking" selected>netbanking</option>
                            <option value="debit_card">debit_card</option>
                            <option value="aadhaar">aadhaar</option>
                        </select>
                    </div>
                </div>
                <button type="submit">Submit seamless mandate</button>
            </form>
        </div>
    </div>

    <!-- D. Retrieve (before C so testers see status first) -->
    <div class="card">
        <h2>D. Mandate Retrieve</h2>
        <p class="hint">GET <code>/v1/mandate/{transaction_id}</code> — confirm <code>authorized</code> before debit.</p>
        <form method="POST">
            <input type="hidden" name="action" value="retrieve_mandate">
            <label>transaction_id</label>
            <input type="text" name="transaction_id" value="<?= $v_txn ?>" required>
            <br>
            <button type="submit">Retrieve mandate</button>
        </form>
    </div>

    <!-- C. Debit -->
    <div class="card">
        <h2>C. Request eNACH Debit</h2>
        <p class="hint">POST <code>/v1/mandate/presentment/</code> — eNACH only (not UPI/SI). Sandbox: include <code>suc</code> in merchant_request_number for success mock.</p>
        <form method="POST">
            <input type="hidden" name="action" value="request_debit">
            <label>transaction_id</label>
            <input type="text" name="transaction_id" value="<?= $v_txn ?>" required>
            <div class="row">
                <div>
                    <label>amount</label>
                    <input type="text" name="amount" value="1.00" required>
                </div>
                <div>
                    <label>merchant_request_number (optional)</label>
                    <input type="text" name="merchant_request_number" placeholder="CLDR_… or enachReq99suc01">
                </div>
            </div>
            <label>presentment_date (optional YYYY-MM-DD)</label>
            <input type="date" name="presentment_date">
            <br>
            <button type="submit">Initiate presentment</button>
        </form>
    </div>

    <div class="card">
        <h2>API log (recent)</h2>
        <p class="hint">Last 15 calls from this playground session. <a href="autocollect_logs.php">View all / clear logs</a></p>
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

    function resolveBankCodeFromIfsc(ifsc) {
        ifsc = (ifsc || '').toUpperCase().trim();
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
            // Always sync from IFSC — Autocollect wants 4-char prefix (HDFC), not legacy HDFCB.
            if (/^[A-Za-z]{4}$/.test(code)) {
                bankInput.value = code.toUpperCase();
            }
        });
    });

    if (sel) {
        sel.addEventListener('change', syncRequestType);
        syncRequestType();
    }
})();
</script>
</body>
</html>

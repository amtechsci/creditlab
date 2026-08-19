<?php
require_once __DIR__ . '/../lib/guard_cli.php';
/**
 * e-NACH presentment smoke test (Autocollect + optional legacy PG).
 *
 * Usage:
 *   php scripts/test_presentment_10.php [uid] [amount] [--retrieve] [--legacy]
 *
 * On production (.env readable only by www-data):
 *   sudo -u www-data php scripts/test_presentment_10.php 42163 10 --retrieve
 *   sudo -u www-data php scripts/test_presentment_10.php 42163 10 --legacy
 *
 * Default routing uses Autocollect with customer_authentication_id (new + migrated old mandates).
 * --legacy forces old initiateDirectDebitRequest (requires auto_debit_access_key).
 */
date_default_timezone_set('Asia/Kolkata');

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . '/.env';

require_once $projectRoot . '/lib/env.php';
require_once $projectRoot . '/lib/database.php';

if (!is_readable($envPath)) {
    fwrite(STDERR, "Cannot read {$envPath}\n");
    fwrite(STDERR, "Run as the web user: sudo -u www-data php scripts/test_presentment_10.php 42163 10\n");
    fwrite(STDERR, "Or export DB_HOST, DB_USER, DB_PASSWORD, DB_NAME in your shell.\n");
    exit(1);
}

$creds = creditlab_db_credentials();
if ($creds['pass'] === '' || $creds['pass'] === null) {
    fwrite(STDERR, "DB_PASSWORD is empty. Check {$envPath} or use: sudo -u www-data php scripts/test_presentment_10.php 42163 10\n");
    exit(1);
}

$uid = 54236;
$amount = '10.00';
$doRetrieve = false;
$forceLegacy = false;
$positional = [];

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--retrieve') {
        $doRetrieve = true;
        continue;
    }
    if ($arg === '--legacy') {
        $forceLegacy = true;
        continue;
    }
    $positional[] = $arg;
}

if (isset($positional[0]) && is_numeric($positional[0])) {
    $uid = (int) $positional[0];
}
if (isset($positional[1]) && is_numeric($positional[1])) {
    $amount = number_format((float) $positional[1], 2, '.', '');
}

$db = creditlab_db_connect();
if (!$db) {
    fwrite(STDERR, "Database connection failed for user '{$creds['user']}'@'{$creds['host']}'.\n");
    fwrite(STDERR, "Verify DB_* in {$envPath}\n");
    exit(1);
}

$GLOBALS['db'] = $db;
if (!defined('CREDITLAB_SKIP_SESSION')) {
    define('CREDITLAB_SKIP_SESSION', true);
}
require_once $projectRoot . '/db.php';
require_once $projectRoot . '/lib/easebuzz_enach.php';
require_once $projectRoot . '/lib/easebuzz_autocollect.php';

$uid_sql = (int) $uid;
$q = towquery("SELECT * FROM easebuzz_adtd WHERE uid=$uid_sql ORDER BY id DESC LIMIT 1");
$row = $q ? towfetch($q) : null;
if (!$row) {
    fwrite(STDERR, "No easebuzz_adtd row for uid=$uid\n");
    exit(1);
}

$transaction_id = creditlab_easebuzz_autocollect_transaction_id($row);
$autoRoute = creditlab_easebuzz_presentment_api_for_row($row);
$apiMode = $forceLegacy ? 'legacy_pg' : $autoRoute;

echo "UID: $uid\n";
echo "request_flow: {$row['request_flow']}\n";
echo "txnid: {$row['txnid']}\n";
echo "customer_authentication_id (Autocollect transaction_id): {$row['customer_authentication_id']}\n";
echo "auto_debit_access_key (legacy PG only): {$row['auto_debit_access_key']}\n";
echo "Status: {$row['authorization_status']} | UMRN: {$row['easepayid']}\n";
echo "Max mandate (udf5): {$row['udf5']}\n";
echo "Auto route: $autoRoute\n";
echo "Test mode: $apiMode" . ($forceLegacy ? ' (--legacy)' : '') . "\n";
echo "Amount: ₹$amount\n\n";

if ($doRetrieve) {
    echo "=== Autocollect retrieve (GET /v1/mandate/{customer_authentication_id}) ===\n";
    $retrieve = creditlab_autocollect_retrieve_mandate($transaction_id);
    $parsed = creditlab_autocollect_parse_mandate_retrieve_data($retrieve);
    echo json_encode([
        'ok' => !empty($retrieve['ok']),
        'http_code' => $retrieve['http_code'] ?? null,
        'transaction_id' => $transaction_id,
        'status' => $parsed['status'] ?? '',
        'sub_status' => $parsed['sub_status'] ?? '',
        'umrn' => $parsed['umrn'] ?? '',
        'message' => $parsed['message'] ?? '',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
}

$merchant_ref = 'CLTEST_' . $uid . '_' . time();
$payment = [
    'amount' => $amount,
    'productinfo' => 'Test Presentment',
    'firstname' => $row['firstname'],
    'email' => $row['email'],
    'phone' => $row['phone'],
    'merchant_debit_id' => $merchant_ref,
    'udf1' => 'CREDITLAB_TEST_10',
];

if ($forceLegacy) {
    echo "=== Legacy PG presentment ===\n";
    $legacy = creditlab_easebuzz_legacy_initiate_direct_debit(array_merge($payment, [
        'customer_authentication_id' => $row['customer_authentication_id'],
        'auto_debit_access_key' => $row['auto_debit_access_key'],
    ]));
    $result = [
        'ok' => !empty($legacy['ok']),
        'api' => 'legacy_pg',
        'error_desc' => $legacy['error_desc'] ?? '',
        'data' => $legacy['data'] ?? null,
        'merchant_debit_id' => $legacy['merchant_debit_id'] ?? $merchant_ref,
        'transaction_id' => $row['customer_authentication_id'],
    ];
} else {
    echo "=== Presentment ($apiMode) ===\n";
    $result = creditlab_easebuzz_initiate_loan_debit($row, $payment);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit(!empty($result['ok']) ? 0 : 1);

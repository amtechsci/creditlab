<?php
require_once __DIR__ . '/../lib/guard_cli.php';
/**
 * Autocollect presentment smoke test.
 *
 * Usage:
 *   php scripts/test_presentment_10.php [uid] [amount]
 *
 * On production (.env readable only by www-data):
 *   sudo -u www-data php scripts/test_presentment_10.php 54236 10
 */
date_default_timezone_set('Asia/Kolkata');

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . '/.env';

require_once $projectRoot . '/lib/env.php';
require_once $projectRoot . '/lib/database.php';
require_once $projectRoot . '/lib/easebuzz_enach.php';

if (!is_readable($envPath)) {
    fwrite(STDERR, "Cannot read {$envPath}\n");
    fwrite(STDERR, "Run as the web user: sudo -u www-data php scripts/test_presentment_10.php 54236 10\n");
    fwrite(STDERR, "Or export DB_HOST, DB_USER, DB_PASSWORD, DB_NAME in your shell.\n");
    exit(1);
}

$creds = creditlab_db_credentials();
if ($creds['pass'] === '' || $creds['pass'] === null) {
    fwrite(STDERR, "DB_PASSWORD is empty. Check {$envPath} or use: sudo -u www-data php scripts/test_presentment_10.php 54236 10\n");
    exit(1);
}

$uid = isset($argv[1]) ? (int) $argv[1] : 54236;
$amount = isset($argv[2]) ? number_format((float) $argv[2], 2, '.', '') : '10.00';

$db = creditlab_db_connect();
if (!$db) {
    fwrite(STDERR, "Database connection failed for user '{$creds['user']}'@'{$creds['host']}'.\n");
    fwrite(STDERR, "Verify DB_* in {$envPath}\n");
    exit(1);
}

$uid_sql = (int) $uid;
$q = mysqli_query($db, "SELECT * FROM easebuzz_adtd WHERE uid=$uid_sql ORDER BY id DESC LIMIT 1");
$row = mysqli_fetch_assoc($q);
if (!$row) {
    fwrite(STDERR, "No easebuzz_adtd row for uid=$uid\n");
    exit(1);
}

echo "UID: $uid\n";
echo "Mandate: {$row['customer_authentication_id']}\n";
echo "Status: {$row['authorization_status']} | UMRN: {$row['easepayid']}\n";
echo "Max mandate (udf5): {$row['udf5']}\n";
echo "API route: " . creditlab_easebuzz_presentment_api_for_row($row) . "\n";
echo "Amount: ₹$amount\n\n";

$merchant_ref = 'CLTEST_' . $uid . '_' . time();
$result = creditlab_easebuzz_initiate_loan_debit($row, [
    'amount' => $amount,
    'productinfo' => 'Test Presentment',
    'firstname' => $row['firstname'],
    'email' => $row['email'],
    'phone' => $row['phone'],
    'merchant_debit_id' => $merchant_ref,
    'udf1' => 'CREDITLAB_TEST_10',
]);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit(!empty($result['ok']) ? 0 : 1);

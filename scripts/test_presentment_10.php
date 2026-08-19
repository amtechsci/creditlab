<?php
/**
 * One-off Autocollect presentment test.
 * Usage: php scripts/test_presentment_10.php [uid] [amount]
 */
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/easebuzz_enach.php';

$uid = isset($argv[1]) ? (int) $argv[1] : 54236;
$amount = isset($argv[2]) ? number_format((float) $argv[2], 2, '.', '') : '10.00';

$db = creditlab_db_connect();
if (!$db) {
    fwrite(STDERR, "DB connect failed\n");
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

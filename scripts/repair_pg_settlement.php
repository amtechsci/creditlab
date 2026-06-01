<?php
/**
 * Repair a successful PG payment where loan was not cleared (and resend cleared SMS).
 *
 *   sudo -u www-data php scripts/repair_pg_settlement.php TXN_abcCL123
 *   sudo -u www-data php scripts/repair_pg_settlement.php PG_admin_1_99_ABCD1234
 */
if (php_sapi_name() !== 'cli') {
    exit('CLI only');
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/pg_link_settlement.php';

$txnid = $argv[1] ?? '';
if ($txnid === '') {
    fwrite(STDERR, "Usage: php scripts/repair_pg_settlement.php <txnid>\n");
    exit(1);
}

$pgTxQ = towquery("SELECT * FROM pg_transaction WHERE txnid='" . towreal($txnid) . "' LIMIT 1");
if (!$pgTxQ || townum($pgTxQ) < 1) {
    fwrite(STDERR, "No pg_transaction for txnid: $txnid\n");
    exit(1);
}
$pgTx = towfetch($pgTxQ);
$amount = (float) ($pgTx['amount'] ?? 0);
$bankRef = (string) ($pgTx['bank_reference_number'] ?? $pgTx['bank_ref_num'] ?? 'REPAIR');
$method = (string) ($pgTx['payment_method'] ?? 'easebuzz');

$result = creditlab_process_pg_payment_success($db, $txnid, $amount, $bankRef, $method);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
exit(($result['ok'] ?? false) ? 0 : 1);

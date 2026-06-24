<?php
require_once __DIR__ . '/../lib/guard_cli.php';
/**
 * Repair loans where pg_transaction is success but loan was not cleared.
 *
 * Usage:
 *   php scripts/repair_pg_settlement.php              # list stuck rows
 *   php scripts/repair_pg_settlement.php --apply      # settle all stuck rows
 *   php scripts/repair_pg_settlement.php TXNID ...  # settle specific txnid(s)
 *   php scripts/repair_pg_settlement.php --apply TXNID
 */
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/pg_link_settlement.php';

$argv = $_SERVER['argv'] ?? [];
$apply = in_array('--apply', $argv, true);
$txnids = [];
foreach ($argv as $i => $arg) {
    if ($i === 0 || $arg === '--apply') {
        continue;
    }
    $txnids[] = $arg;
}

if ($txnids !== []) {
    $escaped = array_map(static function ($t) {
        return "'" . towreal($t) . "'";
    }, $txnids);
    $sql = 'SELECT p.txnid, p.amount, p.bank_reference_number, p.payment_method, p.status, loan.lid, loan.status_log
        FROM pg_transaction p
        INNER JOIN loan ON loan.id = p.loan_id
        WHERE p.txnid IN (' . implode(',', $escaped) . ')';
} else {
    $sql = "SELECT p.txnid, p.amount, p.bank_reference_number, p.payment_method, p.status, loan.lid, loan.status_log
        FROM pg_transaction p
        INNER JOIN loan ON loan.id = p.loan_id
        WHERE p.status = 'success' AND loan.status_log != 'cleared'
        ORDER BY p.txnid DESC
        LIMIT 200";
}

$q = towquery($sql);
if (!$q) {
    fwrite(STDERR, "Query failed\n");
    exit(1);
}

$rows = [];
while ($r = towfetch($q)) {
    $rows[] = $r;
}

if ($rows === []) {
    echo "No stuck pg_transaction rows found.\n";
    exit(0);
}

echo "Found " . count($rows) . " row(s) with pg success but loan not cleared:\n";
foreach ($rows as $r) {
    echo "  txnid={$r['txnid']} CLL{$r['lid']} amount={$r['amount']} loan_status={$r['status_log']}\n";
}

if (!$apply) {
    echo "\nDry run only. Re-run with --apply to settle.\n";
    exit(0);
}

$ok = 0;
$fail = 0;
foreach ($rows as $r) {
    $bankRef = (string) ($r['bank_reference_number'] ?? '');
    if ($bankRef === '' || $bankRef === 'NA') {
        $bankRef = 'REPAIR_' . $r['txnid'];
    }
    $pm = (string) ($r['payment_method'] ?? 'easebuzz');
    $settle = creditlab_process_pg_payment_success($db, (string) $r['txnid'], (float) $r['amount'], $bankRef, $pm);
    if ($settle['ok']) {
        echo "OK  txnid={$r['txnid']} flow=" . ($settle['flow'] ?? '') . "\n";
        $ok++;
    } else {
        echo "FAIL txnid={$r['txnid']} " . ($settle['message'] ?? '') . "\n";
        $fail++;
    }
}

echo "\nDone: $ok settled, $fail failed.\n";
exit($fail > 0 ? 1 : 0);

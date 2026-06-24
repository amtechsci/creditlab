<?php
require_once __DIR__ . '/../lib/guard_cli.php';
/**
 * Repair loans where pg_transaction is success but loan was not cleared.
 *
 * Usage:
 *   php scripts/repair_pg_settlement.php              # list stuck rows
 *   php scripts/repair_pg_settlement.php --apply      # settle all stuck rows
 *   php scripts/repair_pg_settlement.php TXNID ...    # settle specific txnid(s)
 *   php scripts/repair_pg_settlement.php --apply TXNID
 *
 * On production (.env readable only by www-data):
 *   sudo -u www-data php scripts/repair_pg_settlement.php
 *   sudo -u www-data php scripts/repair_pg_settlement.php --apply
 */
date_default_timezone_set('Asia/Kolkata');

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . '/.env';

require_once $projectRoot . '/lib/env.php';
require_once $projectRoot . '/lib/database.php';

if (!is_readable($envPath)) {
    fwrite(STDERR, "Cannot read {$envPath}\n");
    fwrite(STDERR, "Run as the web user: sudo -u www-data php scripts/repair_pg_settlement.php\n");
    fwrite(STDERR, "Or export DB_HOST, DB_USER, DB_PASSWORD, DB_NAME in your shell.\n");
    exit(1);
}

$creds = creditlab_db_credentials();
if ($creds['pass'] === '' || $creds['pass'] === null) {
    fwrite(STDERR, "DB_PASSWORD is empty. Check {$envPath} or use: sudo -u www-data php scripts/repair_pg_settlement.php\n");
    exit(1);
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
require_once $projectRoot . '/lib/pg_link_settlement.php';

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

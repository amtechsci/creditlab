<?php
/**
 * Cron: poll Autocollect presentment status and auto-close loans on debit success.
 *
 * Usage:
 *   php payment/auto_enach_settle.php
 *   php payment/auto_enach_settle.php --dry_run
 *   php payment/auto_enach_settle.php --lookback=14 --limit=200
 *   php payment/auto_enach_settle.php --probe=CLL_AUTO_83380_1789182001
 *   php payment/auto_enach_settle.php --reopen-failures
 *
 * Suggested crontab (every 30 minutes, after presentment cron at 03:00 IST):
 *   every-30-min: www-data /usr/bin/php /path/to/creditlab/payment/auto_enach_settle.php
 *   (cron expression: star-slash-30 star star star star)
 */
set_time_limit(0);
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/enach_presentment_settle.php';
require_once __DIR__ . '/../lib/easebuzz_autocollect.php';

$db = creditlab_db_connect();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}
mysqli_set_charset($db, 'utf8');

$dry_run = in_array('--dry_run', $argv ?? [], true)
    || (isset($_GET['dry_run']) && (string) $_GET['dry_run'] === '1');
$reopen_failures = in_array('--reopen-failures', $argv ?? [], true);
$probe_ref = '';

$lookback = 10;
$limit = 100;
foreach (($argv ?? []) as $arg) {
    if (preg_match('/^--lookback=(\d+)$/', $arg, $m)) {
        $lookback = (int) $m[1];
    }
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int) $m[1];
    }
    if (preg_match('/^--probe=(.+)$/', $arg, $m)) {
        $probe_ref = trim($m[1]);
    }
}
if (isset($_GET['lookback'])) {
    $lookback = (int) $_GET['lookback'];
}
if (isset($_GET['limit'])) {
    $limit = (int) $_GET['limit'];
}
if (!empty($_GET['probe'])) {
    $probe_ref = trim((string) $_GET['probe']);
}

$log_dir = dirname(__DIR__) . '/logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0775, true);
}
$log_file = $log_dir . '/enach_settle_' . date('Y-m-d') . '.log';

$write = static function ($msg) use ($log_file) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
    echo $line;
};

if ($probe_ref !== '') {
    $write("=== PROBE presentment status: $probe_ref ===");
    $retrieve = creditlab_autocollect_retrieve_presentment($probe_ref);
    $parsed = creditlab_autocollect_parse_presentment_status($retrieve);
    $write('http_code=' . (int) ($retrieve['http_code'] ?? 0)
        . ' ok=' . (!empty($retrieve['ok']) ? '1' : '0')
        . ' path=' . (string) ($retrieve['path'] ?? ''));
    $write('RAW ' . json_encode($retrieve['data'] ?? $retrieve['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $write('PARSED ' . json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    exit(0);
}

if ($reopen_failures) {
    creditlab_enach_ensure_presentment_run_table();
    $since = date('Y-m-d', strtotime('-' . max(1, $lookback) . ' days'));
    $q = mysqli_query(
        $db,
        "UPDATE enach_presentment_run
         SET settlement_status='pending', poll_error='reopened for re-poll', settled_at=NULL
         WHERE settlement_status='failure'
           AND presented_on >= '$since'
           AND merchant_ref <> ''"
    );
    $n = $q ? (int) mysqli_affected_rows($db) : 0;
    $write("Reopened $n failure rows to pending (lookback={$lookback}d)");
}

$write('=== AUTO ENACH SETTLE START' . ($dry_run ? ' (DRY RUN)' : '') . ' ===');
$write("lookback={$lookback}d limit={$limit}");

$summary = creditlab_enach_settle_pending_presentments($db, [
    'lookback_days' => $lookback,
    'limit' => $limit,
    'dry_run' => $dry_run,
]);

$write(sprintf(
    'SUMMARY checked=%d cleared=%d failed=%d pending=%d errors=%d',
    $summary['checked'],
    $summary['cleared'],
    $summary['failed'],
    $summary['pending'],
    $summary['errors']
));

foreach ($summary['details'] as $row) {
    $write('DETAIL ' . json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

$write('=== AUTO ENACH SETTLE END ===');
exit(0);

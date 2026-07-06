<?php
require_once __DIR__ . '/../lib/guard_cli.php';
/**
 * Backfill agency_id / agency_name on legacy agency PG links.
 *
 * Usage:
 *   php scripts/backfill_pg_link_agency.php            # dry run (count only)
 *   php scripts/backfill_pg_link_agency.php --list     # show rows that need fixing
 *   php scripts/backfill_pg_link_agency.php --apply    # write to database
 *
 * Production:
 *   sudo -u www-data php scripts/backfill_pg_link_agency.php --list
 *   sudo -u www-data php scripts/backfill_pg_link_agency.php --apply
 */
date_default_timezone_set('Asia/Kolkata');

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . '/.env';

require_once $projectRoot . '/lib/env.php';
require_once $projectRoot . '/lib/database.php';

if (!is_readable($envPath)) {
    fwrite(STDERR, "Cannot read {$envPath}\n");
    fwrite(STDERR, "Run as the web user: sudo -u www-data php scripts/backfill_pg_link_agency.php\n");
    exit(1);
}

$db = creditlab_db_connect();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$GLOBALS['db'] = $db;
if (!defined('CREDITLAB_SKIP_SESSION')) {
    define('CREDITLAB_SKIP_SESSION', true);
}
require_once $projectRoot . '/db.php';
require_once $projectRoot . '/lib/pg_link_agency.php';

$argv = $_SERVER['argv'] ?? [];
$apply = in_array('--apply', $argv, true);
$list = in_array('--list', $argv, true);

if ($list) {
    $candidates = creditlab_pg_link_agency_backfill_candidates();
    if ($candidates === []) {
        echo "No agency PG links need backfill.\n";
        exit(0);
    }
    foreach ($candidates as $item) {
        $link = $item['link'];
        $resolved = $item['resolved'];
        echo sprintf(
            "link #%d txnid=%s CLL%d stored=%s/%s resolved=%s/%s created_by_id=%s\n",
            (int) $link['id'],
            $link['txnid'] ?? '',
            (int) ($link['loan_lid'] ?? 0),
            $link['agency_id'] ?? 'NULL',
            $link['agency_name'] ?? 'NULL',
            $resolved['agency_id'],
            $resolved['agency_name'],
            $link['created_by_id'] ?? '0'
        );
    }
    echo count($candidates) . " row(s) would be updated. Run with --apply to persist.\n";
    exit(0);
}

$stats = creditlab_backfill_pg_link_agency_names($apply);

echo ($apply ? 'Updated' : 'Would update') . " {$stats['links']} pg_payment_link row(s) ({$stats['candidates']} candidate(s)).\n";
if ($apply) {
    echo "Synced pg_transaction on {$stats['transactions']} row(s).\n";
} else {
    echo "Run with --list to inspect rows, or --apply to persist agency names.\n";
}

<?php
require_once __DIR__ . '/../lib/guard_cli.php';
/**
 * Backfill agency_id / agency_name on legacy agency PG links.
 *
 * Usage:
 *   php scripts/backfill_pg_link_agency.php           # dry run (count only)
 *   php scripts/backfill_pg_link_agency.php --apply   # write to database
 *
 * Production:
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

$apply = in_array('--apply', $_SERVER['argv'] ?? [], true);
$stats = creditlab_backfill_pg_link_agency_names($apply);

echo ($apply ? 'Updated' : 'Would update') . " {$stats['links']} pg_payment_link row(s).\n";
if ($apply) {
    echo "Synced pg_transaction on {$stats['transactions']} row(s).\n";
} else {
    echo "Run with --apply to persist agency names on old links.\n";
}

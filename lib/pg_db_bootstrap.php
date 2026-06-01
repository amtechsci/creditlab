<?php
/**
 * Bind mysqli for PG settlement when running outside db.php (e.g. easebuzz_webhook.php).
 */
function creditlab_pg_bind_mysqli($mysqliConn): void
{
    $GLOBALS['db'] = $mysqliConn;
}

/**
 * Load towquery/towreal/etc. and keep the active connection (webhook passes its own $db).
 */
function creditlab_ensure_app_db_helpers($mysqliConn): void
{
    creditlab_pg_bind_mysqli($mysqliConn);
    if (!function_exists('towquery')) {
        require_once __DIR__ . '/../db.php';
        creditlab_pg_bind_mysqli($mysqliConn);
    }
}

<?php
/**
 * One-time migration: agency tables, PG payment links, related columns.
 *
 * Run on the server (CLI only), as the same user that owns .env (often www-data):
 *   cd /var/www/creditlab.in
 *   sudo -u www-data php migrations/20260529_pg_links_and_agency_admin.php
 *
 * Or export credentials for this shell:
 *   export DB_HOST=localhost DB_USER=... DB_PASSWORD=... DB_NAME=credit
 *   php migrations/20260529_pg_links_and_agency_admin.php
 *
 * SQL source: sql/20260529_pg_links_and_agency_admin.sql
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . '/.env';

require_once $projectRoot . '/lib/env.php';
require_once $projectRoot . '/lib/database.php';

if (!is_readable($envPath)) {
    fwrite(STDERR, "Cannot read $envPath\n");
    fwrite(STDERR, "Create .env from .env.example, or run: sudo -u www-data php migrations/20260529_pg_links_and_agency_admin.php\n");
    exit(1);
}

$creds = creditlab_db_credentials();
if ($creds['pass'] === '' || $creds['pass'] === null) {
    fwrite(STDERR, "DB_PASSWORD is empty. Check $envPath (DB_PASSWORD=...).\n");
    fwrite(STDERR, "If .env is only readable by www-data, use: sudo -u www-data php migrations/20260529_pg_links_and_agency_admin.php\n");
    exit(1);
}

$db = creditlab_db_connect();
if (!$db) {
    fwrite(STDERR, "Database connection failed for user '{$creds['user']}'@{$creds['host']} database '{$creds['name']}'.\n");
    fwrite(STDERR, "Verify DB_* in $envPath\n");
    exit(1);
}

echo "Connected to {$creds['name']} as {$creds['user']}@{$creds['host']}\n";

$sqlFile = $projectRoot . '/sql/20260529_pg_links_and_agency_admin.sql';
if (!is_readable($sqlFile)) {
    fwrite(STDERR, "SQL file not found: $sqlFile\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
$sql = preg_replace('/^--.*$/m', '', $sql);
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

$failed = 0;
foreach ($statements as $stmt) {
    if ($stmt === '') {
        continue;
    }
    if (!mysqli_query($db, $stmt)) {
        $err = mysqli_error($db);
        if (stripos($err, 'Duplicate column') !== false
            || stripos($err, 'duplicate column name') !== false
            || stripos($err, 'already exists') !== false) {
            echo "SKIP (exists): " . substr(str_replace("\n", ' ', $stmt), 0, 72) . "...\n";
            continue;
        }
        fwrite(STDERR, "ERROR: $err\n  " . substr(str_replace("\n", ' ', $stmt), 0, 120) . "...\n");
        $failed++;
        continue;
    }
    echo "OK: " . substr(str_replace("\n", ' ', $stmt), 0, 72) . "...\n";
}

$required = ['agency', 'agency_admin', 'pg_payment_link'];
foreach ($required as $table) {
    $chk = mysqli_query($db, "SHOW TABLES LIKE '" . mysqli_real_escape_string($db, $table) . "'");
    if (!$chk || mysqli_num_rows($chk) < 1) {
        fwrite(STDERR, "Missing table after migration: $table\n");
        $failed++;
    }
}

mysqli_close($db);

if ($failed > 0) {
    fwrite(STDERR, "Migration finished with $failed error(s).\n");
    exit(1);
}

echo "Migration completed successfully.\n";
exit(0);

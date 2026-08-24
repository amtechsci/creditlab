<?php
/**
 * Add indexes used by admin/performance_dashboard.php.
 *
 * Run on the server (CLI only), as the same user that owns .env (often www-data):
 *   cd /var/www/creditlab.in
 *   sudo -u www-data php migrations/20260824_performance_dashboard_indexes.php
 *
 * Or export credentials for this shell:
 *   export DB_HOST=localhost DB_USER=... DB_PASSWORD=... DB_NAME=credit
 *   php migrations/20260824_performance_dashboard_indexes.php
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . '/.env';

require_once $projectRoot . '/lib/env.php';
require_once $projectRoot . '/lib/database.php';

if (!is_readable($envPath) && getenv('DB_PASSWORD') === false) {
    fwrite(STDERR, "Cannot read $envPath\n");
    fwrite(STDERR, "Run: sudo -u www-data php migrations/20260824_performance_dashboard_indexes.php\n");
    exit(1);
}

$creds = creditlab_db_credentials();
if ($creds['pass'] === '' || $creds['pass'] === null) {
    fwrite(STDERR, "DB_PASSWORD is empty. Check $envPath (DB_PASSWORD=...).\n");
    fwrite(STDERR, "If .env is only readable by www-data, use:\n");
    fwrite(STDERR, "  sudo -u www-data php migrations/20260824_performance_dashboard_indexes.php\n");
    exit(1);
}

$db = creditlab_db_connect();
if (!$db) {
    fwrite(STDERR, "Database connection failed for user '{$creds['user']}'@{$creds['host']} database '{$creds['name']}'.\n");
    fwrite(STDERR, "Verify DB_* in $envPath, or run as www-data.\n");
    exit(1);
}

echo "Connected to {$creds['name']} as {$creds['user']}@{$creds['host']}\n";

$sqlFile = $projectRoot . '/sql/20260824_performance_dashboard_indexes.sql';
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
        if (stripos($err, 'Duplicate key name') !== false || stripos($err, 'already exists') !== false) {
            echo "SKIP (exists): " . substr(str_replace("\n", ' ', $stmt), 0, 80) . "...\n";
            continue;
        }
        fwrite(STDERR, "ERROR: $err\n  $stmt\n");
        $failed++;
        continue;
    }
    echo "OK: " . substr(str_replace("\n", ' ', $stmt), 0, 80) . "...\n";
}

exit($failed > 0 ? 1 : 0);

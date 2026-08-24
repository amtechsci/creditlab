<?php
/**
 * Add indexes used by admin/performance_dashboard.php.
 *
 *   php migrations/20260824_performance_dashboard_indexes.php
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/lib/env.php';
require_once $projectRoot . '/lib/database.php';

$db = creditlab_db_connect();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$sqlFile = $projectRoot . '/sql/20260824_performance_dashboard_indexes.sql';
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

<?php
/**
 * One-time migration: agency tables, PG payment links, related columns.
 *
 * Run on the server (CLI only):
 *   php migrations/20260529_pg_links_and_agency_admin.php
 *
 * SQL source: sql/20260529_pg_links_and_agency_admin.sql
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once __DIR__ . '/../db.php';

$sqlFile = __DIR__ . '/../sql/20260529_pg_links_and_agency_admin.sql';
if (!is_readable($sqlFile)) {
    fwrite(STDERR, "SQL file not found: $sqlFile\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
// Drop full-line comments, then split on semicolon + newline
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

if ($failed > 0) {
    fwrite(STDERR, "Migration finished with $failed error(s).\n");
    exit(1);
}

echo "Migration completed successfully.\n";
exit(0);

<?php
/**
 * Run once: php scripts/install_pg_agency_schema.php
 */
require_once __DIR__ . '/../db.php';

$sqlFile = __DIR__ . '/../sql/20260529_pg_links_and_agency_admin.sql';
if (!is_readable($sqlFile)) {
    fwrite(STDERR, "SQL file not found\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

foreach ($statements as $stmt) {
    if ($stmt === '' || strpos($stmt, '--') === 0) {
        continue;
    }
    if (!mysqli_query($db, $stmt)) {
        $err = mysqli_error($db);
        if (stripos($err, 'Duplicate column') !== false || stripos($err, 'already exists') !== false) {
            echo "Skip (exists): " . substr($stmt, 0, 60) . "...\n";
            continue;
        }
        echo "Error: $err\nStatement: $stmt\n";
    } else {
        echo "OK: " . substr($stmt, 0, 70) . "...\n";
    }
}

echo "Done.\n";

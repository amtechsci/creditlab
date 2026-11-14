<?php
/**
 * Ensure transaction_details.payment_channel exists and backfill historical rows.
 * Run: php migrations/20261114_transaction_payment_channel.php
 *
 * Safe to re-run. Does not delete data.
 * Backfill runs in small batches to avoid lock wait timeouts on a live server.
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/payment_channel.php';

global $db;

creditlab_ensure_payment_channel_column();
echo "Column ready.\n";

// Prefer finishing quickly over blocking production traffic.
@mysqli_query($db, 'SET SESSION innodb_lock_wait_timeout = 120');
@mysqli_query($db, 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

$batchSize = 300;
$maxBatches = 50000; // safety cap

function creditlab_migration_fetch_ids($sql)
{
    $ids = [];
    $res = towquery($sql);
    if ($res === false) {
        return false;
    }
    while ($row = towfetch($res)) {
        $ids[] = (int) $row['id'];
    }
    return $ids;
}

function creditlab_migration_update_ids(array $ids, string $channel): bool
{
    if (empty($ids)) {
        return true;
    }
    $list = implode(',', array_map('intval', $ids));
    $channelEsc = mysqli_real_escape_string($GLOBALS['db'], $channel);
    $ok = towquery("UPDATE `transaction_details`
        SET `payment_channel` = '$channelEsc'
        WHERE `id` IN ($list)");
    return $ok !== false;
}

echo "PG backfill (batched)...\n";
$pgTotal = 0;
for ($i = 0; $i < $maxBatches; $i++) {
    $ids = creditlab_migration_fetch_ids("
        SELECT td.id
        FROM `transaction_details` td
        INNER JOIN `pg_transaction` pt
          ON CONVERT(pt.bank_reference_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
           = CONVERT(td.transaction_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
         AND pt.bank_reference_number IS NOT NULL
         AND pt.bank_reference_number != ''
         AND pt.status = 'success'
        WHERE (td.payment_channel = 'manual' OR td.payment_channel = '' OR td.payment_channel IS NULL)
        LIMIT $batchSize
    ");
    if ($ids === false) {
        echo "PG backfill FAILED on SELECT — see SQL Error above.\n";
        echo "Column is fine. App works; historical PG labels can be filled later.\n";
        exit(1);
    }
    if (empty($ids)) {
        break;
    }
    if (!creditlab_migration_update_ids($ids, 'pg')) {
        echo "PG backfill FAILED on UPDATE batch — see SQL Error above.\n";
        echo "Updated so far: $pgTotal. Re-run to continue.\n";
        exit(1);
    }
    $pgTotal += count($ids);
    if (($i + 1) % 10 === 0) {
        echo "  … $pgTotal rows marked pg\n";
        // Brief pause so other requests can take locks.
        usleep(100000);
    }
}
echo "PG backfill done ($pgTotal rows).\n";

$enachCheck = @towquery("SHOW TABLES LIKE 'easebuzz_adtd'");
if ($enachCheck && townum($enachCheck) > 0) {
    echo "eNACH backfill (batched)...\n";
    $enachTotal = 0;
    for ($i = 0; $i < $maxBatches; $i++) {
        $ids = creditlab_migration_fetch_ids("
            SELECT td.id
            FROM `transaction_details` td
            INNER JOIN `easebuzz_adtd` ea
              ON (
                (ea.bank_ref_num IS NOT NULL AND ea.bank_ref_num != ''
                  AND CONVERT(ea.bank_ref_num USING utf8mb4) COLLATE utf8mb4_unicode_ci
                    = CONVERT(td.transaction_number USING utf8mb4) COLLATE utf8mb4_unicode_ci)
                OR (ea.txnid IS NOT NULL AND ea.txnid != ''
                  AND CONVERT(ea.txnid USING utf8mb4) COLLATE utf8mb4_unicode_ci
                    = CONVERT(td.transaction_number USING utf8mb4) COLLATE utf8mb4_unicode_ci)
              )
            WHERE (td.payment_channel = 'manual' OR td.payment_channel = '' OR td.payment_channel IS NULL)
            LIMIT $batchSize
        ");
        if ($ids === false) {
            echo "eNACH backfill FAILED on SELECT — see SQL Error above.\n";
            echo "PG rows already updated. Re-run later for eNACH.\n";
            exit(1);
        }
        if (empty($ids)) {
            break;
        }
        if (!creditlab_migration_update_ids($ids, 'enach')) {
            echo "eNACH backfill FAILED on UPDATE batch — see SQL Error above.\n";
            echo "Updated so far: $enachTotal. Re-run to continue.\n";
            exit(1);
        }
        $enachTotal += count($ids);
        if (($i + 1) % 10 === 0) {
            echo "  … $enachTotal rows marked enach\n";
            usleep(100000);
        }
    }
    echo "eNACH backfill done ($enachTotal rows).\n";
} else {
    echo "eNACH table not found — skipped.\n";
}

echo "Done.\n";
echo "Note: new payments already store payment_channel without this backfill.\n";

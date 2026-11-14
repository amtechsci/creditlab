<?php
/**
 * Ensure transaction_details.payment_channel exists and backfill historical rows.
 * Run once: php migrations/20261114_transaction_payment_channel.php
 *
 * Safe to re-run. Does not delete data.
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/payment_channel.php';

creditlab_ensure_payment_channel_column();

echo "Column ready.\n";

// Compare refs with an explicit collation so utf8mb4_general_ci vs unicode_ci does not fail.
$pg = towquery("UPDATE `transaction_details` td
INNER JOIN `pg_transaction` pt
  ON CONVERT(pt.bank_reference_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
   = CONVERT(td.transaction_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND pt.bank_reference_number IS NOT NULL
 AND pt.bank_reference_number != ''
 AND pt.status = 'success'
SET td.payment_channel = 'pg'
WHERE td.payment_channel = 'manual' OR td.payment_channel = '' OR td.payment_channel IS NULL");

if ($pg === false) {
    echo "PG backfill FAILED — see SQL Error above. Column is still fine; re-run after fix.\n";
    exit(1);
}
echo "PG backfill done.\n";

$enachCheck = @towquery("SHOW TABLES LIKE 'easebuzz_adtd'");
if ($enachCheck && townum($enachCheck) > 0) {
    $enach = @towquery("UPDATE `transaction_details` td
INNER JOIN `easebuzz_adtd` ea
  ON (
    (ea.bank_ref_num IS NOT NULL AND ea.bank_ref_num != ''
      AND CONVERT(ea.bank_ref_num USING utf8mb4) COLLATE utf8mb4_unicode_ci
        = CONVERT(td.transaction_number USING utf8mb4) COLLATE utf8mb4_unicode_ci)
    OR (ea.txnid IS NOT NULL AND ea.txnid != ''
      AND CONVERT(ea.txnid USING utf8mb4) COLLATE utf8mb4_unicode_ci
        = CONVERT(td.transaction_number USING utf8mb4) COLLATE utf8mb4_unicode_ci)
  )
SET td.payment_channel = 'enach'
WHERE td.payment_channel = 'manual' OR td.payment_channel = '' OR td.payment_channel IS NULL");
    if ($enach === false) {
        echo "eNACH backfill FAILED — see SQL Error above.\n";
        exit(1);
    }
    echo "eNACH backfill done.\n";
} else {
    echo "eNACH table not found — skipped.\n";
}

echo "Done.\n";

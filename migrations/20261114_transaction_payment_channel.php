<?php
/**
 * Ensure transaction_details.payment_channel exists and backfill historical rows.
 * Run once: php migrations/20261114_transaction_payment_channel.php
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/payment_channel.php';

creditlab_ensure_payment_channel_column();

echo "Column ready.\n";

$pg = towquery("UPDATE `transaction_details` td
INNER JOIN `pg_transaction` pt
  ON pt.bank_reference_number = td.transaction_number
 AND pt.bank_reference_number IS NOT NULL
 AND pt.bank_reference_number != ''
 AND pt.status = 'success'
SET td.payment_channel = 'pg'
WHERE td.payment_channel = 'manual' OR td.payment_channel = '' OR td.payment_channel IS NULL");
echo "PG backfill done.\n";

// Optional eNACH table — skip gracefully if missing columns/table
$enachCheck = @towquery("SHOW TABLES LIKE 'easebuzz_adtd'");
if ($enachCheck && townum($enachCheck) > 0) {
    @towquery("UPDATE `transaction_details` td
INNER JOIN `easebuzz_adtd` ea
  ON (
    (ea.bank_ref_num IS NOT NULL AND ea.bank_ref_num != '' AND ea.bank_ref_num = td.transaction_number)
    OR (ea.txnid IS NOT NULL AND ea.txnid != '' AND ea.txnid = td.transaction_number)
  )
SET td.payment_channel = 'enach'
WHERE td.payment_channel = 'manual' OR td.payment_channel = '' OR td.payment_channel IS NULL");
    echo "eNACH backfill attempted.\n";
}

echo "Done.\n";

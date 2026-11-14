-- Add payment_channel to transaction_details (manual | pg | enach)
ALTER TABLE `transaction_details`
  ADD COLUMN IF NOT EXISTS `payment_channel` VARCHAR(16) NOT NULL DEFAULT 'manual' AFTER `transaction_flow`;

-- Backfill from PG ledger (explicit collation avoids general_ci vs unicode_ci mix)
UPDATE `transaction_details` td
INNER JOIN `pg_transaction` pt
  ON CONVERT(pt.bank_reference_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
   = CONVERT(td.transaction_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND pt.bank_reference_number IS NOT NULL
 AND pt.bank_reference_number != ''
 AND pt.status = 'success'
SET td.payment_channel = 'pg'
WHERE td.payment_channel = 'manual' OR td.payment_channel IS NULL OR td.payment_channel = '';

-- Backfill from eNACH ADTD refs when present
UPDATE `transaction_details` td
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
WHERE td.payment_channel = 'manual' OR td.payment_channel IS NULL OR td.payment_channel = '';

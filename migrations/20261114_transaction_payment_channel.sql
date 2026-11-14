-- Add payment_channel to transaction_details (manual | pg | enach)
ALTER TABLE `transaction_details`
  ADD COLUMN IF NOT EXISTS `payment_channel` VARCHAR(16) NOT NULL DEFAULT 'manual' AFTER `transaction_flow`;

-- Backfill from PG ledger
UPDATE `transaction_details` td
INNER JOIN `pg_transaction` pt
  ON pt.bank_reference_number = td.transaction_number
 AND pt.bank_reference_number IS NOT NULL
 AND pt.bank_reference_number != ''
 AND pt.status = 'success'
SET td.payment_channel = 'pg'
WHERE td.payment_channel = 'manual' OR td.payment_channel IS NULL OR td.payment_channel = '';

-- Backfill from eNACH ADTD refs when present
UPDATE `transaction_details` td
INNER JOIN `easebuzz_adtd` ea
  ON (
   (ea.bank_ref_num IS NOT NULL AND ea.bank_ref_num != '' AND ea.bank_ref_num = td.transaction_number)
   OR (ea.txnid IS NOT NULL AND ea.txnid != '' AND ea.txnid = td.transaction_number)
 )
SET td.payment_channel = 'enach'
WHERE td.payment_channel = 'manual' OR td.payment_channel IS NULL OR td.payment_channel = '';

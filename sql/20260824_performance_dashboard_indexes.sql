-- Indexes for Performance Dashboard (loan_acc_man + transaction_details)
-- Safe to re-run: skip if the index already exists.

ALTER TABLE `loan_acc_man` ADD INDEX `idx_lam_updated_at` (`updated_at`);

ALTER TABLE `loan_acc_man` ADD INDEX `idx_lam_updated_by` (`updated_by`);

ALTER TABLE `loan_acc_man` ADD INDEX `idx_lam_lid_updated_by_id` (`lid`, `updated_by`, `id`);

ALTER TABLE `transaction_details` ADD INDEX `idx_td_date_flow` (`transaction_date`, `transaction_flow`(16));

ALTER TABLE `transaction_details` ADD INDEX `idx_td_cllid_flow` (`cllid`(20), `transaction_flow`(16), `transaction_date`);

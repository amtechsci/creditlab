-- ============================================================================
-- CreditLab empty schema (structure only — NO DATA)
-- ============================================================================
-- Database name placeholder: replace `credit` below or:
--   mysql -e "CREATE DATABASE credit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
--   mysql credit < scripts/schema_creditlab_empty.sql
--
-- Sources:
--   1. Primary: live XAMPP DB `credit_restore` via mysqldump --no-data
--      (Sep 2025 dump u969389823_credit.sql + post-wipe restore + migrations already applied)
--   2. Old dump Generation Time: Sep 07, 2025 (phpMyAdmin / MariaDB 10.11)
--   3. Code/migrations enrichments for gaps not yet on credit_restore
--      (see scripts/SCHEMA_GAPS.md)
--
-- Already present on credit_restore (vs Sep 2025 dump alone):
--   Tables: agency, agency_admin, pg_payment_link, lsp_partners,
--           easebuzz_enach_event_log, smtp_settings
--   Columns: user.block_next_loan; loan.paid_via_*;
--            pg_transaction.pg_link_id/link_type/created_by_*/agency_*
--   Index: loan_acc_man.idx_lam_updated_at
--
-- Added in this file beyond live credit_restore:
--   - loan_acc_man.updated_by + perf indexes
--   - transaction_details perf indexes
--   - site_config, download_links
--
-- Generated: 2026-08-26
-- NO INSERT / NO DATA
-- ============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

-- Optional: uncomment to create DB
-- CREATE DATABASE IF NOT EXISTS `credit` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `credit`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_manager` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `mobile` varchar(25) DEFAULT NULL,
  `otp` int(11) DEFAULT NULL,
  `password` text NOT NULL,
  `reg_date` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agency` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agency_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `agency_id` (`agency_id`),
  CONSTRAINT `agency_admin_agency_fk` FOREIGN KEY (`agency_id`) REFERENCES `agency` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(55) DEFAULT NULL,
  `bank_code` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(512) DEFAULT NULL,
  `company_category` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `document_name` text NOT NULL,
  `password` text NOT NULL,
  `date` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `easebuzz_adtd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `txnid` text DEFAULT NULL,
  `firstname` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
  `email` text DEFAULT NULL,
  `udf5` decimal(10,3) DEFAULT NULL,
  `request_flow` varchar(15) DEFAULT NULL,
  `customer_authentication_id` text DEFAULT NULL,
  `final_collection_date` varchar(25) DEFAULT NULL,
  `hash` text DEFAULT NULL,
  `access_key` text DEFAULT NULL,
  `payment_mode` varchar(8) DEFAULT NULL,
  `ifsc` varchar(20) DEFAULT NULL,
  `account_type` varchar(25) DEFAULT NULL,
  `account_no` varchar(25) DEFAULT NULL,
  `auth_mode` varchar(15) DEFAULT NULL,
  `bank_code` varchar(15) DEFAULT NULL,
  `net_amount_debit` decimal(10,2) DEFAULT NULL,
  `bank_ref_num` varchar(255) DEFAULT NULL,
  `authorization_status` varchar(50) DEFAULT NULL,
  `easepayid` varchar(100) DEFAULT NULL,
  `payment_source` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `addedon` datetime DEFAULT NULL,
  `cash_back_percentage` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `service_tax` decimal(10,2) DEFAULT 0.00,
  `upi_va` varchar(255) DEFAULT 'NA',
  `cardnum` varchar(20) DEFAULT 'NA',
  `bankcode` varchar(20) DEFAULT 'NA',
  `auth_code` varchar(50) DEFAULT '',
  `card_type` varchar(50) DEFAULT 'NA',
  `issuing_bank` varchar(100) DEFAULT 'NA',
  `name_on_card` varchar(100) DEFAULT 'NA',
  `discount_code` varchar(50) DEFAULT 'NA',
  `mandate_status` varchar(50) DEFAULT '',
  `service_charge` decimal(10,2) DEFAULT 0.00,
  `unmappedstatus` varchar(50) DEFAULT 'NA',
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `settlement_amount` decimal(10,2) DEFAULT 0.00,
  `auto_debit_auth_msg` varchar(255) DEFAULT '',
  `cancellation_reason` varchar(255) DEFAULT 'NA',
  `deduction_percentage` decimal(5,2) DEFAULT 0.00,
  `auto_debit_access_key` text DEFAULT 'NA',
  `auto_debit_auth_error` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `easebuzz_enach_event_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT 0,
  `mobile` varchar(15) NOT NULL DEFAULT '',
  `transaction_id` varchar(64) NOT NULL DEFAULT '',
  `stage` varchar(32) NOT NULL DEFAULT '',
  `outcome` varchar(16) NOT NULL DEFAULT 'pending',
  `api` varchar(16) NOT NULL DEFAULT '',
  `auth_mode` varchar(32) NOT NULL DEFAULT '',
  `amount` decimal(12,2) DEFAULT NULL,
  `message` varchar(512) NOT NULL DEFAULT '',
  `meta_json` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_mobile` (`mobile`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_outcome` (`outcome`),
  KEY `idx_stage` (`stage`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fetchdate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `limit_increment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lid` int(11) NOT NULL,
  `uid` bigint(20) NOT NULL,
  `processed_date` datetime DEFAULT NULL,
  `processed_amount` text NOT NULL,
  `exhausted_period` text NOT NULL,
  `p_fee` text NOT NULL,
  `origination_fee` int(11) DEFAULT NULL,
  `account_management_fee` int(11) DEFAULT NULL,
  `service_charge` text NOT NULL,
  `penality_charge` text NOT NULL,
  `total_amount` text NOT NULL,
  `status_log` text NOT NULL,
  `action` text NOT NULL,
  `follow_up_mess` text NOT NULL,
  `advance_amount` text NOT NULL,
  `total_time` varchar(25) NOT NULL,
  `femi` int(11) NOT NULL DEFAULT 0,
  `semi` int(11) NOT NULL DEFAULT 0,
  `is_emi` int(11) DEFAULT NULL,
  `cleard_date` date DEFAULT NULL,
  `limit_inc_prompt` int(11) DEFAULT 0,
  `last_cal_date` date DEFAULT NULL,
  `enach_request` int(11) NOT NULL DEFAULT 0,
  `paid_via_agency_id` int(11) DEFAULT NULL,
  `paid_via_agency_name` varchar(128) DEFAULT NULL,
  `paid_via_pg_link_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lid` (`lid`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_acc_man` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `lid` int(11) DEFAULT NULL,
  `customer_response` text DEFAULT NULL,
  `commitment_date` varchar(25) DEFAULT NULL,
  `commitment_text` text DEFAULT NULL,
  `default_type` varchar(25) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` varchar(128) DEFAULT NULL COMMENT 'Staff display name who wrote this update',
  PRIMARY KEY (`id`),
  KEY `idx_lam_updated_at` (`updated_at`),
  KEY `idx_lam_updated_by` (`updated_by`),
  KEY `idx_lam_lid_updated_by_id` (`lid`,`updated_by`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_apply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NOT NULL,
  `amount` decimal(10,3) DEFAULT NULL,
  `processing_fees` decimal(10,3) DEFAULT NULL,
  `pro_fee_per` int(11) DEFAULT 14,
  `origination_fee` decimal(10,3) DEFAULT 0.000,
  `account_management_fee` decimal(10,3) DEFAULT NULL,
  `service_charge` text NOT NULL,
  `days` bigint(20) NOT NULL,
  `apply_date` varchar(255) NOT NULL,
  `status` varchar(55) NOT NULL,
  `status_date` varchar(255) NOT NULL,
  `follow_up_date` varchar(25) NOT NULL,
  `created_by` text NOT NULL,
  `reason` text NOT NULL,
  `agreement` int(11) NOT NULL DEFAULT 0,
  `keyid` int(11) NOT NULL DEFAULT 0,
  `lat` varchar(85) DEFAULT NULL,
  `longt` varchar(85) DEFAULT NULL,
  `ubank_id` int(11) DEFAULT 0,
  `last_update` varchar(25) DEFAULT NULL,
  `mail_status` int(11) DEFAULT 0,
  `interest_percentage` decimal(10,2) NOT NULL DEFAULT 0.10,
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_status` (`status`),
  KEY `idx_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lsp_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'Active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mytable` (
  `customer_authentication_id` varchar(13) NOT NULL,
  `auto_debit_access_key` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_ref` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `utr_ref` varchar(55) DEFAULT NULL,
  `payment_screenshot` text DEFAULT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `payment_type` varchar(15) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pg_payment_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txnid` varchar(64) NOT NULL,
  `loan_lid` int(11) NOT NULL COMMENT 'loan_apply.id / CLL',
  `loan_internal_id` int(11) NOT NULL COMMENT 'loan.id',
  `uid` int(11) NOT NULL,
  `link_type` enum('total_outstanding','manual') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_url` text DEFAULT NULL,
  `status` enum('created','paid','failed','expired') NOT NULL DEFAULT 'created',
  `created_by_role` enum('admin','account_manager','agency_admin') NOT NULL,
  `created_by_id` int(11) NOT NULL,
  `created_by_name` varchar(128) NOT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `agency_name` varchar(128) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `bank_ref_num` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `txnid` (`txnid`),
  KEY `uid` (`uid`),
  KEY `loan_lid` (`loan_lid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pg_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txnid` varchar(255) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `productinfo` varchar(255) NOT NULL,
  `status` enum('initiated','success','failed') NOT NULL DEFAULT 'initiated',
  `payment_method` varchar(50) DEFAULT NULL,
  `bank_reference_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pg_link_id` int(11) DEFAULT NULL,
  `link_type` enum('total_outstanding','manual') DEFAULT NULL,
  `created_by_role` varchar(32) DEFAULT NULL,
  `created_by_name` varchar(128) DEFAULT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `agency_name` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `txnid` (`txnid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recovery_officer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `mobile` varchar(25) DEFAULT NULL,
  `otp` int(11) DEFAULT NULL,
  `password` text NOT NULL,
  `reg_date` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smtp_settings` (
  `id` tinyint(1) NOT NULL DEFAULT 1,
  `smtp_host` varchar(255) NOT NULL DEFAULT '',
  `smtp_port` int(11) NOT NULL DEFAULT 465,
  `smtp_user` varchar(255) NOT NULL DEFAULT '',
  `smtp_password` varchar(255) NOT NULL DEFAULT '',
  `smtp_secure` varchar(10) NOT NULL DEFAULT 'ssl',
  `from_name` varchar(255) NOT NULL DEFAULT 'CreditLab',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `state_code` (
  `id` int(11) NOT NULL,
  `state_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaction_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cllid` text NOT NULL,
  `transaction_number` text NOT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `transaction_amount` text NOT NULL,
  `transaction_flow` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_td_date_flow` (`transaction_date`,`transaction_flow`(16)),
  KEY `idx_td_cllid_flow` (`cllid`(20),`transaction_flow`(16),`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaction_details2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cllid` text NOT NULL,
  `transaction_number` text NOT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `transaction_amount` text NOT NULL,
  `transaction_flow` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txnid` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `productinfo` text DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `status` enum('Pending','Success','Failure') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `txnid` (`txnid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rcid` text DEFAULT NULL,
  `name` text DEFAULT NULL,
  `father_name` text DEFAULT NULL,
  `pan_name` text NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `altmobile` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `altemail` varchar(255) DEFAULT NULL,
  `state` text DEFAULT NULL,
  `state_code` int(11) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` int(11) DEFAULT 2,
  `pan` text DEFAULT NULL,
  `salary` text DEFAULT NULL,
  `salarystatus` text DEFAULT NULL,
  `present_address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `graduation_year` varchar(25) DEFAULT NULL,
  `marital_status` varchar(25) DEFAULT NULL,
  `college_name` text DEFAULT NULL,
  `freq_app` text DEFAULT NULL,
  `experience` varchar(55) DEFAULT NULL,
  `residence_type` varchar(55) DEFAULT NULL,
  `credit_card` text DEFAULT NULL,
  `company` text DEFAULT NULL,
  `designation` text DEFAULT NULL,
  `office_number` bigint(20) DEFAULT NULL,
  `department` text DEFAULT NULL,
  `annual_income` varchar(55) DEFAULT NULL,
  `office_pincode` varchar(55) DEFAULT NULL,
  `office_address_line1` text DEFAULT NULL,
  `office_address_line2` text DEFAULT NULL,
  `conpanydocument` text DEFAULT NULL,
  `personaldocument` text DEFAULT NULL,
  `salarydocument` text DEFAULT 'no',
  `bankdocument` text DEFAULT 'no',
  `bankdocument2` text DEFAULT 'no',
  `bankdocument3` text DEFAULT 'no',
  `companyidcard` text DEFAULT 'no',
  `addressdocument` text DEFAULT 'no',
  `bank_name` text DEFAULT NULL,
  `branch_name` text NOT NULL,
  `ifsc` text DEFAULT NULL,
  `account_no` text DEFAULT NULL,
  `account_type` text DEFAULT NULL,
  `account_name` text DEFAULT NULL,
  `validation` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `active` int(11) DEFAULT NULL,
  `verify` int(11) DEFAULT NULL,
  `otp` int(11) DEFAULT NULL,
  `reg_date` varchar(255) DEFAULT NULL,
  `status` varchar(25) NOT NULL,
  `document_password` text NOT NULL,
  `get_salary` text DEFAULT NULL,
  `loan` int(11) NOT NULL,
  `loan_limit` int(11) NOT NULL,
  `sloan` int(11) NOT NULL,
  `assign_account_manager` int(11) NOT NULL,
  `assign_recovery_officer` int(11) NOT NULL,
  `aadhar` text NOT NULL,
  `old_document` text NOT NULL,
  `company_url` text NOT NULL,
  `fb_url` text NOT NULL,
  `insta_id` text NOT NULL,
  `comment` text NOT NULL,
  `star_member` int(11) NOT NULL DEFAULT 2,
  `approvenew` int(11) NOT NULL DEFAULT 0,
  `work_from` varchar(25) NOT NULL,
  `average_salary` varchar(255) NOT NULL,
  `salary_date` int(11) NOT NULL,
  `total_emi` int(11) NOT NULL,
  `work_year` int(11) NOT NULL,
  `work_month` int(11) NOT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `pincode` int(11) DEFAULT NULL,
  `credit_score` int(11) NOT NULL DEFAULT 0,
  `latlong` varchar(255) DEFAULT NULL,
  `selfie` text DEFAULT NULL,
  `limit_inc` int(11) DEFAULT 1,
  `old_loan_limit` int(11) DEFAULT NULL,
  `easebuzz` int(11) DEFAULT 0,
  `auto_limit` int(11) DEFAULT 1,
  `member` int(11) NOT NULL DEFAULT 0 COMMENT '0- silver ( 14% pfee  & 0.1% int per day) by default \r\n1- ⁠gold ( 13% pfee & int 0.1% per day ) by default \r\n2- ⁠diamond ( 13% pfee  & 0.05% int per day) by default \r\n3- ⁠Platinum ( 12% pfee by default & 0.1% per day by default) \r\n4- ⁠risky  ( same like silver )',
  `block_next_loan` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Admin flag: auto-hold user on next loan application (1=blocked, 0=normal)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`),
  KEY `idx_id` (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rcid` text DEFAULT NULL,
  `name` text DEFAULT NULL,
  `father_name` text DEFAULT NULL,
  `pan_name` text NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `altmobile` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `altemail` varchar(255) DEFAULT NULL,
  `state` text DEFAULT NULL,
  `state_code` int(11) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` int(11) DEFAULT 2,
  `pan` text DEFAULT NULL,
  `salary` text DEFAULT NULL,
  `salarystatus` text DEFAULT NULL,
  `present_address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `graduation_year` varchar(25) DEFAULT NULL,
  `marital_status` varchar(25) DEFAULT NULL,
  `college_name` text DEFAULT NULL,
  `freq_app` text DEFAULT NULL,
  `experience` varchar(55) DEFAULT NULL,
  `residence_type` varchar(55) DEFAULT NULL,
  `credit_card` text DEFAULT NULL,
  `company` text DEFAULT NULL,
  `designation` text DEFAULT NULL,
  `office_number` bigint(20) DEFAULT NULL,
  `department` text DEFAULT NULL,
  `annual_income` varchar(55) DEFAULT NULL,
  `office_pincode` varchar(55) DEFAULT NULL,
  `office_address_line1` text DEFAULT NULL,
  `office_address_line2` text DEFAULT NULL,
  `conpanydocument` text DEFAULT NULL,
  `personaldocument` text DEFAULT NULL,
  `salarydocument` text DEFAULT NULL,
  `bankdocument` text DEFAULT NULL,
  `bankdocument2` text NOT NULL,
  `bankdocument3` text NOT NULL,
  `addressdocument` text DEFAULT NULL,
  `bank_name` text DEFAULT NULL,
  `branch_name` text NOT NULL,
  `ifsc` text DEFAULT NULL,
  `account_no` text DEFAULT NULL,
  `account_type` text DEFAULT NULL,
  `account_name` text DEFAULT NULL,
  `validation` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `active` int(11) DEFAULT NULL,
  `verify` int(11) DEFAULT NULL,
  `otp` int(11) DEFAULT NULL,
  `reg_date` varchar(255) DEFAULT NULL,
  `status` varchar(25) NOT NULL,
  `document_password` text NOT NULL,
  `get_salary` text DEFAULT NULL,
  `loan` int(11) NOT NULL,
  `loan_limit` int(11) NOT NULL,
  `sloan` int(11) NOT NULL,
  `assign_account_manager` int(11) NOT NULL,
  `assign_recovery_officer` int(11) NOT NULL,
  `aadhar` text NOT NULL,
  `old_document` text NOT NULL,
  `company_url` text NOT NULL,
  `fb_url` text NOT NULL,
  `insta_id` text NOT NULL,
  `comment` text NOT NULL,
  `star_member` int(11) NOT NULL DEFAULT 2,
  `approvenew` int(11) NOT NULL DEFAULT 1,
  `work_from` varchar(25) NOT NULL,
  `average_salary` varchar(255) NOT NULL,
  `salary_date` int(11) NOT NULL,
  `total_emi` int(11) NOT NULL,
  `work_year` int(11) NOT NULL,
  `work_month` int(11) NOT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `pincode` int(11) DEFAULT NULL,
  `credit_score` int(11) NOT NULL DEFAULT 0,
  `latlong` varchar(255) DEFAULT NULL,
  `selfie` text DEFAULT NULL,
  `limit_inc` int(11) DEFAULT 1,
  `old_loan_limit` int(11) DEFAULT NULL,
  `easebuzz` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_bank` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `ac_name` text NOT NULL,
  `ac_no` text NOT NULL,
  `ifsc_code` text NOT NULL,
  `ac_type` text NOT NULL,
  `branch_name` text NOT NULL,
  `bank_name` text NOT NULL,
  `bank_statment` varchar(55) DEFAULT NULL,
  `date` varchar(25) NOT NULL,
  `verify` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_contact_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `user_contact` longtext NOT NULL,
  `contact` longtext NOT NULL,
  `total` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_login_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` text NOT NULL,
  `browser` text NOT NULL,
  `ip_address` text NOT NULL,
  `login_time` text NOT NULL,
  `mobile_handset_uid` varchar(25) NOT NULL,
  `latitude` varchar(50) NOT NULL,
  `longitude` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_ref` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `ref_1` text NOT NULL,
  `ref_2` text NOT NULL,
  `ref_3` text NOT NULL,
  `ref_4` text NOT NULL,
  `ref_5` text NOT NULL,
  `status` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `relation` varchar(100) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verify_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `mobile` varchar(25) DEFAULT NULL,
  `otp` int(11) DEFAULT NULL,
  `password` text NOT NULL,
  `type` int(11) DEFAULT 1 COMMENT '1=verify user,2=NBFC',
  `reg_date` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_no` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wa_phone` varchar(15) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL COMMENT '1 = start to loan apply, 2 = des, 3 = acc',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ############################################################################
-- Enrichments beyond live credit_restore (SCHEMA_GAPS.md)
-- ############################################################################
-- Gap: site_config (db.php / update_base_url.sql) — not present on credit_restore
CREATE TABLE IF NOT EXISTS `site_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- Schema-only: app seeds base_url on first getAppUrl() if empty.

-- Gap: download_links (cron/auto_report_email.php) — not present on credit_restore
CREATE TABLE IF NOT EXISTS `download_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` varchar(50) NOT NULL COMMENT 'Type of report (disbursal, cleared, default, etc.)',
  `report_name` varchar(255) NOT NULL COMMENT 'Human-readable report name',
  `s3_url` text NOT NULL COMMENT 'S3 URL of the report file',
  `s3_key` varchar(500) NOT NULL COMMENT 'S3 object key',
  `file_name` varchar(255) NOT NULL COMMENT 'Original file name',
  `report_date` date NOT NULL COMMENT 'Date for which the report was generated',
  `from_date` date NOT NULL COMMENT 'Start date of the report period',
  `to_date` date NOT NULL COMMENT 'End date of the report period',
  `report_period` varchar(100) NOT NULL COMMENT 'Report period description',
  `email_sent` tinyint(1) DEFAULT 0 COMMENT 'Whether email was sent successfully (1=yes, 0=no)',
  `email_sent_at` datetime DEFAULT NULL COMMENT 'When email was sent',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When record was created',
  PRIMARY KEY (`id`),
  KEY `idx_report_date` (`report_date`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_email_sent` (`email_sent`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores S3 URLs for automated report downloads';

-- End of schema_creditlab_empty.sql

-- PG payment links + agency admin role
-- Run once on creditlab database

CREATE TABLE IF NOT EXISTS `agency` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `agency_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `agency_id` (`agency_id`),
  CONSTRAINT `agency_admin_agency_fk` FOREIGN KEY (`agency_id`) REFERENCES `agency` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pg_payment_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txnid` varchar(64) NOT NULL,
  `loan_lid` int(11) NOT NULL COMMENT 'loan_apply.id / CLL',
  `loan_internal_id` int(11) NOT NULL COMMENT 'loan.id',
  `uid` int(11) NOT NULL,
  `link_type` enum('total_outstanding','manual') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_url` text,
  `status` enum('created','paid','failed','expired') NOT NULL DEFAULT 'created',
  `created_by_role` enum('admin','account_manager','agency_admin') NOT NULL,
  `created_by_id` int(11) NOT NULL,
  `created_by_name` varchar(128) NOT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `agency_name` varchar(128) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `bank_ref_num` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `txnid` (`txnid`),
  KEY `uid` (`uid`),
  KEY `loan_lid` (`loan_lid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- pg_transaction extensions (ignore errors if columns already exist)
ALTER TABLE `pg_transaction`
  ADD COLUMN `pg_link_id` int(11) DEFAULT NULL,
  ADD COLUMN `link_type` enum('total_outstanding','manual') DEFAULT NULL,
  ADD COLUMN `created_by_role` varchar(32) DEFAULT NULL,
  ADD COLUMN `created_by_name` varchar(128) DEFAULT NULL,
  ADD COLUMN `agency_id` int(11) DEFAULT NULL,
  ADD COLUMN `agency_name` varchar(128) DEFAULT NULL;

ALTER TABLE `loan`
  ADD COLUMN `paid_via_agency_id` int(11) DEFAULT NULL,
  ADD COLUMN `paid_via_agency_name` varchar(128) DEFAULT NULL,
  ADD COLUMN `paid_via_pg_link_id` int(11) DEFAULT NULL;

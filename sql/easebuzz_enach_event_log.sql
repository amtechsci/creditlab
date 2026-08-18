-- Per-user e-NACH / Autocollect event log (auto-created on first event via lib/easebuzz_enach_user_log.php)

CREATE TABLE IF NOT EXISTS `easebuzz_enach_event_log` (
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
  `meta_json` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_mobile` (`mobile`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_outcome` (`outcome`),
  KEY `idx_stage` (`stage`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

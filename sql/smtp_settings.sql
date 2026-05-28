-- Single-row SMTP configuration (admin can update via /admin/smtp_settings.php)
CREATE TABLE IF NOT EXISTS `smtp_settings` (
  `id` tinyint(1) NOT NULL DEFAULT 1,
  `smtp_host` varchar(255) NOT NULL DEFAULT '',
  `smtp_port` int(11) NOT NULL DEFAULT 465,
  `smtp_user` varchar(255) NOT NULL DEFAULT '',
  `smtp_password` varchar(255) NOT NULL DEFAULT '',
  `smtp_secure` varchar(10) NOT NULL DEFAULT 'ssl',
  `from_name` varchar(255) NOT NULL DEFAULT 'CreditLab',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `smtp_settings` (`id`) VALUES (1);

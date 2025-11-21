-- SQL Script to Update Base URL
-- Run this to set your base URL for testing or production

-- Create the table if it doesn't exist
CREATE TABLE IF NOT EXISTS `site_config` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `config_key` varchar(100) NOT NULL,
    `config_value` text NOT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Set base URL for testing environment
INSERT INTO `site_config` (`config_key`, `config_value`) 
VALUES ('base_url', 'https://testing.creditlab.in') 
ON DUPLICATE KEY UPDATE `config_value` = 'https://testing.creditlab.in';

-- To change back to production, run:
-- UPDATE `site_config` SET `config_value` = 'https://creditlab.in' WHERE `config_key` = 'base_url';

-- To check current value:
-- SELECT `config_value` FROM `site_config` WHERE `config_key` = 'base_url';


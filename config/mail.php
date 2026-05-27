<?php
require_once __DIR__ . '/../lib/env.php';

if (!defined('MAIL_SMTP_HOST')) {
    define('MAIL_SMTP_HOST', env('MAIL_SMTP_HOST', 'smtp.hostinger.com'));
}
if (!defined('MAIL_SMTP_PORT')) {
    define('MAIL_SMTP_PORT', (int) env('MAIL_SMTP_PORT', '465'));
}
if (!defined('MAIL_SMTP_USER')) {
    define('MAIL_SMTP_USER', env('MAIL_SMTP_USER', 'Note@creditlab.in'));
}
if (!defined('MAIL_SMTP_PASSWORD')) {
    define('MAIL_SMTP_PASSWORD', env('MAIL_SMTP_PASSWORD'));
}
if (!defined('MAIL_SMTP_SECURE')) {
    define('MAIL_SMTP_SECURE', env('MAIL_SMTP_SECURE', 'ssl'));
}

<?php
require_once __DIR__ . '/../lib/env.php';

if (!defined('SMS_API_KEY')) {
    define('SMS_API_KEY', env('SMS_API_KEY'));
}

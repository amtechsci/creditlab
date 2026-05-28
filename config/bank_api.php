<?php
require_once __DIR__ . '/../lib/env.php';

if (!defined('BANK_API_KEY')) {
    define('BANK_API_KEY', env('BANK_API_KEY'));
}
if (!defined('BANK_API_SECRET')) {
    define('BANK_API_SECRET', env('BANK_API_SECRET'));
}

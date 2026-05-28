<?php
require_once __DIR__ . '/../lib/env.php';

if (!defined('SIGNZY_API_KEY')) {
    define('SIGNZY_API_KEY', env('SIGNZY_API_KEY'));
}
if (!defined('SIGNZY_API_SECRET')) {
    define('SIGNZY_API_SECRET', env('SIGNZY_API_SECRET'));
}

<?php
require_once __DIR__ . '/../lib/env.php';

if (!defined('EASEBUZZ_MERCHANT_KEY')) {
    define('EASEBUZZ_MERCHANT_KEY', env('EASEBUZZ_MERCHANT_KEY'));
}
if (!defined('EASEBUZZ_SALT')) {
    define('EASEBUZZ_SALT', env('EASEBUZZ_SALT'));
}
if (!defined('EASEBUZZ_ENV')) {
    define('EASEBUZZ_ENV', env('EASEBUZZ_ENV', 'prod'));
}
if (!defined('EASEBUZZ_AUTOCOLLECT_PLAYGROUND')) {
    // Production: set to 1 in .env to enable payment/autocollect_playground_prod.php (live API).
    // UAT sandbox: payment/autocollect_playground.php (hardcoded UAT keys).
    define('EASEBUZZ_AUTOCOLLECT_PLAYGROUND', env('EASEBUZZ_AUTOCOLLECT_PLAYGROUND', '0'));
}

<?php
/**
 * Live production Autocollect playground (.env merchant key → api.easebuzz.in).
 *
 * Requires on server .env:
 *   EASEBUZZ_ENV=prod
 *   EASEBUZZ_MERCHANT_KEY=…
 *   EASEBUZZ_SALT=…
 *   EASEBUZZ_AUTOCOLLECT_PLAYGROUND=1
 *
 * UAT sandbox: payment/autocollect_playground.php
 */

define('CREDITLAB_AUTOCOLLECT_PLAYGROUND_MODE', 'prod');
define('CREDITLAB_AUTOCOLLECT_WEB_LOG_CHANNEL', 'prod');
define('CREDITLAB_AUTOCOLLECT_SESSION_KEY', 'autocollect_playground_prod');
define('CREDITLAB_AUTOCOLLECT_PLAYGROUND_SELF', 'autocollect_playground_prod.php');
define('CREDITLAB_AUTOCOLLECT_LOGS_SELF', 'autocollect_logs_prod.php');

require __DIR__ . '/autocollect_playground_shared.php';

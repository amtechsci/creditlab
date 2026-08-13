<?php
/**
 * UAT sandbox Autocollect playground (hardcoded 53LFWVJQH / sandbox API).
 * Live prod testing: payment/autocollect_playground_prod.php
 */

define('CREDITLAB_AUTOCOLLECT_PLAYGROUND_MODE', 'uat');
define('CREDITLAB_AUTOCOLLECT_FORCE_UAT', true);
define('CREDITLAB_AUTOCOLLECT_WEB_LOG_CHANNEL', 'uat');
define('CREDITLAB_AUTOCOLLECT_SESSION_KEY', 'autocollect_playground_uat');
define('CREDITLAB_AUTOCOLLECT_PLAYGROUND_SELF', 'autocollect_playground.php');
define('CREDITLAB_AUTOCOLLECT_LOGS_SELF', 'autocollect_logs.php');

require __DIR__ . '/autocollect_playground_shared.php';

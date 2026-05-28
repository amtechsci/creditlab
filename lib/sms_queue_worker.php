<?php
/**
 * Send SMS in a separate CLI process so web workers are not blocked.
 * Usage: php lib/sms_queue_worker.php '<base64 json>'
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

require_once __DIR__ . '/env.php';

$payload = $argc > 1 ? json_decode(base64_decode($argv[1]), true) : null;
if (!is_array($payload)) {
	fwrite(STDERR, "Invalid SMS payload\n");
	exit(1);
}

$mobile = $payload['mobile'] ?? '';
$message = $payload['message'] ?? '';
$template_id = $payload['template_id'] ?? '';

if ($mobile === '' || $message === '' || $template_id === '') {
	fwrite(STDERR, "Missing SMS fields\n");
	exit(1);
}

define('CREDITLAB_SMS_INCLUDE', true);
require_once dirname(__DIR__) . '/send_sms.php';

$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
	@mkdir($logDir, 0755, true);
}
@file_put_contents(
	$logDir . '/sms_worker.log',
	'[' . date('Y-m-d H:i:s') . "] queued SMS to {$mobile} template={$template_id}\n",
	FILE_APPEND | LOCK_EX
);

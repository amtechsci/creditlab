<?php
/**
 * Queue SMS for background delivery (frees PHP-FPM workers immediately).
 */
require_once __DIR__ . '/env.php';

function creditlab_queue_sms(string $mobile, string $message, string $template_id): bool
{
	$payload = base64_encode(json_encode([
		'mobile' => $mobile,
		'message' => $message,
		'template_id' => $template_id,
	], JSON_UNESCAPED_UNICODE));

	$phpBin = getenv('CREDITLAB_PHP_CLI') ?: '/usr/bin/php';
	if (!is_executable($phpBin)) {
		$phpBin = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
	}
	$worker = __DIR__ . '/sms_queue_worker.php';
	$errLog = dirname(__DIR__) . '/logs/sms_worker.err';

	if (function_exists('exec')) {
		$cmd = 'nohup ' . escapeshellarg($phpBin) . ' ' . escapeshellarg($worker) . ' ' . escapeshellarg($payload)
			. ' >> ' . escapeshellarg($errLog) . ' 2>&1 &';
		@exec($cmd);
		return true;
	}

	// Fallback: very short synchronous attempt
	define('CREDITLAB_SMS_INCLUDE', true);
	require_once dirname(__DIR__) . '/send_sms.php';
	return true;
}

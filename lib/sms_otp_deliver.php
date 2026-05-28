<?php
/**
 * Deliver login/signup OTP SMS reliably under PHP-FPM.
 * Background exec() workers often never run or die when the request ends;
 * fastcgi_finish_request() sends SMS after the redirect response is flushed.
 */
require_once __DIR__ . '/env.php';

function creditlab_otp_redirect_and_send(
	string $redirectUrl,
	string $mobile,
	string $message,
	string $template_id
): void {
	header('Location: ' . $redirectUrl);
	if (function_exists('fastcgi_finish_request')) {
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
		fastcgi_finish_request();
	}

	creditlab_send_otp_sms_now($mobile, $message, $template_id);
	exit;
}

function creditlab_send_otp_sms_now(string $mobile, string $message, string $template_id): void
{
	define('CREDITLAB_SMS_INCLUDE', true);
	require_once dirname(__DIR__) . '/send_sms.php';

	$logDir = dirname(__DIR__) . '/logs';
	if (!is_dir($logDir)) {
		@mkdir($logDir, 0755, true);
	}
	@file_put_contents(
		$logDir . '/sms_otp.log',
		'[' . date('Y-m-d H:i:s') . "] OTP SMS to {$mobile} template={$template_id}\n",
		FILE_APPEND | LOCK_EX
	);
}

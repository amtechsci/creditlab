<?php
/**
 * CLI OTP/SMS test — run on server as www-data:
 *   sudo -u www-data php /var/www/creditlab.in/scripts/test_otp_sms.php 8800899875
 */
if (php_sapi_name() !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

require_once dirname(__DIR__) . '/lib/env.php';
require_once dirname(__DIR__) . '/config/sms.php';

$mobile = isset($argv[1]) ? preg_replace('/\D/', '', $argv[1]) : '';
if (strlen($mobile) < 10) {
	fwrite(STDERR, "Usage: php scripts/test_otp_sms.php <10-digit-mobile>\n");
	exit(1);
}

$otp = (string) random_int(1000, 9999);
$message = "$otp is OTP for Creditlab login verification & valid till 2min. Don't share this OTP with anyone.";
$template_id = '1407174844163241940';
$sender = 'CREDLB';

echo "=== 1) Direct smswala API ===\n";
$url = 'https://sms.smswala.in/app/smsapi/index.php?key=' . urlencode(SMS_API_KEY)
	. '&campaign=16613&routeid=30&type=text&contacts=' . $mobile
	. '&senderid=' . $sender . '&msg=' . urlencode($message)
	. '&template_id=' . $template_id;

$ch = curl_init($url);
curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_CONNECTTIMEOUT => 10,
	CURLOPT_TIMEOUT => 15,
]);
$body = curl_exec($ch);
$err = curl_error($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP: $code\n";
echo "curl: " . ($err ?: 'none') . "\n";
echo "body: $body\n";
echo "OTP (for compare): $otp\n\n";

echo "=== 2) Queue worker (same path as register.php) ===\n";
require_once dirname(__DIR__) . '/lib/sms_queue.php';
creditlab_queue_sms($mobile, $message, $template_id);
echo "creditlab_queue_sms() called — check logs/sms_worker.log in ~2s\n";
sleep(2);
$workerLog = dirname(__DIR__) . '/logs/sms_worker.log';
if (is_readable($workerLog)) {
	echo "--- sms_worker.log (last 3 lines) ---\n";
	echo shell_exec('tail -3 ' . escapeshellarg($workerLog));
}

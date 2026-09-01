<?php
require_once __DIR__ . '/sms_mobile.php';

/**
 * Stop OTP SMS flooding: cooldown per number, cap per IP.
 *
 * @return array{ok:bool,retry_after:int,reason:string}
 */
function creditlab_otp_rate_allow(string $mobile, string $ip): array
{
	$dir = dirname(__DIR__) . '/logs/otp_rate';
	if (!is_dir($dir)) {
		@mkdir($dir, 0750, true);
	}

	$now = time();
	$checks = [
		['kind' => 'mobile', 'key' => $mobile, 'window' => 60, 'max' => 1, 'retry' => 60],
		['kind' => 'mobile_hour', 'key' => $mobile, 'window' => 3600, 'max' => 5, 'retry' => 600],
		['kind' => 'ip', 'key' => $ip, 'window' => 600, 'max' => 8, 'retry' => 120],
		['kind' => 'ip_day', 'key' => $ip, 'window' => 86400, 'max' => 30, 'retry' => 3600],
	];

	foreach ($checks as $check) {
		$file = $dir . '/' . $check['kind'] . '_' . hash('sha256', $check['key']) . '.json';
		$fh = @fopen($file, 'c+');
		if ($fh === false) {
			error_log('OTP rate limit: cannot open ' . $file);
			return ['ok' => false, 'retry_after' => 60, 'reason' => 'unavailable'];
		}
		flock($fh, LOCK_EX);
		$raw = stream_get_contents($fh);
		$hits = [];
		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				$hits = $decoded;
			}
		}
		$hits = array_values(array_filter($hits, static function ($ts) use ($now, $check) {
			return is_int($ts) && $ts > ($now - (int) $check['window']);
		}));
		if (count($hits) >= (int) $check['max']) {
			$oldest = min($hits);
			$retry = max(1, ((int) $oldest + (int) $check['window']) - $now);
			$retry = max($retry, (int) $check['retry']);
			flock($fh, LOCK_UN);
			fclose($fh);
			error_log('OTP rate limit blocked ' . $check['kind'] . ' ip=' . $ip);
			return ['ok' => false, 'retry_after' => $retry, 'reason' => $check['kind']];
		}
		$hits[] = $now;
		rewind($fh);
		ftruncate($fh, 0);
		fwrite($fh, json_encode($hits));
		fflush($fh);
		flock($fh, LOCK_UN);
		fclose($fh);
	}

	return ['ok' => true, 'retry_after' => 0, 'reason' => ''];
}

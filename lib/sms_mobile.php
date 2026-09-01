<?php
/**
 * SMS destinations must be a single Indian mobile (no comma-separated bulk contacts).
 */
function creditlab_sms_normalize_mobile($raw): ?string
{
	$digits = preg_replace('/\D+/', '', (string) $raw);
	if ($digits === null || $digits === '') {
		return null;
	}
	if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
		$digits = substr($digits, 2);
	} elseif (strlen($digits) === 11 && $digits[0] === '0') {
		$digits = substr($digits, 1);
	}
	if (!preg_match('/^[6-9][0-9]{9}$/', $digits)) {
		return null;
	}
	return $digits;
}

function creditlab_sms_client_ip(): string
{
	$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
	return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

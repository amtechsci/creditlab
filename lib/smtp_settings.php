<?php
/**
 * SMTP settings stored in DB (single row). Falls back to .env when DB is unavailable.
 */
require_once __DIR__ . '/env.php';

function creditlab_smtp_env_defaults(): array
{
	return [
		'smtp_host' => env('MAIL_SMTP_HOST', 'smtp.hostinger.com'),
		'smtp_port' => (int) env('MAIL_SMTP_PORT', '465'),
		'smtp_user' => env('MAIL_SMTP_USER', 'Note@creditlab.in'),
		'smtp_password' => env('MAIL_SMTP_PASSWORD', ''),
		'smtp_secure' => env('MAIL_SMTP_SECURE', 'ssl'),
		'from_name' => env('MAIL_FROM_NAME', 'CreditLab'),
	];
}

function creditlab_ensure_smtp_settings_table(): bool
{
	global $db;
	if (!isset($db) || !@mysqli_ping($db)) {
		return false;
	}

	mysqli_query($db, "CREATE TABLE IF NOT EXISTS `smtp_settings` (
		`id` tinyint(1) NOT NULL DEFAULT 1,
		`smtp_host` varchar(255) NOT NULL DEFAULT '',
		`smtp_port` int(11) NOT NULL DEFAULT 465,
		`smtp_user` varchar(255) NOT NULL DEFAULT '',
		`smtp_password` varchar(255) NOT NULL DEFAULT '',
		`smtp_secure` varchar(10) NOT NULL DEFAULT 'ssl',
		`from_name` varchar(255) NOT NULL DEFAULT 'CreditLab',
		`updated_at` datetime DEFAULT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$check = mysqli_query($db, 'SELECT id FROM smtp_settings WHERE id = 1 LIMIT 1');
	if ($check && mysqli_num_rows($check) > 0) {
		return true;
	}

	$d = creditlab_smtp_env_defaults();
	$host = mysqli_real_escape_string($db, $d['smtp_host']);
	$port = (int) $d['smtp_port'];
	$user = mysqli_real_escape_string($db, $d['smtp_user']);
	$pass = mysqli_real_escape_string($db, $d['smtp_password']);
	$secure = mysqli_real_escape_string($db, $d['smtp_secure']);
	$fromName = mysqli_real_escape_string($db, $d['from_name']);
	$now = date('Y-m-d H:i:s');

	mysqli_query($db, "INSERT INTO smtp_settings (id, smtp_host, smtp_port, smtp_user, smtp_password, smtp_secure, from_name, updated_at)
		VALUES (1, '$host', $port, '$user', '$pass', '$secure', '$fromName', '$now')");

	return true;
}

function creditlab_clear_smtp_settings_cache(): void
{
	$GLOBALS['creditlab_smtp_settings_cache'] = null;
}

function creditlab_get_smtp_settings(): array
{
	if (array_key_exists('creditlab_smtp_settings_cache', $GLOBALS)
		&& $GLOBALS['creditlab_smtp_settings_cache'] !== null) {
		return $GLOBALS['creditlab_smtp_settings_cache'];
	}

	$defaults = creditlab_smtp_env_defaults();
	global $db;

	if (!isset($db) || !@mysqli_ping($db) || !creditlab_ensure_smtp_settings_table()) {
		$GLOBALS['creditlab_smtp_settings_cache'] = $defaults;
		return $defaults;
	}

	$result = mysqli_query($db, 'SELECT * FROM smtp_settings WHERE id = 1 LIMIT 1');
	if (!$result || mysqli_num_rows($result) === 0) {
		$GLOBALS['creditlab_smtp_settings_cache'] = $defaults;
		return $defaults;
	}

	$row = mysqli_fetch_assoc($result);
	$settings = [
		'smtp_host' => ($row['smtp_host'] ?? '') !== '' ? $row['smtp_host'] : $defaults['smtp_host'],
		'smtp_port' => (int) (($row['smtp_port'] ?? 0) > 0 ? $row['smtp_port'] : $defaults['smtp_port']),
		'smtp_user' => ($row['smtp_user'] ?? '') !== '' ? $row['smtp_user'] : $defaults['smtp_user'],
		'smtp_password' => ($row['smtp_password'] ?? '') !== '' ? $row['smtp_password'] : $defaults['smtp_password'],
		'smtp_secure' => ($row['smtp_secure'] ?? '') !== '' ? $row['smtp_secure'] : $defaults['smtp_secure'],
		'from_name' => ($row['from_name'] ?? '') !== '' ? $row['from_name'] : $defaults['from_name'],
	];

	$GLOBALS['creditlab_smtp_settings_cache'] = $settings;
	return $settings;
}

function creditlab_save_smtp_settings(array $input): bool
{
	global $db;
	if (!isset($db) || !@mysqli_ping($db) || !creditlab_ensure_smtp_settings_table()) {
		return false;
	}

	$current = creditlab_get_smtp_settings();
	$host = mysqli_real_escape_string($db, trim($input['smtp_host'] ?? $current['smtp_host']));
	$port = (int) ($input['smtp_port'] ?? $current['smtp_port']);
	$user = mysqli_real_escape_string($db, trim($input['smtp_user'] ?? $current['smtp_user']));
	$secure = mysqli_real_escape_string($db, trim($input['smtp_secure'] ?? $current['smtp_secure']));
	$fromName = mysqli_real_escape_string($db, trim($input['from_name'] ?? $current['from_name']));
	$now = date('Y-m-d H:i:s');

	if (!in_array($secure, ['ssl', 'tls', ''], true)) {
		$secure = 'ssl';
	}
	if ($port < 1 || $port > 65535) {
		$port = 465;
	}

	$newPassword = trim($input['smtp_password'] ?? '');
	if ($newPassword === '') {
		$pass = mysqli_real_escape_string($db, $current['smtp_password']);
	} else {
		$pass = mysqli_real_escape_string($db, $newPassword);
	}

	$sql = "UPDATE smtp_settings SET
		smtp_host = '$host',
		smtp_port = $port,
		smtp_user = '$user',
		smtp_password = '$pass',
		smtp_secure = '$secure',
		from_name = '$fromName',
		updated_at = '$now'
		WHERE id = 1";

	$ok = (bool) mysqli_query($db, $sql);
	if ($ok) {
		creditlab_clear_smtp_settings_cache();
	}
	return $ok;
}

function creditlab_define_mail_constants(): void
{
	if (defined('MAIL_SMTP_HOST')) {
		return;
	}

	$s = creditlab_get_smtp_settings();
	define('MAIL_SMTP_HOST', $s['smtp_host']);
	define('MAIL_SMTP_PORT', (int) $s['smtp_port']);
	define('MAIL_SMTP_USER', $s['smtp_user']);
	define('MAIL_SMTP_PASSWORD', $s['smtp_password']);
	define('MAIL_SMTP_SECURE', $s['smtp_secure']);
	define('MAIL_FROM_NAME', $s['from_name']);
}

function creditlab_smtp_password_is_set(): bool
{
	$s = creditlab_get_smtp_settings();
	return ($s['smtp_password'] ?? '') !== '';
}

<?php
require_once __DIR__ . '/../config_s3.php';

function creditlab_pocket_api_token(): string
{
	return env('POCKET_API_TOKEN');
}

function creditlab_pocket_api_client_ip(): string
{
	return (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
}

function creditlab_pocket_api_allowed_ips(): array
{
	$raw = env('POCKET_API_ALLOWED_IPS');
	if ($raw === '') {
		return [];
	}
	return array_values(array_filter(array_map('trim', explode(',', $raw))));
}

function creditlab_pocket_api_provided_token(): string
{
	$header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
	if ($header !== '' && preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
		return trim($matches[1]);
	}
	if (!empty($_SERVER['HTTP_X_API_KEY'])) {
		return trim((string) $_SERVER['HTTP_X_API_KEY']);
	}
	return trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
}

function creditlab_validate_pocket_api_token(): bool
{
	$expected = creditlab_pocket_api_token();
	if ($expected === '') {
		return false;
	}
	$provided = creditlab_pocket_api_provided_token();
	if ($provided === '' || !hash_equals($expected, $provided)) {
		return false;
	}
	$allowedIps = creditlab_pocket_api_allowed_ips();
	if ($allowedIps === []) {
		return true;
	}
	$clientIp = creditlab_pocket_api_client_ip();
	if ($clientIp === '') {
		return false;
	}
	if (strpos($clientIp, ',') !== false) {
		$clientIp = trim(explode(',', $clientIp)[0]);
	}
	return in_array($clientIp, $allowedIps, true);
}

function creditlab_pocket_api_require_auth(): void
{
	if (!creditlab_validate_pocket_api_token()) {
		http_response_code(403);
		header('Content-Type: application/json');
		echo json_encode(['ok' => false, 'error' => 'Forbidden']);
		exit;
	}
}

/**
 * Relative path inside pocket/ (no leading slash, no traversal).
 */
function creditlab_pocket_validate_key(string $key): ?string
{
	$key = str_replace('\\', '/', trim($key));
	$key = ltrim($key, '/');
	if ($key === '' || strpos($key, '..') !== false) {
		return null;
	}
	if (!preg_match('#^[a-zA-Z0-9_./-]+$#', $key)) {
		return null;
	}
	return $key;
}

function creditlab_pocket_s3_key(string $relativeKey): string
{
	return S3_POCKET_PREFIX . ltrim($relativeKey, '/');
}

function creditlab_pocket_validate_prefix(string $prefix): ?string
{
	$prefix = str_replace('\\', '/', trim($prefix));
	$prefix = ltrim($prefix, '/');
	if ($prefix === '') {
		return '';
	}
	if (strpos($prefix, '..') !== false || !preg_match('#^[a-zA-Z0-9_./-]+$#', $prefix)) {
		return null;
	}
	if (substr($prefix, -1) !== '/') {
		$prefix .= '/';
	}
	return $prefix;
}

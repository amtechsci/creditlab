<?php
/**
 * Durable backup download links (HMAC). Used because S3 presigns from EC2
 * instance-role credentials expire with the STS session (~6h), not at +7 days.
 */
require_once __DIR__ . '/env.php';

function creditlab_backup_download_secret(): string
{
	$explicit = env('BACKUP_DOWNLOAD_SECRET');
	if ($explicit !== '') {
		return $explicit;
	}
	// Stable fallback so links work without a new env key (prefer setting BACKUP_DOWNLOAD_SECRET).
	return hash('sha256', env('DB_PASSWORD') . '|creditlab-backup|' . env('ZXC_INTERNAL_TOKEN', 'backup'));
}

function creditlab_backup_public_base_url(): string
{
	$base = rtrim(env('APP_URL', 'https://creditlab.in'), '/');
	return $base;
}

/**
 * Build a site URL that stays valid for $ttlSeconds (default 7 days).
 * Clicking it uses the server IAM role to stream from S3 (fresh credentials).
 */
function creditlab_backup_download_url(string $s3Key, int $ttlSeconds = 604800): string
{
	$expires = time() + max(3600, $ttlSeconds);
	$payload = $s3Key . '|' . $expires;
	$sig = hash_hmac('sha256', $payload, creditlab_backup_download_secret());
	return creditlab_backup_public_base_url() . '/backup_download.php?'
		. http_build_query([
			'k' => $s3Key,
			'e' => $expires,
			's' => $sig,
		]);
}

function creditlab_backup_verify_download_request(string $s3Key, $expires, string $sig): bool
{
	$expires = (int) $expires;
	if ($s3Key === '' || $expires < 1 || $sig === '') {
		return false;
	}
	if ($expires < time()) {
		return false;
	}
	// Only allow keys under the configured backup prefix.
	$prefix = rtrim(env('BACKUP_S3_PREFIX', 'uploads/db-backups/'), '/') . '/';
	if (strpos($s3Key, $prefix) !== 0) {
		return false;
	}
	if (strpos($s3Key, '..') !== false) {
		return false;
	}
	$expected = hash_hmac('sha256', $s3Key . '|' . $expires, creditlab_backup_download_secret());
	return hash_equals($expected, $sig);
}

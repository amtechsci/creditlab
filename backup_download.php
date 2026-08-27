<?php
/**
 * Download a DB backup from S3 via HMAC link (valid up to 7 days).
 * Does not use long-lived S3 presigns (those die early with EC2 instance-role STS).
 */
declare(strict_types=1);

require_once __DIR__ . '/lib/env.php';
creditlab_load_env();
require_once __DIR__ . '/lib/backup_link.php';
require_once __DIR__ . '/config_s3.php';
require_once __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$key = (string) ($_GET['k'] ?? '');
$expires = $_GET['e'] ?? '';
$sig = (string) ($_GET['s'] ?? '');

if (!creditlab_backup_verify_download_request($key, $expires, $sig)) {
	http_response_code(403);
	header('Content-Type: text/plain; charset=utf-8');
	echo "Forbidden or expired download link.\n";
	exit;
}

try {
	$s3 = new S3Client(s3_client_config());
	$result = $s3->getObject([
		'Bucket' => S3_BUCKET,
		'Key' => $key,
	]);

	$filename = basename($key);
	if ($filename === '' || $filename === '.' || $filename === '..') {
		$filename = 'creditlab-backup.sql.gz';
	}

	$size = isset($result['ContentLength']) ? (int) $result['ContentLength'] : 0;
	header('Content-Type: application/gzip');
	header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: private, no-store');
	if ($size > 0) {
		header('Content-Length: ' . $size);
	}

	$body = $result['Body'];
	while (!$body->eof()) {
		echo $body->read(1024 * 1024);
		if (function_exists('fastcgi_finish_request') === false) {
			flush();
		}
	}
	exit;
} catch (AwsException $e) {
	http_response_code(502);
	header('Content-Type: text/plain; charset=utf-8');
	echo "Download failed.\n";
	error_log('backup_download: ' . $e->getMessage());
	exit;
} catch (Throwable $e) {
	http_response_code(500);
	header('Content-Type: text/plain; charset=utf-8');
	echo "Download failed.\n";
	error_log('backup_download: ' . $e->getMessage());
	exit;
}

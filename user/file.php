<?php
// Proxy endpoint to stream files from S3, keeping legacy URLs change minimal.
require_once __DIR__ . '/../lib/s3_aws_sdk.php';

$name = isset($_GET['f']) ? basename($_GET['f']) : '';
if ($name === '') {
	header('HTTP/1.1 400 Bad Request');
	echo 'Missing file parameter';
	exit;
}

// Attempt to stream from S3
list($ok, $content, $metadata) = s3_download_file($name);
if (!$ok) {
	header('HTTP/1.1 404 Not Found');
	echo 'File not found: ' . htmlspecialchars($name);
	exit;
}

// Set appropriate headers
header('Content-Type: ' . ($metadata['ContentType'] ?? 'application/octet-stream'));
header('Content-Length: ' . strlen($content));
if (isset($metadata['LastModified'])) {
	header('Last-Modified: ' . $metadata['LastModified']);
}
if (isset($metadata['ETag'])) {
	header('ETag: ' . $metadata['ETag']);
}

echo $content;



<?php
// Hide PHP errors in production
error_reporting(0);
ini_set('display_errors', 0);

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

// Determine content type from file extension if not set in metadata
$contentType = $metadata['ContentType'] ?? 'application/octet-stream';
if ($contentType === 'application/octet-stream') {
	$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
	$mimeTypes = [
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png' => 'image/png',
		'gif' => 'image/gif',
		'pdf' => 'application/pdf',
		'doc' => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'txt' => 'text/plain',
		'mp4' => 'video/mp4',
		'avi' => 'video/x-msvideo',
		'mov' => 'video/quicktime'
	];
	$contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
}

// Set appropriate headers
header('Content-Type: ' . $contentType);
header('Content-Length: ' . strlen($content));
if (isset($metadata['LastModified'])) {
	header('Last-Modified: ' . $metadata['LastModified']);
}
if (isset($metadata['ETag'])) {
	header('ETag: ' . $metadata['ETag']);
}

echo $content;



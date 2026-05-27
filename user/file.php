<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/file_access.php';

$name = isset($_GET['f']) ? basename($_GET['f']) : '';
if (!creditlab_is_valid_upload_filename($name)) {
	header('HTTP/1.1 400 Bad Request');
	echo 'Missing or invalid file parameter';
	exit;
}

if (!creditlab_authorize_file_download($name)) {
	header('HTTP/1.1 403 Forbidden');
	echo 'Forbidden';
	exit;
}

list($ok, $content, $metadata) = creditlab_fetch_upload_file($name);
if (!$ok) {
	header('HTTP/1.1 404 Not Found');
	echo 'File not found';
	exit;
}

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
		'mov' => 'video/quicktime',
	];
	$contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
}

header('Content-Type: ' . $contentType);
header('Content-Length: ' . strlen($content));
header('Cache-Control: private, no-store');
if (isset($metadata['LastModified'])) {
	header('Last-Modified: ' . $metadata['LastModified']);
}
if (isset($metadata['ETag'])) {
	header('ETag: ' . $metadata['ETag']);
}

echo $content;

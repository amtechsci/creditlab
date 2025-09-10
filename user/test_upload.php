<?php
require_once __DIR__ . '/../lib/s3_aws_sdk.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_FILES['file'])) {
		header('HTTP/1.1 400 Bad Request');
		echo 'No file';
		exit;
	}
	$name = basename($_FILES['file']['name']);
	$tmp = $_FILES['file']['tmp_name'];
	$ctype = isset($_FILES['file']['type']) && $_FILES['file']['type'] ? $_FILES['file']['type'] : 'application/octet-stream';
	list($ok, $result) = s3_upload_file($tmp, $name, $ctype);
	if ($ok) {
		list($urlOk, $s3Url) = s3_get_file_url($name);
		echo json_encode([
			'ok' => true,
			'name' => $name,
			's3_url' => $urlOk ? $s3Url : 'N/A',
			'proxy_url' => './file.php?f=' . rawurlencode($name),
			'legacy_url' => './uploads/' . rawurlencode($name),
			'etag' => $result['ETag'] ?? 'N/A'
		]);
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		echo json_encode(['ok' => false, 'error' => $result]);
	}
	exit;
}
?>
<!doctype html>
<html>
<body>
<form method="post" enctype="multipart/form-data">
	<input type="file" name="file">
	<button type="submit">Upload</button>
</form>
</body>
</html>



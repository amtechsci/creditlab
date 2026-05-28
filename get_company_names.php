<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/auth.php';

if (!isset($db) || !@mysqli_ping($db)) {
	require_once __DIR__ . '/lib/database.php';
	creditlab_db_connection_failed();
}

if (!creditlab_is_staff_logged_in()) {
	http_response_code(403);
	exit;
}

$query = isset($_GET['query']) ? towreal($_GET['query']) : '';
$options = '';

$result = towquery("SELECT company_name FROM company_name WHERE company_name LIKE '%$query%' LIMIT 10");
if ($result && townum($result) > 0) {
	while ($row = towfetch($result)) {
		$options .= '<option value="' . htmlspecialchars($row['company_name']) . '">';
	}
}

header('Content-Type: text/html; charset=utf-8');
echo $options;

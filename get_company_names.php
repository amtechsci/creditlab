<?php
require_once __DIR__ . '/db.php';

$query = isset($_GET['query']) ? towreal($_GET['query']) : '';
$options = '';

$result = towquery("SELECT company_name FROM company_name WHERE company_name LIKE '%$query%' LIMIT 10");
if ($result && townum($result) > 0) {
	while ($row = towfetch($result)) {
		$options .= '<option value="' . htmlspecialchars($row['company_name']) . '">';
	}
}

echo $options;

<?php
session_start();
require_once __DIR__ . '/lib/database.php';

$db = creditlab_db_connect();
if (!$db) {
	http_response_code(500);
	exit;
}

function towquery($query)
{
	global $db;
	mysqli_set_charset($db, 'utf8');
	return mysqli_query($db, $query);
}
function townum($query)
{
	return mysqli_num_rows($query);
}
function towfetch($query)
{
	return mysqli_fetch_array($query);
}
function towreal($query)
{
	global $db;
	$re = str_replace("<", "&lt;", $query);
	$re = str_replace(">", "&gt;", $re);
	return mysqli_real_escape_string($db, $re);
}

$query = isset($_GET['query']) ? towreal($_GET['query']) : '';

<?php
require_once __DIR__ . '/../../lib/database.php';

$c = creditlab_db_credentials();
$host = $c['host'];
$dbname = $c['name'];
$username = $c['user'];
$password = $c['pass'];

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

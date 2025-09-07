<?php
// config/database.php

$host = 'localhost';
$dbname = 'credit';
$username = 'root';
$password = 'Atul@1012#';

// Database connection
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

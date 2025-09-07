<?php
// config/database.php

$host = 'localhost';
$dbname = 'u969389823_credit';
$username = 'u969389823_credit';
$password = 'Credit@123';

// Database connection
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php
$filename = 'webhook_data.txt';

// Capture incoming headers
$headers = getallheaders();
$headersFormatted = "Headers:\n" . print_r($headers, true);

// Capture GET and POST data
$getData = "GET Data:\n" . print_r($_GET, true);
$postData = "POST Data:\n" . print_r($_POST, true);

// Capture raw input body
$rawBody = "Raw Body:\n" . file_get_contents('php://input');

// Combine all the data into one string
$logData = "\n=== New Request ===\n";
$logData .= $headersFormatted . "\n";
$logData .= $getData . "\n";
$logData .= $postData . "\n";
$logData .= $rawBody . "\n";

// Save the combined data to the file
file_put_contents($filename, $logData . PHP_EOL, FILE_APPEND);
?>
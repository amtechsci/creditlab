<?php
// webhook_digilocker.php

// Read raw POST JSON data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Simple logging or just print response
header('Content-Type: application/json');

// For debug — print received webhook payload
echo json_encode([
    'message' => 'Webhook received successfully',
    'received_data' => $data
], JSON_PRETTY_PRINT);

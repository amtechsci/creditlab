<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/staff_context.php';
require_once __DIR__ . '/../lib/admin_log_files.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (creditlab_staff_role() !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin only']);
    exit;
}

$action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

if ($action === 'delete') {
    $files = $_POST['files'] ?? [];
    if (!is_array($files)) {
        $files = [$files];
    }
    $files = array_values(array_filter(array_map('strval', $files)));
    if ($files === []) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'No files specified']);
        exit;
    }
    $result = creditlab_admin_delete_logs($files);
    echo json_encode([
        'ok' => count($result['failed']) === 0,
        'deleted' => $result['deleted'],
        'failed' => $result['failed'],
        'freed_bytes' => $result['freed_bytes'],
    ]);
    exit;
}

if ($action === 'delete_before') {
    $date = isset($_POST['date']) ? trim((string) $_POST['date']) : '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid date (use Y-m-d)']);
        exit;
    }
    $result = creditlab_admin_delete_logs_before($date);
    echo json_encode([
        'ok' => count($result['failed']) === 0,
        'deleted' => $result['deleted'],
        'failed' => $result['failed'],
        'freed_bytes' => $result['freed_bytes'],
    ]);
    exit;
}

if ($action === 'delete_older_than_days') {
    $days = isset($_POST['days']) ? (int) $_POST['days'] : 0;
    if ($days < 1) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'days must be at least 1']);
        exit;
    }
    $beforeDate = date('Y-m-d', strtotime('-' . $days . ' days'));
    $result = creditlab_admin_delete_logs_before($beforeDate);
    echo json_encode([
        'ok' => count($result['failed']) === 0,
        'deleted' => $result['deleted'],
        'failed' => $result['failed'],
        'freed_bytes' => $result['freed_bytes'],
        'before_date' => $beforeDate,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unknown action']);

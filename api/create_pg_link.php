<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/pg_link_create.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!creditlab_can_create_pg_link()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$uid = isset($_POST['uid']) ? (int) $_POST['uid'] : 0;
$loanInternalId = isset($_POST['loan_internal_id']) ? (int) $_POST['loan_internal_id'] : 0;
$linkType = isset($_POST['link_type']) ? trim($_POST['link_type']) : 'total_outstanding';
$manualAmount = isset($_POST['manual_amount']) ? (float) $_POST['manual_amount'] : null;

if ($uid < 1 || $loanInternalId < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'uid and loan_internal_id required']);
    exit;
}

$result = creditlab_create_pg_link($uid, $loanInternalId, $linkType, $manualAmount);
if (!$result['ok']) {
    http_response_code(400);
}
echo json_encode($result);

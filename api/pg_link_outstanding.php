<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/staff_context.php';
require_once __DIR__ . '/../lib/loan_outstanding.php';

if (!creditlab_is_staff_logged_in()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$loanInternalId = isset($_GET['loan_internal_id']) ? (int) $_GET['loan_internal_id'] : 0;
if ($loanInternalId < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'loan_internal_id required']);
    exit;
}

$loan = creditlab_fetch_loan_by_internal_id($loanInternalId);
if (!$loan) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Loan not found']);
    exit;
}

$breakdown = creditlab_loan_outstanding_breakdown($loan);
echo json_encode(['ok' => true, 'loan_lid' => (int) $loan['lid'], 'breakdown' => $breakdown]);

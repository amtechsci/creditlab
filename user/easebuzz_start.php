<?php
include_once '../db.php';
require_once __DIR__ . '/../lib/easebuzz_enach.php';
require_once __DIR__ . '/../lib/easebuzz_autocollect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!isset($user) || $user === '') {
    header('Location: ../account/?session_expired=1');
    exit;
}

$userquery = towquery("SELECT * FROM user WHERE mobile='" . mysqli_real_escape_string($db, $user) . "' LIMIT 1");
$userfetch = towfetch($userquery);
if (!$userfetch || !is_array($userfetch)) {
    header('Location: ../account/?session_expired=1');
    exit;
}

extract($userfetch, EXTR_PREFIX_ALL, 'user');
$user_id = (int)$user_id;

$result = creditlab_start_easebuzz_enach($user_id, $_POST, [
    'salary' => isset($user_salary) ? (float)$user_salary : 0,
    'loan_limit' => isset($user_loan_limit) ? (float)$user_loan_limit : 0,
    'rcid' => isset($user_rcid) ? (string)$user_rcid : '',
    'mobile' => isset($user_mobile) ? (string)$user_mobile : '',
]);

if (!$result['ok']) {
    http_response_code(400);
    $log_file = isset($result['log_file']) ? basename($result['log_file']) : basename(creditlab_autocollect_log_path());
    $txnid = isset($result['transaction_id']) ? $result['transaction_id'] : (isset($result['txnid']) ? $result['txnid'] : '');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>e-NACH Error</title></head><body style="font-family:Arial,sans-serif;padding:24px;">';
    echo '<h3>e-NACH could not be started</h3>';
    echo '<p>' . htmlspecialchars($result['error'], ENT_QUOTES) . '</p>';
    if ($txnid !== '') {
        echo '<p><strong>Reference ID:</strong> ' . htmlspecialchars($txnid, ENT_QUOTES) . '</p>';
    }
    echo '<p style="color:#666;font-size:13px;">Support log: <code>logs/' . htmlspecialchars($log_file, ENT_QUOTES) . '</code> — share this file with Easebuzz support.</p>';
    echo '<p><a href="index.php">Go back and try again</a></p>';
    echo '</body></html>';
    exit;
}

echo $result['html'];
exit;

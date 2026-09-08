<?php
/**
 * Easebuzz Autocollect presentment webhook endpoint.
 *
 * Register in Easebuzz dashboard:
 *   https://creditlab.in/payment/autocollect_webhook.php
 *
 * Expects POST (JSON or form) with merchant_request_number / merchant_debit_id
 * (production uses CLL_AUTO_{loan_lid}_{timestamp} from cron/zzenach).
 */
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/app_url.php';
require_once __DIR__ . '/../lib/easebuzz_enach_webhook.php';

$log_file = creditlab_enach_webhook_log_path();

$db = creditlab_db_connect();
if (!$db) {
    creditlab_enach_webhook_log('FATAL: database connection failed');
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}
mysqli_set_charset($db, 'utf8');

$GLOBALS['db'] = $db;
if (!defined('CREDITLAB_SKIP_SESSION')) {
    define('CREDITLAB_SKIP_SESSION', true);
}
require_once __DIR__ . '/../db.php';

$rawBody = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];
creditlab_enach_webhook_log_raw($headers, $_GET, $_POST, $rawBody);

$post = $_POST;
if ($rawBody !== '') {
    $json = json_decode($rawBody, true);
    if (is_array($json)) {
        $post = array_merge($json, is_array($post) ? $post : []);
    }
}

if (!creditlab_enach_webhook_verify($post, $rawBody)) {
    creditlab_enach_webhook_log('REJECTED: invalid webhook signature/secret');
    creditlab_enach_record_settlement_result([
        'cleared' => false,
        'loan_lid' => '',
        'merchant_ref' => '',
        'amount' => '',
        'reason' => 'auth_rejected',
        'message' => 'Invalid webhook signature/secret',
        'meta' => ['raw' => substr((string) $rawBody, 0, 2000)],
    ]);
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Invalid webhook authentication']);
    exit;
}

$event = creditlab_enach_webhook_parse_presentment($post, $rawBody);
if (!$event) {
    creditlab_enach_webhook_log('IGNORED: not a presentment webhook payload');
    creditlab_enach_record_settlement_result([
        'cleared' => false,
        'loan_lid' => '',
        'merchant_ref' => '',
        'amount' => '',
        'reason' => 'not_presentment',
        'message' => 'Not a presentment payload',
        'meta' => ['raw' => substr((string) $rawBody, 0, 2000)],
    ]);
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'action' => 'ignored', 'message' => 'Not a presentment payload']);
    exit;
}

creditlab_enach_webhook_log('Presentment webhook received', $event);

$base_url = function_exists('getAppUrl') ? getAppUrl() : creditlab_get_base_url();
$result = creditlab_enach_webhook_handle_presentment($db, $event, $base_url, function ($msg) use ($log_file) {
    creditlab_enach_webhook_log($msg);
});

http_response_code(!empty($result['ok']) ? 200 : 500);
header('Content-Type: application/json');
echo json_encode($result, JSON_UNESCAPED_SLASHES);

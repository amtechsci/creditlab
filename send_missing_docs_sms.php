<?php
/**
 * Missing Documents SMS Handler
 * Sends SMS when documents are missing or incomplete
 */

session_start();
include_once '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required parameters
if (!isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$user_id = (int)$input['user_id'];

// Get user details
$user_query = "SELECT * FROM user WHERE id = $user_id";
$user_result = towquery($user_query);

if (townum($user_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user = towfetch($user_result);

// Get primary and alternate mobile numbers
$primary_mobile = $user['mobile'];
$alt_mobile = $user['altmobile'];

// Validate mobile numbers
if (empty($primary_mobile) || strlen($primary_mobile) < 10) {
    echo json_encode(['success' => false, 'message' => 'Invalid primary mobile number']);
    exit;
}

// Prepare SMS variables as per CSV specification
$user_name = $user['name'];
$dashboard_link = "creditlab.in/user";

// Create SMS message - exact format from CSV
$message = "Dear $user_name Kindly upload the missing documents at $dashboard_link & get your personal loan disbursed in minutes - Creditlab.in";

// SMS Configuration
$sender = "CREDLB";
$template_id = "1407175015982922397"; // Missing Documents template ID from CSV

// Send SMS to both primary and alternate numbers
$sent_count = 0;
$error_count = 0;
$responses = [];

// Send to primary mobile
if (!empty($primary_mobile) && strlen($primary_mobile) >= 10) {
    $result = sendSMSWithTemplate($primary_mobile, $message, $template_id, $sender);
    $responses['primary'] = $result;
    if ($result['success']) {
        $sent_count++;
    } else {
        $error_count++;
    }
}

// Send to alternate mobile (if different from primary and not empty)
if (!empty($alt_mobile) && strlen($alt_mobile) >= 10 && $alt_mobile != $primary_mobile) {
    $result = sendSMSWithTemplate($alt_mobile, $message, $template_id, $sender);
    $responses['alternate'] = $result;
    if ($result['success']) {
        $sent_count++;
    } else {
        $error_count++;
    }
}

// Log SMS attempts
$log_message = "Missing Documents SMS - User: $user_name (ID: $user_id), Primary: $primary_mobile, Alt: $alt_mobile, Template: $template_id, Sent: $sent_count, Errors: $error_count";
error_log($log_message);

// Log to database for tracking
$log_query = "INSERT INTO `sms_log` (`user_id`, `mobile`, `message`, `template_id`, `type`, `status`, `response`, `created_at`) 
              VALUES ($user_id, '$primary_mobile', '" . mysqli_real_escape_string($db, $message) . "', '$template_id', 'missing_documents', 'sent', '" . mysqli_real_escape_string($db, json_encode($responses)) . "', NOW())";
towquery($log_query);

if ($sent_count > 0) {
    echo json_encode([
        'success' => true, 
        'message' => "Missing Documents SMS sent to $sent_count number(s)",
        'details' => [
            'user_name' => $user_name,
            'user_id' => $user_id,
            'template_id' => $template_id,
            'sent_count' => $sent_count,
            'error_count' => $error_count,
            'responses' => $responses
        ]
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to send Missing Documents SMS to any number',
        'details' => [
            'user_name' => $user_name,
            'user_id' => $user_id,
            'template_id' => $template_id,
            'error_count' => $error_count,
            'responses' => $responses
        ]
    ]);
}

// Function to send SMS with template
function sendSMSWithTemplate($mobile, $message, $template_id, $sender) {
    $url = "https://sms.k7marketinghub.com/app/smsapi/index.php?key=2683C705E7CB39&campaign=16613&routeid=30&type=text&contacts=$mobile&senderid=$sender&msg=".urlencode($message)."&template_id=$template_id&pe_id=1401337620000065797";

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        return ['success' => false, 'error' => $error, 'response' => null];
    } else {
        return ['success' => true, 'error' => null, 'response' => $response, 'http_code' => $httpCode];
    }
}
?>

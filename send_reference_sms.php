<?php
/**
 * Reference Message SMS Handler
 * Sends SMS to reference numbers when admin clicks "Send SMS" button
 */

// Set content type to JSON
header('Content-Type: application/json');

// Error handling
try {
    session_start();
    include_once 'db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}

// Check if admin, account manager, recovery officer, or verify user is logged in
if (!isset($_SESSION['admin']) && !isset($_SESSION['account_manager']) && !isset($_SESSION['recovery_officer']) && !isset($_SESSION['verify_user'])) {
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
if (!isset($input['reference_id']) || !isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Reference ID and User ID are required']);
    exit;
}

$reference_id = (int)$input['reference_id'];
$user_id = (int)$input['user_id'];

try {
    // Get reference details
    $ref_query = "SELECT * FROM user_referrals WHERE id = $reference_id AND uid = $user_id";
    $ref_result = towquery($ref_query);

if (townum($ref_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Reference not found']);
    exit;
}

$reference = towfetch($ref_result);

// Get user details
$user_query = "SELECT * FROM user WHERE id = $user_id";
$user_result = towquery($user_query);

if (townum($user_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user = towfetch($user_result);

// Validate reference phone number
$reference_mobile = $reference['phone'];
if (empty($reference_mobile) || strlen($reference_mobile) < 10) {
    echo json_encode(['success' => false, 'message' => 'Invalid reference phone number']);
    exit;
}

// Prepare SMS variables as per CSV specification
$reference_name = $reference['name'];
$user_full_name = $user['name'];
$support_email = "support@creditlab.in";

// Create SMS message - exact format from CSV
$message = "URGENT ! Hey $reference_name, we are trying to reach $user_full_name. Convey him to contact Creditlab.in @ $support_email regarding his outstanding loan on priority.";

// SMS Configuration
$sender = "CREDLB";
$template_id = "1407175291100618275"; // Reference Message template ID from CSV

// Send SMS
$url = "https://sms.smswala.in/app/smsapi/index.php?key=2683C705E7CB39&campaign=16613&routeid=30&type=text&contacts=$reference_mobile&senderid=$sender&msg=".urlencode($message)."&template_id=$template_id";

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

// Log SMS attempt
$log_message = "Reference SMS - Reference: $reference_name ($reference_mobile), User: $user_full_name, Template: $template_id, Response: $response";
error_log($log_message);

// SMS logging removed - table doesn't exist

if ($error) {
    echo json_encode([
        'success' => false, 
        'message' => 'SMS sending failed: ' . $error,
        'details' => [
            'reference_name' => $reference_name,
            'reference_mobile' => $reference_mobile,
            'user_name' => $user_full_name,
            'template_id' => $template_id
        ]
    ]);
} else {
    echo json_encode([
        'success' => true, 
        'message' => 'SMS sent successfully to reference',
        'details' => [
            'reference_name' => $reference_name,
            'reference_mobile' => $reference_mobile,
            'user_name' => $user_full_name,
            'template_id' => $template_id,
            'http_code' => $httpCode
        ]
    ]);
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

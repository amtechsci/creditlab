<?php
$filename = 'webhook_data.txt';
date_default_timezone_set('Asia/Kolkata');

// --- LOGGING ---
$headers = getallheaders();
$headersFormatted = "Headers:\n" . serialize($headers);
$getData = "GET Data:\n" . serialize($_GET);
$postData = "POST Data:\n" . serialize($_POST);
$rawBody = "Raw Body:\n" . file_get_contents('php://input');
$logData = "\n=== New Request at ".date('Y-m-d H:i:s')." ===\n";
$logData .= $headersFormatted . "\n";
$logData .= $getData . "\n";
$logData .= $postData . "\n";
$logData .= $rawBody . "\n";
file_put_contents($filename, $logData, FILE_APPEND);

// --- DATABASE CONNECTION ---
$db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");

if (mysqli_connect_errno()) {
    error_log("Database connection failed: " . mysqli_connect_error());
    http_response_code(500);
    die("Database connection failed.");
}
mysqli_set_charset($db, 'utf8');

// --- DATABASE FUNCTIONS ---
function towquery($db, $query) {
    return mysqli_query($db, $query);
}
function townum($query_result) {
    return mysqli_num_rows($query_result);
}
function towfetch($query_result) {
    return mysqli_fetch_array($query_result);
}

/**
 * Get base URL from database configuration
 */
function getAppUrl() {
    global $db;
    static $cached_url = null;
    
    if ($cached_url !== null) {
        return $cached_url;
    }
    
    try {
        $table_check = mysqli_query($db, "SHOW TABLES LIKE 'site_config'");
        if (mysqli_num_rows($table_check) == 0) {
            mysqli_query($db, "CREATE TABLE IF NOT EXISTS `site_config` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `config_key` varchar(100) NOT NULL,
                `config_value` text NOT NULL,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `config_key` (`config_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            mysqli_query($db, "INSERT INTO `site_config` (`config_key`, `config_value`) VALUES ('base_url', 'https://creditlab.in') ON DUPLICATE KEY UPDATE `config_value` = 'https://creditlab.in'");
        }
        
        $result = mysqli_query($db, "SELECT `config_value` FROM `site_config` WHERE `config_key` = 'base_url' LIMIT 1");
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $cached_url = rtrim($row['config_value'], '/');
            return $cached_url;
        }
    } catch (Exception $e) {
    }
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'creditlab.in';
    $cached_url = $protocol . $host;
    
    return $cached_url;
}

// --- MAIN PROCESSING LOGIC ---
$data = $_POST;

// Validate required fields for auto-debit processing
if (isset($data['auto_debit_request_state']) && $data['auto_debit_request_state'] == 'success') {
    $merchant_debit_id = $data['merchant_debit_id'];
    $amount = $data['amount'];
    $bank_ref_num = $data['bank_ref_num'];
    $txnid = $data['txnid'];
    $status = $data['status'];
    
    // Extract loan.lid from merchant_debit_id (remove CLL_AUTO_ prefix)
    if (strpos($merchant_debit_id, 'CLL_AUTO_') === 0) {
        // Parse merchant_debit_id format: CLL_AUTO_{lid}_{timestamp}
        $parts = explode('_', $merchant_debit_id);
        if (count($parts) >= 3) {
            $loan_lid = $parts[2]; // Get the loan ID (third part after CLL_AUTO_)
            $timestamp = isset($parts[3]) ? $parts[3] : null; // Get timestamp if available
        } else {
            // Fallback for old format without timestamp
            $loan_lid = substr($merchant_debit_id, 9); // Remove 'CLL_AUTO_' (9 characters)
            $timestamp = null;
        }
        
        // Get loan details
        $loan_data = towquery($db, "SELECT * FROM loan WHERE lid='$loan_lid'");
        if (townum($loan_data) > 0) {
            $loan_details = towfetch($loan_data);
            $uid = $loan_details['uid'];
            
            // Get user details
            $user_data = towquery($db, "SELECT * FROM user WHERE id='$uid'");
            $user_details = towfetch($user_data);
            
            // Calculate credit score points (same logic as admin/profile.php)
            $dpd = $loan_details['exhausted_period'] - 30;
            if ($dpd > 0) {
                if ($dpd > 30) {
                    $point = -50;
                } elseif ($dpd > 10) {
                    $point = -8;
                } else {
                    $point = 2;
                }
            } else {
                $point = 8;
            }
            
            // Check if it's EMI
            $chf_data = towquery($db, "SELECT * FROM pay_ref WHERE loan_id='$loan_lid'");
            $chf = towfetch($chf_data);
            if ($chf && $chf['is_emi'] == 1) {
                towquery($db, "UPDATE `loan` SET `semi`=1,`femi`=1 WHERE lid=$loan_lid");
            }
            
            // Update user credit score and loan count
            towquery($db, "UPDATE `user` SET `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point WHERE id=".$uid);
            
            // Clear the loan
            towquery($db, "UPDATE `loan` SET `action`='cleared',`status_log`='cleared',`cleard_date`='".date('Y-m-d')."' WHERE lid=$loan_lid");
            towquery($db, "UPDATE `user` SET `status`='cleared' WHERE id=".$uid);
            towquery($db, "UPDATE `loan_apply` SET `status`='cleared' WHERE id=".$loan_lid);
            towquery($db, "DELETE FROM `pay_ref` WHERE `loan_id`='$loan_lid'");
            
            // Insert transaction details
            towquery($db, "INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES (".$uid.", '".$loan_lid."', '$bank_ref_num', '".date('Y-m-d H:i:s')."', '$amount', 'full')");
            
            // Generate no-due certificate
            $base_url = getAppUrl();
            file_get_contents($base_url . "/zxc/?url3=" . $base_url . "/no-due-certificate2.php?id=".$loan_lid."&email=".$user_details['email']);
            
            // Send SMS notification
            $template_id = '1107165683325768963';
            $mobile = $user_details['mobile'];
            include '../send_sms.php';
        }
    }
} else {
    // Handle failed auto-debit or other statuses
    if (isset($data['auto_debit_request_state'])) {
        $merchant_debit_id = $data['merchant_debit_id'];
        $error_message = isset($data['error_Message']) ? $data['error_Message'] : 'Auto-debit failed';
        
        // Log the failure for investigation
        error_log("Auto-debit failed for merchant_debit_id: $merchant_debit_id, Error: $error_message");
    }
}

http_response_code(200);

// Close database connection
// mysqli_close($db);
?>
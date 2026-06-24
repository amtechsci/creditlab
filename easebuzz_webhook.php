<?php
$filename = 'webhook_data.txt';
date_default_timezone_set('Asia/Kolkata');

// --- ENHANCED LOGGING ---
$current_date = date('Y-m-d');
$current_time = date('Y-m-d H:i:s');

// Create daily log file
$log_file = "logs/webhook_" . $current_date . ".log";
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Function to write detailed logs
function writeWebhookLog($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    error_log($message); // Also log to system error log
}

// Initialize transaction tracking arrays
$successful_transactions = [];
$failed_transactions = [];

// Start webhook processing
writeWebhookLog("=== WEBHOOK REQUEST STARTED ===", $log_file);
writeWebhookLog("Date: $current_date | Time: $current_time", $log_file);

// --- RAW DATA LOGGING ---
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
$rawLogFile = __DIR__ . '/logs/webhook_raw.txt';
if (!is_dir(dirname($rawLogFile))) {
	@mkdir(dirname($rawLogFile), 0755, true);
}
@file_put_contents($rawLogFile, $logData, FILE_APPEND | LOCK_EX);


require_once __DIR__ . '/lib/database.php';
$db = creditlab_db_connect();

if (!$db) {
    $error_msg = "Database connection failed: " . mysqli_connect_error();
    error_log($error_msg);
    writeWebhookLog("FATAL ERROR: $error_msg", $log_file);
    http_response_code(500);
    die("Database connection failed."); // Stop execution if DB is down
}
mysqli_set_charset($db, 'utf8');
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/zxc_mail.php';
require_once __DIR__ . '/lib/http_fetch.php';
require_once __DIR__ . '/lib/sms_loan_cleared.php';

// Test the connection
if (!mysqli_ping($db)) {
    $error_msg = "Database connection lost";
    error_log($error_msg);
    writeWebhookLog("FATAL ERROR: $error_msg", $log_file);
    http_response_code(500);
    die("Database connection lost");
}


// Local DB helpers (do not name towquery — pg settlement uses db.php helpers).
function webhook_query($db, $query) {
    $result = mysqli_query($db, $query);
    if (!$result) {
        error_log("Database query failed: " . mysqli_error($db) . " - Query: " . $query);
        return false;
    }
    return $result;
}
function webhook_num($query_result) {
    return mysqli_num_rows($query_result);
}
function webhook_fetch($query_result) {
    return mysqli_fetch_array($query_result);
}

function creditlab_webhook_is_autodebit_furl(string $furl, string $baseUrl): bool
{
    foreach (['/payment/cb_auto.php', '/payment/cb.php'] as $path) {
        if ($furl === $baseUrl . $path || strpos($furl, $path) !== false) {
            return true;
        }
    }
    return false;
}

// --- 3. STANDARDIZED BUSINESS LOGIC FUNCTIONS ---
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

/**
 * Calculate credit score points based on DPD (Days Past Due)
 * @param int $dpd Days past due
 * @return int Credit score points
 */
function calculateCreditScorePoints($dpd) {
    if ($dpd > 0) {
        if ($dpd > 30) {
            return -50;
        } elseif ($dpd > 10) {
            return -8;
        } else {
            return 2;
        }
    } else {
        return 8;
    }
}

/**
 * Process loan clearance with transaction support
 * @param mysqli $db Database connection
 * @param int $loan_lid Loan ID
 * @param int $uid User ID
 * @param float $amount Transaction amount
 * @param string $bank_ref_num Bank reference number
 * @param string $transaction_flow Transaction flow type
 * @return bool Success status
 */
function processLoanClearance($db, $loan_lid, $uid, $amount, $bank_ref_num, $transaction_flow = 'full') {
    // Start transaction
    mysqli_autocommit($db, false);
    
    try {
        // Get loan details for DPD calculation
        $loan_lid_escaped = mysqli_real_escape_string($db, $loan_lid);
        $loan_data = webhook_query($db, "SELECT * FROM loan WHERE lid='$loan_lid_escaped'");
        if (!$loan_data || webhook_num($loan_data) == 0) {
            throw new Exception("Loan not found: $loan_lid");
        }
        $loan_details = webhook_fetch($loan_data);
        
        // Fetch loan days from loan_apply table
        $loan_apply_data = webhook_fetch(webhook_query($db, "SELECT days FROM loan_apply WHERE id='$loan_lid_escaped'"));
        $loan_days = isset($loan_apply_data['days']) && $loan_apply_data['days'] > 0 ? (int)$loan_apply_data['days'] : 30;
        
        // Calculate credit score points
        $dpd = $loan_details['exhausted_period'] - $loan_days;
        $point = calculateCreditScorePoints($dpd);
        
        // Check if it's EMI and update accordingly
        $chf_data = webhook_query($db, "SELECT * FROM pay_ref WHERE loan_id='$loan_lid_escaped'");
        if ($chf_data && webhook_num($chf_data) > 0) {
            $chf = webhook_fetch($chf_data);
            if ($chf && isset($chf['is_emi']) && $chf['is_emi'] == 1) {
                $emi_result = webhook_query($db, "UPDATE `loan` SET `semi`=1,`femi`=1 WHERE lid='$loan_lid_escaped'");
                if (!$emi_result) {
                    throw new Exception("Failed to update EMI status");
                }
            }
        }
        
        // Update user credit score and loan count
        $uid_escaped = mysqli_real_escape_string($db, $uid);
        $point_escaped = mysqli_real_escape_string($db, $point);
        $user_update = webhook_query($db, "UPDATE `user` SET `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point_escaped WHERE id='$uid_escaped'");
        if (!$user_update) {
            throw new Exception("Failed to update user credit score");
        }
        
        // Clear the loan
        $current_date_escaped = mysqli_real_escape_string($db, date('Y-m-d'));
        $loan_clear = webhook_query($db, "UPDATE `loan` SET `action`='cleared',`status_log`='cleared',`cleard_date`='$current_date_escaped' WHERE lid='$loan_lid_escaped'");
        if (!$loan_clear) {
            throw new Exception("Failed to clear loan");
        }
        
        $user_clear = webhook_query($db, "UPDATE `user` SET `status`='cleared' WHERE id='$uid_escaped'");
        if (!$user_clear) {
            throw new Exception("Failed to clear user status");
        }
        
        $loan_apply_clear = webhook_query($db, "UPDATE `loan_apply` SET `status`='cleared' WHERE id='$loan_lid_escaped'");
        if (!$loan_apply_clear) {
            throw new Exception("Failed to clear loan application");
        }
        
        // Delete payment references
        $pay_ref_delete = webhook_query($db, "DELETE FROM `pay_ref` WHERE `loan_id`='$loan_lid_escaped'");
        if (!$pay_ref_delete) {
            throw new Exception("Failed to delete payment references");
        }
        
        // Insert transaction details
        $bank_ref_num_escaped = mysqli_real_escape_string($db, $bank_ref_num);
        $amount_escaped = mysqli_real_escape_string($db, $amount);
        $transaction_flow_escaped = mysqli_real_escape_string($db, $transaction_flow);
        $current_datetime_escaped = mysqli_real_escape_string($db, date('Y-m-d H:i:s'));
        $transaction_insert = webhook_query($db, "INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES ('$uid_escaped', '$loan_lid_escaped', '$bank_ref_num_escaped', '$current_datetime_escaped', '$amount_escaped', '$transaction_flow_escaped')");
        if (!$transaction_insert) {
            throw new Exception("Failed to insert transaction details");
        }
        
        // Commit transaction
        mysqli_commit($db);
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($db);
        error_log("Transaction failed for loan $loan_lid: " . $e->getMessage());
        return false;
    } finally {
        // Re-enable autocommit
        mysqli_autocommit($db, true);
    }
}


// --- 3. MAIN LOGIC ---
$data = $_POST;

require_once __DIR__ . '/lib/easebuzz_verify.php';
if (!creditlab_easebuzz_validate_callback($data)) {
	creditlab_easebuzz_reject_invalid_callback();
}

// --- FIELD VALIDATION ---
if (!isset($data['furl']) || empty($data['furl'])) {
    error_log("Missing furl field in webhook request");
    writeWebhookLog("ERROR: Missing furl field in webhook request", $log_file);
    http_response_code(400);
    die("Missing required field: furl");
}

// Validate that furl is a valid URL
if (!filter_var($data['furl'], FILTER_VALIDATE_URL)) {
    error_log("Invalid furl URL format: " . $data['furl']);
    writeWebhookLog("ERROR: Invalid furl URL format: " . $data['furl'], $log_file);
    http_response_code(400);
    die("Invalid furl URL format");
}

// Make sure the required fields are available in the callback
$base_url = getAppUrl();
if (creditlab_webhook_is_autodebit_furl($data['furl'], $base_url)) {
    // Handle auto-debit execution results
    if (isset($data['auto_debit_request_state']) && $data['auto_debit_request_state'] == 'success') {
        // Validate required fields for auto-debit processing
        $required_fields = ['merchant_debit_id', 'amount', 'txnid', 'status'];
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            error_log("Missing required fields for cb_auto processing: " . implode(', ', $missing_fields));
            http_response_code(400);
            die("Missing required fields: " . implode(', ', $missing_fields));
        }
        $merchant_debit_id = $data['merchant_debit_id'];
        $amount = $data['amount'];
        $bank_ref_num = $data['bank_ref_num'] ?? $data['easepayid'] ?? ('AUTO_' . $data['txnid']);
        $txnid = $data['txnid'];
        
        // Validate numeric fields
        if (!is_numeric($amount) || $amount <= 0) {
            $error_msg = "Invalid amount: $amount";
            writeWebhookLog("ERROR: $error_msg", $log_file);
            http_response_code(400);
            die($error_msg);
        }
        
        // Validate transaction ID format
        if (empty($txnid) || strlen($txnid) < 5) {
            $error_msg = "Invalid transaction ID: $txnid";
            writeWebhookLog("ERROR: $error_msg", $log_file);
            http_response_code(400);
            die($error_msg);
        }
        
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
            
            $timestamp_info = $timestamp ? " | Timestamp: $timestamp (" . date('Y-m-d H:i:s', $timestamp) . ")" : "";
            writeWebhookLog("Processing auto-debit for loan CLL$loan_lid | Amount: ₹$amount | Bank Ref: $bank_ref_num$timestamp_info", $log_file);
            
            // Get loan details to verify loan exists and get user ID
            $loan_lid_escaped = mysqli_real_escape_string($db, $loan_lid);
            $loan_data = webhook_query($db, "SELECT * FROM loan WHERE lid='$loan_lid_escaped'");
            if (!$loan_data) {
                $error_msg = "Database error while fetching loan $loan_lid";
                writeWebhookLog("ERROR: $error_msg", $log_file);
                $failed_transactions[] = "CLL$loan_lid - $error_msg";
                http_response_code(500);
                die($error_msg);
            }
            
            if (webhook_num($loan_data) > 0) {
                $loan_details = webhook_fetch($loan_data);
                $uid = $loan_details['uid'];
                
                // Get user details
                $uid_escaped = mysqli_real_escape_string($db, $uid);
                $user_data = webhook_query($db, "SELECT * FROM user WHERE id='$uid_escaped'");
                if (!$user_data || webhook_num($user_data) == 0) {
                    $error_msg = "User not found for loan CLL$loan_lid";
                    writeWebhookLog("ERROR: $error_msg", $log_file);
                    $failed_transactions[] = "CLL$loan_lid - $error_msg";
                    http_response_code(404);
                    die($error_msg);
                }
                $user_details = webhook_fetch($user_data);

                if (($loan_details['status_log'] ?? '') === 'cleared' || ($loan_details['action'] ?? '') === 'cleared') {
                    writeWebhookLog("SKIPPED: Loan CLL$loan_lid already cleared (duplicate webhook)", $log_file);
                    $successful_transactions[] = "CLL$loan_lid - already cleared";
                } else {
                // Process loan clearance with transaction support
                $clearance_success = processLoanClearance($db, $loan_lid, $uid, $amount, $bank_ref_num, 'full');
                
                if ($clearance_success) {
                    writeWebhookLog("SUCCESS: Loan CLL$loan_lid cleared successfully | User: {$user_details['name']} | Amount: ₹$amount", $log_file);
                    $successful_transactions[] = "CLL$loan_lid - ₹$amount";
                    
                    // Generate no-due certificate
                    creditlab_zxc_mail_trigger(creditlab_zxc_mail_url($base_url, $user_details['email'], null, null, $base_url . '/no-due-certificate2.php?id=' . $loan_lid));
                    writeWebhookLog("No-due certificate mail triggered for CLL$loan_lid", $log_file);
                    
                    if (creditlab_send_loan_cleared_sms((string) $user_details['mobile'], (string) $user_details['name'], (int) $loan_lid, $base_url)) {
                        writeWebhookLog("SMS notification sent for CLL$loan_lid to {$user_details['mobile']}", $log_file);
                    } else {
                        writeWebhookLog("SMS not sent for CLL$loan_lid (missing mobile or gateway)", $log_file);
                    }
                    
                } else {
                    $error_msg = "Failed to process loan clearance for CLL$loan_lid";
                    writeWebhookLog("ERROR: $error_msg", $log_file);
                    $failed_transactions[] = "CLL$loan_lid - $error_msg";
                    http_response_code(500);
                    die($error_msg);
                }
                }
            } else {
                $error_msg = "Loan CLL$loan_lid not found";
                writeWebhookLog("ERROR: $error_msg", $log_file);
                $failed_transactions[] = "CLL$loan_lid - $error_msg";
                http_response_code(404);
                die($error_msg);
            }
        } else {
            $error_msg = "Invalid merchant_debit_id format: $merchant_debit_id";
            writeWebhookLog("ERROR: $error_msg", $log_file);
            $failed_transactions[] = "Invalid ID - $error_msg";
            http_response_code(400);
            die($error_msg);
        }
    } elseif (isset($data['auto_debit_request_state']) && $data['auto_debit_request_state'] == 'failure') {
        // Handle auto-debit failure
        $merchant_debit_id = $data['merchant_debit_id'] ?? null;
        $amount = $data['amount'] ?? 'N/A';
        $error_message = $data['error_Message'] ?? 'Unknown reason';
        $txnid = $data['txnid'] ?? 'N/A';

        if ($merchant_debit_id && strpos($merchant_debit_id, 'CLL_AUTO_') === 0) {
            $parts = explode('_', $merchant_debit_id);
            $loan_lid = count($parts) >= 3 ? $parts[2] : substr($merchant_debit_id, 9);
            
            writeWebhookLog("Processing auto-debit FAILURE for loan CLL$loan_lid | Amount: ₹$amount | Reason: $error_message", $log_file);

            $loan_lid_escaped = mysqli_real_escape_string($db, $loan_lid);
            $loan_data = webhook_query($db, "SELECT * FROM loan WHERE lid='$loan_lid_escaped'");
            if ($loan_data && webhook_num($loan_data) > 0) {
                $loan_details = webhook_fetch($loan_data);
                $uid = $loan_details['uid'];

                $user_data = webhook_query($db, "SELECT * FROM user WHERE id='".mysqli_real_escape_string($db, $uid)."'");
                if ($user_data && webhook_num($user_data) > 0) {
                    $user_details = webhook_fetch($user_data);
                    
                    $template_id = '1407175016580415506'; // autodebit bounce template ID
                    $mobile = $user_details['mobile'];
                    $payment_link = $base_url . '/user';
                    // $message = "Auto-debit of Creditlab.in loan of Rs. {$amount} got bounced due to insufficient funds. Close it now {$payment_link} to avoid further debits/bounce charges & legal action";
                    
                    // if (file_exists('send_sms.php')) {
                    //     include 'send_sms.php';
                    // }
                    // writeWebhookLog("Autodebit bounce SMS sent for loan CLL$loan_lid to $mobile", $log_file);
                } else {
                    writeWebhookLog("ERROR: User not found for failed auto-debit on loan CLL$loan_lid", $log_file);
                }
            } else {
                writeWebhookLog("ERROR: Loan not found for failed auto-debit with merchant_debit_id $merchant_debit_id", $log_file);
            }
        } else {
            writeWebhookLog("ERROR: Invalid or missing merchant_debit_id for failed auto-debit.", $log_file);
        }
    }
}
elseif ($data['furl'] == $base_url . '/easebuzz_callback.php') {
    // Validate required fields for easebuzz_callback processing
    $required_fields = ['txnid', 'authorization_status', 'net_amount_debit', 'bank_ref_num', 'easepayid', 'addedon', 'cash_back_percentage', 'status', 'error_Message', 'auto_debit_access_key'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        error_log("Missing required fields for easebuzz_callback processing: " . implode(', ', $missing_fields));
        http_response_code(400);
        die("Missing required fields: " . implode(', ', $missing_fields));
    }
    
    // Using prepared statements to prevent SQL Injection
    $stmt1 = mysqli_prepare($db, "UPDATE easebuzz_adtd SET authorization_status = ?, net_amount_debit = ?, bank_ref_num = ?, easepayid = ?, addedon = ?, cash_back_percentage = ?, status = ?, auto_debit_access_key = ? WHERE txnid = ?");

    $txnid = $data['txnid'];
    $authorization_status = strtolower($data['authorization_status']);
    $net_amount_debit = $data['net_amount_debit'];
    $bank_ref_num = $data['bank_ref_num'];
    $easepayid = $data['easepayid'];
    $addedon = $data['addedon'];
    $cash_back_percentage = $data['cash_back_percentage'];
    $status = $data['status'];
    $error_message = $data['error_Message'];
    $auto_debit_access_key = $data['auto_debit_access_key'];

    $update_status = $authorization_status;
    $user_easebuzz_status = 1;
    if ($authorization_status === 'rejected') {
        $update_status = 'rejected';
        $user_easebuzz_status = 2;
    } elseif ($status === 'failure') {
        $user_easebuzz_status = 0;
    }

    mysqli_stmt_bind_param($stmt1, "sssssssss", $update_status, $net_amount_debit, $bank_ref_num, $easepayid, $addedon, $cash_back_percentage, $status, $auto_debit_access_key, $txnid);

    if (mysqli_stmt_execute($stmt1)) {

        // Get the `uid` for the txnid to update the corresponding user table
        $stmt2 = mysqli_prepare($db, "SELECT uid FROM easebuzz_adtd WHERE txnid = ?");
        mysqli_stmt_bind_param($stmt2, "s", $txnid);
        mysqli_stmt_execute($stmt2);
        $result = mysqli_stmt_get_result($stmt2);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $uid = $row['uid'];

            $stmt3 = mysqli_prepare($db, "UPDATE user SET easebuzz = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt3, "is", $user_easebuzz_status, $uid);

            if (mysqli_stmt_execute($stmt3)) {
                // User table updated successfully
            }
        }
    }

} elseif (isset($data['furl']) && strpos($data['furl'], 'payeasebuzz/response.php') !== false) {
    // Validate required fields for payment response processing
    $required_fields = ['txnid', 'status', 'amount'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        error_log("Missing required fields for payment response processing: " . implode(', ', $missing_fields));
        http_response_code(400);
        die("Missing required fields: " . implode(', ', $missing_fields));
    }
    
    $result = $_POST;
    $txnid = $result['txnid'];
    $status = $result['status'];
    $amount = isset($result['net_amount_debit']) && $result['net_amount_debit'] !== ''
        ? $result['net_amount_debit']
        : $result['amount'];
    $bank_ref_num = $result['bank_ref_num'] ?? ($result['easepayid'] ?? '');
    if ($bank_ref_num === '' || $bank_ref_num === 'NA') {
        $bank_ref_num = $result['easepayid'] ?? ('WEBHOOK_' . $txnid);
    }
    $payment_method = $result['mode'] ?? ($result['payment_mode'] ?? 'easebuzz');
    
    // Validate numeric fields for payment processing
    if (!is_numeric($amount) || $amount <= 0) {
        $error_msg = "Invalid amount for payment processing: $amount";
        writeWebhookLog("ERROR: $error_msg", $log_file);
        http_response_code(400);
        die($error_msg);
    }
    
    // Validate transaction ID format
    if (empty($txnid) || strlen($txnid) < 5) {
        $error_msg = "Invalid transaction ID for payment processing: $txnid";
        writeWebhookLog("ERROR: $error_msg", $log_file);
        http_response_code(400);
        die($error_msg);
    }
    
    if (isset($status) && $status == "success") {
        require_once __DIR__ . '/lib/pg_db_bootstrap.php';
        require_once __DIR__ . '/lib/pg_link_settlement.php';
        creditlab_ensure_app_db_helpers($db);
        writeWebhookLog("Processing payment for txnid: $txnid | Amount: ₹$amount", $log_file);
        $settle = creditlab_process_pg_payment_success($db, $txnid, (float) $amount, (string) $bank_ref_num, (string) $payment_method);
        $flow = $settle['flow'] ?? '';
        if ($settle['ok']) {
            if ($flow === 'skipped') {
                writeWebhookLog("SKIPPED (already settled): txnid $txnid", $log_file);
            } elseif ($flow === 'part') {
                writeWebhookLog("PART only (loan still open): txnid $txnid paid=$amount", $log_file);
                $failed_transactions[] = "$txnid - part payment only";
            } else {
                $smsNote = !empty($settle['sms_ok']) ? 'sms=ok' : 'sms=fail';
                writeWebhookLog("SUCCESS: txnid $txnid flow=$flow $smsNote", $log_file);
                $successful_transactions[] = "$txnid - ₹$amount";
                if (empty($settle['sms_ok'])) {
                    $txEsc = towreal($txnid);
                    $lq = towquery("SELECT loan.lid, user.mobile, user.name FROM pg_transaction INNER JOIN loan ON loan.id = pg_transaction.loan_id INNER JOIN user ON user.id = loan.uid WHERE pg_transaction.txnid='$txEsc' LIMIT 1");
                    if ($lq && townum($lq) > 0) {
                        $lr = towfetch($lq);
                        if (creditlab_send_loan_cleared_sms((string) ($lr['mobile'] ?? ''), (string) ($lr['name'] ?? 'Customer'), (int) $lr['lid'], $base_url)) {
                            writeWebhookLog("Cleared SMS sent (retry) for CLL{$lr['lid']}", $log_file);
                        }
                    }
                }
            }
        } else {
            writeWebhookLog("ERROR: txnid $txnid - " . ($settle['message'] ?? ''), $log_file);
            $failed_transactions[] = "$txnid - " . ($settle['message'] ?? '');
        }
    } else {
        require_once __DIR__ . '/lib/pg_db_bootstrap.php';
        require_once __DIR__ . '/lib/pg_link_settlement.php';
        creditlab_ensure_app_db_helpers($db);
        creditlab_pg_mark_tx_failure($db, $txnid);
    }
}

// --- FINAL SUMMARY LOGGING ---
writeWebhookLog("=== WEBHOOK PROCESSING SUMMARY ===", $log_file);
writeWebhookLog("Date: $current_date | Time: $current_time", $log_file);
writeWebhookLog("Successful Transactions: " . count($successful_transactions), $log_file);
writeWebhookLog("Failed Transactions: " . count($failed_transactions), $log_file);

// Log detailed transaction lists
if (!empty($successful_transactions)) {
    writeWebhookLog("Successful Transaction Details:", $log_file);
    foreach ($successful_transactions as $transaction) {
        writeWebhookLog("  ✓ $transaction", $log_file);
    }
}

if (!empty($failed_transactions)) {
    writeWebhookLog("Failed Transaction Details:", $log_file);
    foreach ($failed_transactions as $transaction) {
        writeWebhookLog("  ✗ $transaction", $log_file);
    }
}

writeWebhookLog("=== WEBHOOK PROCESSING COMPLETED ===", $log_file);

http_response_code(200);

// Close the single database connection at the end
// mysqli_close($db);
?>
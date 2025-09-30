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
file_put_contents($filename, $logData, FILE_APPEND);


// --- 1. ESTABLISH ONE DATABASE CONNECTION ---
// This is more efficient and allows us to use the connection variable everywhere.
$db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");

// Always check for connection errors
if (mysqli_connect_errno()) {
    $error_msg = "Database connection failed: " . mysqli_connect_error();
    error_log($error_msg);
    writeWebhookLog("FATAL ERROR: $error_msg", $log_file);
    http_response_code(500);
    die("Database connection failed."); // Stop execution if DB is down
}
mysqli_set_charset($db, 'utf8');

// Test the connection
if (!mysqli_ping($db)) {
    $error_msg = "Database connection lost";
    error_log($error_msg);
    writeWebhookLog("FATAL ERROR: $error_msg", $log_file);
    http_response_code(500);
    die("Database connection lost");
}


// --- 2. REVISED DATABASE FUNCTIONS ---
// These now require the $db connection to be passed in.
function towquery($db, $query) {
    $result = mysqli_query($db, $query);
    if (!$result) {
        error_log("Database query failed: " . mysqli_error($db) . " - Query: " . $query);
        return false;
    }
    return $result;
}
function townum($query_result) {
    return mysqli_num_rows($query_result);
}
function towfetch($query_result) {
    return mysqli_fetch_array($query_result);
}

// --- 3. STANDARDIZED BUSINESS LOGIC FUNCTIONS ---
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
        $loan_data = towquery($db, "SELECT * FROM loan WHERE lid='$loan_lid_escaped'");
        if (!$loan_data || townum($loan_data) == 0) {
            throw new Exception("Loan not found: $loan_lid");
        }
        $loan_details = towfetch($loan_data);
        
        // Calculate credit score points
        $dpd = $loan_details['exhausted_period'] - 30;
        $point = calculateCreditScorePoints($dpd);
        
        // Check if it's EMI and update accordingly
        $chf_data = towquery($db, "SELECT * FROM pay_ref WHERE loan_id='$loan_lid_escaped'");
        if ($chf_data && townum($chf_data) > 0) {
            $chf = towfetch($chf_data);
            if ($chf && isset($chf['is_emi']) && $chf['is_emi'] == 1) {
                $emi_result = towquery($db, "UPDATE `loan` SET `semi`=1,`femi`=1 WHERE lid='$loan_lid_escaped'");
                if (!$emi_result) {
                    throw new Exception("Failed to update EMI status");
                }
            }
        }
        
        // Update user credit score and loan count
        $uid_escaped = mysqli_real_escape_string($db, $uid);
        $point_escaped = mysqli_real_escape_string($db, $point);
        $user_update = towquery($db, "UPDATE `user` SET `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point_escaped WHERE id='$uid_escaped'");
        if (!$user_update) {
            throw new Exception("Failed to update user credit score");
        }
        
        // Clear the loan
        $current_date_escaped = mysqli_real_escape_string($db, date('Y-m-d'));
        $loan_clear = towquery($db, "UPDATE `loan` SET `action`='cleared',`status_log`='cleared',`cleard_date`='$current_date_escaped' WHERE lid='$loan_lid_escaped'");
        if (!$loan_clear) {
            throw new Exception("Failed to clear loan");
        }
        
        $user_clear = towquery($db, "UPDATE `user` SET `status`='cleared' WHERE id='$uid_escaped'");
        if (!$user_clear) {
            throw new Exception("Failed to clear user status");
        }
        
        $loan_apply_clear = towquery($db, "UPDATE `loan_apply` SET `status`='cleared' WHERE id='$loan_lid_escaped'");
        if (!$loan_apply_clear) {
            throw new Exception("Failed to clear loan application");
        }
        
        // Delete payment references
        $pay_ref_delete = towquery($db, "DELETE FROM `pay_ref` WHERE `loan_id`='$loan_lid_escaped'");
        if (!$pay_ref_delete) {
            throw new Exception("Failed to delete payment references");
        }
        
        // Insert transaction details
        $bank_ref_num_escaped = mysqli_real_escape_string($db, $bank_ref_num);
        $amount_escaped = mysqli_real_escape_string($db, $amount);
        $transaction_flow_escaped = mysqli_real_escape_string($db, $transaction_flow);
        $current_datetime_escaped = mysqli_real_escape_string($db, date('Y-m-d H:i:s'));
        $transaction_insert = towquery($db, "INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES ('$uid_escaped', '$loan_lid_escaped', '$bank_ref_num_escaped', '$current_datetime_escaped', '$amount_escaped', '$transaction_flow_escaped')");
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
if ($data['furl'] == 'https://creditlab.in/payment/cb_auto.php') {
    // Handle auto-debit execution results
    if (isset($data['auto_debit_request_state']) && $data['auto_debit_request_state'] == 'success') {
        // Validate required fields for auto-debit processing
        $required_fields = ['merchant_debit_id', 'amount', 'bank_ref_num', 'txnid', 'status'];
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
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
        $bank_ref_num = $data['bank_ref_num'] ?? $data['easepayid'];
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
            $loan_data = towquery($db, "SELECT * FROM loan WHERE lid='$loan_lid_escaped'");
            if (!$loan_data) {
                $error_msg = "Database error while fetching loan $loan_lid";
                writeWebhookLog("ERROR: $error_msg", $log_file);
                $failed_transactions[] = "CLL$loan_lid - $error_msg";
                http_response_code(500);
                die($error_msg);
            }
            
            if (townum($loan_data) > 0) {
                $loan_details = towfetch($loan_data);
                $uid = $loan_details['uid'];
                
                // Get user details
                $uid_escaped = mysqli_real_escape_string($db, $uid);
                $user_data = towquery($db, "SELECT * FROM user WHERE id='$uid_escaped'");
                if (!$user_data || townum($user_data) == 0) {
                    $error_msg = "User not found for loan CLL$loan_lid";
                    writeWebhookLog("ERROR: $error_msg", $log_file);
                    $failed_transactions[] = "CLL$loan_lid - $error_msg";
                    http_response_code(404);
                    die($error_msg);
                }
                $user_details = towfetch($user_data);
                
                // Process loan clearance with transaction support
                $clearance_success = processLoanClearance($db, $loan_lid, $uid, $amount, $bank_ref_num, 'full');
                
                if ($clearance_success) {
                    writeWebhookLog("SUCCESS: Loan CLL$loan_lid cleared successfully | User: {$user_details['name']} | Amount: ₹$amount", $log_file);
                    $successful_transactions[] = "CLL$loan_lid - ₹$amount";
                    
                    // Generate no-due certificate
                    $cert_result = file_get_contents("https://creditlab.in/zxc/?url3=https://creditlab.in/no-due-certificate2.php?id=".$loan_lid."&email=".$user_details['email']);
                    if ($cert_result) {
                        writeWebhookLog("No-due certificate generated for CLL$loan_lid", $log_file);
                    } else {
                        writeWebhookLog("WARNING: Failed to generate no-due certificate for CLL$loan_lid", $log_file);
                    }
                    
                    // Send SMS notification
                    $template_id = '1107165683325768963';
                    $mobile = $user_details['mobile'];
                    $message = "Dear {$user_details['name']}, we acknowledge the repayment of your loan CLL$loan_lid & it's cleared. You can apply again. https://creditlab.in/ -Creditlab";
                    include 'send_sms.php';
                    writeWebhookLog("SMS notification sent for CLL$loan_lid to $mobile", $log_file);
                    
                } else {
                    $error_msg = "Failed to process loan clearance for CLL$loan_lid";
                    writeWebhookLog("ERROR: $error_msg", $log_file);
                    $failed_transactions[] = "CLL$loan_lid - $error_msg";
                    http_response_code(500);
                    die($error_msg);
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
    }
}
elseif ($data['furl'] == 'https://creditlab.in/easebuzz_callback.php') {
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

} elseif (isset($data['furl']) && $data['furl'] == 'https://creditlab.in/payeasebuzz/response.php') {
    // Validate required fields for payment response processing
    $required_fields = ['txnid', 'status', 'amount', 'bank_ref_num'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
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
    $amount = $result['amount'];
    $bank_ref_num = $result['bank_ref_num'];
    // FIX: Define $payment_method. Change 'payment_mode' to the actual key from your payment provider.
    $payment_method = isset($result['payment_mode']) ? $result['payment_mode'] : 'N/A';
    
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
        $txnid_escaped = mysqli_real_escape_string($db, $txnid);
        $pg_transaction = towquery($db, "SELECT * FROM pg_transaction WHERE txnid='$txnid_escaped' AND `status`!='success'");
        if (townum($pg_transaction) > 0) {
            writeWebhookLog("Processing payment for txnid: $txnid | Amount: ₹$amount", $log_file);
            $pg_data = towfetch($pg_transaction);
            $cllid = $pg_data['loan_id'];
            
            $cllid_escaped = mysqli_real_escape_string($db, $cllid);
            $loan_data = towquery($db, "SELECT * FROM loan WHERE id='$cllid_escaped'");
            $loan_details = towfetch($loan_data);
            $uid = $loan_details['uid'];

            // Check if loan is already cleared to prevent duplicate processing
            if ($loan_details['status_log'] == 'cleared') {
                writeWebhookLog("SKIPPED: Loan CLL{$loan_details['lid']} already cleared for txnid: $txnid", $log_file);
                $amount_escaped = mysqli_real_escape_string($db, $amount);
                $payment_method_escaped = mysqli_real_escape_string($db, $payment_method);
                $bank_ref_num_escaped = mysqli_real_escape_string($db, $bank_ref_num);
                towquery($db, "UPDATE `pg_transaction` SET `status`='success', `amount`='$amount_escaped', `payment_method`='$payment_method_escaped', `bank_reference_number`='$bank_ref_num_escaped' WHERE txnid='$txnid_escaped'");
                // Skip to next iteration - this is not in a loop, so we'll just exit this block
                goto end_payment_processing;
            }

            // FIX: Fetch user details so $user_details is defined
            $uid_escaped = mysqli_real_escape_string($db, $uid);
            $user_data_result = towquery($db, "SELECT * FROM user WHERE id='$uid_escaped'");
            $user_details = towfetch($user_data_result);

            $dpd = $loan_details['exhausted_period'] - 30;
            $point = ($dpd > 0) ? (($dpd > 30) ? -50 : (($dpd > 10) ? -8 : 2)) : 8;

            $point_escaped = mysqli_real_escape_string($db, $point);
            $current_date_escaped = mysqli_real_escape_string($db, date('Y-m-d'));
            $loan_id_escaped = mysqli_real_escape_string($db, $loan_details['id']);
            $loan_lid_escaped = mysqli_real_escape_string($db, $loan_details['lid']);
            $bank_ref_num_escaped = mysqli_real_escape_string($db, $bank_ref_num);
            $current_datetime_escaped = mysqli_real_escape_string($db, date('Y-m-d H:i:s'));
            $amount_escaped = mysqli_real_escape_string($db, $amount);

            towquery($db, "UPDATE `user` SET `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point_escaped WHERE id='$uid_escaped'");
            towquery($db, "UPDATE `loan` SET `action`='cleared', `status_log`='cleared', `cleard_date`='$current_date_escaped' WHERE id='$loan_id_escaped'");
            towquery($db, "UPDATE `user` SET `status`='cleared' WHERE id='$uid_escaped'");
            towquery($db, "UPDATE `loan_apply` SET `status`='cleared' WHERE id='$loan_lid_escaped'");
            towquery($db, "INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES ('$uid_escaped', '$loan_lid_escaped', '$bank_ref_num_escaped', '$current_datetime_escaped', '$amount_escaped', 'full')");

            // FIX: $user_details is now defined and can be used here
            file_get_contents("https://creditlab.in/zxc/?url3=https://creditlab.in/no-due-certificate2.php?id=".$loan_details['lid']."&email=".$user_details['email']);
            
            $template_id='1107165683325768963';
            // FIX: $user_details is now defined and can be used here
            $mobile = $user_details['mobile'];
            $message = "Dear {$user_details['name']}, we acknowledge the repayment of your loan CLL{$loan_details['lid']} & it's cleared. You can apply again. https://creditlab.in/ -Creditlab";
            include '../send_sms.php';

            // FIX: The query now includes the defined $payment_method variable
            $payment_method_escaped = mysqli_real_escape_string($db, $payment_method);
            towquery($db, "UPDATE `pg_transaction` SET `status`='success', `amount`='$amount_escaped', `payment_method`='$payment_method_escaped', `bank_reference_number`='$bank_ref_num_escaped' WHERE txnid='$txnid_escaped'");
        }
    } else {
        $error_msg = isset($result['error_Message']) ? $result['error_Message'] : "Unknown error.";
        $txnid_escaped = mysqli_real_escape_string($db, $txnid);
        towquery($db, "UPDATE `pg_transaction` SET `status`='failure' WHERE txnid='$txnid_escaped'");
    }
    
    end_payment_processing:
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
mysqli_close($db);
?>
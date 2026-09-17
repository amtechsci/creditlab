<?php
include '../db.php';
require_once __DIR__ . '/../lib/easebuzz_enach.php';

// --- ENHANCED LOGGING ---
$current_date = date('Y-m-d');
$current_time = date('Y-m-d H:i:s');

// Create daily log file
$log_file = "logs/zzenach_" . $current_date . ".log";
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Function to write detailed logs
function writeZzenachLog($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    error_log($message); // Also log to system error log
}

// Start logging
writeZzenachLog("=== ZZENACH PROCESSING STARTED ===", $log_file);
writeZzenachLog("Date: $current_date | Time: $current_time", $log_file);

/**
 * Calculate total amount with dynamic calculation including +1 day extra
 * @param array $loan Loan data from database
 * @param array $loan_apply Loan application data
 * @return float Total calculated amount
 */
function calculateTotalAmount($loan, $loan_apply) {
    require_once __DIR__ . '/../lib/loan_charge_calc.php';
    return creditlab_enach_presentment_breakdown($loan, $loan_apply)['total'];
}

function calculateAmountBreakdown($loan, $loan_apply) {
    require_once __DIR__ . '/../lib/loan_charge_calc.php';
    return creditlab_enach_amount_log_fields($loan, $loan_apply);
}

/**
 * Initiates eNACH presentment — Autocollect (new cai… mandates) or legacy PG (old customer_authentication_id).
 *
 * @return string JSON for zzenach ({status:1|0, error_desc, api, ...})
 */
function initiateEasebuzzDirectDebit(array $postParams, array $easebuzz_row = []): string
{
    require_once __DIR__ . '/../lib/easebuzz_enach.php';
    return creditlab_easebuzz_initiate_direct_debit_json($postParams, $easebuzz_row);
}


// --- Main Logic ---

$lid = towreal($_GET['lid']);
writeZzenachLog("Processing loan CLL$lid", $log_file);

$userdata = towquery("SELECT * FROM `loan` INNER JOIN user ON user.id=loan.uid WHERE lid=$lid");
$userdataff = towfetch($userdata);

if (!$userdataff) {
    writeZzenachLog("ERROR: Loan CLL$lid not found", $log_file);
    echo "<script>alert('Loan not found!');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
    exit;
}

// Check if loan is already cleared to prevent duplicate autopay deduction
if ($userdataff['status_log'] == 'cleared' || $userdataff['action'] == 'cleared') {
    writeZzenachLog("SKIPPED: Loan CLL$lid is already cleared. Preventing duplicate autopay deduction.", $log_file);
    echo "<script>alert('Loan CLL$lid is already cleared. No payment needed.');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
    exit;
}

$loan_data = towquery("SELECT * FROM loan_apply WHERE id='$lid'");
$loan_fetch = towfetch($loan_data);

if (!$loan_fetch) {
    writeZzenachLog("ERROR: Loan application not found for CLL$lid", $log_file);
    echo "<script>alert('Loan application not found!');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
    exit;
}

// Use dynamic calculation with +1 day extra
$totalamount = calculateTotalAmount($userdataff, $loan_fetch);
$totalamount = number_format($totalamount, 2, '.', '');

// Calculate breakdown for logging
$breakdown = calculateAmountBreakdown($userdataff, $loan_fetch);

writeZzenachLog("Loan CLL$lid: Dynamic calculation completed - Amount: ₹$totalamount", $log_file);
writeZzenachLog("Loan CLL$lid: Breakdown - Processed: ₹" . number_format($breakdown['processed_amount'], 2) . " | Processing Fee: ₹" . number_format($breakdown['p_fee'], 2) . " | Service Charge: ₹" . number_format($breakdown['service_charge'], 2) . " | Penalty: ₹" . number_format($breakdown['penalty_charge'], 2), $log_file);

// Autocollect mandates: authorized (new) or legacy accepted
$easebuzz_adtd = towquery("SELECT * FROM `easebuzz_adtd` WHERE uid='{$userdataff['uid']}' AND LOWER(authorization_status) IN ('authorized', 'accepted')");
$enach_count = townum($easebuzz_adtd);

if($enach_count > 0){
    writeZzenachLog("Loan CLL$lid: Found $enach_count E-Nach authorization(s) for user {$userdataff['uid']}", $log_file);
    
    $success_count = 0;
    $failed_count = 0;
    $success_messages = [];
    $error_messages = [];
    
    // Process each E-Nach authorization
    $auth_count = 0;
    while ($easebuzz_adtdff = towfetch($easebuzz_adtd)) {
        $auth_count++;
        writeZzenachLog("Loan CLL$lid: Processing E-Nach authorization #$auth_count of $enach_count | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | API: " . creditlab_easebuzz_presentment_api_for_row($easebuzz_adtdff), $log_file);
        
        $paymentDetails = [
            "amount" => "$totalamount",
            "productinfo" => "Loan Repayment Manual",
            "firstname" => $userdataff['name'],
            "email" => $userdataff['email'],
            "phone" => $userdataff['mobile'],
            "customer_authentication_id" => $easebuzz_adtdff['customer_authentication_id'],
            "merchant_debit_id" => "CLL_AUTO_".$lid."_".time(),
            "auto_debit_access_key" => $easebuzz_adtdff['auto_debit_access_key'],
            "udf1" => "CREDITLAB_ZZENACH",
        ];
        
        // Debug: Log the exact API call data
        writeZzenachLog("Loan CLL$lid: API Call Data - " . json_encode($paymentDetails), $log_file);
        
        $apiResponse = initiateEasebuzzDirectDebit($paymentDetails, $easebuzz_adtdff);
        writeZzenachLog("Loan CLL$lid: API Response - " . $apiResponse, $log_file);
        
        $res = json_decode($apiResponse, true);
        
        // Check if the response was successfully decoded and if the status key exists and is true
        if($res && isset($res['status']) && $res['status']){
            towquery("UPDATE `loan` SET `enach_request`=1, `enach_request_date`='".date('Y-m-d')."' WHERE lid=$lid");
            $success_count++;
            $success_messages[] = "E-Nach #$auth_count (Auth ID: {$easebuzz_adtdff['customer_authentication_id']}) - SUCCESS";
            writeZzenachLog("SUCCESS: E-Nach request initiated for CLL$lid | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | Amount: ₹$totalamount", $log_file);
        } else {
            $failed_count++;
            $errorMessage = isset($res['error_desc']) ? $res['error_desc'] : 'Unknown API error';
            $error_messages[] = "E-Nach #$auth_count (Auth ID: {$easebuzz_adtdff['customer_authentication_id']}) - FAILED: $errorMessage";
            writeZzenachLog("FAILED: E-Nach request for CLL$lid | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']} | Error: $errorMessage", $log_file);
        }
    }
    
    // Prepare final message
    $final_message = "E-Nach Processing Complete for CLL$lid:\n\n";
    $final_message .= "Total E-Nach Authorizations: $enach_count\n";
    $final_message .= "Successful: $success_count\n";
    $final_message .= "Failed: $failed_count\n\n";
    
    if (!empty($success_messages)) {
        $final_message .= "SUCCESS DETAILS:\n";
        foreach ($success_messages as $msg) {
            $final_message .= "✓ $msg\n";
        }
        $final_message .= "\n";
    }
    
    if (!empty($error_messages)) {
        $final_message .= "FAILED DETAILS:\n";
        foreach ($error_messages as $msg) {
            $final_message .= "✗ $msg\n";
        }
    }
    
    writeZzenachLog("=== ZZENACH PROCESSING COMPLETED ===", $log_file);
    writeZzenachLog("Final Result: $success_count successful, $failed_count failed out of $enach_count total", $log_file);
    
    echo "<script>alert('$final_message');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
    exit;
    
} else {
    writeZzenachLog("ERROR: No E-Nach details found for user {$userdataff['uid']}, loan CLL$lid", $log_file);
    echo "<script>alert('E-Nach details not found for this user.');window.location.replace('/admin/profile.php?id=".$userdataff['uid']."&tab=oldloan');</script>";
    exit;
}
?>
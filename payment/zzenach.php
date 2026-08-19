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
    // Get current date and calculate tday (days since processed_date)
    $stop_date = date_create($loan['processed_date']);
    $sa = date_create(date('Y-m-d 23:59:59'));
    $aa = date_diff($stop_date, $sa);
    $tday = (int)$aa->format("%a");
    
    // Get days from loan_apply
    $loan_days_raw = isset($loan_apply['days']) ? (int)$loan_apply['days'] : 30;
    $loan_is_emi = isset($loan['is_emi']) ? (int)$loan['is_emi'] : (($loan_days_raw <= 30) ? 1 : 0);
    $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
    
    // E-Nach triggers on tday = days + 1 (DPD = 1), so use tday for calculations
    $days = $tday; // Use tday for service charge calculation
    
    // Calculate base amount with GST on processing fee (18% GST)
    $t = $loan['processed_amount'] + $loan['p_fee'] + ($loan['p_fee'] * 0.18);
    
    $service_charge = 0;
    $penality = 0;
    
    // Calculate service charge based on interest percentage
    if ($loan_apply['interest_percentage'] == 1) {
        // Special case for 1% interest - tiered calculation
        $remaining_days = $days;
        if ($remaining_days >= 3) {
            $fee = $t * 3 / 100 * 0;
            $remaining_days = $remaining_days - 3;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if ($remaining_days >= 7) {
            $fee = $t * 7 / 100 * 0.1;
            $remaining_days = $remaining_days - 7;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0.1;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if ($remaining_days >= 20) {
            $fee = $t * 20 / 100 * 0.115;
            $remaining_days = $remaining_days - 20;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0.115;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if ($remaining_days >= 1) {
            $fee = $t * $remaining_days / 100 * 0.1;
            $remaining_days = 0;
            $service_charge += $fee;
        }
    } else {
        // Standard interest calculation
        $fee = $t * $days / 100 * $loan_apply['interest_percentage'];
        $service_charge += $fee;
    }
    
    // Calculate penalty based on DPD (Days Past Due) = tday - loan_days
    // E-Nach triggers when DPD = 1, so penalty starts from DPD = 1
    $dpd = $tday - $loan_days; // DPD = Days Past Due
    if ($dpd > 0) {
        $penalitydays = $dpd - 1; // Penalty starts from DPD = 1, so subtract 1
        $penality = (($t) / 100) * 4; // First day penalty (4%)
        if ($penalitydays > 0) {
            $atnp = ((($t) / 100) * 0.2) * $penalitydays; // Additional penalty for remaining days (0.2%)
            $penality = $penality + $atnp;
        }
    } else {
        $penality = 0;
    }
    
    // Add 18% GST to penalty
    $penality = ($penality + ($penality * 0.18));
    
    // Calculate total amount (including GST on processing fee)
    $p_fee_gst = $loan['p_fee'] * 0.18;
    $totalamount = (float)$loan['processed_amount'] + (float)$loan['p_fee'] + $p_fee_gst + (float)$service_charge + (float)$penality;
    
    return $totalamount;
}

/**
 * Calculate detailed breakdown of loan amount components
 * @param array $loan Loan data from database
 * @param array $loan_apply Loan application data
 * @return array Breakdown of all amount components
 */
function calculateAmountBreakdown($loan, $loan_apply) {
    // Get current date and calculate tday (days since processed_date)
    $stop_date = date_create($loan['processed_date']);
    $sa = date_create(date('Y-m-d 23:59:59'));
    $aa = date_diff($stop_date, $sa);
    $tday = (int)$aa->format("%a");
    
    // Get days from loan_apply
    $loan_days_raw = isset($loan_apply['days']) ? (int)$loan_apply['days'] : 30;
    $loan_is_emi = isset($loan['is_emi']) ? (int)$loan['is_emi'] : (($loan_days_raw <= 30) ? 1 : 0);
    $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
    
    // E-Nach triggers on tday = days + 1 (DPD = 1), so use tday for calculations
    $days = $tday; // Use tday for service charge calculation
    
    // Calculate base amount with GST on processing fee (18% GST)
    $p_fee_gst = $loan['p_fee'] * 0.18;
    $t = $loan['processed_amount'] + $loan['p_fee'] + $p_fee_gst;
    
    $service_charge = 0;
    $penality = 0;
    
    // Calculate service charge based on interest percentage
    if ($loan_apply['interest_percentage'] == 1) {
        // Special case for 1% interest - tiered calculation
        $remaining_days = $days;
        if ($remaining_days >= 3) {
            $fee = $t * 3 / 100 * 0;
            $remaining_days = $remaining_days - 3;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if ($remaining_days >= 7) {
            $fee = $t * 7 / 100 * 0.1;
            $remaining_days = $remaining_days - 7;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0.1;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if ($remaining_days >= 20) {
            $fee = $t * 20 / 100 * 0.115;
            $remaining_days = $remaining_days - 20;
            $service_charge += $fee;
        } else {
            $fee = $t * $remaining_days / 100 * 0.115;
            $remaining_days = 0;
            $service_charge += $fee;
        }
        if ($remaining_days >= 1) {
            $fee = $t * $remaining_days / 100 * 0.1;
            $remaining_days = 0;
            $service_charge += $fee;
        }
    } else {
        // Standard interest calculation
        $fee = $t * $days / 100 * $loan_apply['interest_percentage'];
        $service_charge += $fee;
    }
    
    // Calculate penalty based on DPD (Days Past Due) = tday - loan_days
    // E-Nach triggers when DPD = 1, so penalty starts from DPD = 1
    $dpd = $tday - $loan_days; // DPD = Days Past Due
    if ($dpd > 0) {
        $penalitydays = $dpd - 1; // Penalty starts from DPD = 1, so subtract 1
        $penality = (($t) / 100) * 4; // First day penalty (4%)
        if ($penalitydays > 0) {
            $atnp = ((($t) / 100) * 0.2) * $penalitydays; // Additional penalty for remaining days (0.2%)
            $penality = $penality + $atnp;
        }
    } else {
        $penality = 0;
    }
    
    // Add 18% GST to penalty
    $penality = ($penality + ($penality * 0.18));
    
    return [
        'processed_amount' => (float)$loan['processed_amount'],
        'p_fee' => (float)$loan['p_fee'],
        'p_fee_gst' => $p_fee_gst,
        'service_charge' => $service_charge,
        'penalty_charge' => $penality,
        'days' => $days
    ];
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
<?php

include '../db.php';

// Set headers to download the CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=loan_default_cibil.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Output the column headings for the CIBIL CSV file
fputcsv($output, [
    'Consumer Name', 'Date of Birth', 'Gender', 'Income Tax ID Number', 'Passport Number', 'Passport Issue Date', 
    'Passport Expiry Date', 'Voter ID Number', 'Driving License Number', 'Driving License Issue Date', 
    'Driving License Expiry Date', 'Ration Card Number', 'Universal ID Number', 'Additional ID #1', 'Additional ID #2', 
    'Telephone No.Mobile', 'Telephone No.Residence', 'Telephone No.Office', 'Extension Office', 'Telephone No.Other', 
    'Extension Other', 'Email ID 1', 'Email ID 2', 'Address Line 1', 'State Code 1', 'PIN Code 1', 'Address Category 1', 
    'Residence Code 1', 'Address Line 2', 'State Code 2', 'PIN Code 2', 'Address Category 2', 'Residence Code 2', 
    'Current/New Member Code', 'Current/New Member Short Name', 'Curr/New Account No', 'Account Type', 
    'Ownership Indicator', 'Date Opened/Disbursed', 'Date of Last Payment', 'Date Closed', 'Date Reported', 
    'High Credit/Sanctioned Amt', 'Current Balance', 'Amt Overdue', 'No of Days Past Due', 'Old Mbr Code', 
    'Old Mbr Short Name', 'Old Acc No', 'Old Acc Type', 'Old Ownership Indicator', 'Suit Filed / Wilful Default', 
    'Credit Facility Status', 'Asset Classification', 'Value of Collateral', 'Type of Collateral', 'Credit Limit', 
    'Cash Limit', 'Rate of Interest', 'Repayment Tenure', 'EMI Amount', 'Written-off Amount (Total)', 
    'Written-off Principal Amount', 'Settlement Amt', 'Payment Frequency', 'Actual Payment Amt', 'Occupation Code', 
    'Income', 'Net/Gross Income Indicator', 'Monthly/Annual Income Indicator', 'CKYC', 'NREGA Card Number'
]);

// Match account_manager.php logic exactly: Get all UIDs first, then fetch loans per user
$default_loans_all = [];
$all_am_query = towquery("SELECT loan_apply.id as laid, user.id as uid FROM loan_apply INNER JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status IN ('account manager', 'recovery officer')");
$potential_default_uids = [];
while($r = towfetch($all_am_query)) { 
    $potential_default_uids[] = $r['uid']; 
}
$potential_default_uids = array_unique($potential_default_uids);

foreach($potential_default_uids as $uid) {
    $q = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi, loan_apply.days as loan_apply_days, loan_apply.status_date FROM user INNER JOIN loan ON loan.uid=user.id INNER JOIN loan_apply ON loan_apply.id=loan.lid WHERE user.id=$uid AND loan.status_log IN ('recovery officer', 'account manager')");
    while($b = towfetch($q)) {
        if (!empty($b['processed_date'])) {
            // Calculate DPD exactly like account_manager.php (line 90-95)
            $processed_date_str = date('Y-m-d', strtotime($b['processed_date'] . " -1 day"));
            $tday = ceil((strtotime(date('Y-m-d')) - strtotime($processed_date_str)) / (60 * 60 * 24));
            $loan_days_raw = isset($b['loan_apply_days']) ? (int)$b['loan_apply_days'] : 30;
            $loan_is_emi = isset($b['is_emi']) ? (int)$b['is_emi'] : 0;
            $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
            $dpd = $tday - $loan_days;
            
            // Only include if DPD > 0 (for CIBIL default report)
            if ($dpd > 0) {
                $b['calculated_dpd'] = $dpd;
                $default_loans_all[] = $b;
            }
        }
    }
}

// Remove duplicates (same as account_manager.php lines 107-115)
$unique_default_loans = [];
$seen_lids = [];
foreach($default_loans_all as $l) {
    if (!in_array($l['lid'], $seen_lids)) {
        $unique_default_loans[] = $l;
        $seen_lids[] = $l['lid'];
    }
}

// Sort by DPD descending (same as account_manager.php line 117)
usort($unique_default_loans, function($a, $b) { return $b['calculated_dpd'] <=> $a['calculated_dpd']; });

// Loop through unique rows and write each row to the CSV
foreach ($unique_default_loans as $row) {
    // Format dates - handle NULL values
    $dob = '';
    if (!empty($row['dob'])) {
        $dob = date('dmY', strtotime($row['dob']));
        if (substr($dob, 0, 1) === '0') {
            $dob = "\t" . $dob;
        }
    } else {
        $dob = "\t01011970"; // Default date if NULL
    }
    
    $date_opened = '';
    if (!empty($row['processed_date'])) {
        $date_opened = date('dmY', strtotime($row['processed_date']));
        if (substr($date_opened, 0, 1) === '0') {
            $date_opened = "\t" . $date_opened;
        }
    } else {
        $date_opened = "\t01011970"; // Default date if NULL
    }

    // Map gender - handle NULL values
    $gender_map = ['female' => 1, 'male' => 2, 'transgender' => 3];
    $marital_status = isset($row['marital_status']) ? strtolower($row['marital_status']) : '';
    $gender = isset($gender_map[$marital_status]) ? $gender_map[$marital_status] : 0;

    // Get DPD from calculated value
    $dpd = isset($row['calculated_dpd']) ? $row['calculated_dpd'] : 0;
    
    // Calculate financial details
    $processing_fees = isset($row['p_fee']) ? (float)$row['p_fee'] : (isset($row['processing_fees']) ? (float)$row['processing_fees'] : 0);
    $amount = isset($row['processed_amount']) ? (float)$row['processed_amount'] : (isset($row['amount']) ? (float)$row['amount'] : 0);
    
    $gst = $processing_fees * 0.18;
    $totalamount = round($amount + $processing_fees + $gst); // CIBIL: whole numbers only
    $service_charge = isset($row['service_charge']) ? (float)$row['service_charge'] : 0;
    $penality_charge = isset($row['penality_charge']) ? (float)$row['penality_charge'] : 0;
    $current_balance = round($totalamount + $service_charge + $penality_charge); // CIBIL: whole numbers only
    
    // Suit Filed: 01 if DPD > 60
    $suit_filed = ($dpd > 60) ? '01' : '';
    
    // Format state code
    $state_code = isset($row['state_code']) ? $row['state_code'] : '';
    if ($state_code >= 1 && $state_code <= 9) {
        $state_code = "\t0" . $state_code;
    }
    
    $data = [
        isset($row['pan_name']) ? $row['pan_name'] : '',     // Consumer Name
        $dob,                                                  // Date of Birth
        $gender,                                               // Gender
        isset($row['pan']) ? $row['pan'] : '',                // Income Tax ID Number
        '', '', '', '', '', '', '', '', '', '', '',           // Passport to Additional ID #2
        '', '', '', '', '', '',                               // Phone numbers
        '', '',                                               // Emails
        isset($row['present_address']) ? $row['present_address'] : '', // Address Line 1
        $state_code,                                          // State Code 1
        isset($row['pincode']) ? $row['pincode'] : '',       // PIN Code 1
        "\t02",                                               // Address Category 1
        '',                                                   // Residence Code 1
        '', '', '', '', '',                                   // Address 2 fields
        'NB36250001',                                         // Current/New Member Code
        'SONUMARPL',                                          // Current/New Member Short Name
        $row['lid'],                                          // Curr/New Account No
        "\t69",                                               // Account Type
        '1',                                                  // Ownership Indicator
        $date_opened,                                         // Date Opened/Disbursed
        '',                                                   // Date of Last Payment
        '',                                                   // Date Closed
        "\t".date('dmY'),                                     // Date Reported
        $totalamount,                                         // High Credit/Sanctioned Amt
        $current_balance,                                     // Current Balance (already rounded)
        $current_balance,                                     // Amt Overdue (already rounded)
        $dpd,                                                 // No of Days Past Due
        '', '', '', '', '',                                   // Old member fields
        $suit_filed,                                          // Suit Filed / Wilful Default
        '',                                                   // Credit Facility Status
        '',                                                   // Asset Classification (blank)
        '', '', '', '',                                       // Collateral and limits
        '', '', '',                                           // Rate, Tenure, EMI
        '', '',                                               // Written-off amounts
        '',                                                   // Settlement Amt
        '', '',                                               // Payment fields
        '', '', '', '', '', ''                                // Occupation to NREGA
    ];

    fputcsv($output, $data);
}

fclose($output);
?>

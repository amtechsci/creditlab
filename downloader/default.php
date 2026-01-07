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

// Query matching account_manager.php logic exactly
// Start from loan table (like the working SQL), then join others
$sql = "SELECT 
    l.lid, l.processed_date, l.service_charge, l.penality_charge, l.is_emi,
    la.days as loan_apply_days, la.amount, la.processing_fees,
    u.pan_name, u.dob, u.marital_status, u.pan, u.mobile, u.email, 
    u.present_address, u.state_code, u.pincode
FROM loan l
INNER JOIN loan_apply la ON la.id = l.lid
LEFT JOIN user u ON u.id = la.uid
WHERE l.status_log IN ('recovery officer', 'account manager')";

$result = towquery($sql);

// Store unique rows to prevent duplicates (using lid as business key)
$unique_rows = [];
$seen_lids = [];

// Collect unique rows and calculate DPD
while ($row = towfetch($result)) {
    // Remove duplicates based on loan ID (business key)
    if (isset($seen_lids[$row['lid']])) {
        continue; // Skip duplicate
    }
    
    // Skip if no processed_date
    if (empty($row['processed_date'])) {
        continue;
    }
    
    // Calculate DPD using DateTime for accurate day difference (matches SQL DATEDIFF exactly)
    $today = new DateTime(date('Y-m-d'));
    $processed = new DateTime(date('Y-m-d', strtotime($row['processed_date'])));
    $processed->modify('-1 day'); // Same as SQL: DATE_SUB(processed_date, INTERVAL 1 DAY)
    $tday = (int)$today->diff($processed)->days;
    
    // Get loan days - check is_emi flag like account_manager.php
    $loan_days_raw = isset($row['loan_apply_days']) ? (int)$row['loan_apply_days'] : 30;
    $loan_is_emi = isset($row['is_emi']) ? (int)$row['is_emi'] : 0;
    $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
    
    // Calculate DPD
    $dpd = $tday - $loan_days;
    
    // Only include if DPD > 0
    if ($dpd <= 0) {
        continue;
    }
    
    $seen_lids[$row['lid']] = true;
    $row['calculated_dpd'] = $dpd;
    $unique_rows[] = $row;
}

// Loop through unique rows and write each row to the CSV
foreach ($unique_rows as $row) {
    
    // Format dates
    $dob = date('dmY', strtotime($row['dob']));
    $date_opened = date('dmY', strtotime($row['processed_date']));

    // Map gender
    $gender_map = ['female' => 1, 'male' => 2, 'transgender' => 3];
    $gender = isset($gender_map[strtolower($row['marital_status'])]) ? $gender_map[strtolower($row['marital_status'])] : 0;

    // Get DPD from calculated value
    $dpd = isset($row['calculated_dpd']) ? $row['calculated_dpd'] : 0;
    
    // Calculate financial details
    $gst = (float)$row['processing_fees'] * 0.18;
    $totalamount = (float)$row['amount'] + (float)$row['processing_fees'] + $gst;
    $current_balance = $totalamount + (float)$row['service_charge'] + (float)$row['penality_charge'];
    
    // Suit Filed: 01 if DPD > 60
    $suit_filed = ($dpd > 60) ? '01' : '';
    
    // Format state code
    $state_code = $row['state_code'];
    if ($state_code >= 1 && $state_code <= 9) {
        $state_code = "\t0" . $state_code;
    }
    
    // Format dates with tab if starts with 0
    if (substr($dob, 0, 1) === '0') {
        $dob = "\t" . $dob;
    }
    if (substr($date_opened, 0, 1) === '0') {
        $date_opened = "\t" . $date_opened;
    }
    
    $data = [
        $row['pan_name'],               // Consumer Name
        $dob,                           // Date of Birth
        $gender,                        // Gender
        $row['pan'],                    // Income Tax ID Number
        '', '', '', '', '', '', '', '', '', '', '',  // Passport to Additional ID #2
        '', '', '', '', '', '',         // Phone numbers
        '', '',                         // Emails
        $row['present_address'],        // Address Line 1
        $state_code,                    // State Code 1
        $row['pincode'],                // PIN Code 1
        "\t02",                         // Address Category 1
        '',                             // Residence Code 1
        '', '', '', '', '',             // Address 2 fields
        'NB36250001',                   // Current/New Member Code
        'SONUMARPL',                    // Current/New Member Short Name
        $row['lid'],                    // Curr/New Account No
        "\t69",                         // Account Type
        '1',                            // Ownership Indicator
        $date_opened,                   // Date Opened/Disbursed
        '',                             // Date of Last Payment
        '',                             // Date Closed
        "\t".date('dmY'),               // Date Reported
        $totalamount,                   // High Credit/Sanctioned Amt
        ceil($current_balance),         // Current Balance
        ceil($current_balance),         // Amt Overdue
        $dpd,                           // No of Days Past Due
        '', '', '', '', '',             // Old member fields
        $suit_filed,                    // Suit Filed / Wilful Default
        '',                             // Credit Facility Status
        '',                             // Asset Classification (blank)
        '', '', '', '',                 // Collateral and limits
        '', '', '',                     // Rate, Tenure, EMI
        '', '',                         // Written-off amounts
        '',                             // Settlement Amt
        '', '',                         // Payment fields
        '', '', '', '', '', ''          // Occupation to NREGA
    ];

    fputcsv($output, $data);
}

fclose($output);
?>

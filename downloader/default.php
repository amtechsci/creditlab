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

// Simple query: Get loans where exhausted_period > total_time (DPD > 0)
$sql = "SELECT 
    u.pan_name, u.dob, u.marital_status, u.pan, u.mobile, u.email, 
    u.present_address, u.state_code, u.pincode, 
    l.processed_date, l.lid, la.amount, la.processing_fees, 
    l.service_charge, l.exhausted_period, l.total_time, l.penality_charge
FROM user u
LEFT JOIN loan_apply la ON u.id = la.uid
JOIN loan l ON la.id = l.lid
WHERE l.status_log IN ('recovery officer', 'account manager')
AND l.exhausted_period > l.total_time";

$result = towquery($sql);

while ($row = towfetch($result)) {
    // Format dates
    $dob = date('dmY', strtotime($row['dob']));
    $date_opened = date('dmY', strtotime($row['processed_date']));

    // Map gender
    $gender_map = ['female' => 1, 'male' => 2, 'transgender' => 3];
    $gender = isset($gender_map[strtolower($row['marital_status'])]) ? $gender_map[strtolower($row['marital_status'])] : 0;

    // Calculate financial details
    $gst = (float)$row['processing_fees'] * 0.18;
    $totalamount = (float)$row['amount'] + (float)$row['processing_fees'] + $gst;
    $current_balance = $totalamount + (float)$row['service_charge'] + (float)$row['penality_charge'];
    
    // DPD = exhausted_period - total_time
    $dpd = (int)$row['exhausted_period'] - (int)$row['total_time'];
    
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

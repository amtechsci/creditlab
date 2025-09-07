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

// SQL query to fetch data for loans in default (status 'recovery officer')
$sql = "SELECT u.pan_name, u.dob, u.marital_status, u.pan, u.mobile, u.email, u.present_address, u.state_code, u.pincode, u.rcid, 
               l.processed_date, l.lid, la.amount, la.processing_fees, l.service_charge, l.exhausted_period, l.penality_charge, l.status_log, la.status
        FROM user u
        LEFT JOIN loan_apply la ON u.id = la.uid
        JOIN loan l ON la.id = l.lid
        WHERE DATEDIFF(NOW(), processed_date) > 29 AND l.status_log in ('recovery officer','account manager')"; // Filter for 'recovery officer' status

// Execute the query
$result = towquery($sql);

// Loop through the result and write each row to the CSV
while ($row = towfetch($result)) {
    // Format the date of birth and loan dates as DDMMYYYY
    $dob = date('dmY', strtotime($row['dob']));
    $date_opened = date('dmY', strtotime($row['processed_date']));

    // Map gender to numbers
    $gender_map = ['female' => 1, 'male' => 2, 'transgender' => 3];
    $gender = isset($gender_map[strtolower($row['marital_status'])]) ? $gender_map[strtolower($row['marital_status'])] : 0;

    // Calculate financial details
    $current_balance = $row['amount'] + $row['service_charge']; // Add service charge to the loan amount
    
    $gst = ($row['processing_fees']*0.18);
    $totalamount = $row['amount'] + $row['processing_fees'] + $gst;
    $dpd = $row['exhausted_period']-30;
    if($dpd > 61){$dpdt = '05';}elseif($dpd > 31){$dpdt = '03';}elseif($dpd > 1){$dpdt = '02';}else{$dpdt = '01';}
    if($dpd > 60){$sf = '02';}else{$dpdt = '';}
    $cb = $totalamount+$row['service_charge']+$row['penality_charge'];
    
    $state_code = $row['state_code'];
    if ($state_code >= 1 && $state_code <= 9) {
        $state_code = "\t0" . $state_code;
    }
    
    if (substr($dob, 0, 1) === '0') {
        $dob = "\t" . $dob;
    }
    
    if (substr($date_opened, 0, 1) === '0') {
        $date_opened = "\t" . $date_opened;
    }
    // Create the array for CSV row
    $data = [
        $row['pan_name'],               // Consumer Name
        $dob,                       // Date of Birth
        $gender,                    // Gender
        $row['pan'],                // Income Tax ID Number
        '',                         // Passport Number
        '',                         // Passport Issue Date
        '',                         // Passport Expiry Date
        '',                         // Voter ID Number
        '',                         // Driving License Number
        '',                         // Driving License Issue Date
        '',                         // Driving License Expiry Date
        '',                         // Ration Card Number
        '',                         // Universal ID Number
        '',                         // Additional ID #1
        '',                         // Additional ID #2
        '',             // Telephone No.Mobile
        '',                         // Telephone No.Residence
        '',                         // Telephone No.Office
        '',                         // Extension Office
        '',                         // Telephone No.Other
        '',                         // Extension Other
        '',              // Email ID 1
        '',                         // Email ID 2
        $row['present_address'],    // Address Line 1
        $state_code,         // State Code 1
        $row['pincode'],            // PIN Code 1
        "\t02",                       // Address Category 1 (default)
        '',                         // Residence Code 1
        '',                         // Address Line 2
        '',                         // State Code 2
        '',                         // PIN Code 2
        '',                         // Address Category 2
        '',                         // Residence Code 2
        '',               // Current/New Member Code
        '',               // Current/New Member Short Name
        $row['lid'],                // Curr/New Account No
        "\t05",                       // Account Type (default)
        '1',                        // Ownership Indicator (default)
        $date_opened,               // Date Opened/Disbursed
        '',                         // Date of Last Payment
        '',                         // Date Closed
        '',                         // Date Reported
        $totalamount,               // High Credit/Sanctioned Amt
        ceil($cb),           // Current Balance
        ceil($cb),                         // Amt Overdue
        $dpd,                         // No of Days Past Due
        '',                         // Old Mbr Code
        '',                         // Old Mbr Short Name
        '',                         // Old Acc No
        '',                         // Old Acc Type
        '',                         // Old Ownership Indicator
        "\t".$sf,                        // Suit Filed / Wilful Default (Assume '1' for defaults)
        '',                       // Credit Facility Status (Assume '02' for default loans)
        "\t".$dpdt,                       // Asset Classification (Assume '03' for sub-standard assets)
        '',                         // Value of Collateral
        '',                         // Type of Collateral
        '',                         // Credit Limit
        '',                         // Cash Limit
        '',                         // Rate of Interest
        '',                         // Repayment Tenure
        '',                         // EMI Amount
        '',                         // Written-off Amount (Total)
        '',                         // Written-off Principal Amount
        '',                         // Settlement Amt
        '',                         // Payment Frequency
        '',                         // Actual Payment Amt
        '',                         // Occupation Code
        '',                         // Income
        '',                         // Net/Gross Income Indicator
        '',                         // Monthly/Annual Income Indicator
        '',                         // CKYC
        ''                          // NREGA Card Number
    ];

    // Write each row to the CSV file
    fputcsv($output, $data);
}

// Close the output stream
fclose($output);

?>

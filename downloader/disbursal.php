<?php

include '../db.php';

// Set headers to download as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=loan_disbursal_cibil.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Output the column headings as per your required format
fputcsv($output, [
    'Consumer Name', 'Date of Birth', 'Gender', 'Income Tax ID Number', 'Passport Number', 'Passport Issue Date', 
    'Passport Expiry Date', 'Voter ID Number', 'Driving License Number', 'Driving License Issue Date', 
    'Driving License Expiry Date', 'Ration Card Number', 'Universal ID Number', 'Additional ID #1', 'Additional ID #2', 
    'Telephone No.Mobile', 'Telephone No.Residence', 'Telephone No.Office', 'Extension Office', 'Telephone No.Other', 
    'Extension Other', 'Email ID 1', 'Email ID 2', 'Address Line 1', 'State Code 1', 'PIN Code 1', 'Address Category 1', 
    'Residence Code 1', 'Address Line 2', 'State Code 2', 'PIN Code 2', 'Address Category 2', 'Residence Code 2', 
    'Current/New Member Code', 'Current/New Member Short Name', 'Curr/New Account No', 'Account Type', 
    'Ownership Indicator', 'Date Opened/Disbursed', 'Date of Last Payment', 'Date Closed', 'Date Reported', 
    'High Credit/Sanctioned Amt', 'Current Balance', 'Amt Overdue', 'No of Days Past Due',
    '', '', '', '', '', '', '',  // 7 empty columns
    'Asset classification'
]);

// Get date range parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

// SQL query to fetch data
$sql = "SELECT u.pan_name, u.dob, u.marital_status AS gender, u.pan, u.mobile, u.email, u.present_address, u.state_code, u.pincode, u.permanent_address, u.rcid, 
               l.processed_date, l.lid, la.amount, la.processing_fees, l.p_fee, l.service_charge
        FROM user u
        LEFT JOIN loan_apply la ON u.id = la.uid
        JOIN loan l ON la.id = l.lid
        WHERE l.status_log in ('recovery officer','account manager')";

// Add date range filter if provided
if ($from_date && $to_date) {
    $sql .= " AND DATE(l.processed_date) BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
}

// Execute query
$result = towquery($sql);

// Store unique rows to prevent duplicates (using lid as business key)
$unique_rows = [];
$seen_lids = [];

// Process each row and write to CSV
while ($row = towfetch($result)) {
    // Remove duplicates based on loan ID (business key)
    if (isset($seen_lids[$row['lid']])) {
        continue; // Skip duplicate
    }
    $seen_lids[$row['lid']] = true;
    $unique_rows[] = $row;
}

// Process unique rows
foreach ($unique_rows as $row) {
    // Format date of birth as DDMMYYYY
    $dob = date('dmY', strtotime($row['dob']));
    $gender_map = ['female' => 1, 'male' => 2, 'transgender' => 3];
    $gender = isset($gender_map[$row['gender']]) ? $gender_map[$row['gender']] : 0;
    $high_credit = $row['amount'];
    $date_opened = date('dmY', strtotime($row['processed_date']));

    $gst = ($row['processing_fees']*0.18);
    $totalamount = $row['amount'] + $row['processing_fees'] + $gst;
    
    // Use service_charge from loan table (already calculated and updated per day)
    $current_balance = $totalamount + (float)$row['service_charge'];
    $state_code = $row['state_code'];
    if ($state_code >= 1 && $state_code <= 9) {
        $state_code = "\t0" . $state_code;
    }
    if (substr($dob, 0, 1) === '0') {
        $dob = "\t" . $dob;
    }
    
    // Create the data array for the CSV
    $data = [
        $row['pan_name'],             // Consumer Name
        $dob,                     // Date of Birth
        $gender,                  // Gender
        $row['pan'],              // Income Tax ID Number
        '',                        // Passport Number
        '',                        // Passport Issue Date
        '',                        // Passport Expiry Date
        '',                        // Voter ID Number
        '',                        // Driving License Number
        '',                        // Driving License Issue Date
        '',                        // Driving License Expiry Date
        '',                        // Ration Card Number
        '',                        // Universal ID Number
        '',                        // Additional ID #1
        '',                        // Additional ID #2
        '',           // Telephone No.Mobile
        '',                        // Telephone No.Residence
        '',                        // Telephone No.Office
        '',                        // Extension Office
        '',                        // Telephone No.Other
        '',                        // Extension Other
        '',            // Email ID 1
        '',                        // Email ID 2
        $row['present_address'],  // Address Line 1
        $state_code,            // State Code 1
        $row['pincode'],          // PIN Code 1
        "\t02",                        // Address Category 1
        '',                        // Residence Code 1
        '',// Address Line 2
        '',                        // State Code 2
        '',                        // PIN Code 2
        '',                        // Address Category 2
        '',                        // Residence Code 2
        'NB36250001',             // Current/New Member Code
        'SONUMARPL',             // Current/New Member Short Name
        $row['lid'],              // Curr/New Account No
        "\t69",                     // Account Type
        '1',                      // Ownership Indicator
        "\t".$date_opened,             // Date Opened/Disbursed
        '',                        // Date of Last Payment
        '',                        // Date Closed
        "\t".date('dmY'),          // Date Reported (current date)
        $totalamount,             // High Credit/Sanctioned Amt
        $current_balance,         // Current Balance
        '',                        // Amt Overdue
        '',                        // No of Days Past Due
        '', '', '', '', '', '', '',  // 7 empty columns
        "\t01"                    // Asset classification (value "01" for all rows)
    ];

    // Write the row to the CSV
    fputcsv($output, $data);
}

// Close output stream
fclose($output);

?>

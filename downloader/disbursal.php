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
    'High Credit/Sanctioned Amt', 'Current Balance', 'Amt Overdue', 'No of Days Past Due'
]);

// SQL query to fetch data
$sql = "SELECT u.pan_name, u.dob, u.marital_status AS gender, u.pan, u.mobile, u.email, u.present_address, u.state_code, u.pincode, u.permanent_address, u.rcid, 
               l.processed_date, l.lid, la.amount, la.processing_fees, l.p_fee, l.service_charge, la.service_charge AS lsc  
        FROM user u
        LEFT JOIN loan_apply la ON u.id = la.uid
        JOIN loan l ON la.id = l.lid
        WHERE l.status_log in ('recovery officer','account manager')";

// Execute query
$result = towquery($sql);

// Process each row and write to CSV
while ($row = towfetch($result)) {
    // Format date of birth as DDMMYYYY
    $dob = date('dmY', strtotime($row['dob']));
    $gender_map = ['female' => 1, 'male' => 2, 'transgender' => 3];
    $gender = isset($gender_map[$row['gender']]) ? $gender_map[$row['gender']] : 0;
    $high_credit = $row['amount'];
    $date_opened = date('dmY', strtotime($row['processed_date']));


    $gst = ($row['processing_fees']*0.18);
    $totalamount = $row['amount'] + $row['processing_fees'] + $gst;
    $current_balance = $totalamount + $row['lsc'];
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
        '',             // Current/New Member Code
        '',             // Current/New Member Short Name
        $row['lid'],              // Curr/New Account No
        "\t05",                     // Account Type
        '1',                      // Ownership Indicator
        "\t".$date_opened,             // Date Opened/Disbursed
        '',                        // Date of Last Payment
        '',                        // Date Closed
        '',                        // Date Reported
        $totalamount,             // High Credit/Sanctioned Amt
        $current_balance,         // Current Balance
        '',                        // Amt Overdue
        ''                         // No of Days Past Due
    ];

    // Write the row to the CSV
    fputcsv($output, $data);
}

// Close output stream
fclose($output);

?>

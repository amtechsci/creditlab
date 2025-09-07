<?php

include '../db.php';

// Set headers to download the CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=loan_part_payment_cibil.csv');

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

// SQL query to fetch data for loan part payments (transaction_flow = 'part')
$sql = "SELECT u.pan_name, u.dob, u.gender, u.pan, u.mobile, u.email, u.present_address, u.state_code, u.pincode, u.rcid, 
               l.processed_date, l.lid, la.amount, l.service_charge, td.transaction_date, td.transaction_amount, td.transaction_flow
        FROM user u
        LEFT JOIN loan_apply la ON u.id = la.uid
        JOIN loan l ON la.id = l.lid
        JOIN transaction_details td ON l.lid = td.cllid
        WHERE td.transaction_flow = 'part'"; // Filter for part payment transactions

// Execute the query
$result = towquery($sql);

// Loop through the result and write each row to the CSV
while ($row = towfetch($result)) {
    // Format the date of birth and loan dates as DDMMYYYY
    $dob = date('dmY', strtotime($row['dob']));
    $date_opened = date('dmY', strtotime($row['processed_date']));
    $transaction_date = date('dmY', strtotime($row['transaction_date']));

    // Map gender to numbers
    $gender_map = ['female' => 1, 'male' => 2, 'transgender' => 3];
    $gender = isset($gender_map[$row['gender']]) ? $gender_map[$row['gender']] : 0;

    // Calculate financial details
    $high_credit = $row['amount']; // Loan amount is the high credit
    $current_balance = $row['amount'] + $row['transaction_amount']; // Add part payment to the loan amount
    $state_code = $row['state_code'];
    if ($state_code >= 1 && $state_code <= 9) {
        $state_code = "\t0" . $state_code;
    }
    // Create the array for CSV row
    $data = [
        $row['pan_name'],               // Consumer Name
        "\t".$dob,                       // Date of Birth
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
        $row['mobile'],             // Telephone No.Mobile
        '',                         // Telephone No.Residence
        '',                         // Telephone No.Office
        '',                         // Extension Office
        '',                         // Telephone No.Other
        '',                         // Extension Other
        $row['email'],              // Email ID 1
        '',                         // Email ID 2
        $row['present_address'],    // Address Line 1
        $state_code,         // State Code 1
        $row['pincode'],            // PIN Code 1
        '02',                       // Address Category 1 (default)
        '',                         // Residence Code 1
        '',                         // Address Line 2
        '',                         // State Code 2
        '',                         // PIN Code 2
        '',                         // Address Category 2
        '',                         // Residence Code 2
        $row['rcid'],               // Current/New Member Code
        $row['name'],               // Current/New Member Short Name
        $row['lid'],                // Curr/New Account No
        '05',                       // Account Type (default)
        '1',                        // Ownership Indicator (default)
        "\t".$date_opened,               // Date Opened/Disbursed
        '',                         // Date of Last Payment
        '',                         // Date Closed
        $transaction_date,          // Date Reported (part payment date)
        $high_credit,               // High Credit/Sanctioned Amt
        $current_balance,           // Current Balance
        '',                         // Amt Overdue
        '',                         // No of Days Past Due
        '',                         // Old Mbr Code
        '',                         // Old Mbr Short Name
        '',                         // Old Acc No
        '',                         // Old Acc Type
        '',                         // Old Ownership Indicator
        '0',                        // Suit Filed / Wilful Default (No for part payment)
        '01',                       // Credit Facility Status (Assume '01' for part payments)
        '01',                       // Asset Classification (Assume '01' for standard loans)
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
        $row['transaction_amount'], // Actual Payment Amt (Part payment amount)
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

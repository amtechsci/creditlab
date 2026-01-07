<?php

include '../db.php';

// Set headers to download the CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=loan_settlement_cibil.csv');

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

// Get date range parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

// SQL query to fetch data for loans with settlement transactions
// Use INNER JOIN to ensure we only get loans that have settlement transactions
$sql = "SELECT 
    u.pan_name, u.dob, u.gender, u.marital_status, u.pan, u.mobile, u.email, 
    u.present_address, u.state_code, u.pincode, u.rcid, 
    l.processed_date, l.cleard_date, l.lid, 
    la.amount, la.processing_fees, l.exhausted_period, l.service_charge, l.penality_charge, l.status_log,
    t.transaction_number, t.transaction_date, t.transaction_amount, t.transaction_flow
FROM loan l
INNER JOIN loan_apply la ON la.id = l.lid
INNER JOIN user u ON u.id = la.uid
INNER JOIN transaction_details t ON t.cllid = l.lid AND t.transaction_flow = 'settlement'
WHERE l.status_log = 'cleared'";

// Add date range filter if provided (filter ONLY by transaction_date for settled loans)
if ($from_date && $to_date) {
    $from_date_escaped = date('Y-m-d', strtotime($from_date));
    $to_date_escaped = date('Y-m-d', strtotime($to_date));
    // Filter only by transaction_date (when settlement transaction occurred)
    // Use CAST or DATE() to handle both datetime and date formats
    $sql .= " AND DATE(t.transaction_date) >= '$from_date_escaped' AND DATE(t.transaction_date) <= '$to_date_escaped'";
}

// Order by transaction date to ensure consistent results
$sql .= " ORDER BY t.transaction_date DESC, l.lid ASC";

// Execute the query
$result = towquery($sql);

// Store unique rows to prevent duplicates (using lid as business key)
$unique_rows = [];
$seen_lids = [];

// Collect unique rows
while ($row = towfetch($result)) {
    // Remove duplicates based on loan ID (business key)
    if (isset($seen_lids[$row['lid']])) {
        continue; // Skip duplicate
    }
    $seen_lids[$row['lid']] = true;
    $unique_rows[] = $row;
}

// Loop through unique rows and write each row to the CSV
foreach ($unique_rows as $row) {
    // Format the date of birth and loan dates as DDMMYYYY
    $dob = date('dmY', strtotime($row['dob']));
    $date_opened = date('dmY', strtotime($row['processed_date']));
    $date_cleared = date('dmY', strtotime($row['cleard_date']));

    // Map gender to numbers
    $gender_map = ['female' => 1, 'male' => 2, 'transgender' => 3];
    $gender = isset($gender_map[strtolower($row['marital_status'])]) ? $gender_map[strtolower($row['marital_status'])] : 0;
    // $gender = $row['marital_status'];

    // Calculate financial details
    $high_credit = $row['amount']; // Loan amount is the high credit
    $current_balance = (float)$row['amount'] + (float)$row['service_charge'];
    
    $gst = ((float)$row['processing_fees']*0.18);
    $totalamount = (float)$row['amount'] + (float)$row['processing_fees'] + $gst;
    
    // Total Outstanding = Principal + Processing Fees + GST + Service Charge + Penalty Charge
    $penalty_charge = isset($row['penality_charge']) ? (float)$row['penality_charge'] : 0;
    $total_outstanding = (float)$row['amount'] + (float)$row['processing_fees'] + $gst + (float)$row['service_charge'] + $penalty_charge;
    
    // Settlement amount from transaction
    $settlement_amount = isset($row['transaction_amount']) ? (float)$row['transaction_amount'] : 0;
    
    // Fetch loan days from loan_apply table
    $loan_apply_data = towfetch(towquery("SELECT days FROM loan_apply WHERE id=" . (int)$row['lid']));
    $loan_days = isset($loan_apply_data['days']) && $loan_apply_data['days'] > 0 ? (int)$loan_apply_data['days'] : 30;
    $dpd = ((float)$row['exhausted_period'] > $loan_days) ? (float)$row['exhausted_period']-$loan_days : 0;
    
    // Asset Classification based on DPD:
    // 01 - if dpd < 90
    // 02 - if 90 <= dpd < 180
    // 03 - if 180 <= dpd <= 360
    // 04 - if dpd > 360
    if ($dpd < 90) {
        $dpdt = '01';
    } elseif ($dpd >= 90 && $dpd < 180) {
        $dpdt = '02';
    } elseif ($dpd >= 180 && $dpd <= 360) {
        $dpdt = '03';
    } else { // dpd > 360
        $dpdt = '04';
    }
    
    // Calculate Written-off amounts
    // For settlement: Written-off Total = Total Outstanding - Settlement Amount
    // Total Outstanding = Principal + Processing Fees + GST + Service Charge + Penalty Charge
    // Settlement Amount = Amount paid in settlement transaction
    
    // Written-off Amount (Total) = Total Outstanding - Settlement Amount
    $written_off_total = $total_outstanding - $settlement_amount;
    if ($written_off_total < 0) {
        $written_off_total = 0;
    }
    // Round to next number (ceiling)
    $written_off_total = ceil($written_off_total);
    
    // Get total paid amount (all payments including settlement) for principal calculation
    $paid_query = towquery("SELECT SUM(transaction_amount) as total_paid FROM transaction_details WHERE cllid = " . (int)$row['lid'] . " AND transaction_flow IN ('part', 'renew', 'full', 'settlement')");
    $paid_data = towfetch($paid_query);
    $total_paid = isset($paid_data['total_paid']) ? (float)$paid_data['total_paid'] : 0;
    
    // Written-off Principal Amount = Principal Amount - Total Paid (including settlement)
    $principal_amount = (float)$row['amount'];
    $written_off_principal = $principal_amount - $total_paid;
    
    // If result is negative or zero, force value to 1
    // If result is positive, keep the calculated value
    if ($written_off_principal <= 0) {
        $written_off_principal = 1;
    } else {
        // Round to next number (ceiling) if positive
        $written_off_principal = ceil($written_off_principal);
    }
    
    // Create the array for CSV row
    $state_code = $row['state_code'];
    if ($state_code >= 1 && $state_code <= 9) {
        $state_code = "\t0" . $state_code;
    }
    if (substr($dob, 0, 1) === '0') {
        $dob = "\t" . $dob;
    }

    $data = [
        $row['pan_name'],               // Consumer Name
        $dob,                  // Date of Birth
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
        '',                         // Telephone No.Mobile
        '',                         // Telephone No.Residence
        '',                         // Telephone No.Office
        '',                         // Extension Office
        '',                         // Telephone No.Other
        '',                         // Extension Other
        '',                         // Email ID 1
        '',                         // Email ID 2
        $row['present_address'],    // Address Line 1
        $state_code,         // State Code 1
        $row['pincode'],            // PIN Code 1
        "\t02",                      // Address Category 1 (default)
        '',                         // Residence Code 1
        '',                         // Address Line 2
        '',                         // State Code 2
        '',                         // PIN Code 2
        '',                         // Address Category 2
        '',                         // Residence Code 2
        'NB36250001',                         // Current/New Member Code
        'SONUMARPL',                         // Current/New Member Short Name
        $row['lid'],                // Curr/New Account No
        "\t69",                       // Account Type
        '1',                        // Ownership Indicator (default)
        "\t".$date_opened,               // Date Opened/Disbursed
        "\t".$date_cleared,              // Date of Last Payment
        "\t".$date_cleared,              // Date Closed
        "\t".date('dmY'),                         // Date Reported (current date)
        $totalamount,               // High Credit/Sanctioned Amt
        0,           // Current Balance
        '',                         // Amt Overdue
        '',                         // No of Days Past Due (blank)
        '',                         // Old Mbr Code
        '',                         // Old Mbr Short Name
        '',                         // Old Acc No
        '',                         // Old Acc Type
        '',                         // Old Ownership Indicator
        '',                         // Suit Filed / Wilful Default
        "\t03",                         // Credit Facility Status (Assume '01' for cleared)
        "\t".$dpdt,                 // Asset Classification (Assume '01' for standard)
        '',                         // Value of Collateral
        '',                         // Type of Collateral
        '',                         // Credit Limit
        '',                         // Cash Limit
        '',                         // Rate of Interest
        '',                         // Repayment Tenure
        '',                         // EMI Amount
        $written_off_total,                         // Written-off Amount (Total)
        $written_off_principal,                         // Written-off Principal Amount
        $row['transaction_amount'],                         // Settlement Amt
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
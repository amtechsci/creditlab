<?php

include '../db.php';

/**
 * Default CIBIL Report Generator
 * 
 * This file generates the Default CIBIL report with compliance filters applied.
 * 
 * COMPLIANCE FILTERS APPLIED:
 * 1. Excludes loans with missing mandatory fields (PAN, DOB)
 * 2. Removes duplicate records (business-key level de-duplication)
 * 3. Only includes loans with DPD > 0 (calculated as exhausted_period - loan_days)
 *    - No minimum days restriction - loans with any tenure (e.g., 20 days) are included if DPD > 0
 * 4. Only includes loans with status_log = 'recovery officer' or 'account manager'
 * 
 * NOTE: The dashboard may show more loans with DPD > 0 than this report because:
 * - Dashboard shows live operational count
 * - This report applies additional compliance and regulatory validations
 * - Some loans are excluded due to status, missing fields, or duplicate suppression
 */

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

// Get date range parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

// SQL query to fetch data for loans in default
// Filter by status_log: 'recovery officer' or 'account manager'
// DPD is calculated DYNAMICALLY from processed_date (like zz.php and recoveryagency.php)
// This allows loans with any tenure (e.g., 20 days) to be included if DPD > 0
$sql = "SELECT 
    u.pan_name, u.dob, u.marital_status, u.pan, u.mobile, u.email, 
    u.present_address, u.state_code, u.pincode, u.rcid, 
    l.processed_date, l.lid, la.amount, la.processing_fees, 
    l.service_charge, l.exhausted_period, l.penality_charge, l.status_log, 
    la.status, la.days AS loan_days
FROM user u
LEFT JOIN loan_apply la ON u.id = la.uid
JOIN loan l ON la.id = l.lid
WHERE l.status_log IN ('recovery officer', 'account manager')";

// Add date range filter if provided (filter by processed_date)
if ($from_date && $to_date) {
    $from_date_escaped = date('Y-m-d', strtotime($from_date));
    $to_date_escaped = date('Y-m-d', strtotime($to_date));
    $sql .= " AND DATE(l.processed_date) BETWEEN '$from_date_escaped' AND '$to_date_escaped'";
}

// Execute the query
$result = towquery($sql);

// Store unique rows to prevent duplicates (using lid as business key)
$unique_rows = [];
$seen_lids = [];

// Collect unique rows and apply compliance validations
while ($row = towfetch($result)) {
    // COMPLIANCE FILTER 1: Remove duplicates based on loan ID (business key)
    if (isset($seen_lids[$row['lid']])) {
        continue; // Skip duplicate
    }
    
    // COMPLIANCE FILTER 2: Validate mandatory fields (PAN and DOB are required for CIBIL reporting)
    if (empty($row['pan']) || empty($row['dob'])) {
        continue; // Skip if mandatory fields are missing
    }
    
    $seen_lids[$row['lid']] = true;
    $unique_rows[] = $row;
}

// Loop through unique rows and write each row to the CSV
foreach ($unique_rows as $row) {
    // Format the date of birth and loan dates as DDMMYYYY
    $dob = date('dmY', strtotime($row['dob']));
    $date_opened = date('dmY', strtotime($row['processed_date']));

    // Map gender to numbers
    $gender_map = ['female' => 1, 'male' => 2, 'transgender' => 3];
    $gender = isset($gender_map[strtolower($row['marital_status'])]) ? $gender_map[strtolower($row['marital_status'])] : 0;

    // Calculate financial details
    $gst = (float)$row['processing_fees'] * 0.18;
    $totalamount = (float)$row['amount'] + (float)$row['processing_fees'] + $gst;
    
    // Current Balance = Principal + Processing Fees + GST + Service Charge + Penalty Charge
    $current_balance = $totalamount + (float)$row['service_charge'] + (float)$row['penality_charge'];
    
    // Get loan days from query result (default to 30 if not set)
    $loan_days = isset($row['loan_days']) && $row['loan_days'] > 0 ? (int)$row['loan_days'] : 30;
    
    // Calculate exhausted_days DYNAMICALLY from processed_date (like zz.php and recoveryagency.php)
    // This ensures we get accurate current day count, not relying on database exhausted_period
    $exhausted_days = 0;
    if (!empty($row['processed_date'])) {
        $exhausted_days = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($row['processed_date'] . " -1 day")))) / (60 * 60 * 24));
    }
    
    // Calculate DPD (Days Past Due) = exhausted_days - loan_days
    $dpd = $exhausted_days - $loan_days;
    
    // REQUIREMENT 1: Only include loans where DPD > 0 (exclude DPD = 0 or negative)
    if ($dpd <= 0) {
        continue; // Skip this row
    }
    
    // REQUIREMENT 3: Suit Filed / Wilful Default
    // If DPD > 60 → set value as "01", otherwise blank
    if ($dpd > 60) {
        $suit_filed = '01';
    } else {
        $suit_filed = '';
    }
    
    // REQUIREMENT 2: Asset Classification - Keep blank for all rows
    $asset_classification = '';
    
    // Format state code (add leading zero if single digit)
    $state_code = $row['state_code'];
    if ($state_code >= 1 && $state_code <= 9) {
        $state_code = "\t0" . $state_code;
    }
    
    // Format DOB (add tab if starts with 0)
    if (substr($dob, 0, 1) === '0') {
        $dob = "\t" . $dob;
    }
    
    // Format date opened (add tab if starts with 0)
    if (substr($date_opened, 0, 1) === '0') {
        $date_opened = "\t" . $date_opened;
    }
    
    // Create the array for CSV row
    $data = [
        $row['pan_name'],               // Consumer Name
        $dob,                           // Date of Birth
        $gender,                        // Gender
        $row['pan'],                    // Income Tax ID Number
        '',                             // Passport Number
        '',                             // Passport Issue Date
        '',                             // Passport Expiry Date
        '',                             // Voter ID Number
        '',                             // Driving License Number
        '',                             // Driving License Issue Date
        '',                             // Driving License Expiry Date
        '',                             // Ration Card Number
        '',                             // Universal ID Number
        '',                             // Additional ID #1
        '',                             // Additional ID #2
        '',                             // Telephone No.Mobile
        '',                             // Telephone No.Residence
        '',                             // Telephone No.Office
        '',                             // Extension Office
        '',                             // Telephone No.Other
        '',                             // Extension Other
        '',                             // Email ID 1
        '',                             // Email ID 2
        $row['present_address'],        // Address Line 1
        $state_code,                    // State Code 1
        $row['pincode'],                // PIN Code 1
        "\t02",                         // Address Category 1 (default)
        '',                             // Residence Code 1
        '',                             // Address Line 2
        '',                             // State Code 2
        '',                             // PIN Code 2
        '',                             // Address Category 2
        '',                             // Residence Code 2
        'NB36250001',                   // REQUIREMENT 5: Current/New Member Code (Column AH)
        'SONUMARPL',                    // REQUIREMENT 6: Current/New Member Short Name (Column AI)
        $row['lid'],                    // Curr/New Account No
        "\t69",                         // REQUIREMENT 4: Account Type (69 for all rows)
        '1',                            // Ownership Indicator (default)
        $date_opened,                   // Date Opened/Disbursed
        '',                             // Date of Last Payment
        '',                             // Date Closed
        "\t".date('dmY'),               // REQUIREMENT 7: Date Reported (current date of download)
        $totalamount,                   // High Credit/Sanctioned Amt
        ceil($current_balance),         // Current Balance (rounded up)
        ceil($current_balance),         // Amt Overdue (same as current balance)
        $dpd,                           // No of Days Past Due
        '',                             // Old Mbr Code
        '',                             // Old Mbr Short Name
        '',                             // Old Acc No
        '',                             // Old Acc Type
        '',                             // Old Ownership Indicator
        $suit_filed,                    // REQUIREMENT 3: Suit Filed / Wilful Default (01 if DPD > 60, otherwise blank)
        '',                             // Credit Facility Status
        $asset_classification,          // REQUIREMENT 2: Asset Classification (blank for all rows)
        '',                             // Value of Collateral
        '',                             // Type of Collateral
        '',                             // Credit Limit
        '',                             // Cash Limit
        '',                             // Rate of Interest
        '',                             // Repayment Tenure
        '',                             // EMI Amount
        '',                             // Written-off Amount (Total)
        '',                             // Written-off Principal Amount
        '',                             // Settlement Amt
        '',                             // Payment Frequency
        '',                             // Actual Payment Amt
        '',                             // Occupation Code
        '',                             // Income
        '',                             // Net/Gross Income Indicator
        '',                             // Monthly/Annual Income Indicator
        '',                             // CKYC
        ''                              // NREGA Card Number
    ];

    // Write each row to the CSV file
    fputcsv($output, $data);
}

// Close the output stream
fclose($output);

?>

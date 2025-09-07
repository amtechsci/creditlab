<?php

include '../db.php';

// Set headers to download the CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=bs_loan_disbursal_file.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Output the column headings for the CSV file
fputcsv($output, [
    'CLID (Account ID)', 'Name', 'Ledger Name', 'Reg.Type', 'Master type', 'Voucher No. (or CLLID)', 
    'Sanctioned Amount', 'Disbursal Amount', 'Reference No. (or Payout ID)', 'Mode', 'Status', 'LoanDate', 
    'Country', 'State', 'Processing fee(%)', 'Tenure', 'Processing Fees Collected', 'GST Amount on Processing Fees', 
    'Check', 'Remarks'
]);

// SQL query to fetch data for loan disbursal
$sql = "SELECT u.rcid, u.pan_name, u.state_code, l.lid, la.amount, la.processing_fees, l.processed_amount, l.p_fee, l.exhausted_period, l.processed_date, la.pro_fee_per FROM loan l INNER JOIN loan_apply la ON la.id = l.lid INNER JOIN user u ON u.id = la.uid WHERE l.status_log IN ('account manager','cleared')";  // Only fetch records where status_log = 'account manager'

// Execute the query
$result = towquery($sql);

// Fetch the state pan_names for mapping
$state_result = towquery("SELECT id, state_name FROM state_code");
$state_map = [];
while ($state_row = towfetch($state_result)) {
    $state_map[$state_row['id']] = $state_row['state_name'];
}

// Loop through the result and write each row to the CSV
while ($row = towfetch($result)) {
    
    $gst = ($row['processing_fees']*0.18);
    $totalamount = $row['amount'] + $row['processing_fees'] + $gst;
    
    // Format the loan ID with prefix CLL
    $voucher_no = 'CLL' . $row['lid'];
    $trnum = towquery("SELECT * FROM `transaction_details` WHERE `transaction_flow`='creditlab To Customer' AND cllid=".$row['lid']);
    $tno = townum($trnum) ? towfetch($trnum)['transaction_number'] : 0;
    // Calculate GST on processing fees
    $gst_amount = $row['p_fee'] * 0.18;

    // Create the array for CSV row
    $data = [
        $row['rcid'],                    // CLID (Account ID)
        $row['pan_name'],                    // pan_name
        '',                              // Ledger Name (empty)
        '',                              // Reg.Type (empty)
        '',                              // Master type (empty)
        $voucher_no,                     // Voucher No. (or CLLID)
        $totalamount,                  // Sanctioned Amount
        $row['processed_amount'],        // Disbursal Amount
        $tno,                     // Reference No. (or Payout ID)
        '',                              // Mode (empty)
        'Disbursed',               // Status
        date('d/m/Y', strtotime($row['processed_date'])), // LoanDate (formatted as DDMMYYYY)
        'India',                         // Country
        $state_map[$row['state_code']] ?? '', // State (mapped by state_code)
        $row['pro_fee_per'],             // Processing fee (%)
        30,        // Tenure
        $row['p_fee'],                   // Processing Fees Collected
        $gst_amount,                     // GST Amount on Processing Fees
        '',                              // Check (empty)
        ''                               // Remarks (empty)
    ];

    // Write each row to the CSV file
    fputcsv($output, $data);
}

// Close the output stream
fclose($output);

?>

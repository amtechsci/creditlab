<?php

include '../db.php';

define('GST_RATE', 0.18);
define('GST_FACTOR', 1.18);
define('DAILY_INTEREST_RATE', 0.001); // 0.1% as a decimal

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=bs_repayment.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'CLID', 'Name', 'Ledger Name', 'Reg.Type', 'Master type', 'Voucher No. (or CLLID)', 
    'Loan Process Date', 'Exhausted Days',
    'Sanctioned Amount', 'Disbursal Amount', 'Narration Journal', 'Reference No. (or Payout ID)', 
    'Mode', 'Status', 'LoanDate', 'Country', 'State', 'Processing fee(%)', 'Processing Fees Collected', 
    'GST Amount on Processing Fees', 'INTEREST (%)', 'LOAN CLOSURE TYPE', 'INTEREST COLLECTED', 'PENALTY', 'GST On PENALTY', 
    'REPAYMENT AMOUNT'
]);

$sql = "SELECT 
            u.rcid, u.pan_name, u.state_code, 
            l.lid, l.processed_amount, l.p_fee, l.service_charge, l.penality_charge,
            la.amount AS principal_amount, la.processing_fees, la.pro_fee_per, la.interest_percentage,
            td.transaction_number, td.transaction_date, td.transaction_flow, td.transaction_amount,
            l.processed_date AS loan_start_date
        FROM transaction_details td
        INNER JOIN loan_apply la ON la.id = td.cllid
        INNER JOIN user u ON u.id = la.uid
        INNER JOIN loan l ON l.lid = td.cllid
        WHERE td.transaction_flow IN ('settlement', 'part', 'renew', 'full', 'preclose')
        ORDER BY td.transaction_date DESC"; 

$result = towquery($sql);
$state_result = towquery("SELECT id, state_name FROM state_code");
$state_map = [];
while ($state_row = towfetch($state_result)) {
    $state_map[$state_row['id']] = $state_row['state_name'];
}

while ($row = towfetch($result)) {
    $voucher_no = 'CLL' . $row['lid'];
    $loan_closure_type = $row['transaction_flow'];
    
    $processing_fee_collected = $row['transaction_flow'] === 'part' ? 'P.P' : $row['processing_fees'];
    $gst_on_processing_fees = $row['transaction_flow'] === 'part' ? 'P.P' : ($row['processing_fees'] * GST_RATE);
    
    $principal_amt = (float)$row['principal_amount'];
    $disbursed_amount = (float)$row['processed_amount'];
    
    $pf_numeric = is_numeric($row['processing_fees']) ? (float)$row['processing_fees'] : 0;
    $gst_inclusive_pf = $pf_numeric + ($pf_numeric * GST_RATE);
    $sanctioned_amount = $principal_amt + $gst_inclusive_pf;

    $interest_collected = 0;
    $penalty = 0;
    $gst_on_penalty = 0;
    $exhausted_days = 0; // Initialize to 0

    // =========================== THE FIX IS HERE ===========================
    // The day calculation is now here, outside the 'if' block, so it runs for every row.
    // =======================================================================
    if (!empty($row['loan_start_date'])) {
        $loan_start_date = new DateTime($row['loan_start_date']);
        $repayment_date = new DateTime($row['transaction_date']);

        $loan_start_date->setTime(0, 0, 0);
        $repayment_date->setTime(0, 0, 0);
        
        $interval = $loan_start_date->diff($repayment_date);
        $exhausted_days = $interval->days;
        $exhausted_days = $exhausted_days + 1;
    }

    $repayment_amt = (float)$row['transaction_amount'];
    $extra_amount = $repayment_amt - $sanctioned_amount;

    // Interest and penalty are still only calculated if there was an overpayment.
    if ($extra_amount > 0 && !empty($row['loan_start_date'])) {
        
        $calculated_interest = $sanctioned_amount * DAILY_INTEREST_RATE * $exhausted_days;

        if (($extra_amount > $calculated_interest) && ($exhausted_days > 30)) {
            $interest_collected = $calculated_interest;
            $remainder_for_penalty = $extra_amount - $calculated_interest;
            
            $penalty = $remainder_for_penalty / GST_FACTOR;
            $gst_on_penalty = $penalty * GST_RATE;
        } else {
            $interest_collected = $extra_amount;
            $penalty = 0;
            $gst_on_penalty = 0;
        }
    }
    
    $data = [
        $row['rcid'],
        $row['pan_name'],
        '', '', '',
        $voucher_no,
        date('d-m-Y', strtotime($row['loan_start_date'])),
        $exhausted_days, // This will now have the correct value for all rows
        number_format($sanctioned_amount, 2, '.', ''),
        $disbursed_amount,
        'REPAYMENT DONE',
        $row['transaction_number'],
        '',
        'received',
        date('d-m-Y', strtotime($row['transaction_date'])),
        'India',
        $state_map[$row['state_code']] ?? '',
        $row['pro_fee_per'],
        $processing_fee_collected,
        ($gst_on_processing_fees === 'P.P') ? 'P.P' : number_format($gst_on_processing_fees, 2, '.', ''),
        $row['interest_percentage'],
        $loan_closure_type,
        number_format($interest_collected, 2, '.', ''),
        number_format($penalty, 2, '.', ''),
        number_format($gst_on_penalty, 2, '.', ''),
        number_format($repayment_amt, 2, '.', '')
    ];

    fputcsv($output, $data);
}

fclose($output);
exit;
?>
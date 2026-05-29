<?php

include '../db.php';

set_time_limit(3000);
ignore_user_abort(true);

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

// Get date range parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

// Single query: avoid N+1 lookups per row (was causing PHP-FPM timeout / connection reset)
$sql = "SELECT u.rcid, u.pan_name, u.state_code, l.lid, la.amount, la.processing_fees, l.processed_amount, l.p_fee, l.exhausted_period, l.processed_date, la.pro_fee_per,
        td.transaction_number
        FROM loan l
        INNER JOIN loan_apply la ON la.id = l.lid
        INNER JOIN user u ON u.id = la.uid
        LEFT JOIN (
            SELECT cllid, MAX(transaction_number) AS transaction_number
            FROM transaction_details
            WHERE transaction_flow = 'creditlab To Customer'
            GROUP BY cllid
        ) td ON td.cllid = l.lid
        WHERE l.status_log IN ('account manager','cleared')";

if ($from_date && $to_date) {
    $fromEsc = towreal(date('Y-m-d', strtotime($from_date)));
    $toEsc = towreal(date('Y-m-d', strtotime($to_date)));
    $sql .= " AND DATE(l.processed_date) BETWEEN '$fromEsc' AND '$toEsc'";
}

$result = towquery($sql);

// Fetch the state names for mapping
$state_result = towquery("SELECT id, state_name FROM state_code");
$state_map = [];
while ($state_row = towfetch($state_result)) {
    $state_map[$state_row['id']] = $state_row['state_name'];
}

$rowCount = 0;
while ($row = towfetch($result)) {
    $gst = ($row['processing_fees'] * 0.18);
    $totalamount = $row['amount'] + $row['processing_fees'] + $gst;

    $voucher_no = 'CLL' . $row['lid'];
    $tno = !empty($row['transaction_number']) ? $row['transaction_number'] : 0;
    $gst_amount = $row['p_fee'] * 0.18;

    fputcsv($output, [
        $row['rcid'],
        $row['pan_name'],
        '',
        '',
        '',
        $voucher_no,
        $totalamount,
        $row['processed_amount'],
        $tno,
        '',
        'Disbursed',
        date('d/m/Y', strtotime($row['processed_date'])),
        'India',
        $state_map[$row['state_code']] ?? '',
        $row['pro_fee_per'],
        30,
        $row['p_fee'],
        $gst_amount,
        '',
        ''
    ]);

    $rowCount++;
    if (($rowCount % 500) === 0) {
        fflush($output);
    }
}

fclose($output);

?>

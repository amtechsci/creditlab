<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/loan_charge_calc.php';
require_once __DIR__ . '/../lib/loan_dpd.php';

set_time_limit(300);
ini_set('memory_limit', '512M');

// Set headers to download as CSV
header('Content-Type: text/csv; charset=utf-8');

// Get date range parameters
$from_date = isset($_GET['from_date']) && !empty($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) && !empty($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

$from_date_escaped = date('Y-m-d', strtotime($from_date));
$to_date_escaped = date('Y-m-d', strtotime($to_date));
$as_of_date = $to_date_escaped;

$filename = 'portfolio_dump_' . $from_date_escaped . '_to_' . $to_date_escaped . '.csv';
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Output column headings as per Credit LAB portfolio Dump format
fputcsv($output, [
    'Customer ID',
    'Sanction Date',
    'Disbursement Date',
    'Contract number / Loan ID',
    'Customer Name',
    'Masked Aadhar number',
    'Masked PAN number',
    'Customer Mobile Number',
    'State',
    'Address',
    'Pin code',
    'Sanction Amount',
    'Disbursement Amount',
    'Processing Fee Amount',
    'Processing Fee (%)',
    'Tenure',
    'Accrued Interest Amt',
    'Loan Status ( Active / Closed / Settled)',
    'DPD (days)',
    'Repayment date',
    'Principal Collected Amount',
    'Interest Collected',
    'Penalty',
    'Monthly Income (declared)',
    'As of Date'
]);

// Helper function to mask Aadhaar: Show only last 4 digits
function portfolio_mask_aadhar(?string $aadhar): string {
    if (empty($aadhar)) {
        return '';
    }
    $clean = preg_replace('/\D/', '', $aadhar);
    if (strlen($clean) >= 4) {
        return 'XXXXXXXX' . substr($clean, -4);
    }
    return '';
}

// Helper function to mask PAN: Show first 5 masked, last 5 visible (e.g. XXXXX1234F)
function portfolio_mask_pan(?string $pan): string {
    if (empty($pan)) {
        return '';
    }
    $clean = strtoupper(trim($pan));
    if (strlen($clean) === 10) {
        return 'XXXXX' . substr($clean, 5);
    } elseif (strlen($clean) > 4) {
        return str_repeat('X', strlen($clean) - 4) . substr($clean, -4);
    }
    return '';
}

// Pre-fetch state mapping
$state_map = [];
$res_state = towquery("SELECT id, state_name FROM state_code");
if ($res_state) {
    while ($st = towfetchassoc($res_state)) {
        $state_map[$st['id']] = $st['state_name'];
    }
}

// Pre-fetch customer repayments on or before as_of_date (excluding disbursals to customer)
$tx_map = [];
$tx_query = towquery("SELECT cllid, transaction_flow, transaction_amount, transaction_date 
                      FROM transaction_details 
                      WHERE transaction_flow != 'creditlab To Customer' 
                      AND DATE(transaction_date) <= '$as_of_date'");
if ($tx_query) {
    while ($tx = towfetchassoc($tx_query)) {
        $tx_map[$tx['cllid']][] = $tx;
    }
}

// Fetch all active loans as of To Date:
// Disbursed on or before To Date, and not cleared prior to From Date
$sql = "SELECT l.id AS loan_id, l.lid, l.uid, l.processed_date, l.processed_amount, l.cleard_date, l.status_log, l.action,
               l.total_amount, l.p_fee, l.service_charge, l.penality_charge, l.is_emi, l.total_time,
               la.amount, la.processing_fees, la.pro_fee_per, la.days, la.apply_date, la.status_date, la.interest_percentage,
               u.rcid, u.pan_name, u.name, u.pan, u.aadhar, u.mobile, u.state, u.state_code, u.present_address, u.permanent_address, u.pincode, u.salary, u.average_salary, u.annual_income
        FROM loan l
        INNER JOIN loan_apply la ON la.id = l.lid
        INNER JOIN user u ON u.id = la.uid
        WHERE l.processed_date IS NOT NULL
        AND DATE(l.processed_date) <= '$as_of_date'
        AND (l.cleard_date IS NULL OR l.cleard_date = '0000-00-00' OR DATE(l.cleard_date) >= '$from_date_escaped')
        ORDER BY l.processed_date ASC, l.lid ASC";

$result = towquery($sql);

if ($result) {
    while ($row = towfetchassoc($result)) {
        $lid = $row['lid'];
        $loan_txs = $tx_map[$lid] ?? [];

        // Determine if loan was cleared on or before the snapshot As of Date
        $is_cleared_as_of = (!empty($row['cleard_date']) 
                             && $row['cleard_date'] !== '0000-00-00' 
                             && strtotime(date('Y-m-d', strtotime($row['cleard_date']))) <= strtotime($as_of_date));

        // Total payments collected on or before snapshot date
        $total_collected = 0.0;
        $has_settlement_tx = false;
        foreach ($loan_txs as $tx) {
            $total_collected += (float)$tx['transaction_amount'];
            if (strtolower(trim($tx['transaction_flow'])) === 'settlement') {
                $has_settlement_tx = true;
            }
        }

        // Determine Loan Status as of snapshot date
        if ($is_cleared_as_of) {
            $status = ($has_settlement_tx || strtolower(trim((string)$row['action'])) === 'settlement') ? 'Settled' : 'Closed';
        } else {
            $status = 'Active';
        }

        // Tenure and dates
        $tenure = creditlab_loan_tenure_days($row, (int)($row['days'] ?? 30));
        $processed_ts = strtotime($row['processed_date']);
        $processed_date_str = date('Y-m-d', $processed_ts);
        $repayment_date_str = date('d-m-Y', strtotime($processed_date_str . ' +' . ($tenure - 1) . ' day'));

        // Financial amounts
        $disbursed_amt = (float)$row['processed_amount'];
        $pf_amt = (float)$row['processing_fees'];
        $pf_per = isset($row['pro_fee_per']) && $row['pro_fee_per'] !== '' ? $row['pro_fee_per'] : '14';
        $sanction_amt = (float)$row['total_amount'] > 0 ? (float)$row['total_amount'] : round((float)$row['amount'] + ($pf_amt * 1.18), 2);

        $repayment_base = creditlab_loan_repayment_base($disbursed_amt, $pf_amt);

        if ($status === 'Active') {
            // Loan was active as of snapshot date
            $stop_date = date_create($processed_date_str);
            $sa = date_create($as_of_date . ' 23:59:59');
            $tday = 0;
            if ($stop_date && $sa) {
                $aa = date_diff($stop_date, $sa);
                $tday = (int)$aa->format('%a');
            }
            $exhausted_period = $tday + 1;
            $dpd = max(0, $exhausted_period - $tenure);

            // Calculate accrued interest as of snapshot date
            $interest_percentage = $row['interest_percentage'] ?? 1;
            $service_charge = 0.0;
            $days_rem = $exhausted_period;
            if ((float)$interest_percentage == 1.0) {
                if ($days_rem >= 3) {
                    $days_rem -= 3;
                } else {
                    $days_rem = 0;
                }
                if ($days_rem >= 7) {
                    $service_charge += $repayment_base * 7 / 100 * 0.1;
                    $days_rem -= 7;
                } else {
                    $service_charge += $repayment_base * $days_rem / 100 * 0.1;
                    $days_rem = 0;
                }
                if ($days_rem >= 20) {
                    $service_charge += $repayment_base * 20 / 100 * 0.115;
                    $days_rem -= 20;
                } else {
                    $service_charge += $repayment_base * $days_rem / 100 * 0.115;
                    $days_rem = 0;
                }
                if ($days_rem >= 1) {
                    $service_charge += $repayment_base * $days_rem / 100 * 0.1;
                }
            } else {
                $service_charge = $repayment_base * $exhausted_period / 100 * (float)$interest_percentage;
            }

            // Calculate penalty as of snapshot date
            $penality = 0.0;
            if ($dpd > 0) {
                $penality = ($repayment_base / 100) * 4;
                $pdays = $dpd - 1;
                if ($pdays > 0) {
                    $penality += (($repayment_base / 100) * 0.2) * $pdays;
                }
            }

            $accrued_interest = round($service_charge, 2);
            $penalty_amt = round($penality, 2);

            $principal_collected = min($total_collected, $sanction_amt);
            $interest_collected = max(0.0, $total_collected - $sanction_amt);
        } elseif ($status === 'Closed') {
            $dpd = 0;
            $accrued_interest = 0.0;
            $principal_collected = $total_collected > 0 ? min($total_collected, $sanction_amt) : $sanction_amt;
            $interest_collected = (float)$row['service_charge'] > 0 ? (float)$row['service_charge'] : max(0.0, $total_collected - $sanction_amt);
            $penalty_amt = (float)$row['penality_charge'];
        } else { // Settled
            $dpd = 0;
            $accrued_interest = 0.0;
            $principal_collected = min($total_collected, $sanction_amt);
            $interest_collected = max(0.0, $total_collected - $sanction_amt);
            $penalty_amt = 0.0;
        }

        $customer_id = !empty($row['rcid']) ? $row['rcid'] : ('CL' . $row['uid']);
        $sanction_date = !empty($row['status_date']) 
            ? date('d-m-Y', strtotime($row['status_date'])) 
            : (!empty($row['apply_date']) ? date('d-m-Y', strtotime($row['apply_date'])) : date('d-m-Y', $processed_ts));
        $disbursement_date = date('d-m-Y', $processed_ts);
        $contract_no = 'CLL' . $lid;
        $customer_name = !empty($row['pan_name']) ? $row['pan_name'] : ($row['name'] ?? '');
        $state_name = $state_map[$row['state_code']] ?? ($row['state'] ?? '');
        $address = !empty($row['present_address']) ? $row['present_address'] : ($row['permanent_address'] ?? '');

        // Monthly declared income
        $monthly_income = '';
        if (!empty($row['salary'])) {
            $monthly_income = $row['salary'];
        } elseif (!empty($row['average_salary'])) {
            $monthly_income = $row['average_salary'];
        } elseif (!empty($row['annual_income'])) {
            $monthly_income = round((float)$row['annual_income'] / 12, 2);
        }

        fputcsv($output, [
            $customer_id,
            $sanction_date,
            $disbursement_date,
            $contract_no,
            $customer_name,
            portfolio_mask_aadhar($row['aadhar']),
            portfolio_mask_pan($row['pan']),
            $row['mobile'] ?? '',
            $state_name,
            $address,
            $row['pincode'] ?? '',
            number_format($sanction_amt, 2, '.', ''),
            number_format($disbursed_amt, 2, '.', ''),
            number_format($pf_amt, 2, '.', ''),
            $pf_per,
            $tenure,
            number_format($accrued_interest, 2, '.', ''),
            $status,
            $dpd,
            $repayment_date_str,
            number_format($principal_collected, 2, '.', ''),
            number_format($interest_collected, 2, '.', ''),
            number_format($penalty_amt, 2, '.', ''),
            $monthly_income,
            date('d-m-Y', strtotime($as_of_date))
        ]);
    }
}

fclose($output);

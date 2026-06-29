<?php
/**
 * Agency wise payments CSV export (admin Downloader).
 *
 * Lists every payment collected through an agency PG link (full close or part
 * payment), with the DPD as of the date the payment was received.
 */

require_once __DIR__ . '/loan_dpd.php';

function creditlab_agency_payments_csv_headers(): array
{
    return [
        'Loan ID',
        'Agency Name',
        'Payment Amount',
        'Payment Status',
        'Paid Date',
        'DPD (days) on paid date',
    ];
}

/**
 * DPD as of a given paid date (0 when paid on/before the due date).
 */
function creditlab_agency_payment_dpd(array $loanRow, ?string $paidDate): int
{
    if (empty($loanRow['processed_date']) || empty($paidDate)) {
        return 0;
    }
    $due = creditlab_loan_due_date($loanRow);
    if ($due === null) {
        return 0;
    }
    $paidTs = strtotime(date('Y-m-d', strtotime($paidDate)));
    $dueTs = strtotime($due);
    if ($paidTs === false || $dueTs === false) {
        return 0;
    }
    return max(0, (int) round(($paidTs - $dueTs) / 86400));
}

/**
 * @param resource $output
 */
function creditlab_write_agency_payments_csv($output, ?string $fromDate = null, ?string $toDate = null): void
{
    fputcsv($output, creditlab_agency_payments_csv_headers());

    $sql = "SELECT
                l.lid AS loan_lid,
                pt.agency_name,
                pt.amount AS pg_amount,
                pt.link_type,
                pt.created_at AS pg_created_at,
                td.transaction_amount AS td_amount,
                td.transaction_flow,
                td.transaction_date AS td_paid_date,
                l.processed_date,
                l.total_time,
                l.is_emi,
                la.days AS loan_apply_days
            FROM pg_transaction pt
            INNER JOIN loan l ON l.id = pt.loan_id
            LEFT JOIN loan_apply la ON la.id = l.lid
            LEFT JOIN transaction_details td
                ON td.cllid = l.lid
                AND td.transaction_number = pt.bank_reference_number
                AND td.transaction_number <> ''
                AND td.transaction_flow IN ('full', 'part')
            WHERE pt.status = 'success'
              AND pt.agency_name IS NOT NULL
              AND pt.agency_name <> ''
            ORDER BY pt.agency_name ASC, td.transaction_date ASC, pt.created_at ASC";

    $result = towquery($sql);
    if (!$result) {
        return;
    }

    $fromTs = $fromDate ? strtotime(date('Y-m-d 00:00:00', strtotime($fromDate))) : null;
    $toTs = $toDate ? strtotime(date('Y-m-d 23:59:59', strtotime($toDate))) : null;

    while ($row = towfetch($result)) {
        $paidDate = !empty($row['td_paid_date']) ? $row['td_paid_date'] : $row['pg_created_at'];

        if ($fromTs !== null || $toTs !== null) {
            $paidTs = $paidDate ? strtotime($paidDate) : false;
            if ($paidTs === false) {
                continue;
            }
            if ($fromTs !== null && $paidTs < $fromTs) {
                continue;
            }
            if ($toTs !== null && $paidTs > $toTs) {
                continue;
            }
        }

        $flow = $row['transaction_flow'] ?? null;
        if ($flow === 'full') {
            $status = 'Full paid';
        } elseif ($flow === 'part') {
            $status = 'Part payment';
        } else {
            // No matching transaction_details row: fall back to the link type.
            $status = ($row['link_type'] ?? '') === 'total_outstanding' ? 'Full paid' : 'Part payment';
        }

        $amount = $row['td_amount'] !== null ? (float) $row['td_amount'] : (float) $row['pg_amount'];

        $loanRow = [
            'processed_date' => $row['processed_date'],
            'loan_apply_days' => $row['loan_apply_days'] ?? 30,
            'is_emi' => $row['is_emi'] ?? 0,
            'total_time' => $row['total_time'] ?? 0,
        ];
        $dpd = creditlab_agency_payment_dpd($loanRow, $paidDate);

        fputcsv($output, [
            'CLL' . $row['loan_lid'],
            $row['agency_name'],
            number_format($amount, 2, '.', ''),
            $status,
            $paidDate ? date('Y-m-d', strtotime($paidDate)) : '',
            $dpd,
        ]);
    }
}

function creditlab_send_agency_payments_csv(string $filename, ?string $fromDate = null, ?string $toDate = null): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    creditlab_write_agency_payments_csv($output, $fromDate, $toDate);
    fclose($output);
    exit;
}

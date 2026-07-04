<?php
/**
 * Agency wise payments CSV export (admin Downloader).
 *
 * Lists every payment collected through an agency PG link (full close, part
 * payment, or manual agency payment pending staff review).
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
                pl.loan_lid,
                COALESCE(NULLIF(pl.agency_name, ''), NULLIF(pt.agency_name, ''), ag.name, pl.created_by_name) AS agency_name,
                pt.amount AS pg_amount,
                pl.link_type,
                pl.paid_at,
                pt.created_at AS pg_created_at,
                td.transaction_amount AS td_amount,
                td.transaction_flow,
                td.transaction_date AS td_paid_date,
                l.processed_date,
                l.total_time,
                l.is_emi,
                la.days AS loan_apply_days
            FROM pg_payment_link pl
            INNER JOIN pg_transaction pt ON pt.txnid = pl.txnid
            INNER JOIN loan l ON l.id = pl.loan_internal_id
            LEFT JOIN agency ag ON ag.id = pl.agency_id
            LEFT JOIN loan_apply la ON la.id = pl.loan_lid
            LEFT JOIN transaction_details td
                ON td.cllid = pl.loan_lid
                AND td.transaction_number = pt.bank_reference_number
                AND td.transaction_number <> ''
                AND td.transaction_flow IN ('full', 'part')
            WHERE pl.status = 'paid'
              AND pt.status = 'success'
              AND (pl.created_by_role = 'agency_admin' OR pl.agency_id IS NOT NULL)
            ORDER BY agency_name ASC, COALESCE(pl.paid_at, pt.created_at) ASC";

    $result = towquery($sql);
    if (!$result) {
        return;
    }

    $fromTs = $fromDate ? strtotime(date('Y-m-d 00:00:00', strtotime($fromDate))) : null;
    $toTs = $toDate ? strtotime(date('Y-m-d 23:59:59', strtotime($toDate))) : null;

    while ($row = towfetch($result)) {
        $paidDate = !empty($row['paid_at'])
            ? $row['paid_at']
            : (!empty($row['td_paid_date']) ? $row['td_paid_date'] : $row['pg_created_at']);

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

        $linkType = $row['link_type'] ?? '';
        $flow = $row['transaction_flow'] ?? null;
        if ($linkType === 'manual') {
            $status = 'Manual payment (pending review)';
        } elseif ($flow === 'full') {
            $status = 'Full paid';
        } elseif ($flow === 'part') {
            $status = 'Part payment';
        } else {
            $status = $linkType === 'total_outstanding' ? 'Full paid' : 'Part payment';
        }

        $amount = $row['td_amount'] !== null && $linkType !== 'manual'
            ? (float) $row['td_amount']
            : (float) $row['pg_amount'];

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

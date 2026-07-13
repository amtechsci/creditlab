<?php
/**
 * Recovery agency CSV export (same columns as admin downloader/recoveryagency.php).
 */

require_once __DIR__ . '/loan_dpd.php';

function creditlab_format_recovery_response(?string $response, ?string $date): string
{
    $response_text = trim($response ?? '');
    $date_text = trim($date ?? '');
    if ($response_text === '' && $date_text === '') {
        return 'NA';
    }
    return $response_text . ' & ' . $date_text;
}

function creditlab_recovery_agency_csv_headers(): array
{
    return [
        'customer PAN NAME', 'LoanID', 'monthly net salary', 'monthly salary date',
        'LoanPrinciple', 'loanOutstanding', 'Loan exhausted days', 'DPD',
        'loanPenalty', 'totalInterest', 'Company', 'log in data ( address lat,long)',
        'permanent Address', 'PrimaryNo', 'AltMobile',
        'reference 1 name / relation /number /status', 'reference 2 name / relation /number /status',
        'reference 3 name / relation /number /status', 'reference 4 name / relation /number /status',
        'reference 5 name / relation /number /status', 'reference 6 name / relation /number /status',
        'reference 7 name / relation /number /status', 'reference 8 name / relation /number /status',
        'reference 9 name / relation /number /status', 'reference 10 name / relation /number /status',
        'customer response 1 & commitment date 1', 'customer response 2 & commitment date 2',
        'customer response 3 & commitment date 3', 'customer response 4 & commitment date 4',
        'customer response 5 & commitment date 5',
    ];
}

/**
 * Latest 5 AM responses per lid (ORDER BY id DESC).
 *
 * @param int[] $loanIds
 * @return array<int, array{responses: list<string|null>, dates: list<string|null>}>
 */
function creditlab_recovery_agency_responses_by_lid(array $loanIds): array
{
    $byLid = [];
    $loanIds = array_values(array_unique(array_filter(array_map('intval', $loanIds))));
    if ($loanIds === []) {
        return $byLid;
    }

    // Chunk IN() lists to keep queries manageable on large exports.
    foreach (array_chunk($loanIds, 1000) as $chunk) {
        $ids = implode(',', $chunk);
        $q = towquery("SELECT lid, customer_response, commitment_date FROM `loan_acc_man` WHERE lid IN ($ids) ORDER BY id DESC");
        if (!$q) {
            continue;
        }
        while ($r = towfetch($q)) {
            $lid = (int) $r['lid'];
            if (!isset($byLid[$lid])) {
                $byLid[$lid] = ['responses' => [], 'dates' => []];
            }
            if (count($byLid[$lid]['responses']) >= 5) {
                continue;
            }
            $byLid[$lid]['responses'][] = $r['customer_response'];
            $byLid[$lid]['dates'][] = $r['commitment_date'];
        }
    }

    return $byLid;
}

/**
 * First 10 referrals per uid (ORDER BY id ASC).
 *
 * @param int[] $userIds
 * @return array<int, list<string>>
 */
function creditlab_recovery_agency_referrals_by_uid(array $userIds): array
{
    $byUid = [];
    $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
    if ($userIds === []) {
        return $byUid;
    }

    foreach (array_chunk($userIds, 1000) as $chunk) {
        $ids = implode(',', $chunk);
        $q = towquery("SELECT uid, name, relation, phone, status FROM `user_referrals` WHERE uid IN ($ids) ORDER BY id ASC");
        if (!$q) {
            continue;
        }
        while ($r = towfetch($q)) {
            $uid = (int) $r['uid'];
            if (!isset($byUid[$uid])) {
                $byUid[$uid] = [];
            }
            if (count($byUid[$uid]) >= 10) {
                continue;
            }
            $byUid[$uid][] = $r['name'] . ' / ' . $r['relation'] . ' / ' . $r['phone'] . ' / ' . $r['status'];
        }
    }

    return $byUid;
}

/**
 * @param resource $output
 */
function creditlab_write_recovery_agency_csv($output, ?int $minDpd = null, ?string $fromDate = null, ?string $toDate = null): void
{
    fputcsv($output, creditlab_recovery_agency_csv_headers());

    $sql = "SELECT 
            u.id AS user_id,
            u.pan_name,
            u.salary,
            u.salary_date,
            u.company,
            u.latlong,
            u.permanent_address,
            u.mobile,
            u.altmobile,
            l.lid AS loan_id,
            l.processed_amount,
            l.p_fee,
            l.service_charge AS total_interest,
            l.penality_charge,
            l.processed_date AS loan_start_date,
            la.amount,
            la.days AS loan_tenure,
            l.is_emi
        FROM 
            loan_apply la
        INNER JOIN 
            user u ON la.uid = u.id
        INNER JOIN 
            loan l ON la.id = l.lid
        WHERE 
            la.status = 'account manager'";

    if ($fromDate && $toDate) {
        $fromEsc = towreal(date('Y-m-d', strtotime($fromDate)));
        $toEsc = towreal(date('Y-m-d', strtotime($toDate)));
        $sql .= " AND DATE(l.processed_date) BETWEEN '$fromEsc' AND '$toEsc'";
    }

    $sql .= ' ORDER BY la.id DESC';

    $result = towquery($sql);
    if (!$result) {
        return;
    }

    // Collect rows first so we can bulk-load related data (avoids N+1 timeout).
    $rows = [];
    $loanIds = [];
    $userIds = [];
    while ($row = towfetch($result)) {
        $exhausted_days = 0;
        $dpd = 0;
        if (!empty($row['loan_start_date'])) {
            $loanRow = [
                'processed_date' => $row['loan_start_date'],
                'loan_apply_days' => $row['loan_tenure'] ?? 30,
                'is_emi' => $row['is_emi'] ?? 0,
                'status_log' => 'account manager',
            ];
            $exhausted_days = (int) ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($row['loan_start_date'] . ' -1 day')))) / 86400);
            $dpd = creditlab_calculate_dpd($loanRow);
        }

        if ($minDpd !== null && $dpd <= $minDpd) {
            continue;
        }

        $row['exhausted_days'] = $exhausted_days;
        $row['calculated_dpd'] = $dpd;
        $rows[] = $row;
        $loanIds[] = (int) $row['loan_id'];
        $userIds[] = (int) $row['user_id'];
    }

    $responsesByLid = creditlab_recovery_agency_responses_by_lid($loanIds);
    $referralsByUid = creditlab_recovery_agency_referrals_by_uid($userIds);

    $rowCount = 0;
    foreach ($rows as $row) {
        $loanId = (int) $row['loan_id'];
        $userId = (int) $row['user_id'];
        $loan_principle = (float) $row['processed_amount'] + (float) $row['p_fee'] + ((float) $row['p_fee'] * 0.18);
        $outstanding_amount = (float) $row['processed_amount'] + (float) $row['p_fee'] + ((float) $row['p_fee'] * 0.18) + (float) $row['total_interest'] + (float) $row['penality_charge'];

        $responses = $responsesByLid[$loanId]['responses'] ?? [];
        $commit_dates = $responsesByLid[$loanId]['dates'] ?? [];
        $referrals = $referralsByUid[$userId] ?? [];

        fputcsv($output, [
            $row['pan_name'],
            'CLL' . $row['loan_id'],
            $row['salary'],
            $row['salary_date'],
            number_format($loan_principle, 2, '.', ''),
            number_format($outstanding_amount, 2, '.', ''),
            $row['exhausted_days'],
            $row['calculated_dpd'],
            $row['penality_charge'],
            $row['total_interest'],
            $row['company'],
            $row['latlong'],
            $row['permanent_address'],
            $row['mobile'],
            $row['altmobile'],
            $referrals[0] ?? '',
            $referrals[1] ?? '',
            $referrals[2] ?? '',
            $referrals[3] ?? '',
            $referrals[4] ?? '',
            $referrals[5] ?? '',
            $referrals[6] ?? '',
            $referrals[7] ?? '',
            $referrals[8] ?? '',
            $referrals[9] ?? '',
            creditlab_format_recovery_response($responses[0] ?? null, $commit_dates[0] ?? null),
            creditlab_format_recovery_response($responses[1] ?? null, $commit_dates[1] ?? null),
            creditlab_format_recovery_response($responses[2] ?? null, $commit_dates[2] ?? null),
            creditlab_format_recovery_response($responses[3] ?? null, $commit_dates[3] ?? null),
            creditlab_format_recovery_response($responses[4] ?? null, $commit_dates[4] ?? null),
        ]);

        $rowCount++;
        if (($rowCount % 200) === 0) {
            fflush($output);
            if (function_exists('flush')) {
                flush();
            }
        }
    }
}

function creditlab_send_recovery_agency_csv(string $filename, ?int $minDpd = null, ?string $fromDate = null, ?string $toDate = null): void
{
    set_time_limit(3000);
    ignore_user_abort(true);
    ini_set('memory_limit', '512M');

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    $output = fopen('php://output', 'w');
    creditlab_write_recovery_agency_csv($output, $minDpd, $fromDate, $toDate);
    fclose($output);
    exit;
}

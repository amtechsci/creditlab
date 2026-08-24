<?php
/**
 * Admin performance dashboard data (used by admin/index.php).
 */

function performance_dashboard_sql_in(array $values): string
{
    $parts = [];
    foreach ($values as $value) {
        $parts[] = "'" . towreal($value) . "'";
    }
    return implode(',', $parts);
}

function performance_dashboard_fetch_all($result): array
{
    $rows = [];
    if ($result) {
        while ($row = towfetch($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function performance_dashboard_repayments_by_user(string $pay_start, string $pay_end, string $responding_in, ?string $lam_before = null): array
{
    $map = [];
    $lam_date_sql = $lam_before !== null ? "AND updated_at < '" . towreal($lam_before) . "'" : '';
    $query = "SELECT last_lam.updated_by,
                     COUNT(DISTINCT td.cllid) AS cnt,
                     COALESCE(SUM(td.transaction_amount), 0) AS total_amount
              FROM transaction_details td
              INNER JOIN (
                  SELECT lam.lid, lam.updated_by
                  FROM loan_acc_man lam
                  INNER JOIN (
                      SELECT lid, updated_by, MAX(id) AS max_id
                      FROM loan_acc_man
                      WHERE updated_by IS NOT NULL AND updated_by != ''
                        $lam_date_sql
                        AND lid IN (
                            SELECT cllid FROM transaction_details
                            WHERE transaction_date >= '$pay_start'
                              AND transaction_date < '$pay_end'
                              AND transaction_flow = 'full'
                        )
                      GROUP BY lid, updated_by
                  ) t ON lam.id = t.max_id
                  WHERE LOWER(TRIM(lam.customer_response)) IN ($responding_in)
              ) last_lam ON last_lam.lid = td.cllid
              WHERE td.transaction_date >= '$pay_start'
                AND td.transaction_date < '$pay_end'
                AND td.transaction_flow = 'full'
              GROUP BY last_lam.updated_by";
    foreach (performance_dashboard_fetch_all(towquery($query)) as $row) {
        $map[$row['updated_by']] = [
            'count' => (int) $row['cnt'],
            'amount' => (float) $row['total_amount'],
        ];
    }
    return $map;
}

function creditlab_performance_dashboard_load(): array
{
    $selected_date = isset($_GET['date']) ? towreal($_GET['date']) : date('Y-m-d');
    $selected_user = isset($_GET['user']) ? towreal($_GET['user']) : '';
    $view_mode = isset($_GET['view']) ? towreal($_GET['view']) : 'userwise';
    if ($view_mode !== 'updates') {
        $view_mode = 'userwise';
    }

    if (empty($selected_date) || !strtotime($selected_date)) {
        $selected_date = date('Y-m-d');
    }

    $month_start = date('Y-m-01', strtotime($selected_date));
    $month_end = $selected_date;
    $day_start = $selected_date . ' 00:00:00';
    $day_end = date('Y-m-d', strtotime($selected_date . ' +1 day')) . ' 00:00:00';
    $month_start_ts = $month_start . ' 00:00:00';
    $month_end_exclusive = date('Y-m-d', strtotime($month_end . ' +1 day')) . ' 00:00:00';

    $responding_categories_lower = array_unique(array_map('strtolower', [
        'shall pay by eod',
        'shall pay tomorrow',
        'shall pay ontime',
        'shall pay on time',
        'need extension',
        'called back',
        'shall pay part payment',
        'Sell pay part payment',
        'already paid',
        'sms sent by mobile',
    ]));
    $not_responding_categories_lower = array_unique(array_map('strtolower', [
        'call not answering',
        'cutting call',
        'Cut the call',
        'cutting the call',
        'switched off',
        'Mobile switched off',
        'out of coverage',
        'Out of coverage area',
        'number not working',
        'wrong number',
        'Wrong no',
        'call answered but no proper response',
        'Call lifted by others',
    ]));
    $repayment_responding_lower = [
        'shall pay by eod',
        'shall pay tomorrow',
        'shall pay ontime',
        'shall pay on time',
        'need extension',
        'called back',
        'shall pay part payment',
        'already paid',
        'sms sent by mobile',
    ];

    $from_date = isset($_GET['from_date']) ? towreal($_GET['from_date']) : $month_start;
    $to_date = isset($_GET['to_date']) ? towreal($_GET['to_date']) : $month_end;
    if (empty($from_date) || !strtotime($from_date)) {
        $from_date = $month_start;
    }
    if (empty($to_date) || !strtotime($to_date)) {
        $to_date = $month_end;
    }
    $from_ts = $from_date . ' 00:00:00';
    $to_exclusive = date('Y-m-d', strtotime($to_date . ' +1 day')) . ' 00:00:00';

    $selected_user_escaped = !empty($selected_user) ? towreal($selected_user) : '';

    $user_wise_rows = [];
    $today_repay_by_user = [];
    $monthly_repay_by_user = [];
    $total_updates = 0;
    $summary_rows = [];
    $details_rows = [];
    $account_managers = [];

    $responding_in = performance_dashboard_sql_in($responding_categories_lower);
    $not_responding_in = performance_dashboard_sql_in($not_responding_categories_lower);
    $repay_responding_in = performance_dashboard_sql_in($repayment_responding_lower);

    if ($view_mode === 'userwise') {
        $user_wise_query = "SELECT
                            lam.updated_by,
                            COUNT(*) as total_calls,
                            SUM(CASE WHEN LOWER(TRIM(lam.customer_response)) IN ($responding_in) THEN 1 ELSE 0 END) as responding_count,
                            SUM(CASE WHEN LOWER(TRIM(lam.customer_response)) IN ($not_responding_in) THEN 1 ELSE 0 END) as not_responding_count
                            FROM loan_acc_man lam
                            WHERE lam.updated_at >= '$day_start' AND lam.updated_at < '$day_end'
                            AND lam.updated_by IS NOT NULL AND lam.updated_by != ''
                            GROUP BY lam.updated_by
                            ORDER BY total_calls DESC";
        $user_wise_rows = performance_dashboard_fetch_all(towquery($user_wise_query));

        if (!empty($user_wise_rows)) {
            $today_repay_by_user = performance_dashboard_repayments_by_user(
                $day_start,
                $day_end,
                $repay_responding_in
            );
            $monthly_repay_by_user = performance_dashboard_repayments_by_user(
                $month_start_ts,
                $month_end_exclusive,
                $repay_responding_in,
                $month_end_exclusive
            );
        }
    } else {
        $user_where_updates = '';
        if ($selected_user_escaped !== '') {
            $user_where_updates = "AND updated_by = '$selected_user_escaped'";
        }

        $total_updates_result = towfetch(towquery(
            "SELECT COUNT(*) as total FROM loan_acc_man
             WHERE updated_at >= '$from_ts' AND updated_at < '$to_exclusive' $user_where_updates"
        ));
        $total_updates = isset($total_updates_result['total']) ? (int) $total_updates_result['total'] : 0;

        $summary_query = "SELECT updated_by, COUNT(*) as update_count,
                          MIN(updated_at) as first_update,
                          MAX(updated_at) as last_update
                          FROM loan_acc_man
                          WHERE updated_at >= '$from_ts' AND updated_at < '$to_exclusive'
                            $user_where_updates
                            AND updated_by IS NOT NULL AND updated_by != ''
                          GROUP BY updated_by
                          ORDER BY update_count DESC";
        $summary_rows = performance_dashboard_fetch_all(towquery($summary_query));

        $details_query = "SELECT lam.id, lam.uid, lam.lid, lam.customer_response, lam.commitment_date,
                                 lam.commitment_text, lam.default_type, lam.updated_at, lam.updated_by,
                                 u.name as customer_name, u.mobile as customer_mobile, u.email as customer_email
                          FROM loan_acc_man lam
                          LEFT JOIN user u ON lam.uid = u.id
                          WHERE lam.updated_at >= '$from_ts' AND lam.updated_at < '$to_exclusive' $user_where_updates
                          ORDER BY lam.updated_at DESC
                          LIMIT 500";
        $details_rows = performance_dashboard_fetch_all(towquery($details_query));

        $account_managers = performance_dashboard_fetch_all(towquery(
            "SELECT DISTINCT updated_by FROM loan_acc_man
             WHERE updated_by IS NOT NULL AND updated_by != ''
             ORDER BY updated_by"
        ));
    }

    $userwise_url = 'index.php?view=userwise&date=' . urlencode($selected_date);
    $updates_url = 'index.php?view=updates&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date);
    if ($selected_user !== '') {
        $updates_url .= '&user=' . urlencode($selected_user);
    }

    return compact(
        'selected_date',
        'selected_user',
        'view_mode',
        'from_date',
        'to_date',
        'user_wise_rows',
        'today_repay_by_user',
        'monthly_repay_by_user',
        'total_updates',
        'summary_rows',
        'details_rows',
        'account_managers',
        'userwise_url',
        'updates_url'
    );
}

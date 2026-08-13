<?php
include '../db.php';
require_once __DIR__ . '/../lib/staff_context.php';

if (!creditlab_can_download_account_manager_data()) {
    http_response_code(403);
    exit('Forbidden');
}

// Large AM exports were timed out (browser: "Check Internet connection")
// due to N+1 queries per loan. Stream one JOIN + flush periodically.
set_time_limit(3000);
ignore_user_abort(true);
ini_set('memory_limit', '512M');

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Account_manager_data.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$output = fopen('php://output', 'w');
fputcsv($output, [
    'Name', 'primary number', 'alt number', 'primary mail', 'alt mail',
    'principal loan', 'processed amount', 'exhausted days', 'DPD',
    'outstanding amount', 'total loans', 'loan id',
]);

// One row per AM loan (same scope as account_manager.php download).
$sql = "SELECT
            u.name,
            u.mobile,
            u.altmobile,
            u.email,
            u.altemail,
            u.id AS uid,
            l.lid,
            l.processed_date,
            l.processed_amount,
            l.p_fee,
            l.service_charge,
            l.penality_charge,
            l.is_emi,
            la.days AS loan_apply_days,
            (
                SELECT COUNT(*)
                FROM `loan` lc
                WHERE lc.uid = u.id
            ) AS total_loans
        FROM `loan_apply` la
        INNER JOIN `loan` l ON l.lid = la.id AND l.status_log = 'account manager'
        INNER JOIN `user` u ON u.id = l.uid
        WHERE la.status = 'account manager'
        ORDER BY la.id ASC";

$result = towquery($sql);
if (!$result) {
    fclose($output);
    exit;
}

$rowCount = 0;
while ($b = towfetch($result)) {
    if (empty($b['processed_date'])) {
        continue;
    }

    $processed_amount = (float) $b['processed_amount'];
    $p_fee = (float) $b['p_fee'];
    $service_charge = (float) $b['service_charge'];
    $penality_charge = (float) $b['penality_charge'];

    $tday = (int) ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($b['processed_date'] . ' -1 day')))) / (60 * 60 * 24));
    $loan_days_raw = isset($b['loan_apply_days']) ? (int) $b['loan_apply_days'] : 30;
    $loan_is_emi = isset($b['is_emi']) ? (int) $b['is_emi'] : 0;
    $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
    $dpd = $tday - $loan_days;

    $principal = $processed_amount + $p_fee + ($p_fee * 0.18);
    $outstanding = $principal + $service_charge + $penality_charge;

    fputcsv($output, [
        $b['name'],
        $b['mobile'],
        $b['altmobile'],
        $b['email'],
        $b['altemail'],
        $principal,
        $processed_amount,
        $tday,
        $dpd,
        $outstanding,
        (int) $b['total_loans'],
        $b['lid'],
    ]);

    $rowCount++;
    if (($rowCount % 200) === 0) {
        fflush($output);
        if (function_exists('flush')) {
            flush();
        }
    }
}

fclose($output);
exit;
?>

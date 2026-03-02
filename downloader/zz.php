<?php
include '../db.php';

// One row per loan (same scope as account manager page: all status='account manager')
$loan_query = mysqli_query($db, "SELECT id FROM `loan_apply` WHERE `status`='account manager' ORDER BY id ASC");

// Set headers for file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Account_manager_data.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, [
    "Name" , "primary number", "alt number", "primary mail" , "alt mail" , "principal loan", "processed amount", "exhausted days" , "DPD", "outstanding amount", "total loans","loan id"
]);

while ($loan_row = towfetch($loan_query)) {
    $lid = (int) $loan_row['id'];
    $a = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi, loan_acc_man.customer_response, loan_acc_man.commitment_date, loan_acc_man.updated_at, loan_apply.days FROM user INNER JOIN loan ON loan.uid=user.id INNER JOIN loan_apply ON loan.lid = loan_apply.id LEFT JOIN loan_acc_man ON loan_acc_man.uid=user.id AND loan_acc_man.lid=loan.lid WHERE loan.lid=$lid AND loan.status_log='account manager'");
    if (townum($a) > 0) {
        $b = towfetch($a);
        if (empty($b['processed_date'])) continue;
        extract($b, EXTR_PREFIX_ALL, "user");

        $loan_count_query = towquery("SELECT COUNT(*) AS total_loans FROM `loan` WHERE uid=" . intval($user_uid));
        $loan_count_row = $loan_count_query ? towfetch($loan_count_query) : null;
        $loan_count = $loan_count_row ? (int)$loan_count_row['total_loans'] : 0;

        $tday = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($user_processed_date . " -1 day")))) / (60 * 60 * 24));
        $loan_days_raw = isset($user_days) ? (int)$user_days : 30;
        $loan_is_emi = isset($user_is_emi) ? (int)$user_is_emi : 0;
        $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
        $dpd = $tday - $loan_days;

        $row = [
            $user_name,
            $user_mobile,
            $user_altmobile,
            $user_email,
            $user_altemail,
            (float)$user_processed_amount + (float)$user_p_fee + ((float)$user_p_fee * 0.18),
            $user_processed_amount,
            $tday,
            $dpd,
            (float)$user_processed_amount + (float)$user_p_fee + ((float)$user_p_fee * 0.18) + (float)$user_service_charge + (float)$user_penality_charge,
            $loan_count,
            $user_lid
        ];

        fputcsv($output, $row);
    }
}

// Close the output stream
fclose($output);

exit;
?>

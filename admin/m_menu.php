<?php
// Use $counts_data from Left_menu.php if present; fallback to avoid notice
if (!isset($counts_data) || !is_array($counts_data)) {
    $counts = towquery("
        SELECT
            (SELECT COUNT(*) FROM `pay_ref`) AS loanclearance_count,
            (SELECT COUNT(*) FROM `user` WHERE `active`=1 AND `verify`=0) AS newusers_count,
            (SELECT COUNT(*) FROM `user` WHERE `verify`=1 AND `status`='Approved') AS approved_users_count,
            (SELECT COUNT(DISTINCT uid) FROM `loan_apply` WHERE `status`='pending') AS applied_loans_count,
            (SELECT COUNT(DISTINCT loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND user.sloan > 0) AS repeat_loan_d_count,
            (SELECT COUNT(DISTINCT loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='cancel') AS cancelled_loans_count,
            (SELECT COUNT(DISTINCT loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE user.status='HOLD') AS hold_loans_count,
            (SELECT COUNT(DISTINCT uid) FROM `loan_apply` WHERE `status`='follow up') AS follow_up_count,
            (SELECT COUNT(loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND sloan=0) AS disbursal_count,
            (SELECT COUNT(DISTINCT uid) FROM loan_apply WHERE `status`='account manager') AS account_manager_count,
            (SELECT COUNT(DISTINCT uid) FROM loan_apply WHERE `status`='recovery officer') AS recovery_officer_count,
            (SELECT COUNT(*) FROM `loan` WHERE `advance_amount` > 0 AND (`status_log` != 'cleared' OR `status_log` IS NULL)) AS part_payment_count
    ");
    $counts_data = towfetch($counts);
}
// Legacy MeanMenu accordion replaced by the modern CreditLab off-canvas drawer in Left_menu.php
?>

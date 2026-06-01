<?php
/**
 * Loan cleared SMS (DLT 1407175016297384512 via send_sms.php mapping).
 */
function creditlab_send_loan_cleared_sms(string $mobile, string $customerName, int $loanLid, ?string $baseUrl = null): bool
{
    if ($mobile === '' || !file_exists(__DIR__ . '/../send_sms.php')) {
        return false;
    }
    if ($baseUrl === null) {
        require_once __DIR__ . '/zxc_mail.php';
        $baseUrl = creditlab_get_base_url();
    }
    $baseUrl = rtrim($baseUrl, '/');
    $template_id = '1107165683325768963';
    $message = "Dear {$customerName}, we acknowledge the repayment of your loan CLL{$loanLid} & it's cleared. You can apply again. {$baseUrl}/ -Creditlab";
    define('CREDITLAB_SMS_INCLUDE', true);
    include __DIR__ . '/../send_sms.php';
    return true;
}

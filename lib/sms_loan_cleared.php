<?php
/**
 * Loan cleared SMS (DLT 1407175016297384512 via send_sms.php mapping).
 */
require_once __DIR__ . '/app_url.php';
require_once __DIR__ . '/zxc_mail.php';
require_once __DIR__ . '/http_fetch.php';

function creditlab_send_loan_cleared_sms(string $mobile, string $customerName, int $loanLid, ?string $baseUrl = null): bool
{
    $mobile = preg_replace('/\D/', '', $mobile);
    if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
        $mobile = substr($mobile, 2);
    }
    if (strlen($mobile) < 10 || !file_exists(__DIR__ . '/../send_sms.php')) {
        error_log("loan cleared SMS skipped: mobile=$mobile lid=$loanLid");
        return false;
    }
    if ($baseUrl === null) {
        $baseUrl = creditlab_get_base_url();
    }
    $baseUrl = rtrim($baseUrl, '/');
    $template_id = '1107165683325768963';
    $message = "Dear {$customerName}, we acknowledge the repayment of your loan CLL{$loanLid} & it's cleared. You can apply again. {$baseUrl}/ -Creditlab";
    if (!defined('CREDITLAB_SMS_INCLUDE')) {
        define('CREDITLAB_SMS_INCLUDE', true);
    }
    $GLOBALS['creditlab_last_sms_ok'] = false;
    include __DIR__ . '/../send_sms.php';
    return !empty($GLOBALS['creditlab_last_sms_ok']);
}

/**
 * After PG settlement: email + cleared SMS with logging.
 */
function creditlab_pg_notify_loan_cleared(array $userDetails, int $loanLid, ?string $baseUrl = null): bool
{
    $base = $baseUrl ?? creditlab_get_base_url();
    if (!empty($userDetails['email'])) {
        creditlab_zxc_mail_trigger(creditlab_zxc_mail_url($base, $userDetails['email'], null, null, $base . '/no-due-certificate2.php?id=' . $loanLid));
    }
    return creditlab_send_loan_cleared_sms(
        (string) ($userDetails['mobile'] ?? ''),
        (string) ($userDetails['name'] ?? 'Customer'),
        $loanLid,
        $base
    );
}

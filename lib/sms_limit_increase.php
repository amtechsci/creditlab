<?php
/**
 * Limit-increase SMS (DLT template 1407175198059581991).
 */
require_once __DIR__ . '/app_url.php';

function creditlab_send_limit_increase_sms(string $mobile, $newLimit, ?string $baseUrl = null): bool
{
    $mobile = preg_replace('/\D/', '', $mobile);
    if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
        $mobile = substr($mobile, 2);
    }
    if (strlen($mobile) < 10 || !file_exists(__DIR__ . '/../send_sms.php')) {
        error_log("limit increase SMS skipped: mobile=$mobile");
        return false;
    }
    if ($baseUrl === null) {
        $baseUrl = creditlab_get_base_url();
    }
    $baseUrl = rtrim($baseUrl, '/');
    $limitStr = (string) (int) round((float) $newLimit);
    $template_id = '1407175198059581991';
    $message = "Dear Creditlab.in customer, your limit has been updated to Rs{$limitStr}. Please log in to your account to withdraw: {$baseUrl}";
    if (!defined('CREDITLAB_SMS_INCLUDE')) {
        define('CREDITLAB_SMS_INCLUDE', true);
    }
    $GLOBALS['creditlab_last_sms_ok'] = false;
    include __DIR__ . '/../send_sms.php';
    return !empty($GLOBALS['creditlab_last_sms_ok']);
}

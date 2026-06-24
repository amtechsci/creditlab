<?php
/**
 * Authorization and internal reads for user upload files (S3-backed).
 */

require_once __DIR__ . '/s3_aws_sdk.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/staff_context.php';

function creditlab_is_valid_upload_filename(string $filename): bool
{
    $filename = basename($filename);
    return $filename !== '' && strtolower($filename) !== 'no';
}

function creditlab_user_owns_file(int $userId, string $filename): bool
{
    if (!creditlab_is_valid_upload_filename($filename)) {
        return false;
    }

    $userId = (int) $userId;
    $fn = towreal(basename($filename));

    $scalarCols = [
        'conpanydocument',
        'salarydocument',
        'bankdocument',
        'bankdocument2',
        'bankdocument3',
        'addressdocument',
        'companyidcard',
        'signature',
        'selfie',
    ];
    $parts = [];
    foreach ($scalarCols as $col) {
        $parts[] = "($col = '$fn' AND $col NOT IN ('', 'no'))";
    }
    $parts[] = "(personaldocument = '$fn' OR personaldocument LIKE '$fn#%' OR personaldocument LIKE '%#$fn' OR personaldocument LIKE '%#$fn#%')";
    $where = implode(' OR ', $parts);

    $userMatch = towquery("SELECT id FROM user WHERE id = $userId AND ($where) LIMIT 1");
    if ($userMatch && townum($userMatch) > 0) {
        return true;
    }

    $bankMatch = towquery(
        "SELECT id FROM user_bank WHERE uid = $userId AND bank_statment = '$fn' AND bank_statment NOT IN ('', 'no') LIMIT 1"
    );
    if ($bankMatch && townum($bankMatch) > 0) {
        return true;
    }

    $payMatch = towquery(
        "SELECT id FROM pay_ref WHERE uid = $userId AND payment_screenshot = '$fn' AND payment_screenshot NOT IN ('', 'no') LIMIT 1"
    );
    return $payMatch && townum($payMatch) > 0;
}

function creditlab_authorize_file_download(string $filename): bool
{
    if (!creditlab_is_valid_upload_filename($filename)) {
        return false;
    }
    if (creditlab_is_staff_logged_in()) {
        if (!creditlab_can_view_documents()) {
            return false;
        }
        return true;
    }
    $customerId = creditlab_get_logged_in_customer_id();
    if ($customerId !== null) {
        return creditlab_user_owns_file($customerId, $filename);
    }
    return false;
}

/**
 * Read upload bytes from S3 (for in-app use, e.g. PDF generation — not via HTTP).
 *
 * @return array{0: bool, 1: string, 2: array|null}
 */
function creditlab_fetch_upload_file(string $filename): array
{
    if (!creditlab_is_valid_upload_filename($filename)) {
        return [false, '', null];
    }
    return s3_download_file(basename($filename));
}

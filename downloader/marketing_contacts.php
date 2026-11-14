<?php
/**
 * Marketing contact export for TRAI / SMS list hygiene.
 * Columns: primary mobile | alt mobile | primary mail | status
 * Admin only. Does not send SMS.
 */
include '../db.php';
require_once __DIR__ . '/../lib/staff_context.php';

if (creditlab_staff_role() !== 'admin') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Forbidden — admin login required.');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=marketing_contacts.csv');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$output = fopen('php://output', 'w');
fputcsv($output, [
    'primary mobile number',
    'alt mobile number',
    'primary mail',
    'status',
]);

/**
 * Map stored user.status to marketing label.
 */
function creditlab_marketing_status_label(?string $status): string
{
    $raw = trim((string) $status);
    $key = strtolower($raw);

    $map = [
        'waiting' => 'registered',
        'pending' => 'applied',
        'applied' => 'applied',
        'approved' => 'approved',
        'follow up' => 'follow up',
        'disbursal' => 'disbursal',
        'cleared' => 'cleared',
        'account manager' => 'account manager',
        'hold' => 'hold',
    ];

    if (isset($map[$key])) {
        return $map[$key];
    }
    return $raw !== '' ? $raw : '';
}

$result = towquery('SELECT mobile, altmobile, email, status FROM `user` ORDER BY id ASC');
if ($result) {
    while ($row = towfetch($result)) {
        fputcsv($output, [
            $row['mobile'] ?? '',
            $row['altmobile'] ?? '',
            $row['email'] ?? '',
            creditlab_marketing_status_label($row['status'] ?? ''),
        ]);
    }
}

fclose($output);
exit;

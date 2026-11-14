<?php
/**
 * Payment channel helpers for transaction_details.payment_channel
 * Values: manual | pg | enach
 */

function creditlab_payment_channel_label(?string $channel): string
{
    switch (strtolower(trim((string) $channel))) {
        case 'pg':
            return 'Normal PG';
        case 'enach':
            return 'Enach';
        case 'manual':
            return 'Manual';
        default:
            return $channel !== null && $channel !== '' ? (string) $channel : 'Manual';
    }
}

function creditlab_payment_channel_badge(?string $channel): string
{
    $key = strtolower(trim((string) $channel));
    if ($key === '') {
        $key = 'manual';
    }
    $styles = [
        'manual' => ['bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#e2e8f0'],
        'pg' => ['bg' => '#dbeafe', 'fg' => '#1d4ed8', 'border' => '#bfdbfe'],
        'enach' => ['bg' => '#f3e8ff', 'fg' => '#7e22ce', 'border' => '#e9d5ff'],
    ];
    $c = $styles[$key] ?? $styles['manual'];
    $label = creditlab_payment_channel_label($key);
    return '<span class="dash-status" style="background:' . $c['bg'] . ';color:' . $c['fg'] . ';border:1px solid ' . $c['border'] . ';font-size:11px;font-weight:700;padding:2px 8px;border-radius:9999px;white-space:nowrap;display:inline-flex;">'
        . htmlspecialchars($label)
        . '</span>';
}

/**
 * Ensure payment_channel column exists (idempotent).
 * @param mysqli|null $dbConn optional raw connection (eNACH path)
 */
function creditlab_ensure_payment_channel_column($dbConn = null): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    if ($dbConn instanceof mysqli) {
        $check = mysqli_query($dbConn, "SHOW COLUMNS FROM `transaction_details` LIKE 'payment_channel'");
        if ($check && mysqli_num_rows($check) > 0) {
            return;
        }
        mysqli_query($dbConn, "ALTER TABLE `transaction_details` ADD COLUMN `payment_channel` VARCHAR(16) NOT NULL DEFAULT 'manual' AFTER `transaction_flow`");
        return;
    }
    if (!function_exists('towquery')) {
        return;
    }
    $check = towquery("SHOW COLUMNS FROM `transaction_details` LIKE 'payment_channel'");
    if ($check && townum($check) > 0) {
        return;
    }
    towquery("ALTER TABLE `transaction_details` ADD COLUMN `payment_channel` VARCHAR(16) NOT NULL DEFAULT 'manual' AFTER `transaction_flow`");
}

/**
 * Distinct payment channels for a loan (cllid), as badge HTML.
 */
function creditlab_loan_payment_channels_html(int $cllid): string
{
    creditlab_ensure_payment_channel_column();
    $q = towquery("SELECT DISTINCT payment_channel FROM `transaction_details` WHERE cllid=" . intval($cllid) . " AND payment_channel IS NOT NULL AND payment_channel != ''");
    // Also include rows that may predate column default
    if (!$q || townum($q) === 0) {
        $any = towquery('SELECT id FROM `transaction_details` WHERE cllid=' . intval($cllid) . ' LIMIT 1');
        if ($any && townum($any) > 0) {
            return creditlab_payment_channel_badge('manual');
        }
        return '<span style="color:var(--cl-slate-400); font-size:12px;">—</span>';
    }
    $parts = [];
    while ($row = towfetch($q)) {
        $parts[] = creditlab_payment_channel_badge($row['payment_channel'] ?? 'manual');
    }
    if (empty($parts)) {
        return '<span style="color:var(--cl-slate-400); font-size:12px;">—</span>';
    }
    return implode(' ', $parts);
}

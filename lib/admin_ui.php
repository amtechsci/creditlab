<?php
function creditlab_dash_text(?string $value, string $fallback = '—'): string
{
    $value = trim((string) $value);
    return $value !== '' ? htmlspecialchars($value) : $fallback;
}

function creditlab_status_badge(?string $status): string
{
    $raw = trim((string) $status);
    $key = strtolower($raw);
    $label = $key === 'waiting' ? 'Just registered' : ($raw !== '' ? $raw : 'Unknown');

    $styles = [
        'default' => ['bg' => '#fee2e2', 'fg' => '#b91c1c'],
        'disbursal' => ['bg' => '#dcfce7', 'fg' => '#15803d'],
        'hold' => ['bg' => '#fef3c7', 'fg' => '#b45309'],
        'approved' => ['bg' => '#dbeafe', 'fg' => '#1d4ed8'],
        'applied' => ['bg' => '#e0e7ff', 'fg' => '#4338ca'],
        'waiting' => ['bg' => '#f3f4f6', 'fg' => '#374151'],
        'cancel' => ['bg' => '#ffe4e6', 'fg' => '#be123c'],
    ];
    $c = $styles[$key] ?? ['bg' => '#e0f2fe', 'fg' => '#0369a1'];

    return '<span class="dash-status" style="background:' . $c['bg'] . ';color:' . $c['fg'] . ';">'
        . htmlspecialchars($label)
        . '</span>';
}

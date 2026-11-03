<?php
function creditlab_dash_text(?string $value, string $fallback = '—'): string
{
    $value = trim((string) $value);
    return $value !== '' ? htmlspecialchars($value) : $fallback;
}

function creditlab_currency($amount): string
{
    if ($amount === null || $amount === '') {
        return '₹0.00';
    }
    return '₹' . number_format((float)$amount, 2);
}

function creditlab_status_badge(?string $status): string
{
    $raw = trim((string) $status);
    $key = strtolower($raw);
    
    $label = $raw !== '' ? $raw : 'Unknown';
    if ($key === 'waiting') {
        $label = 'Just registered';
    } elseif ($key === 'recovery officer') {
        $label = 'Recovery Officer';
    } elseif ($key === 'account manager') {
        $label = 'Account Manager';
    }

    $styles = [
        'default' => ['bg' => '#fee2e2', 'fg' => '#b91c1c', 'border' => '#fecaca'],
        'cleared' => ['bg' => '#dcfce7', 'fg' => '#15803d', 'border' => '#bbf7d0'],
        'settled' => ['bg' => '#dcfce7', 'fg' => '#15803d', 'border' => '#bbf7d0'],
        'disbursal' => ['bg' => '#dcfce7', 'fg' => '#15803d', 'border' => '#bbf7d0'],
        'approved' => ['bg' => '#dbeafe', 'fg' => '#1d4ed8', 'border' => '#bfdbfe'],
        'applied' => ['bg' => '#e0e7ff', 'fg' => '#4338ca', 'border' => '#c7d2fe'],
        'pending' => ['bg' => '#e0e7ff', 'fg' => '#4338ca', 'border' => '#c7d2fe'],
        'hold' => ['bg' => '#fef3c7', 'fg' => '#b45309', 'border' => '#fde68a'],
        'follow up' => ['bg' => '#fef3c7', 'fg' => '#b45309', 'border' => '#fde68a'],
        'waiting' => ['bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#e2e8f0'],
        'cancel' => ['bg' => '#ffe4e6', 'fg' => '#be123c', 'border' => '#fecdd3'],
        'account manager' => ['bg' => '#f3e8ff', 'fg' => '#7e22ce', 'border' => '#e9d5ff'],
        'recovery officer' => ['bg' => '#fae8ff', 'fg' => '#86198f', 'border' => '#f5d0fe'],
    ];
    $c = $styles[$key] ?? ['bg' => '#e0f2fe', 'fg' => '#0369a1', 'border' => '#bae6fd'];

    return '<span class="dash-status" style="background:' . $c['bg'] . ';color:' . $c['fg'] . ';border:1px solid ' . $c['border'] . ';font-size:11.5px;font-weight:600;padding:2px 9px;border-radius:9999px;white-space:nowrap;display:inline-flex;align-items:center;line-height:1.4;">'
        . htmlspecialchars($label)
        . '</span>';
}

function creditlab_render_pagination(int $pageno, int $total_pages, string $url_prefix = '?pageno='): string
{
    if ($total_pages <= 1) {
        return '';
    }
    
    $prev_url = $pageno <= 1 ? '#' : $url_prefix . ($pageno - 1);
    $next_url = $pageno >= $total_pages ? '#' : $url_prefix . ($pageno + 1);

    $html = '<nav aria-label="Page navigation"><ul class="pagination" style="margin:0;">';
    
    // Previous
    $html .= '<li class="page-item ' . ($pageno <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $prev_url . '" aria-label="Previous">&laquo;</a></li>';

    // Window logic
    $win_start = max(1, $pageno - 2);
    $win_end = min($total_pages, $pageno + 2);

    if ($win_start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url_prefix . '1">1</a></li>';
        if ($win_start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    for ($i = $win_start; $i <= $win_end; $i++) {
        $active = $i == $pageno ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url_prefix . $i . '">' . $i . '</a></li>';
    }

    if ($win_end < $total_pages) {
        if ($win_end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $url_prefix . $total_pages . '">' . $total_pages . '</a></li>';
    }

    // Next
    $html .= '<li class="page-item ' . ($pageno >= $total_pages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $next_url . '" aria-label="Next">&raquo;</a></li>';

    $html .= '</ul></nav>';
    return $html;
}

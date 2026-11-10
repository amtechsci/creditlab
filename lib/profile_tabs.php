<?php
/**
 * Profile sub-tab visibility by staff role.
 */

/**
 * Role used for profile tab nav / pane guards (agency wrapper, session, or default).
 */
function creditlab_profile_page_role(): string
{
    if (!empty($GLOBALS['creditlab_staff_role'])) {
        return (string) $GLOBALS['creditlab_staff_role'];
    }
    if (!function_exists('creditlab_staff_role')) {
        require_once __DIR__ . '/staff_context.php';
    }
    $sessionRole = creditlab_staff_role();
    if ($sessionRole !== null) {
        return $sessionRole;
    }
    return 'account_manager';
}

function creditlab_profile_tab_definitions(): array
{
    return [
        'Personal' => ['id' => 'Personal', 'label' => 'Personal', 'href' => '#Personal', 'icon' => 'fa-solid fa-user'],
        'INFORMATION' => ['id' => 'INFORMATION', 'label' => 'Documents', 'href' => '#INFORMATION', 'icon' => 'fa-solid fa-file'],
        'Bank' => ['id' => 'Bank', 'label' => 'Bank Info', 'href' => '#Bank', 'icon' => 'fa-solid fa-building-columns'],
        'Reference' => ['id' => 'Reference', 'label' => 'Reference', 'href' => '#Reference', 'icon' => 'fa-solid fa-address-book'],
        'login_data' => ['id' => 'login_data', 'label' => 'Login Data', 'href' => '#login_data', 'icon' => 'fa-solid fa-right-to-bracket'],
        'profile_detail' => ['id' => 'profile_detail', 'label' => 'Profile Detail', 'href' => '#profile_detail', 'icon' => 'fa-solid fa-id-card'],
        'user_contact' => ['id' => 'user_contact', 'label' => 'Contacts', 'href' => '#user_contact', 'icon' => 'fa-solid fa-phone'],
        'contact' => ['id' => 'contact', 'label' => 'Contact Logs', 'href' => '#contact', 'icon' => 'fa-solid fa-address-card'],
        'loan' => ['id' => 'loan', 'label' => 'Apply Loan', 'href' => '#loan', 'icon' => 'fa-solid fa-plus-circle'],
        'oldloan' => ['id' => 'oldloan', 'label' => 'All Loans', 'href' => '#oldloan', 'icon' => 'fa-solid fa-file-invoice-dollar'],
        'transaction_details' => ['id' => 'transaction_details', 'label' => 'Transactions', 'href' => '#transaction_details', 'icon' => 'fa-solid fa-receipt'],
        'Validation' => ['id' => 'Validation', 'label' => 'Validation', 'href' => '#Validation', 'icon' => 'fa-solid fa-shield-halved'],
        'follow_up' => ['id' => 'follow_up', 'label' => 'Follow Up', 'href' => '#follow_up', 'icon' => 'fa-solid fa-phone-volume'],
        'note' => ['id' => 'note', 'label' => 'Notes', 'href' => '#note', 'icon' => 'fa-solid fa-note-sticky'],
        'sms' => ['id' => 'sms', 'label' => 'SMS', 'href' => '#sms', 'icon' => 'fa-solid fa-comment-sms'],
        'cibil_analysis' => ['id' => 'cibil_analysis', 'label' => 'CIBIL', 'href' => '#cibil_analysis', 'icon' => 'fa-solid fa-chart-line'],
        'pan_analysis' => ['id' => 'pan_analysis', 'label' => 'PAN', 'href' => '#pan_analysis', 'icon' => 'fa-solid fa-id-badge'],
        'adhar_analysis' => ['id' => 'adhar_analysis', 'label' => 'Aadhaar', 'href' => '#adhar_analysis', 'icon' => 'fa-solid fa-fingerprint'],
        'bank_analysis' => ['id' => 'bank_analysis', 'label' => 'Bank Statement', 'href' => '#bank_analysis', 'icon' => 'fa-solid fa-chart-simple'],
        'mail' => ['id' => 'mail', 'label' => 'Mail', 'href' => '#mail', 'icon' => 'fa-solid fa-envelope'],
        'manager' => ['id' => 'manager', 'label' => 'Account Manager', 'href' => '#manager', 'icon' => 'fa-solid fa-briefcase'],
        'payment' => ['id' => 'payment', 'label' => 'Payment', 'href' => '#payment', 'icon' => 'fa-solid fa-credit-card'],
        'pg_link' => ['id' => 'pg_link', 'label' => 'PG Link', 'href' => '#pg_link', 'icon' => 'fa-solid fa-link'],
    ];
}

function creditlab_profile_tabs_for_role(?string $role): array
{
    $all = array_keys(creditlab_profile_tab_definitions());

    if ($role === 'agency_admin') {
        return ['Personal', 'Reference', 'oldloan', 'note', 'manager', 'pg_link'];
    }

    $staffWithPg = ['admin', 'account_manager', 'agency_admin', 'recovery_officer'];
    if (in_array($role, $staffWithPg, true)) {
        $base = $all;
        if (!in_array('pg_link', $base, true)) {
            $base[] = 'pg_link';
        }
        return $base;
    }

    return $all;
}

function creditlab_profile_tab_visible(string $tabKey, ?string $role = null): bool
{
    if ($role === null) {
        $role = creditlab_staff_role();
        if ($role === null && !empty($GLOBALS['creditlab_staff_role'])) {
            $role = $GLOBALS['creditlab_staff_role'];
        }
        if ($role === null) {
            $role = 'account_manager';
        }
    }
    return in_array($tabKey, creditlab_profile_tabs_for_role($role), true);
}

function creditlab_render_profile_tab_nav(?string $role = null, string $defaultActive = 'Personal'): void
{
    $defs = creditlab_profile_tab_definitions();
    $allowed = creditlab_profile_tabs_for_role($role ?? creditlab_staff_role() ?? ($GLOBALS['creditlab_staff_role'] ?? 'account_manager'));
    $first = true;
    foreach ($allowed as $key) {
        if (!isset($defs[$key])) {
            continue;
        }
        $d = $defs[$key];
        $active = ($first && $defaultActive === $key) || ($defaultActive === $key);
        $liClass = $active ? ' class="active"' : '';
        $style = $first ? ' style="margin-bottom:30px;"' : '';
        echo '<li' . $liClass . ' data-toggle="tab"' . $style . '><a href="' . htmlspecialchars($d['href']) . '" data-toggle="tab">' . htmlspecialchars($d['label']) . '</a></li>';
        if ($first && $defaultActive === 'Personal') {
            $first = false;
        }
    }
}

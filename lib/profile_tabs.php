<?php
/**
 * Profile sub-tab visibility by staff role.
 */

function creditlab_profile_tab_definitions(): array
{
    return [
        'Personal' => ['id' => 'Personal', 'label' => 'Personal', 'href' => '#Personal'],
        'INFORMATION' => ['id' => 'INFORMATION', 'label' => 'Documents', 'href' => '#INFORMATION'],
        'Bank' => ['id' => 'Bank', 'label' => 'Bank Information', 'href' => '#Bank'],
        'Reference' => ['id' => 'Reference', 'label' => 'Reference', 'href' => '#Reference'],
        'login_data' => ['id' => 'login_data', 'label' => 'Login Data', 'href' => '#login_data'],
        'profile_detail' => ['id' => 'profile_detail', 'label' => 'Profile Detail', 'href' => '#profile_detail'],
        'user_contact' => ['id' => 'user_contact', 'label' => 'Number Contact', 'href' => '#user_contact'],
        'contact' => ['id' => 'contact', 'label' => 'Contact', 'href' => '#contact'],
        'loan' => ['id' => 'loan', 'label' => 'Apply Loan', 'href' => '#loan'],
        'oldloan' => ['id' => 'oldloan', 'label' => 'All Loan', 'href' => '#oldloan'],
        'transaction_details' => ['id' => 'transaction_details', 'label' => 'Transaction Details', 'href' => '#transaction_details'],
        'Validation' => ['id' => 'Validation', 'label' => 'Validation', 'href' => '#Validation'],
        'follow_up' => ['id' => 'follow_up', 'label' => 'Follow Up', 'href' => '#follow_up'],
        'note' => ['id' => 'note', 'label' => 'Note', 'href' => '#note'],
        'sms' => ['id' => 'sms', 'label' => 'SMS', 'href' => '#sms'],
        'cibil_analysis' => ['id' => 'cibil_analysis', 'label' => 'CIBIL ANALYSIS', 'href' => '#cibil_analysis'],
        'pan_analysis' => ['id' => 'pan_analysis', 'label' => 'PAN ANALYSIS', 'href' => '#pan_analysis'],
        'adhar_analysis' => ['id' => 'adhar_analysis', 'label' => 'Adhar ANALYSIS', 'href' => '#adhar_analysis'],
        'bank_analysis' => ['id' => 'bank_analysis', 'label' => 'Bank Statement ANALYSIS', 'href' => '#bank_analysis'],
        'mail' => ['id' => 'mail', 'label' => 'Mail', 'href' => '#mail'],
        'manager' => ['id' => 'manager', 'label' => 'Account manager', 'href' => '#manager'],
        'payment' => ['id' => 'payment', 'label' => 'payment', 'href' => '#payment'],
        'pg_link' => ['id' => 'pg_link', 'label' => 'PG link', 'href' => '#pg_link'],
    ];
}

function creditlab_profile_tabs_for_role(?string $role): array
{
    $all = array_keys(creditlab_profile_tab_definitions());

    if ($role === 'agency_admin') {
        return ['pg_link', 'Reference'];
    }

    $staffWithPg = ['admin', 'account_manager', 'agency_admin'];
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

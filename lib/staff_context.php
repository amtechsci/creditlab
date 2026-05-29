<?php
/**
 * Current staff session (admin, account_manager, agency_admin, etc.).
 */
require_once __DIR__ . '/auth.php';

function creditlab_staff_role(): ?string
{
    global $admin, $account_manager, $recovery_officer, $verify_user, $agency_admin;
    if (!empty($admin)) {
        return 'admin';
    }
    if (!empty($account_manager)) {
        return 'account_manager';
    }
    if (!empty($agency_admin)) {
        return 'agency_admin';
    }
    if (!empty($recovery_officer)) {
        return 'recovery_officer';
    }
    if (!empty($verify_user)) {
        return 'verify_user';
    }
    return null;
}

function creditlab_staff_actor(): ?array
{
    global $admin, $account_manager, $agency_admin, $user_id, $user_name;

    $role = creditlab_staff_role();
    if ($role === null) {
        return null;
    }

    if ($role === 'admin') {
        global $admin;
        $email = $admin;
        $q = towquery("SELECT id, name, email FROM user WHERE email='" . towreal($email) . "' AND active=2 LIMIT 1");
        if ($q && townum($q) > 0) {
            $row = towfetch($q);
            return [
                'role' => 'admin',
                'id' => (int) $row['id'],
                'name' => $row['name'] ?? 'Admin',
                'email' => $row['email'] ?? $email,
                'agency_id' => null,
                'agency_name' => null,
            ];
        }
        return ['role' => 'admin', 'id' => 0, 'name' => 'Admin', 'email' => $email, 'agency_id' => null, 'agency_name' => null];
    }

    if ($role === 'account_manager') {
        return [
            'role' => 'account_manager',
            'id' => (int) ($user_id ?? 0),
            'name' => $user_name ?? 'Account Manager',
            'email' => $GLOBALS['account_manager'] ?? '',
            'agency_id' => null,
            'agency_name' => null,
        ];
    }

    if ($role === 'agency_admin') {
        global $agency_admin, $agency_admin_agency_id, $agency_admin_agency_name;
        return [
            'role' => 'agency_admin',
            'id' => (int) ($user_id ?? 0),
            'name' => $user_name ?? 'Agency',
            'email' => $agency_admin ?? '',
            'agency_id' => isset($agency_admin_agency_id) ? (int) $agency_admin_agency_id : null,
            'agency_name' => $agency_admin_agency_name ?? null,
        ];
    }

    return ['role' => $role, 'id' => 0, 'name' => ucfirst(str_replace('_', ' ', $role)), 'email' => '', 'agency_id' => null, 'agency_name' => null];
}

function creditlab_can_view_pan_aadhar(): bool
{
    return creditlab_staff_role() !== 'agency_admin';
}

function creditlab_can_create_pg_link(): bool
{
    $role = creditlab_staff_role();
    return in_array($role, ['admin', 'account_manager', 'agency_admin'], true);
}

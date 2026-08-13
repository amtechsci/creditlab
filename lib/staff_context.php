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
        $agencyId = isset($agency_admin_agency_id) ? (int) $agency_admin_agency_id : null;
        $agencyName = $agency_admin_agency_name ?? null;
        $actorId = (int) ($user_id ?? 0);
        $actorName = $user_name ?? 'Agency';

        if ((empty($agencyId) || empty($agencyName)) && !empty($agency_admin)) {
            $q = towquery(
                "SELECT aa.id, aa.name, aa.agency_id, ag.name AS agency_name
                FROM agency_admin aa
                INNER JOIN agency ag ON ag.id = aa.agency_id
                WHERE aa.email='" . towreal($agency_admin) . "' AND aa.active=1
                LIMIT 1"
            );
            if ($q && townum($q) > 0) {
                $row = towfetch($q);
                $actorId = (int) $row['id'];
                $actorName = $row['name'] ?? $actorName;
                $agencyId = (int) $row['agency_id'];
                $agencyName = $row['agency_name'] ?? null;
            }
        }

        return [
            'role' => 'agency_admin',
            'id' => $actorId,
            'name' => $actorName,
            'email' => $agency_admin ?? '',
            'agency_id' => $agencyId ?: null,
            'agency_name' => $agencyName,
        ];
    }

    return ['role' => $role, 'id' => 0, 'name' => ucfirst(str_replace('_', ' ', $role)), 'email' => '', 'agency_id' => null, 'agency_name' => null];
}

function creditlab_can_view_pan_aadhar(): bool
{
    return creditlab_staff_role() !== 'agency_admin';
}

function creditlab_can_view_documents(): bool
{
    return creditlab_staff_role() !== 'agency_admin';
}

function creditlab_mask_sensitive_id(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    $len = strlen($value);
    if ($len <= 4) {
        return str_repeat('X', $len);
    }
    return str_repeat('X', $len - 4) . substr($value, -4);
}

function creditlab_can_create_pg_link(): bool
{
    $role = creditlab_staff_role();
    return in_array($role, ['admin', 'account_manager', 'agency_admin'], true);
}

/** Master admin: internal staff in `user` with active=2 (admin portal). */
function creditlab_is_master_admin(): bool
{
    global $admin;
    return !empty($admin);
}

/** Full account-manager CSV export is master-admin only. */
function creditlab_can_download_account_manager_data(): bool
{
    return creditlab_is_master_admin();
}

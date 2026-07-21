<?php
/**
 * Shared authentication helpers.
 */
require_once __DIR__ . '/env.php';

function creditlab_has_staff_session(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    foreach (['admin', 'account_manager', 'recovery_officer', 'verify_user', 'agency_admin'] as $key) {
        if (!empty($_SESSION[$key])) {
            return true;
        }
    }
    return false;
}

function creditlab_is_staff_logged_in(): bool
{
    global $admin, $account_manager, $recovery_officer, $verify_user, $agency_admin;
    if (!empty($admin) || !empty($account_manager) || !empty($recovery_officer) || !empty($verify_user) || !empty($agency_admin)) {
        return true;
    }
    return creditlab_has_staff_session();
}

function creditlab_require_staff(string $redirect = '/account/login.php'): void
{
    if (!creditlab_is_staff_logged_in()) {
        if (!headers_sent()) {
            http_response_code(403);
        }
        header('Location: ' . $redirect);
        exit;
    }
}

function creditlab_safe_internal_redirect(string $next, string $default): string
{
    $next = trim($next);
    if ($next === '' || $next[0] !== '/' || strpos($next, '//') !== false) {
        return $default;
    }
    return $next;
}

function creditlab_hash_password(string $plain): string
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

function creditlab_verify_password(string $plain, string $stored): bool
{
    if ($stored === '') {
        return false;
    }
    $info = password_get_info($stored);
    if (!empty($info['algo'])) {
        return password_verify($plain, $stored);
    }
    return hash_equals((string) $stored, (string) $plain);
}

function creditlab_set_auth_cookie(string $name, string $value, int $lifetimeSeconds = 2592000): void
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie($name, $value, [
        'expires' => time() + $lifetimeSeconds,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Allow fetching only same-site document URLs (blocks SSRF in zxc/index.php).
 */
function creditlab_is_allowed_document_url(string $url): bool
{
    $url = trim($url);
    if ($url === '') {
        return false;
    }
    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return false;
    }

    $host = strtolower($parts['host']);
    $allowedHosts = array_filter(array_unique([
        strtolower($_SERVER['HTTP_HOST'] ?? ''),
        'localhost',
        '127.0.0.1',
        'creditlab.in',
        'www.creditlab.in',
        'testing.creditlab.in',
    ]));

    if (!in_array($host, $allowedHosts, true)) {
        return false;
    }

    $path = $parts['path'] ?? '/';
    $allowedPrefixes = [
        '/no-due-certificate',
        '/no-due-certificate2.php',
        '/admin/',
        '/user/',
        '/account_manager/',
        '/recovery_officer/',
        '/agency_admin/',
        '/verify_user/',
        '/api/',
        '/zSMPLLOANAGREEMENTFINAL.php',
        '/key2.php',
        '/key2old.php',
    ];
    foreach ($allowedPrefixes as $prefix) {
        if (stripos($path, $prefix) === 0) {
            return true;
        }
    }

    return (bool) preg_match('#\.php$#i', $path);
}

function creditlab_internal_token(): string
{
    return env('ZXC_INTERNAL_TOKEN');
}

function creditlab_validate_internal_token(): bool
{
    $expected = creditlab_internal_token();
    if ($expected === '') {
        return false;
    }
    $provided = $_GET['token'] ?? $_POST['token'] ?? '';
    return $provided !== '' && hash_equals($expected, (string) $provided);
}

function creditlab_append_internal_token(string $url): string
{
    $token = creditlab_internal_token();
    if ($token === '') {
        return $url;
    }
    return $url . (strpos($url, '?') !== false ? '&' : '?') . 'token=' . rawurlencode($token);
}

/** Query suffix for server-to-server calls to /zxc/ */
function creditlab_zxc_access_query(): string
{
    $token = creditlab_internal_token();
    return $token === '' ? '' : '&token=' . rawurlencode($token);
}

function creditlab_get_logged_in_customer_id(): ?int
{
    global $user;
    if (empty($user)) {
        return null;
    }
    $mobile = towreal($user);
    $result = towquery("SELECT id FROM user WHERE mobile='$mobile' LIMIT 1");
    if ($result && townum($result) > 0) {
        $row = towfetch($result);
        return (int) $row['id'];
    }
    return null;
}

function creditlab_can_access_loan_apply(int $loanApplyId): bool
{
    if (creditlab_is_staff_logged_in() || creditlab_validate_internal_token()) {
        return true;
    }
    $customerId = creditlab_get_logged_in_customer_id();
    if ($customerId === null) {
        return false;
    }
    $loanApplyId = (int) $loanApplyId;
    $result = towquery("SELECT uid FROM loan_apply WHERE id = $loanApplyId LIMIT 1");
    if (!$result || townum($result) < 1) {
        return false;
    }
    $row = towfetch($result);
    return (int) $row['uid'] === $customerId;
}

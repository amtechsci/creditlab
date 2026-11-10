<?php
/**
 * Smoke-test all CreditLab portals on local PHP built-in server (e.g. :8000).
 * Usage: php scripts/local_portal_smoke_test.php [baseUrl]
 */
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$password = 'LocalTest123!';
$cookieJar = tempnam(sys_get_temp_dir(), 'cl_smoke_');

$roles = [
    'admin' => [
        'login' => false,
        'path' => '/admin/index.php',
        'expect' => ['modern_admin.css', 'cl-sidebar'],
    ],
    'account_manager' => [
        'email' => 'srikanth@creditlab.in',
        'path' => '/account_manager/index.php',
        'session' => 'account_manager',
        'expect' => ['modern_admin.css', 'cl-top-header-container'],
    ],
    'verify_user' => [
        'email' => 'NBFC@creditlab.in',
        'path' => '/verify_user/index.php',
        'session' => 'verify_user',
        'expect' => ['modern_admin.css', 'cl-top-header-container'],
    ],
    'agency_admin' => [
        'email' => 'Fairdebt@creditlab.com',
        'path' => '/agency_admin/index.php',
        'session' => 'agency_admin',
        'expect' => ['modern_admin.css', 'cl-top-header-container'],
    ],
    'recovery_officer' => [
        'email' => 'local.recovery@test.local',
        'path' => '/recovery_officer/index.php',
        'session' => 'recovery_officer',
        'expect' => ['modern_admin.css', 'cl-top-header-container'],
    ],
    'end_user' => [
        'email' => 'ramcharanrao@gmail.com',
        'path' => '/user/index.php',
        'session' => null,
        'expect' => ['dashboard'],
    ],
];

function http_request(string $url, string $method = 'GET', array $post = [], string $cookieJar = ''): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_TIMEOUT => 60,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    if ($raw === false) {
        return ['code' => 0, 'body' => '', 'headers' => '', 'error' => $err];
    }
    $headerSize = strpos($raw, "\r\n\r\n");
    $headers = $headerSize !== false ? substr($raw, 0, $headerSize) : '';
    $body = $headerSize !== false ? substr($raw, $headerSize + 4) : $raw;
    return ['code' => $code, 'body' => $body, 'headers' => $headers, 'error' => ''];
}

function login(string $base, string $email, string $password, string $cookieJar): bool
{
    $res = http_request($base . '/account/login.php', 'POST', [
        'email' => $email,
        'password' => $password,
    ], $cookieJar);
    if ($res['code'] >= 300 && $res['code'] < 400 && stripos($res['headers'], 'location:') !== false) {
        return true;
    }
  // Cookie may be set even on 200 in edge cases
    return stripos($res['headers'], 'PHPSESSID') !== false || stripos($res['headers'], 'creditlab_auth') !== false;
}

$results = [];
foreach ($roles as $role => $cfg) {
    if (!empty($cfg['login']) || $role === 'admin') {
        if ($role !== 'admin') {
            @unlink($cookieJar);
            touch($cookieJar);
            if (!login($base, $cfg['email'], $password, $cookieJar)) {
                $results[$role] = ['ok' => false, 'detail' => 'login failed for ' . $cfg['email']];
                continue;
            }
        }
    } else {
        @unlink($cookieJar);
        touch($cookieJar);
        if (!login($base, $cfg['email'], $password, $cookieJar)) {
            $results[$role] = ['ok' => false, 'detail' => 'login failed for ' . $cfg['email']];
            continue;
        }
    }

    $res = http_request($base . $cfg['path'], 'GET', [], $cookieJar);
    if ($res['code'] === 302 || $res['code'] === 301) {
        if (preg_match('/^location:\s*(\S+)/mi', $res['headers'], $m)) {
            $res = http_request(trim($m[1]), 'GET', [], $cookieJar);
        }
    }

    $found = [];
    foreach ($cfg['expect'] as $needle) {
        $found[$needle] = stripos($res['body'], $needle) !== false;
    }
    $ok = $res['code'] === 200 && !in_array(false, $found, true);
    $missing = array_keys(array_filter($found, static fn ($v) => !$v));
    $results[$role] = [
        'ok' => $ok,
        'http' => $res['code'],
        'detail' => $ok ? 'ok' : ('http ' . $res['code'] . ( $missing ? '; missing: ' . implode(', ', $missing) : '')),
    ];
}

@unlink($cookieJar);

echo "Base URL: {$base}\n";
echo str_repeat('-', 60) . "\n";
$pass = 0;
foreach ($results as $role => $r) {
    $status = $r['ok'] ? 'PASS' : 'FAIL';
    if ($r['ok']) {
        $pass++;
    }
    echo sprintf("%-18s %s  %s\n", $role, $status, $r['detail'] . (isset($r['http']) ? '' : ''));
}
echo str_repeat('-', 60) . "\n";
echo count($results) . ' roles, ' . $pass . " passed\n";
exit($pass === count($results) ? 0 : 1);

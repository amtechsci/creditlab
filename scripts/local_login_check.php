<?php
/**
 * Login + landing page checks for every staff/customer role.
 * Usage: php scripts/local_login_check.php [baseUrl]
 */
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$password = 'LocalTest123!';
$cookieJar = sys_get_temp_dir() . '/cl_login_check_' . getmypid() . '.txt';

$cases = [
    'admin' => ['login' => false, 'email' => 'admin@creditlab.in', 'paths' => ['/admin/index.php']],
    'account_manager' => ['email' => 'srikanth@creditlab.in', 'paths' => ['/account_manager/index.php', '/account_manager/profile.php?id=50837&tab=Personal']],
    'verify_user' => ['email' => 'NBFC@creditlab.in', 'paths' => ['/verify_user/index.php']],
    'agency_admin' => ['email' => 'Fairdebt@creditlab.com', 'paths' => ['/agency_admin/index.php', '/agency_admin/profile.php?id=50837&tab=Personal']],
    'recovery_officer' => ['email' => 'local.recovery@test.local', 'paths' => ['/recovery_officer/index.php']],
    'end_user' => ['email' => 'ramcharanrao@gmail.com', 'paths' => ['/user/index.php']],
];

function req(string $url, string $jar, string $method = 'GET', array $post = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 90,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    return ['code' => $code, 'body' => $body === false ? '' : $body, 'url' => $final];
}

function login(string $base, string $email, string $pass, string $jar): bool
{
  @unlink($jar);
    touch($jar);
    $r = req($base . '/account/login.php', $jar, 'POST', ['email' => $email, 'password' => $pass]);
    return $r['code'] === 200 && (strpos($r['url'], '/account/login') === false || strpos($r['body'], 'Sign in to start') === false);
}

echo "Base: $base\n" . str_repeat('-', 72) . "\n";
$allOk = true;
foreach ($cases as $role => $cfg) {
    @unlink($cookieJar);
    if (!empty($cfg['login']) || $role !== 'admin') {
        if ($role !== 'admin' && !login($base, $cfg['email'], $password, $cookieJar)) {
            echo sprintf("%-18s FAIL  login failed (%s)\n", $role, $cfg['email']);
            $allOk = false;
            continue;
        }
    } else {
        touch($cookieJar);
    }
    $roleOk = true;
    $notes = [];
    foreach ($cfg['paths'] as $path) {
        $r = req($base . $path, $cookieJar);
        if ($r['code'] !== 200) {
            $roleOk = false;
            $notes[] = basename($path) . " HTTP {$r['code']}";
            continue;
        }
        if (stripos($r['body'], 'Fatal error') !== false || stripos($r['body'], 'Parse error') !== false) {
            $roleOk = false;
            $notes[] = basename($path) . ' PHP error';
        }
        if (stripos($r['url'], 'login.php') !== false) {
            $roleOk = false;
            $notes[] = basename($path) . ' bounced to login';
        }
        if ($role === 'agency_admin' && strpos($path, 'profile') !== false) {
            if (stripos($r['body'], 'View only — contact and employment') === false) {
                $roleOk = false;
                $notes[] = 'agency profile not restricted';
            }
            if (stripos($r['body'], 'name="email"') !== false && stripos($r['body'], 'View only') === false) {
                $roleOk = false;
                $notes[] = 'editable email field exposed';
            }
        }
        if (in_array($role, ['account_manager', 'verify_user', 'recovery_officer'], true) && strpos($path, 'index') !== false) {
            if (stripos($r['body'], 'modern_admin.css') === false) {
                $roleOk = false;
                $notes[] = 'missing modern staff CSS';
            }
        }
    }
    if ($roleOk) {
        echo sprintf("%-18s PASS  %s\n", $role, implode(', ', $cfg['paths']));
    } else {
        echo sprintf("%-18s FAIL  %s\n", $role, implode('; ', $notes));
        $allOk = false;
    }
}
echo str_repeat('-', 72) . "\n";
echo $allOk ? "All logins OK\n" : "Some checks failed\n";
@unlink($cookieJar);
exit($allOk ? 0 : 1);

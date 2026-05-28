<?php
include_once 'head.php';

function server_health_tail_file(string $path, int $lines = 80): string
{
	if (!is_readable($path)) {
		return "(not readable: $path)";
	}
	$content = @file($path, FILE_IGNORE_NEW_LINES);
	if ($content === false) {
		return "(failed to read: $path)";
	}
	return implode("\n", array_slice($content, -$lines));
}

function server_health_try_load(string $path): string
{
	return is_readable($path) ? server_health_tail_file($path, 80) : '';
}

$dbStart = microtime(true);
$dbOk = isset($db) && @mysqli_ping($db);
$dbMs = round((microtime(true) - $dbStart) * 1000, 2);

$pendingLoans = 0;
$activeUsers = 0;
if ($dbOk) {
	$r = towfetch(towquery("SELECT COUNT(*) AS c FROM loan_apply WHERE status IN ('pending','follow up')"));
	$pendingLoans = (int) ($r['c'] ?? 0);
	$r = towfetch(towquery("SELECT COUNT(*) AS c FROM user WHERE active=1"));
	$activeUsers = (int) ($r['c'] ?? 0);
}

$logCandidates = [
	'Nginx error' => '/var/log/nginx/error.log',
	'PHP-FPM error' => '/var/log/php8.3-fpm.log',
	'PHP-FPM alt' => '/var/log/php-fpm/error.log',
	'PHP slow log' => '/var/log/php8.3-fpm-slow.log',
	'MariaDB error' => '/var/log/mysql/error.log',
	'Syslog' => '/var/log/syslog',
];

$logSections = [];
foreach ($logCandidates as $label => $path) {
	$text = server_health_try_load($path);
	if ($text !== '') {
		$logSections[$label . " ($path)"] = $text;
	}
}

$load = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
?>
<!DOCTYPE html>
<html>
<head><title>Server Health - Admin</title></head>
<body>
<?php include_once 'Left_menu.php'; ?>
<?php include_once 'welcome.php'; ?>
<div class="all-content-wrapper">
<div class="container-fluid">
<div class="row"><div class="col-lg-12">
<div class="breadcome-list"><h2>Server Health &amp; 504 Diagnostics</h2>
<p>Use this page when users report timeouts. A 504 means Nginx gave up waiting for PHP-FPM — workers are usually blocked or the pool is exhausted.</p>
</div></div></div>

<div class="row"><div class="col-lg-6">
<div class="card" style="padding:16px;margin-bottom:16px;">
<h4>PHP runtime</h4>
<ul>
<li><strong>PHP version:</strong> <?= htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?></li>
<li><strong>max_execution_time:</strong> <?= (int) ini_get('max_execution_time') ?>s</li>
<li><strong>memory_limit:</strong> <?= htmlspecialchars(ini_get('memory_limit'), ENT_QUOTES, 'UTF-8') ?></li>
<li><strong>pm.max_children:</strong> <?= htmlspecialchars(ini_get('pm.max_children') ?: 'n/a (check pool conf)', ENT_QUOTES, 'UTF-8') ?></li>
<li><strong>fastcgi_finish_request:</strong> <?= function_exists('fastcgi_finish_request') ? 'yes' : 'no' ?></li>
</ul>
</div>
</div>
<div class="col-lg-6">
<div class="card" style="padding:16px;margin-bottom:16px;">
<h4>Load &amp; database</h4>
<ul>
<li><strong>Load average:</strong> <?= $load ? implode(' / ', array_map('number_format', $load)) : 'unavailable' ?></li>
<li><strong>DB ping:</strong> <?= $dbOk ? "OK ({$dbMs} ms)" : 'FAILED' ?></li>
<li><strong>Pending/follow-up loans:</strong> <?= $pendingLoans ?></li>
<li><strong>Active users:</strong> <?= $activeUsers ?></li>
<li><strong>Stale sweep lock:</strong> <?= is_file(sys_get_temp_dir() . '/creditlab_stale_loan_sweep.lock') ? date('Y-m-d H:i:s', filemtime(sys_get_temp_dir() . '/creditlab_stale_loan_sweep.lock')) : 'never' ?></li>
</ul>
</div>
</div></div>

<div class="row"><div class="col-lg-12">
<div class="card" style="padding:16px;margin-bottom:16px;">
<h4>Common 504 causes in this app</h4>
<ol>
<li><strong>PHP-FPM pool full</strong> — all workers busy on slow requests (SMS, PDF mail, heavy admin pages).</li>
<li><strong>Upstream timeout</strong> — Nginx <code>fastcgi_read_timeout</code> shorter than PHP work time.</li>
<li><strong>Row locks</strong> — staff <code>head.php</code> doing mass UPDATEs (now throttled to 1×/hour).</li>
<li><strong>Blocking HTTP</strong> — <code>file_get_contents</code> to <code>/zxc/</code> waiting for PDF+SMTP (now 3s trigger).</li>
</ol>
<h4>SSH commands (run on production)</h4>
<pre style="background:#f5f5f5;padding:12px;overflow:auto;"># 504 errors with upstream timed out
sudo tail -100 /var/log/nginx/error.log | grep -E '504|upstream timed out|connect\(\) failed'

# PHP-FPM pool status (if pm.status_path enabled)
curl -s http://127.0.0.1/status?full | head -40

# Active / stuck PHP workers
ps aux | grep 'php-fpm: pool' | wc -l
sudo tail -50 /var/log/php8.3-fpm-slow.log

# MariaDB locks
sudo mysql -e "SHOW FULL PROCESSLIST;"
sudo mysql -e "SHOW ENGINE INNODB STATUS\G" | grep -A30 'LATEST DETECTED DEADLOCK'

# Live load
uptime && free -h</pre>
</div>
</div></div>

<?php if (empty($logSections)): ?>
<div class="row"><div class="col-lg-12">
<div class="alert alert-warning">Could not read server logs from PHP (permission denied). Use SSH commands above on the production server.</div>
</div></div>
<?php else: ?>
<?php foreach ($logSections as $title => $text): ?>
<div class="row"><div class="col-lg-12">
<div class="card" style="padding:16px;margin-bottom:16px;">
<h4><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> <small>(last 80 lines)</small></h4>
<pre style="background:#111;color:#eee;padding:12px;max-height:400px;overflow:auto;font-size:11px;"><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></pre>
</div>
</div></div>
<?php endforeach; ?>
<?php endif; ?>

</div></div>
<?php include_once 'foot.php'; ?>
</body>
</html>

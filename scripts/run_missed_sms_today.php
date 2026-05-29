<?php
/**
 * Replay today's SMS cron time windows (for runs missed by hourly :00 cron).
 *
 *   php scripts/run_missed_sms_today.php --list
 *   php scripts/run_missed_sms_today.php --dry-run          # only windows already passed today (IST)
 *   php scripts/run_missed_sms_today.php --yes                # send for passed windows
 *   php scripts/run_missed_sms_today.php --yes --all        # all windows (including future today)
 *
 * On server:
 *   sudo -u www-data php /var/www/creditlab.in/scripts/run_missed_sms_today.php --yes
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

date_default_timezone_set('Asia/Kolkata');

/** Midpoint inside each distinct zzautosms_complete.php window */
$windows = [
	['time' => '08:02', 'label' => '08:00 — initial / salary / limit / legal'],
	['time' => '08:32', 'label' => '08:30 — DPD 1-5, DPD 6-10'],
	['time' => '09:02', 'label' => '09:00 — reminders'],
	['time' => '10:02', 'label' => '10:00 — pre-due'],
	['time' => '11:47', 'label' => '11:45 — CIBIL drop, DPD 11-15'],
	['time' => '12:52', 'label' => '12:50 — limit increase'],
	['time' => '13:32', 'label' => '13:30 — commitment'],
	['time' => '13:47', 'label' => '13:45 — salary reminder'],
	['time' => '14:02', 'label' => '14:00 — preclose / due'],
	['time' => '14:12', 'label' => '14:10 — were to pay'],
	['time' => '14:37', 'label' => '14:35 — legal / attention'],
	['time' => '14:47', 'label' => '14:45 — field recovery'],
	['time' => '15:02', 'label' => '15:00 — final alert / legal'],
	['time' => '16:02', 'label' => '16:00 — overdue / limit'],
	['time' => '16:32', 'label' => '16:30 — initial reminder'],
	['time' => '16:37', 'label' => '16:35 — DPD 1-5 PM'],
	['time' => '17:02', 'label' => '17:00 — due reminder'],
	['time' => '18:02', 'label' => '18:00 — DPD 6-10'],
	['time' => '18:32', 'label' => '18:30 — preclose PM'],
	['time' => '18:37', 'label' => '18:35 — DPD 11-15 PM'],
	['time' => '19:32', 'label' => '19:30 — CIBIL dip'],
	['time' => '19:37', 'label' => '19:35 — legal suit'],
	['time' => '20:02', 'label' => '20:00 — due date missed'],
];

$root = dirname(__DIR__);
$cronScript = $root . '/zzautosms_complete.php';
$listOnly = in_array('--list', $argv, true);
$dryRun = in_array('--dry-run', $argv, true) || (!$listOnly && !in_array('--yes', $argv, true));
$send = in_array('--yes', $argv, true);
$allWindows = in_array('--all', $argv, true);
$delay = 3;
foreach ($argv as $arg) {
	if (preg_match('/^--delay=(\d+)$/', $arg, $m)) {
		$delay = max(0, (int) $m[1]);
	}
}

if (!is_readable($cronScript)) {
	fwrite(STDERR, "Missing $cronScript\n");
	exit(1);
}

$now = date('H:i');
$toRun = [];
foreach ($windows as $w) {
	if ($allWindows || $w['time'] <= $now) {
		$toRun[] = $w;
	}
}

echo 'IST now: ' . date('Y-m-d H:i:s') . "\n";
echo 'Windows to replay: ' . count($toRun) . ' of ' . count($windows) . "\n";
echo 'Mode: ' . ($listOnly ? 'list' : ($send ? 'SEND' : 'dry-run')) . "\n\n";

if ($listOnly) {
	foreach ($toRun as $i => $w) {
		echo sprintf("%2d. %s  %s\n", $i + 1, $w['time'], $w['label']);
	}
	if (!$allWindows && count($toRun) < count($windows)) {
		echo "\nFuture today (skipped unless --all):\n";
		foreach ($windows as $w) {
			if ($w['time'] > $now) {
				echo "    {$w['time']}  {$w['label']}\n";
			}
		}
	}
	exit(0);
}

$phpBin = is_executable('/usr/bin/php') ? '/usr/bin/php' : 'php';
$logFile = $root . '/logs/missed_sms_replay_' . date('Y-m-d_His') . '.log';
@mkdir($root . '/logs', 0755, true);

foreach ($toRun as $i => $w) {
	$n = $i + 1;
	$line = "[$n/" . count($toRun) . "] {$w['time']} {$w['label']}";
	echo $line . "\n";
	file_put_contents($logFile, $line . "\n", FILE_APPEND);

	$arg = 'time=' . $w['time'];
	if ($dryRun) {
		$arg .= '&dry_run=1';
	}

	$cmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($cronScript) . ' ' . escapeshellarg($arg);
	$cmd = 'cd ' . escapeshellarg($root) . ' && ' . $cmd . ' 2>&1';
	$out = shell_exec($cmd);
	$snippet = trim(preg_replace('/\s+/', ' ', (string) $out));
	if (strlen($snippet) > 400) {
		$snippet = substr($snippet, -400);
	}
	echo "    " . ($snippet !== '' ? $snippet : '(no output)') . "\n\n";
	file_put_contents($logFile, $snippet . "\n\n", FILE_APPEND);

	if ($delay > 0 && $n < count($toRun)) {
		sleep($delay);
	}
}

echo "Finished. Log: $logFile\n";
echo "Detail log: {$root}/logs/sms_cron_" . date('Y-m-d') . ".log\n";
if ($dryRun) {
	echo "Dry-run only. Re-run with --yes to send real SMS.\n";
}

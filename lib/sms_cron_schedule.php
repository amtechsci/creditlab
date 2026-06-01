<?php
/**
 * Full IST schedule for zzautosms_complete.php (cron SMS).
 * CLI: php zzautosms_complete.php --schedule
 *      php scripts/print_sms_schedule.php
 */
function creditlab_sms_cron_schedule(): array
{
	return [
		['id' => 0, 'name' => 'daily_cleanup', 'windows' => ['00:00-00:04'], 'when' => 'DB cleanup only (no customer SMS)'],
		['id' => 1, 'name' => 'cibil_drop_alert', 'windows' => ['11:45-11:49'], 'when' => 'Days to due 0-4 OR DPD = 0'],
		['id' => 2, 'name' => 'dpd_1_5', 'windows' => ['08:30-08:34', '16:35-16:39'], 'when' => 'DPD 1-5'],
		['id' => 3, 'name' => 'dpd_6_10', 'windows' => ['08:30-08:34', '18:00-18:04'], 'when' => 'DPD 6-10'],
		['id' => 4, 'name' => 'dpd_11_15', 'windows' => ['08:00-08:04', '11:45-11:49', '18:35-18:39'], 'when' => 'DPD 10-15'],
		['id' => 5, 'name' => '5_days_before_reminder', 'windows' => ['10:00-10:04', '17:00-17:04'], 'when' => 'Exactly 5 days before due'],
		['id' => 6, 'name' => 'initial_reminder', 'windows' => ['09:00-09:04', '16:30-16:34'], 'when' => 'Days to due 0-4'],
		['id' => 7, 'name' => 'preclose', 'windows' => ['08:00-08:04', '16:00-16:04'], 'when' => 'Loan day (tday) = 10, 15, 20, or 25'],
		['id' => 8, 'name' => 'salary_day_active', 'windows' => ['14:00-14:04', '18:30-18:34', '20:00-20:04'], 'when' => 'Before due + today = user salary_date'],
		['id' => 9, 'name' => '45th_day_reminder', 'windows' => ['15:00-15:04'], 'when' => 'DPD 15-30'],
		['id' => 10, 'name' => 'field_recovery', 'windows' => ['14:35-14:39'], 'when' => 'DPD 35-40'],
		['id' => 11, 'name' => 'legal_notice', 'windows' => ['19:35-19:39'], 'when' => 'DPD 16-30'],
		['id' => 12, 'name' => 'final_alert', 'windows' => ['14:35-14:39'], 'when' => 'DPD 16-30'],
		['id' => 13, 'name' => 'cibil_dip', 'windows' => ['15:00-15:04'], 'when' => 'DPD 15-30'],
		['id' => 14, 'name' => 'legal_suit', 'windows' => ['16:00-16:04'], 'when' => 'DPD 31-44'],
		['id' => 15, 'name' => 'written_off', 'windows' => ['19:30-19:34'], 'when' => 'DPD 46, 59, 69, or 89'],
		['id' => 16, 'name' => 'waive_off', 'windows' => ['14:10-14:14'], 'when' => 'DPD 46, 50, or 59'],
		['id' => 17, 'name' => 'attention', 'windows' => ['14:45-14:49'], 'when' => 'DPD 15-30'],
		['id' => 18, 'name' => 'were_to_pay', 'windows' => ['13:30-13:34'], 'when' => 'DPD 6-15'],
		['id' => 19, 'name' => 'due_date_missed', 'windows' => ['13:45-13:49'], 'when' => 'DPD 1-5'],
		['id' => 20, 'name' => 'enach_will_not_happen', 'windows' => ['14:00-14:04'], 'when' => 'DPD 0 or 1'],
		['id' => 21, 'name' => 'salary_date_overdue', 'windows' => ['16:00-16:04'], 'when' => 'DPD >= 0 + today = salary_date'],
		['id' => 22, 'name' => 'limit_increase', 'windows' => ['08:00-08:04', '12:50-12:54', '16:00-16:04'], 'when' => 'loan_limit > loan total_amount'],
	];
}

/** All unique 5-minute fire times today (IST), sorted */
function creditlab_sms_cron_all_time_slots(): array
{
	$slots = [];
	foreach (creditlab_sms_cron_schedule() as $row) {
		foreach ($row['windows'] as $w) {
			$slots[$w] = true;
		}
	}
	$keys = array_keys($slots);
	usort($keys, function ($a, $b) {
		return strcmp(substr($a, 0, 5), substr($b, 0, 5));
	});
	return $keys;
}

function creditlab_print_sms_cron_schedule(): void
{
	$schedule = creditlab_sms_cron_schedule();
	echo "CreditLab cron SMS schedule (IST) — zzautosms_complete.php\n";
	echo "Loans: status = account manager only. Cron must run during each 5-min window (e.g. every 1–3 min).\n";
	echo str_repeat('=', 72) . "\n\n";

	echo "BY TEMPLATE (# = rule id in code)\n";
	echo str_repeat('-', 72) . "\n";
	printf("%-4s %-28s %-32s %s\n", '#', 'Template', 'IST window(s)', 'Sends when');
	echo str_repeat('-', 72) . "\n";
	foreach ($schedule as $row) {
		printf(
			"%2d   %-28s %-32s %s\n",
			$row['id'],
			$row['name'],
			implode(', ', $row['windows']),
			$row['when']
		);
	}

	echo "\n\nBY TIME (all slots — fire cron during these 5 minutes)\n";
	echo str_repeat('-', 72) . "\n";
	$byTime = [];
	foreach ($schedule as $row) {
		if ($row['id'] === 0) {
			continue;
		}
		foreach ($row['windows'] as $w) {
			$byTime[$w][] = $row['name'];
		}
	}
	ksort($byTime);
	foreach ($byTime as $window => $names) {
		echo $window . '  →  ' . implode(', ', $names) . "\n";
	}

	echo "\n\nCHRONOLOGICAL (unique minutes to hit with */1 or */3 cron)\n";
	echo str_repeat('-', 72) . "\n";
	$seen = [];
	foreach ($byTime as $window => $names) {
		$start = substr($window, 0, 5);
		if (!isset($seen[$start])) {
			$seen[$start] = $names;
		} else {
			$seen[$start] = array_merge($seen[$start], $names);
		}
	}
	ksort($seen);
	foreach ($seen as $time => $names) {
		echo $time . "  →  " . implode(', ', array_unique($names)) . "\n";
	}

	echo "\nTotal templates: " . (count($schedule) - 1) . " (+ midnight cleanup)\n";
	echo "Total distinct windows: " . count($byTime) . "\n";
}

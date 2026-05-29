<?php
/**
 * Test all CreditLab SMS templates on one mobile (smswala + optional k7).
 *
 *   php scripts/test_all_sms.php --list
 *   php scripts/test_all_sms.php 8800899875 --group=app --yes
 *   php scripts/test_all_sms.php 8800899875 --group=cron --yes
 *   php scripts/test_all_sms.php 8800899875 --group=all --yes
 *   php scripts/test_all_sms.php 8800899875 --group=k7 --yes
 *
 * Without --yes: dry-run (prints only). Add --delay=2 between sends (default 2).
 */
if (php_sapi_name() !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

require_once dirname(__DIR__) . '/lib/env.php';
require_once dirname(__DIR__) . '/config/sms.php';

/** Same mapping as send_sms.php */
function creditlab_sms_map_template_id(string $template_id): string
{
	$map = [
		'1107165683325768963' => '1407175016297384512',
		'1107169454135117024' => '1407175016260362259',
		'1107165683340185966' => '1407175016944191447',
		'1107165683293779914' => '1407175015930870249',
		'1107169453425832956' => '1407175190737426693',
		'1107165683279440796' => '1407175016362205820',
		'reference_message' => '1407175291100618275',
		'1407175291100618275' => '1407175291100618275',
	];
	return $map[$template_id] ?? $template_id;
}

function creditlab_sms_send_smswala(string $mobile, string $message, string $template_id): array
{
	$final = creditlab_sms_map_template_id($template_id);
	$url = 'https://sms.smswala.in/app/smsapi/index.php?key=' . urlencode(SMS_API_KEY)
		. '&campaign=16613&routeid=30&type=text&contacts=' . $mobile
		. '&senderid=CREDLB&msg=' . urlencode($message)
		. '&template_id=' . urlencode($final);

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT => 20,
	]);
	$body = curl_exec($ch);
	$err = curl_error($ch);
	$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	$ok = $err === '' && is_string($body)
		&& (stripos($body, 'SMS-SHOOT-ID') !== false || stripos($body, 'success') !== false)
		&& stripos($body, 'NOT sent') === false;

	return [
		'ok' => $ok,
		'http' => $http,
		'curl' => $err,
		'body' => is_string($body) ? trim($body) : '',
		'mapped_template' => $final,
		'gateway' => 'smswala',
	];
}

function creditlab_sms_send_k7(string $mobile, string $message, string $template_id): array
{
	$url = 'https://sms.k7marketinghub.com/app/smsapi/index.php?key=' . urlencode(SMS_API_KEY)
		. '&campaign=16613&routeid=30&type=text&contacts=' . $mobile
		. '&senderid=CREDLB&msg=' . urlencode($message)
		. '&template_id=' . urlencode($template_id)
		. '&pe_id=1401337620000065797';

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT => 20,
	]);
	$body = curl_exec($ch);
	$err = curl_error($ch);
	$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	$ok = $err === '' && is_string($body) && stripos($body, 'NOT sent') === false && $http === 200;

	return [
		'ok' => $ok,
		'http' => $http,
		'curl' => $err,
		'body' => is_string($body) ? trim($body) : '',
		'mapped_template' => $template_id,
		'gateway' => 'k7',
	];
}

function creditlab_sms_test_cases(): array
{
	$base = 'https://creditlab.in/';
	$name = 'Test User';
	$first = 'Test';
	$lid = '99999';
	$otp = (string) random_int(1000, 9999);
	$amt = '5000';
	$date = date('d-m-Y');

	$app = [
		['group' => 'app', 'key' => 'otp_login', 'template_id' => '1407174844163241940', 'message' => "$otp is OTP for Creditlab login verification & valid till 2min. Don't share this OTP with anyone."],
		['group' => 'app', 'key' => 'otp_resend', 'template_id' => '1407175016441663626', 'message' => $otp . ' is the OTP for your Mobile verification. This is usable once and valid for 2mins from the request. PLS DON"T SHARE WITH ANYONE -CREDITLAB'],
		['group' => 'app', 'key' => 'loan_cleared', 'template_id' => '1107165683325768963', 'message' => "Dear $name, we acknowledge the repayment of your loan CLL$lid & it's cleared. You can apply again. $base -Creditlab"],
		['group' => 'app', 'key' => 'part_payment', 'template_id' => '1107169454135117024', 'message' => "We got a part payment of Rs $amt w.r.t your Creditlab.in loan CLL$lid. Pay the balance to close the loan. Discuss with your RM & settle immediately."],
		['group' => 'app', 'key' => 'account_manager', 'template_id' => '1107165683340185966', 'message' => "Dear $name, your creditlab Account Manager is RM Name 9999999999. Reach out for any info"],
		['group' => 'app', 'key' => 'accept_agreement', 'template_id' => '1107165683293779914', 'message' => "Dear $name, please accept your loan agreement for CLL$lid at $base -Creditlab"],
		['group' => 'app', 'key' => 'bank_linked', 'template_id' => '1107165683279440796', 'message' => "Dear $name, Your bank account is now associated with us. Name : Canara Bank, No: 110302382889. -Creditlab"],
		['group' => 'app', 'key' => 'kyc_pending', 'template_id' => '1107169453425832956', 'message' => "Dear $name, your KYC is pending on Creditlab.in. Complete at $base"],
		['group' => 'app', 'key' => 'reference', 'template_id' => '1407175291100618275', 'message' => "Dear $name, reference message test from Creditlab.in $base"],
		['group' => 'app', 'key' => 'autodebit_bounce', 'template_id' => '1407175016580415506', 'message' => "Auto-debit of Creditlab.in loan of Rs. $amt got bounced due to insufficient funds. Close it now $base to avoid further debits/bounce charges & legal action"],
	];

	$cronDefs = [
		'cibil_drop_alert' => ['Act Now %s ! Your latest Creditlab loan is reported to 4 CIBIL bureaus. Pay on time to avoid CIBIL score drop : %s', [$first, $base]],
		'dpd_1_5' => ['URGENT %s ! Your Creditlab loan is OVERDUE. Pay immediately to avoid Penalty & severe CIBIL impact : %s', [$first, $base]],
		'dpd_6_10' => ['ATTENTION %s ! Your Creditlab loan is still OVERDUE. Recovery proceedings & CIBIL impact begin. Clear now to stop further action : %s', [$first, $base]],
		'dpd_11_15' => ['FINAL WARNING %s ! Your Creditlab.in loan remains OVERDUE. Legal, RECOVERY & CIBIL DAMAGE imminent. Settle dues TODAY to avoid escalation.', [$first]],
		'initial_reminder' => ["Dear Creditlab.in user, It's %s day reminder to repay your loan before due date. Doing so will grow Trust Score & increase your CIBIL, Experian & CRIF scores.", [3]],
		'preclose' => ["Dear creditlab.in user, It's been%sdays! Pre-close ur loan now, save %s interest & boost your CIBIL score. Act now : %s", [5, '100', $base]],
		'salary_day' => ['Dear Creditlab.in user, Clear the loan on salary day & reapply. It aligns ur repayment with ur salary day for smooth cycle from next loan %s', [$base]],
		'45th_day_reminder' => ['Dear %s, you have a pending loan with Creditlab.in, Repay it immediately. Failure leads to DEFAULT/OVERDUE in CIBIL & to Debt collection agency.', [$first]],
		'field_recovery' => ['Dear %s, your Creditlab.in loan %s, is now moved for Field Recovery. Our Field Recovery agent will visit your home & office addresses anytime in the next 6 to 10 days. Incase you choose to settle/close it before physical recovery visit, please contact %s', [$first, "CLL$lid", 'support@creditlab.in']],
		'legal_notice' => ["LEGAL NOTICE !!! It's a follow-up reminder to close your Overdued Creditlab.in loan immediately to avoid further Legal consequences.", []],
		'final_alert' => ['FINAL WARNING ! ! All Creditlab overdue loans will be reported as "Default" to CIBIL/CRIF/EXPERIAN/CRIF. Clear now %s', [$base]],
		'cibil_dip' => ['Hello %s, your creditlab.in loan is OVERDUE & reported as DEFAULTER to CIBIL. Your score will drop 50-100 points. Avoid damage, pay now: %s', [$first, $base]],
		'legal_suit' => ['FINAL WARNING : %s Creditlab.in loan in DEFAULT. Legal suit being filed. This is your last chance to settle & close loan %s', [$first, $base]],
		'written_off' => ['Hey %s, your creditlab.in loan reported to CIBIL as written-off & default which affects all future loans. Repay the Principal to cancel this.', [$first]],
		'waive_off' => ['Hey Creditlab.in user, 100% penalty waived off for a limited period ! Close your pending loan & remove your CIBIL defaulter tag. Contact support@creditlab.in', []],
		'attention' => ['Attention! %s, your creditlab.in loan is unpaid despite reminders. LEGAL actions initiated. If incorrect, contact us at support@creditlab.in', [$first]],
		'were_to_pay' => ["Alert! Hey Creditlab.in user, You were to pay Rs %s for your loan. It's overdue! Pay now to avoid CIBIL & recovery complications : %s", [$amt, $base]],
		'due_date_missed' => ['Alert ! ! Your Creditlab.in loan DUE DATE is Crossed. Close now to avoid Reminder calls, EXTRA PENALTY & Late Payment reporting to CIBIL : %s', [$base]],
		'enach_reminder' => ['Hi ! Your Creditlab.in loan of Rs. %s will auto-debit on %s. Ensure sufficient balance to avoid chq bounce & legal action under Section 138 N.I. Act', [$amt, $date]],
		'enach_will_not_happen' => ['Repay your Creditlab.in loan directly through the dashboard %s .If you repay now before any further extension/default, auto-debit will not occur.', [$base]],
		'autodebit_bounce' => ['Auto-debit of Creditlab.in loan of Rs. %s got bounced due to insufficient funds. Close it now %s to avoid further debits/bounce charges & legal action', [$amt, $base]],
		'commitment_day_reminder' => ['As per the commitment given to your Relationship Manager, we urge you to repay today the due amount of Rs %s through this link: %s -Creditlab', [$amt, $base]],
		'commit_to_pay_reminder' => ['Reminder: You had committed to pay Rs %s to your Creditlab Account Manager. Pay & Reapply today immediately: %s', [$amt, $base]],
		'salary_date_reminder' => ['Dear %s, you must have received salary. Repay your Creditlab.in loan now. Failure leads to Penalty & reduce CIBIL/Experian/CRIF/EQUIFAX scores', [$first]],
		'limit_increase' => ['Dear Creditlab.in customer, your limit has been updated to Rs%s. Please log in to your account to withdraw: %s', ['25000', $base]],
	];

	$cronIds = [
		'cibil_drop_alert' => '1407175283747333288',
		'dpd_1_5' => '1407175283203362638',
		'dpd_6_10' => '1407175283363256063',
		'dpd_11_15' => '1407175283390827183',
		'initial_reminder' => '1407175016269681511',
		'preclose' => '1407175024263728707',
		'salary_day' => '1407175007069974553',
		'45th_day_reminder' => '1407175016251351187',
		'field_recovery' => '1407175016192466512',
		'legal_notice' => '1407175016047912195',
		'final_alert' => '1407175016080435385',
		'cibil_dip' => '1407175267110690531',
		'legal_suit' => '1407175267151421703',
		'written_off' => '1407175016041686176',
		'waive_off' => '1407175006859804198',
		'attention' => '1407175016862547934',
		'were_to_pay' => '1407175024235958869',
		'due_date_missed' => '1407175108833441096',
		'enach_reminder' => '1407175015994490488',
		'enach_will_not_happen' => '14071750161538869',
		'autodebit_bounce' => '1407175016580415506',
		'commitment_day_reminder' => '1407175016237946657',
		'commit_to_pay_reminder' => '1407175017002651513',
		'salary_date_reminder' => '1407175016247659901',
		'limit_increase' => '1407175198059581991',
	];

	$cron = [];
	foreach ($cronIds as $key => $tid) {
		[$fmt, $args] = $cronDefs[$key];
		$cron[] = [
			'group' => 'cron',
			'key' => $key,
			'template_id' => $tid,
			'message' => $args ? vsprintf($fmt, $args) : $fmt,
		];
	}

	$k7 = [
		['group' => 'k7', 'key' => 'enach_reminder_k7', 'template_id' => '1407175015994490488', 'message' => "Hi ! Your Creditlab.in loan of Rs. $amt will auto-debit on $date. Ensure sufficient balance to avoid chq bounce & legal action under Section 138 N.I. Act"],
		['group' => 'k7', 'key' => 'accept_agreement_k7', 'template_id' => '1407175015930870248', 'message' => "Dear $name, please accept your loan agreement for CLL$lid at $base -Creditlab"],
	];

	return array_merge($app, $cron, $k7);
}

// --- CLI ---
$listOnly = in_array('--list', $argv, true);
$send = in_array('--yes', $argv, true);
$group = $listOnly ? 'all' : 'app';
$only = [];
$delay = 2;
$mobile = '8800899875';

foreach ($argv as $arg) {
	if (preg_match('/^--group=(.+)$/', $arg, $m)) {
		$group = $m[1];
	}
	if (preg_match('/^--only=(.+)$/', $arg, $m)) {
		$only = array_map('trim', explode(',', $m[1]));
	}
	if (preg_match('/^--delay=(\d+)$/', $arg, $m)) {
		$delay = max(0, (int) $m[1]);
	}
}
foreach ($argv as $arg) {
	if (preg_match('/^[6-9]\d{9}$/', preg_replace('/\D/', '', $arg), $m)) {
		$mobile = $m[0];
		break;
	}
}

$cases = creditlab_sms_test_cases();

if (SMS_API_KEY === '' && !$listOnly) {
	fwrite(STDERR, "SMS_API_KEY missing in .env\n");
	exit(1);
}
$groups = $group === 'all' ? ['app', 'cron', 'k7'] : explode(',', $group);

$filtered = [];
foreach ($cases as $c) {
	if (!in_array($c['group'], $groups, true)) {
		continue;
	}
	if ($only !== [] && !in_array($c['key'], $only, true)) {
		continue;
	}
	$filtered[] = $c;
}

echo "CreditLab SMS test — mobile: $mobile | cases: " . count($filtered) . " | mode: " . ($send ? 'SEND' : 'dry-run') . "\n";
echo "Gateway: smswala (app+cron), k7 (enach/agreement in auto_enach/admin)\n\n";

if ($listOnly) {
	foreach ($filtered as $i => $c) {
		$mapped = creditlab_sms_map_template_id($c['template_id']);
		echo sprintf(
			"%3d. [%s] %s | tpl %s%s\n     %s\n",
			$i + 1,
			$c['group'],
			$c['key'],
			$c['template_id'],
			$mapped !== $c['template_id'] ? " -> $mapped" : '',
			substr($c['message'], 0, 90) . (strlen($c['message']) > 90 ? '...' : '')
		);
	}
	exit(0);
}

if (!$send) {
	echo "Dry-run only. Add --yes to send " . count($filtered) . " SMS (warning: many messages).\n";
	echo "Tip: --group=app --yes  (10 SMS) or --only=otp_login --yes\n";
	exit(0);
}

$ok = 0;
$fail = 0;
$logFile = dirname(__DIR__) . '/logs/sms_test_' . date('Y-m-d_His') . '.log';
@mkdir(dirname($logFile), 0755, true);

foreach ($filtered as $i => $c) {
	$n = $i + 1;
	echo "[$n/" . count($filtered) . "] {$c['group']}/{$c['key']} ... ";

	if ($c['group'] === 'k7') {
		$r = creditlab_sms_send_k7($mobile, $c['message'], $c['template_id']);
	} else {
		$r = creditlab_sms_send_smswala($mobile, $c['message'], $c['template_id']);
	}

	$line = sprintf(
		"[%s] %s/%s tpl=%s mapped=%s http=%d ok=%s body=%s\n",
		date('Y-m-d H:i:s'),
		$c['group'],
		$c['key'],
		$c['template_id'],
		$r['mapped_template'],
		$r['http'],
		$r['ok'] ? 'yes' : 'no',
		substr($r['body'], 0, 200)
	);
	file_put_contents($logFile, $line, FILE_APPEND);

	if ($r['ok']) {
		$ok++;
		echo "OK — " . substr($r['body'], 0, 60) . "\n";
	} else {
		$fail++;
		echo "FAIL http={$r['http']} " . ($r['curl'] ?: substr($r['body'], 0, 80)) . "\n";
	}

	if ($delay > 0 && $n < count($filtered)) {
		sleep($delay);
	}
}

echo "\nDone: $ok ok, $fail failed. Log: $logFile\n";
exit($fail > 0 ? 1 : 0);

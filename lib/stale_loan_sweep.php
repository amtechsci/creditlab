<?php
/**
 * Throttled cancellation of very old pending/follow-up loans.
 * Runs at most once per hour per server to avoid row locks on every staff page load.
 */
function creditlab_sweep_stale_loans(): void
{
	static $ran = false;
	if ($ran) {
		return;
	}
	$ran = true;

	$lockFile = sys_get_temp_dir() . '/creditlab_stale_loan_sweep.lock';
	if (is_file($lockFile) && (time() - filemtime($lockFile)) < 3600) {
		return;
	}
	@touch($lockFile);

	if (!function_exists('towquery') || !function_exists('towfetch')) {
		return;
	}

	$date = date('Y-m-d H:i:s');
	$staleLoans = towquery(
		"SELECT id, uid FROM `loan_apply`"
		. " WHERE `status` IN ('pending','follow up')"
		. " AND `apply_date` <= DATE_SUB(NOW(), INTERVAL 720 DAY)"
		. " LIMIT 200"
	);
	if (!$staleLoans) {
		return;
	}

	while ($a = towfetch($staleLoans)) {
		$loanId = (int) $a['id'];
		$uid = (int) $a['uid'];
		towquery("UPDATE `loan_apply` SET `status`='cancel', `status_date`='$date' WHERE id=$loanId");
		towquery("UPDATE `user` SET `loan`=2,`status`='cancel',`sloan`=0 WHERE id=$uid");
	}
}

function creditlab_staff_head_count_queries(): array
{
	return [
		'verifyquery' => towquery("SELECT id FROM `user` WHERE `active`=1 AND `verify`=1"),
		'newquery' => towquery("SELECT id FROM `user` WHERE `active`=1 AND `verify`=0"),
		'loanquery' => towquery("SELECT id FROM `loan_apply` WHERE `status`='account manager'"),
		'newloanquery' => towquery("SELECT id FROM `loan_apply` WHERE `status`='pending' OR `status`='follow up'"),
	];
}

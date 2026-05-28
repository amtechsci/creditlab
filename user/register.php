<?php
include '../db.php';
require_once __DIR__ . '/../lib/sms_otp_deliver.php';

/**
 * Pick the staff member with the fewest assigned users (single GROUP BY query).
 */
function creditlab_pick_least_assigned(string $staffTable, string $assignColumn): int
{
	$counts = [];
	$staffQuery = towquery("SELECT id FROM `$staffTable`");
	if ($staffQuery) {
		while ($row = towfetch($staffQuery)) {
			$counts[(int) $row['id']] = 0;
		}
	}
	if (empty($counts)) {
		return 0;
	}

	$countQuery = towquery(
		"SELECT `$assignColumn` AS staff_id, COUNT(*) AS cnt FROM `user`"
		. " WHERE `$assignColumn` > 0 GROUP BY `$assignColumn`"
	);
	if ($countQuery) {
		while ($row = towfetch($countQuery)) {
			$staffId = (int) $row['staff_id'];
			if (array_key_exists($staffId, $counts)) {
				$counts[$staffId] = (int) $row['cnt'];
			}
		}
	}

	asort($counts);
	$keys = array_keys($counts);
	return (int) $keys[0];
}

if (isset($_POST['mobile'])) {
	$mobile = towreal($_POST['mobile']);
	$resend = !empty($_POST['resend']) ? 1 : 0;
	$otp = rand(1000, 9999);
	$result = towquery("SELECT id FROM user WHERE mobile ='$mobile' LIMIT 1");

	if ($result && townum($result) === 1) {
		$message = "$otp is OTP for Creditlab login verification & valid till 2min. Don't share this OTP with anyone.";
		$template_id = '1407174844163241940';
		towquery("UPDATE user SET otp='$otp' WHERE mobile ='$mobile'");
		creditlab_otp_redirect_and_send("../account/confirm.php?id=$mobile", $mobile, $message, $template_id);
	}

	$assign_account = creditlab_pick_least_assigned('account_manager', 'assign_account_manager');
	$assign_recovery = creditlab_pick_least_assigned('recovery_officer', 'assign_recovery_officer');
	$reg_date = date('Y-m-d H:i:s');
	$document_password = 'pan no password pan#aadhar no password aadhar#aadha2 no password aadha2#salary no password salary#bank no password bank#address no password address#bank2 no password bank2#bank3 no password bank3';
	$tempRcid = 'TMP' . bin2hex(random_bytes(8));

	$newUserId = towquery2(
		"INSERT INTO `user`(`rcid`, `mobile`, `active`, `verify`, `otp`, `validation`, `reg_date`, `status`,"
		. " `document_password`, `loan_limit`, `assign_account_manager`, `assign_recovery_officer`, `approvenew`)"
		. " VALUES ('$tempRcid','$mobile',0,0,$otp,'','$reg_date','waiting','$document_password',10000,$assign_account,$assign_recovery,0)"
	);

	if (!$newUserId) {
		error_log('register.php: failed to insert user for mobile ' . $mobile);
		http_response_code(500);
		exit('Registration failed. Please try again.');
	}

	$rcid = 'CL' . date('ymd') . $newUserId;
	towquery("UPDATE user SET rcid='$rcid' WHERE id=$newUserId");

	$message = "$otp is OTP for Creditlab login verification & valid till 2min. Don't share this OTP with anyone.";
	$template_id = '1407174844163241940';
	creditlab_otp_redirect_and_send("../account/confirm.php?id=$mobile", $mobile, $message, $template_id);
}

echo "<script>window.location.replace('" . $app_url . "account/');</script>";

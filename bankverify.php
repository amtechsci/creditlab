<?php
include 'db.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/config/bank_api.php';

creditlab_require_staff();

if (!isset($_GET['bank_id'])) {
	http_response_code(400);
	exit('Missing bank_id');
}

if (BANK_API_KEY === '' || BANK_API_SECRET === '') {
	http_response_code(500);
	exit('Bank API credentials not configured');
}

if (isset($_GET['type'])) {
	$uid = towreal($_GET['user_id']);
	$bank_id = (int) towreal($_GET['bank_id']);
	towquery("DELETE FROM `user_bank` WHERE `id`=" . $bank_id);
	towquery("UPDATE `loan_apply` SET `ubank_id`=2 WHERE uid='$uid' AND status='disbursal'");
	print_r("<script>alert('Bank record removed');window.location.replace('/admin/profile.php?id=" . (int) $uid . "');</script>");
	exit;
}

$bank_id = (int) towreal($_GET['bank_id']);
$f = towfetch(towquery("SELECT user_bank.`ac_name`, user_bank.`ac_no`, user_bank.`ifsc_code`, user_bank.`ac_type`, user_bank.`branch_name`, user_bank.`bank_name`, user_bank.`date`, user_bank.`verify`, user.* FROM `user_bank` INNER JOIN user ON user_bank.`uid` = user.`id` WHERE user_bank.id=" . $bank_id));

if (!$f) {
	http_response_code(404);
	exit('Bank record not found');
}

$curl = curl_init();
curl_setopt_array($curl, [
	CURLOPT_URL => 'https://api.sandbox.co.in/authenticate',
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => '',
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => 'POST',
	CURLOPT_HTTPHEADER => [
		'x-api-key: ' . BANK_API_KEY,
		'x-api-secret: ' . BANK_API_SECRET,
		'x-api-version: 1.0',
	],
]);

$response = curl_exec($curl);
curl_close($curl);
$response = json_decode($response, true);

if (empty($response['access_token'])) {
	print_r("<script>alert('Bank API authentication failed');window.location.replace('/admin/profile.php?id=" . (int) $f['id'] . "');</script>");
	exit;
}

$access_token = $response['access_token'];
$url = 'https://api.sandbox.co.in/bank/' . $f['ifsc_code'] . '/accounts/' . $f['ac_no'] . '/verify?name=' . urlencode($f['name']) . '&mobile=' . urlencode($f['mobile']);

$curl = curl_init();
curl_setopt_array($curl, [
	CURLOPT_URL => $url,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => '',
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => 'GET',
	CURLOPT_HTTPHEADER => [
		'Authorization: ' . $access_token,
		'x-api-key: ' . BANK_API_KEY,
		'x-api-version: 1.0',
	],
]);

$response = curl_exec($curl);
curl_close($curl);

function bankverify_is_json($string)
{
	return is_string($string) && is_array(json_decode($string, true));
}

if (bankverify_is_json($response)) {
	$response = json_decode($response, true);
	if (isset($response['data']['name_at_bank']) && !empty($response['data']['name_at_bank'])) {
		$name = towreal($response['data']['name_at_bank']);
		towquery("UPDATE `user_bank` SET `ac_name`='$name',`verify`=1 WHERE `id`=" . $bank_id);
	} else {
		towquery("UPDATE `user_bank` SET `verify`=1 WHERE `id`=" . $bank_id);
	}
	$msg = isset($response['data']['message']) ? $response['data']['message'] : 'Verified';
	print_r("<script>alert('" . addslashes($msg) . "');window.location.replace('/admin/profile.php?id=" . (int) $f['id'] . "');</script>");
} else {
	print_r("<script>alert('Not Verify');window.location.replace('/admin/profile.php?id=" . (int) $f['id'] . "');</script>");
}

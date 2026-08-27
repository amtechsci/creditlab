<?php
include '../db.php';
require_once __DIR__ . '/../lib/auth.php';
creditlab_require_staff('/account/login.php');

$id = (int) ($_GET['id'] ?? 0);
$status = towreal($_GET['status'] ?? '');
if ($id < 1 || $status === '') {
	http_response_code(400);
	exit('Bad request');
}
$a = towquery("SELECT * FROM loan WHERE id=$id");
if (!$a || townum($a) < 1) {
	http_response_code(404);
	exit('Not found');
}
$a = towfetch($a);
if ($status == 'cleared') {
	towquery('UPDATE `user` SET `sloan`=`sloan`+1 WHERE id=' . (int) $a['uid']);
}
towquery("UPDATE `loan` SET `action`='$status',`status_log`='$status' WHERE `id`=" . (int) $a['id']);
towquery("UPDATE `user` SET `status`='$status' WHERE id=" . (int) $a['uid']);
towquery('DELETE FROM `loan_apply` WHERE id=' . (int) $a['lid']);
header('location: profile.php?id=' . (int) $a['uid']);

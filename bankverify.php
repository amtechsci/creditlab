<?php
/**
 * Staff-only bank verify / reject (no external API).
 * Verify sets user_bank.verify = 1 after admin review.
 */
include 'db.php';
require_once __DIR__ . '/lib/auth.php';

creditlab_require_staff();

if (!isset($_GET['bank_id'])) {
	http_response_code(400);
	exit('Missing bank_id');
}

$bank_id = (int) towreal($_GET['bank_id']);
if ($bank_id <= 0) {
	http_response_code(400);
	exit('Invalid bank_id');
}

$row = towfetch(towquery(
	"SELECT user_bank.id, user.id AS uid FROM `user_bank`"
	. " INNER JOIN user ON user_bank.uid = user.id WHERE user_bank.id=" . $bank_id
));

if (!$row) {
	http_response_code(404);
	exit('Bank record not found');
}

$uid = (int) $row['uid'];

function bankverify_redirect(int $userId, string $message): void
{
	print_r("<script>alert('" . addslashes($message) . "');window.location.replace('/admin/profile.php?id=" . $userId . "');</script>");
	exit;
}

if (isset($_GET['type'])) {
	towquery("DELETE FROM `user_bank` WHERE `id`=" . $bank_id);
	towquery("UPDATE `loan_apply` SET `ubank_id`=2 WHERE uid='$uid' AND status='disbursal'");
	bankverify_redirect($uid, 'Bank record removed');
}

towquery("UPDATE `user_bank` SET `verify`=1 WHERE `id`=" . $bank_id);
bankverify_redirect($uid, 'Bank marked as verified');

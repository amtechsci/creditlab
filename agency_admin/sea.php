<?php
include '../db.php';

if (isset($agency_admin)) {
    $authQuery = towquery(
        "SELECT agency_admin.id FROM agency_admin INNER JOIN agency ON agency.id = agency_admin.agency_id
         WHERE agency_admin.email='" . towreal($agency_admin) . "' AND agency_admin.active=1 LIMIT 1"
    );
    if (!$authQuery || townum($authQuery) <= 0) {
        header('location:/agency_admin/logout.php');
        exit;
    }
} else {
    header('location:/account/login.php');
    exit;
}

$search = isset($_GET['sea']) ? trim(towreal($_GET['sea'])) : '';
if ($search === '') {
    echo "<script>alert('Enter a search term'); window.close();</script>";
    exit;
}

$loanId = $search;
if (preg_match('/^CLL(\d+)$/i', $search, $matches)) {
    $loanId = $matches[1];
}

$seausersquery = towquery(
    "SELECT id FROM `user` WHERE `altmobile` = '$search' OR `mobile` = '$search' OR `email` = '$search'
     OR `altemail` = '$search' OR `rcid` = '$search' OR `account_no` = '$search' LIMIT 1"
);
if ($seausersquery && townum($seausersquery) > 0) {
    $a = towfetch($seausersquery);
    header('Location: profile.php?id=' . (int) $a['id']);
    exit;
}

$seausersquerys = towquery("SELECT uid FROM `loan_apply` WHERE `id` = '" . towreal($loanId) . "' LIMIT 1");
if ($seausersquerys && townum($seausersquerys) > 0) {
    $aa = towfetch($seausersquerys);
    header('Location: profile.php?id=' . (int) $aa['uid'] . '&tab=oldloan');
    exit;
}

echo "<script>alert('Not Found'); window.close();</script>";

<?php
include '../db.php';
require_once __DIR__ . '/../lib/auth.php';
if(isset($user)){
    $userquery = towquery("SELECT * FROM user WHERE mobile='$user'");
    $userfetch = towfetch($userquery);
    extract($userfetch,EXTR_PREFIX_ALL,"user");
}else{
    header('location:../account/');
    exit;
}
if(isset($_GET['id'])){
    $id = creditlab_require_loan_apply_access();
    $a = towquery("SELECT * FROM loan_apply WHERE id='$id' ORDER BY id DESC");
    if(towfetch($a)['keyid'] == 0)
    towquery("UPDATE `loan_apply` SET `keyid`=1 WHERE `id`=$id");
    else
    towquery("UPDATE `loan_apply` SET `agreement`=1 WHERE `id`=$id");
    if(isset($_GET['from'])){
    print_r("<script>window.location.replace('/user/newloan.php');</script>");
    }else{
    print_r("<script>window.location.replace('/user/');</script>");
    }
}
?>
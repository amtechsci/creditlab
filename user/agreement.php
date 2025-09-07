<?php
include '../db.php';
if(isset($user)){
    $userquery = towquery("SELECT * FROM user WHERE mobile='$user'");
    $userfetch = towfetch($userquery);
    extract($userfetch,EXTR_PREFIX_ALL,"user");
}else{
    header('location:../account/');
}
if(isset($_GET['id'])){
    $id = $_GET['id'];
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
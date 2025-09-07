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
    $b = towfetch($a);
    if($b['ubank_id'] == 0 or $b['ubank_id'] == 2)
    towquery("UPDATE `loan_apply` SET `ubank_id`=1 WHERE `id`=$id");
    if(isset($_GET['from'])){
    print_r("<script>window.location.replace('/user/newloan.php');</script>");
    }else{
    print_r("<script>window.location.replace('/user/');</script>");
    }
}
?>
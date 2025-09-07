<?php 
include '../db.php';
$search = towreal($_GET['sea']);
$seausersquery = towquery("SELECT * FROM `user` WHERE `altmobile` = '$search' OR `mobile`= '$search' OR `email`= '$search' OR `altemail`= '$search' OR `rcid`= '$search' OR `pan`= '$search' OR `account_no`= '$search'");
if(townum($seausersquery) > 0){
$a = towfetch($seausersquery);
header('location: profile.php?id='.$a['id'].'');
}else{
    $seausersquerys = towquery("SELECT * FROM `loan_apply` WHERE `id`= '$search'");
    if(townum($seausersquerys) > 0){
        $aa = towfetch($seausersquerys);
        header('location: profile.php?id='.$aa['uid'].'&tab=loan');
    }else{
        print_r("<script>alert('Not Found');window.close();</script>");
    }
}
?>
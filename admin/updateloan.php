<?php 
include '../db.php';
$status = towreal($_GET['status']);
$email = towreal($_GET['email']);
if(isset($_GET['id'])){
    $id = towreal($_GET['id']);
    $a = towquery("SELECT * FROM loan WHERE id=$id");
}else{
    $lid = towreal($_GET['lid']);
    $a = towquery("SELECT * FROM loan WHERE lid=$lid");
}
$a = towfetch($a);
if($status == "cleared"){
    if(($a['exhausted_period'] > 30) and $a['limit_inc_prompt'] == 1){
        $p = "`sloan`=`sloan`+1, `loan_limit`=`old_loan_limit`";
    }else{
        $p = "`sloan`=`sloan`+1";
    }
        towquery("UPDATE `user` SET $p WHERE id=".$a['uid']."");
}
towquery("UPDATE `loan` SET `action`='$status',`status_log`='$status',`cleard_date`='".date('Y-m-d')."' WHERE `id`=".$a['id']."");
towquery("UPDATE `user` SET `status`='$status' WHERE id=".$a['uid']."");
file_get_contents("https://creditlab.in/zxc/?url3=https://creditlab.in/no-due-certificate2.php?id=".$a['lid']."&email=$email");
// towquery("DELETE FROM `loan_apply` WHERE id=".$a['lid']."");
header('location: profile.php?id='.$a['uid'].'');
?>
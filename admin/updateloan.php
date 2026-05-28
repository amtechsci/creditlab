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
$base_url = getAppUrl();
require_once __DIR__ . '/../lib/zxc_mail.php';
require_once __DIR__ . '/../lib/http_fetch.php';
creditlab_zxc_mail_trigger(creditlab_zxc_mail_url($base_url, $email, null, null, $base_url . '/no-due-certificate2.php?id=' . $a['lid']));
// towquery("DELETE FROM `loan_apply` WHERE id=".$a['lid']."");
header('location: profile.php?id='.$a['uid'].'');
?>
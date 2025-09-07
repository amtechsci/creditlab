<?php 
include '../db.php';
$id = towreal($_GET['id']);
$status = towreal($_GET['status']);
$a = towquery("SELECT * FROM loan WHERE id=$id");
$a = towfetch($a);
if($status == "cleared"){
   towquery("UPDATE `user` SET `sloan`=`sloan`+1 WHERE id=".$a['uid'].""); 
}
towquery("UPDATE `loan` SET `action`='$status',`status_log`='$status' WHERE `id`=".$a['id']."");
towquery("UPDATE `user` SET `status`='$status' WHERE id=".$a['uid']."");
towquery("DELETE FROM `loan_apply` WHERE id=".$a['lid']."");
header('location: profile.php?id='.$a['uid'].'');

?>
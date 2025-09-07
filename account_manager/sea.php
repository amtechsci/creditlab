<?php 
include '../db.php';
$search = towreal($_GET['sea']);
$seausersquery = towquery("SELECT * FROM `user` WHERE `altmobile` = '$search' OR `mobile`= '$search' OR `email`= '$search' OR `altemail`= '$search' OR `rcid`= '$search' OR `pan`= '$search' OR `account_no`= '$search'");
if(townum($seausersquery) > 0){
$a = towfetch($seausersquery);
// print_r("SELECT * FROM `user` WHERE `altmobile`= '$search' OR `mobile`= '$search' OR `email`= '$search' OR `altemail`= '$search' OR `rcid`= '$search' OR `pan`= '$search' OR `account_no`= '$search' AND NOT `altmobile` = 0");
// print_r($a);
header('location: profile.php?id='.$a['id'].'');
}else{
print_r("<script>alert('Not Found');window.close();</script>");
}
?>
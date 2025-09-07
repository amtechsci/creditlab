<?php
// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);
include '../db.php';
if(isset($admin)){
    $userquery = towquery("SELECT * FROM user WHERE email='$admin'");
    $userfetch = towfetch($userquery);
    extract($userfetch,EXTR_PREFIX_ALL,"user");
}else{
    header('location:/account/login.php');
}
if(isset($_GET['id'])and isset($_GET['type'])){
    $extract = towrealarray($_GET);
    towquery("DELETE FROM `".$extract['type']."` WHERE id=".$extract['id']);
    header('location:/admin/add_user.php');
}
?>
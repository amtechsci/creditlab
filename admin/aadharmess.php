<?php 
include '../db.php';
$search = towreal($_POST['aadharmess']);
$id = towreal($_POST['id']);
if($search != ""){
$seausersquery = towquery("SELECT * FROM `user` WHERE `aadhar` LIKE '%$search%' AND NOT id=$id");
if(townum($seausersquery) > 0)
echo townum($seausersquery)." exist";
}
?>
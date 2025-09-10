<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/s3_aws_sdk.php';
if(isset($_POST['ac_no'])){
    $id = towreal($_GET['id']);
    if(!empty($_FILES["bank_statment"]["name"])){
    $ft = explode('.',$_FILES['bank_statment']['name']);
    $ft = end($ft);
    $file_type = strtolower($ft);
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
    $bank_statment = $_FILES["bank_statment"]["name"];
    $bank_statment = $user_name.'conpany'.date('YmdHis').'.'.$file_type;
    list($success, $result) = s3_upload_file($_FILES["bank_statment"]["tmp_name"], $bank_statment, 'application/octet-stream');
    if (!$success) $bank_statment = "no";
    }else{$bank_statment = "no";}
    }else{$bank_statment = "no";}
    $ext = towrealarray2($_POST);
    extract($ext);
    towquery("UPDATE `loan_apply` SET `ubank_id`=1 WHERE `id`=$id");
    towquery("INSERT INTO `user_bank`(`uid`, `ac_no`, `ifsc_code`, `bank_statment`) VALUES ('$user_id', '$ac_no', '$ifsc_code', '$bank_statment')") and print_r("<script>alert('Your data is successfully updated'); window.location.replace('index.php');</script>");
}
?>
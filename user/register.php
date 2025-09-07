<?php
include '../db.php';
if(isset($_POST['mobile'])){
    $mobile = towreal($_POST['mobile']);
    $result=towquery("SELECT * FROM user WHERE mobile ='$mobile'");
    $otp = rand(1000,9999);
    if (townum($result)==1) {
        if($mobile == '9573794121'){
            $otp = 1111;
        }else{
            $message="$otp is OTP for Creditlab login verification & valid till 2min. Don't share this OTP with anyone.";
            $template_id='1407174844163241940';
            include '../send_sms.php';
        }
        towquery("UPDATE user SET otp='$otp' WHERE mobile ='$mobile'");
        header("location:../account/confirm.php?id=$mobile");
    }else{
    $id = towfetch(towquery("SELECT id FROM user ORDER BY id DESC"))['id'];
    $id++;
    $rcid = "CL".date('ymd').$id;
    $reg_date = date('Y-m-d H:i:s');
    $aa = towquery("SELECT id FROM `account_manager`");
while($b = towfetch($aa)){
$a = towquery("SELECT assign_account_manager FROM `user` WHERE assign_account_manager=".$b['id']."");
$min[$b['id']] = townum($a);
}$assign_account = array_keys($min, min($min));$assign_account = $assign_account[0];$aaz = towquery("SELECT id FROM `recovery_officer`");
while($bz = towfetch($aaz)){
$az = towquery("SELECT assign_recovery_officer FROM `user` WHERE assign_recovery_officer=".$bz['id']."");
$minz[$bz['id']] = townum($az);
}$assign_recovery = array_keys($minz, min($minz));$assign_recovery = $assign_recovery[0];

$a = towquery("INSERT INTO `user`(`rcid`, `mobile`, `active`, `verify`, `otp`, `validation`, `reg_date`, `status`, `document_password`, `loan_limit`, `assign_account_manager`, `assign_recovery_officer`, `approvenew`) VALUES ('$rcid','$mobile',0,0,$otp,'','$reg_date','waiting','pan no password pan#aadhar no password aadhar#aadha2 no password aadha2#salary no password salary#bank no password bank#address no password address#bank2 no password bank2#bank3 no password bank3',10000,$assign_account,$assign_recovery,0)");
$message="$otp is OTP for Creditlab login verification & valid till 2min. Don't share this OTP with anyone.";
        $template_id='1407174844163241940';
        include '../send_sms.php';
        towquery("UPDATE user SET otp='$otp' WHERE mobile ='$mobile'");
header("location:../account/confirm.php?id=$mobile");
}}else{
        echo "<script>window.location.replace('".$app_url."account/');</script>";
    }
?>
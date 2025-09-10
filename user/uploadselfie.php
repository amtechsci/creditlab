<?php
include '../db.php';
require_once __DIR__ . '/../lib/s3_aws_sdk.php';
if(isset($user)){
    $userquery = towquery("SELECT * FROM user WHERE mobile='$user'");
    $userfetch = towfetch($userquery);
    extract($userfetch,EXTR_PREFIX_ALL,"user");
}else{
    header('location:../account/');
}
$ext = towrealarray2($_POST);
    extract($ext);
    if(!empty($_FILES['selfie']['name'])){
        $selfie = generateImage($_FILES['selfie'],'selfie');
    }
    $selfie = $selfie;
    $re = towquery("UPDATE `user` SET `selfie`='$selfie' WHERE id='$user_id'");
    
function generateImage($img,$name){
    $folderPath = "uploads/";
    $a = $img['name'];
    $file_type = strtolower(end(explode('.',$a)));
    $allowed = array("jpg", "jpeg", "png", "pdf");
        if(in_array($file_type, $allowed)) {
            $ext = explode(".",$a);
            $ext = end($ext);
            $number = time();
            $new = "$name$number";
            $excel = "$new.$ext";
            list($success, $result) = s3_upload_file($img['tmp_name'], $excel, 'image/jpeg');
            if ($success) {
            }
        }
        $imgname = $excel;
        $file = $folderPath . $imgname;
        return $imgname;
}
print_r("<script>window.location.replace('index.php');</script>");
?>
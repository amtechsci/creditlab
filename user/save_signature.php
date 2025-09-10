<?php
// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);
include '../db.php';
require_once __DIR__ . '/../lib/s3_aws_sdk.php';
if(isset($user)){
    $userquery = towquery("SELECT * FROM user WHERE mobile='$user'");
    $userfetch = towfetch($userquery);
    extract($userfetch,EXTR_PREFIX_ALL,"user");
    $data = json_decode(file_get_contents('php://input'), true);
    $imageData = $data['image'];
    list($type, $imageData) = explode(';', $imageData);
    list(, $imageData)      = explode(',', $imageData);
    $imageData = base64_decode($imageData);
    $fileName = 'signature_' . time() . '.png';
    list($success, $result) = s3_upload_string($imageData, $fileName, 'image/png');
    if (!$success) {
        echo 0;
        exit;
    }
    $re = towquery("UPDATE `user` SET `signature`='$fileName' WHERE id='$user_id'");
    echo 1;
}else{
    echo 2;
}
?>
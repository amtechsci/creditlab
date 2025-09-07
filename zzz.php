<?php
include 'db.php';
if(isset($user)){
    $userquery = towquery("SELECT * FROM user WHERE mobile='$user'");
    $userfetch = towfetch($userquery);
    extract($userfetch,EXTR_PREFIX_ALL,"user");
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the uploaded video file
    $video = $_FILES['video'];
    // Move the file to the uploads directory
    $uploadDir = 'user/uploads/';
    $n = time().$video['name'];
    $uploadFile = $uploadDir . basename($n);

    if (move_uploaded_file($video['tmp_name'], $uploadFile)) {
        $re = towquery("UPDATE `user` SET `selfie`='$n' WHERE id='$user_id'");
      echo 1;
    } else {
      echo 2;
    }
 }
}else{
}
?>
<?php
include 'db.php';
require_once 'lib/s3_upload_helper.php';
if(isset($user)){
    $userquery = towquery("SELECT * FROM user WHERE mobile='$user'");
    $userfetch = towfetch($userquery);
    extract($userfetch,EXTR_PREFIX_ALL,"user");
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the uploaded video file
    $video = $_FILES['video'];
    
    // Debug: Log file info
    error_log("Video upload attempt - File: " . $video['name'] . ", Size: " . $video['size'] . ", Type: " . $video['type']);
    
    // Generate unique filename
    $n = time().$video['name'];
    
    // Upload to S3 instead of local storage
    list($success, $message) = s3_upload_file_from_upload($video['tmp_name'], $n, 'video/mp4');
    
    // Debug: Log upload result
    error_log("S3 upload result - Success: " . ($success ? 'true' : 'false') . ", Message: " . $message);
    
    if ($success) {
        // Update database with filename
        $re = towquery("UPDATE `user` SET `selfie`='$n' WHERE id='$user_id'");
        error_log("Database updated with filename: " . $n);
        echo 1;
    } else {
        error_log("Video upload failed: " . $message);
        echo 2;
    }
 }
}else{
}
?>
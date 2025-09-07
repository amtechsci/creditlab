<?php
include_once 'head.php';
if(isset($_POST['submit'])){
    $ext = towrealarray2($_POST); extract($ext);
    if(!empty($_FILES["payment_screenshot"]["name"])){
    $ft = explode('.',$_FILES['payment_screenshot']['name']);
    $ft = end($ft);
    $file_type = strtolower($ft);
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
    $payment_screenshot = $_FILES["payment_screenshot"]["name"];
    $payment_screenshot = $user_name.'conpany'.date('YmdHis').'.'.$file_type;
    move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], 'uploads/'.$payment_screenshot);
    }else{$payment_screenshot = "no";}
    }else{$payment_screenshot = "no";}
    $ext = towrealarray2($_POST);
    extract($ext);
    towquery("INSERT INTO `pay_ref`(`uid`, `utr_ref`, `payment_screenshot`, `loan_id`, `payment_type`) VALUES ('$user_id','$utr_ref','$payment_screenshot','$loan_id','$payment_type')") and print_r("<script>alert('Your data is successfully updated'); window.location.replace('index.php');</script>");
}else{
    echo 44;
}
?>
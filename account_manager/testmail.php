<?php
include '../db.php';
if (isset($_POST['email'])) {
    $ext = towrealarray($_POST); extract($ext);
//  $headers = "MIME-Version: 1.0" . "\r\n";
// $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
// $headers .= 'From: <Rush4cash docs@rush4cash.in>' . "\r\n";
// $to=$email;
// mail($to,$subject,$message,$headers);          
}
use PHPMailer\PHPMailer\PHPMailer;
require 'vendor/autoload.php';
$mail = new PHPMailer;
$mail->isSMTP();
$mail->SMTPDebug = 2;
$mail->Host = 'smtp.hostinger.com';
$mail->Port = 587;
$mail->SMTPAuth = true;
$mail->Username = 'docs@rush4cash.in';
$mail->Password = '?rL5L!&Ca';
$mail->setFrom('docs@rush4cash.in', 'Rush4Cash');
$mail->addReplyTo('docs@rush4cash.in', 'Rush4Cash');
$mail->addAddress($email, 'atul');
$mail->Subject = $subject;
$mail->msgHTML(file_get_contents('message.html'), __DIR__);
$mail->Body = $message;
//$mail->addAttachment('test.txt');
if (!$mail->send()) {
    // echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    // echo 'The email message was sent.';
    print_r("<script>window.location.replace('email.php');</script>");
}
?>
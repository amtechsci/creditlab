<?php

require 'class/class.phpmailer.php';
include('pdf.php');
require_once __DIR__ . '/../lib/s3_aws_sdk.php';
require_once __DIR__ . '/../config/mail.php';
if(isset($_GET['url'])){
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => $_GET['url'],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);
// echo $response;
// exit;
if(isset($_GET['email']))
{
	$file_name = hash('md5',$_GET['url']) . '.pdf';
// 	$html_code = '<link rel="stylesheet" href="bootstrap.min.css">';
	$pdf = new Pdf();
	$pdf->load_html($response);
	$pdf->render();
	$pdf->setPaper('A4','landscape');
	$file = $pdf->output();
	
	// Upload to S3 only - no local storage
	list($success, $result) = s3_upload_string($file, $file_name, 'application/pdf');
	
	if (!$success) {
		// If S3 upload fails, show error
		echo "Error uploading to S3: " . $result;
		exit;
	}
	
	// For email attachment, we need to create a temporary local file
	$temp_file = sys_get_temp_dir() . '/' . $file_name;
	file_put_contents($temp_file, $file);
// 	echo $file;
	$mail = new PHPMailer;
// 	$mail->SMTPDebug = true;
	$mail->IsSMTP();
	$mail->Host = MAIL_SMTP_HOST;
	$mail->Port = (string) MAIL_SMTP_PORT;
	$mail->SMTPAuth = true;
	$mail->Username = MAIL_SMTP_USER;
	$mail->Password = MAIL_SMTP_PASSWORD;
	$mail->SMTPSecure = MAIL_SMTP_SECURE;
	$mail->From = MAIL_SMTP_USER;
	$mail->FromName = 'CreditLab';			//Sets the From name of the message
	$mail->AddAddress($_GET['email'], 'Name');		//Adds a "To" address
	$mail->WordWrap = 50;							//Sets word wrapping on the body of the message to a given number of characters
	$mail->IsHTML(true);							//Sets message type to HTML				
	$mail->AddAttachment($temp_file);     				//Adds an attachment from a path on the filesystem
	$mail->Subject = 'LOAN AGREEMENT';			//Sets the Subject of the message
	$mail->Body = 'Dear customer,<br><br>

Please find the LOAN AGREEMENT attached below which was accepted by you digitally in web/app.<br>
<br><br>

Best regards<br>
Creditlab.in';				//An HTML or plain text message body
if(!$mail->Send()) {
    echo "Mail Error: " . $mail->ErrorInfo;
} else {
    echo "Mail sent successfully";
}
}
}

if(isset($_GET['url2'])){
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $_GET['url2'],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);
// echo $response;
// exit;
if(isset($_GET['email']))
{
	$file_name = hash('md5',$_GET['url2']) . '.pdf';
// 	$html_code = '<link rel="stylesheet" href="bootstrap.min.css">';
	$pdf = new Pdf();
	$pdf->load_html($response);
	$pdf->render();
	$pdf->setPaper('A4','landscape');
	$file = $pdf->output();
	
	// Upload to S3 only - no local storage
	list($success, $result) = s3_upload_string($file, $file_name, 'application/pdf');
	
	if (!$success) {
		// If S3 upload fails, show error
		echo "Error uploading to S3: " . $result;
		exit;
	}
	
	// For email attachment, we need to create a temporary local file
	$temp_file = sys_get_temp_dir() . '/' . $file_name;
	file_put_contents($temp_file, $file);
	$mail2 = new PHPMailer;
// 	$mail2->SMTPDebug = true;
	$mail2->IsSMTP();
	$mail2->Host = MAIL_SMTP_HOST;
	$mail2->Port = (string) MAIL_SMTP_PORT;
	$mail2->SMTPAuth = true;
	$mail2->Username = MAIL_SMTP_USER;
	$mail2->Password = MAIL_SMTP_PASSWORD;
	$mail2->SMTPSecure = MAIL_SMTP_SECURE;
	$mail2->From = MAIL_SMTP_USER;
	$mail2->FromName = 'CreditLab';
	$mail2->AddAddress($_GET['email'], 'Name');
	$mail2->WordWrap = 50;
	$mail2->IsHTML(true);
	$mail2->AddAttachment($temp_file);
	$mail2->Subject = 'SANCTION LETTER / KEY FACT STATEMENT';			//Sets the Subject of the message
	$mail2->Body = 'Dear customer,<br><br>

Please find the SANCTION LETTER / KEY FACT STATEMENT attached below which was accepted by you digitally in web/app.<br>
<br><br>

Best regards<br>
Creditlab.in';				//An HTML or plain text message body
if(!$mail2->Send()) {
    echo "Mail Error: " . $mail2->ErrorInfo;
} else {
    echo "Mail sent successfully";
}
}
}


if($_GET['url3']){
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $_GET['url3'],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);
// echo $response;
// exit;
if(isset($_GET['email']))
{
	$file_name = hash('md5',$_GET['url3']) . '.pdf';
// 	$html_code = '<link rel="stylesheet" href="bootstrap.min.css">';
	$pdf = new Pdf();
	$pdf->load_html($response);
	$pdf->render();
	$pdf->setPaper('A4','landscape');
	$file = $pdf->output();
	
	// Upload to S3 only - no local storage
	list($success, $result) = s3_upload_string($file, $file_name, 'application/pdf');
	
	if (!$success) {
		// If S3 upload fails, show error
		echo "Error uploading to S3: " . $result;
		exit;
	}
	
	// For email attachment, we need to create a temporary local file
	$temp_file = sys_get_temp_dir() . '/' . $file_name;
	file_put_contents($temp_file, $file);
// 	echo $file;
	$mail2 = new PHPMailer;
// 	$mail2->SMTPDebug = true;
	$mail2->IsSMTP();
	$mail2->Host = MAIL_SMTP_HOST;
	$mail2->Port = (string) MAIL_SMTP_PORT;
	$mail2->SMTPAuth = true;
	$mail2->Username = MAIL_SMTP_USER;
	$mail2->Password = MAIL_SMTP_PASSWORD;
	$mail2->SMTPSecure = MAIL_SMTP_SECURE;
	$mail2->From = MAIL_SMTP_USER;
	$mail2->FromName = 'CreditLab';
	$mail2->AddAddress($_GET['email'], 'Name');
	$mail2->WordWrap = 50;
	$mail2->IsHTML(true);
	$mail2->AddAttachment($temp_file);
	$mail2->Subject = 'NO  DUE';			//Sets the Subject of the message
	$mail2->Body = 'Dear customer,<br><br>

Please find attached the NO DUES CERTIFICATE for the recently cleared loan.<br>
<br><br>

Best regards<br>
Creditlab.in';				//An HTML or plain text message body
if(!$mail2->Send()) {
    echo "Mail Error: " . $mail2->ErrorInfo;
} else {
    echo "Mail sent successfully";
}
}
}
?>
<?php

require 'class/class.phpmailer.php';
include('pdf.php');
require_once '../lib/s3_aws_sdk.php';
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
	$mail->IsSMTP();								//Sets Mailer to send message using SMTP
	$mail->Host = 'smtp.hostinger.com';		//Sets the SMTP hosts of your Email hosting, this for Godaddy
	$mail->Port = '465';								//Sets the default SMTP server port
	$mail->SMTPAuth = true;							//Sets SMTP authentication. Utilizes the Username and Password variables
	$mail->Username = 'no-reply@creditlab.in';					//Sets SMTP username
	$mail->Password = 'A@njk9hb';					//Sets SMTP password
	$mail->SMTPSecure = 'ssl';							//Sets connection prefix. Options are "", "ssl" or "tls"
	$mail->From = 'no-reply@creditlab.in';			//Sets the From email address for the message
	$mail->FromName = 'no-reply';			//Sets the From name of the message
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
$mail->Send();
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
	$mail2->IsSMTP();								//Sets Mailer to send message using SMTP
	$mail2->Host = 'smtp.hostinger.com';		//Sets the SMTP hosts of your Email hosting, this for Godaddy
	$mail2->Port = '465';								//Sets the default SMTP server port
	$mail2->SMTPAuth = true;							//Sets SMTP authentication. Utilizes the Username and Password variables
	$mail2->Username = 'no-reply@creditlab.in';					//Sets SMTP username
	$mail2->Password = 'A@njk9hb';					//Sets SMTP password
	$mail2->SMTPSecure = 'ssl';							//Sets connection prefix. Options are "", "ssl" or "tls"
	$mail2->From = 'no-reply@creditlab.in';			//Sets the From email address for the message
	$mail2->FromName = 'no-reply';			//Sets the From name of the message
	$mail2->AddAddress($_GET['email'], 'Name');		//Adds a "To" address
	$mail2->WordWrap = 50;							//Sets word wrapping on the body of the message to a given number of characters
	$mail2->IsHTML(true);							//Sets message type to HTML				
	$mail2->AddAttachment($temp_file);     				//Adds an attachment from a path on the filesystem
	$mail2->Subject = 'SANCTION LETTER / KEY FACT STATEMENT';			//Sets the Subject of the message
	$mail2->Body = 'Dear customer,<br><br>

Please find the SANCTION LETTER / KEY FACT STATEMENT attached below which was accepted by you digitally in web/app.<br>
<br><br>

Best regards<br>
Creditlab.in';				//An HTML or plain text message body
$mail2->Send();
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
	$mail2->IsSMTP();								//Sets Mailer to send message using SMTP
	$mail2->Host = 'smtp.hostinger.com';		//Sets the SMTP hosts of your Email hosting, this for Godaddy
	$mail2->Port = '465';								//Sets the default SMTP server port
	$mail2->SMTPAuth = true;							//Sets SMTP authentication. Utilizes the Username and Password variables
	$mail2->Username = 'no-reply@creditlab.in';					//Sets SMTP username
	$mail2->Password = 'A@njk9hb';					//Sets SMTP password
	$mail2->SMTPSecure = 'ssl';							//Sets connection prefix. Options are "", "ssl" or "tls"
	$mail2->From = 'no-reply@creditlab.in';			//Sets the From email address for the message
	$mail2->FromName = 'no-reply';			//Sets the From name of the message
	$mail2->AddAddress($_GET['email'], 'Name');		//Adds a "To" address
	$mail2->WordWrap = 50;							//Sets word wrapping on the body of the message to a given number of characters
	$mail2->IsHTML(true);							//Sets message type to HTML				
	$mail2->AddAttachment($temp_file);     				//Adds an attachment from a path on the filesystem
	$mail2->Subject = 'NO  DUE';			//Sets the Subject of the message
	$mail2->Body = 'Dear customer,<br><br>

Please find attached the NO DUES CERTIFICATE for the recently cleared loan.<br>
<br><br>

Best regards<br>
Creditlab.in';				//An HTML or plain text message body
$mail2->Send();
}
}
?>
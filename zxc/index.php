<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/auth.php';
require 'class/class.phpmailer.php';
include 'pdf.php';
require_once __DIR__ . '/../lib/s3_aws_sdk.php';
require_once __DIR__ . '/../config/mail.php';

if (!creditlab_is_staff_logged_in() && !creditlab_validate_internal_token()) {
	http_response_code(403);
	exit('Forbidden');
}

function zxc_fetch_document_html(string $param): string
{
	if (empty($_GET[$param])) {
		http_response_code(400);
		exit('Missing document URL');
	}
	$target = creditlab_append_internal_token((string) $_GET[$param]);
	if (!creditlab_is_allowed_document_url($target)) {
		http_response_code(403);
		exit('URL not allowed');
	}

	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => $target,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 5,
		CURLOPT_TIMEOUT => 60,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
	]);
	$response = curl_exec($curl);
	$code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);

	if ($response === false || $code >= 400) {
		http_response_code(502);
		exit('Failed to fetch document');
	}

	return $response;
}

function zxc_send_pdf_mail(string $sourceParam, string $subject, string $body): void
{
	if (empty($_GET['email']) || !filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
		http_response_code(400);
		exit('Valid email required');
	}

	$response = zxc_fetch_document_html($sourceParam);
	$file_name = hash('md5', (string) $_GET[$sourceParam]) . '.pdf';
	$pdf = new Pdf();
	$pdf->load_html($response);
	$pdf->render();
	$pdf->setPaper('A4', 'landscape');
	$file = $pdf->output();

	list($success, $result) = s3_upload_string($file, $file_name, 'application/pdf');
	if (!$success) {
		echo 'Error uploading to S3: ' . $result;
		exit;
	}

	$temp_file = sys_get_temp_dir() . '/' . $file_name;
	file_put_contents($temp_file, $file);

	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->Host = MAIL_SMTP_HOST;
	$mail->Port = (string) MAIL_SMTP_PORT;
	$mail->SMTPAuth = true;
	$mail->Username = MAIL_SMTP_USER;
	$mail->Password = MAIL_SMTP_PASSWORD;
	$mail->SMTPSecure = MAIL_SMTP_SECURE;
	$mail->From = MAIL_SMTP_USER;
	$mail->FromName = MAIL_FROM_NAME;
	$mail->AddAddress($_GET['email'], 'Name');
	$mail->WordWrap = 50;
	$mail->IsHTML(true);
	$mail->AddAttachment($temp_file);
	$mail->Subject = $subject;
	$mail->Body = $body;

	if (!$mail->Send()) {
		echo 'Mail Error: ' . $mail->ErrorInfo;
	} else {
		echo 'Mail sent successfully';
	}
	@unlink($temp_file);
}

if (isset($_GET['url']) && isset($_GET['email'])) {
	zxc_send_pdf_mail(
		'url',
		'LOAN AGREEMENT',
		'Dear customer,<br><br>Please find the LOAN AGREEMENT attached below which was accepted by you digitally in web/app.<br><br>Best regards<br>Creditlab.in'
	);
}

if (isset($_GET['url2']) && isset($_GET['email'])) {
	zxc_send_pdf_mail(
		'url2',
		'SANCTION LETTER / KEY FACT STATEMENT',
		'Dear customer,<br><br>Please find the SANCTION LETTER / KEY FACT STATEMENT attached below which was accepted by you digitally in web/app.<br><br>Best regards<br>Creditlab.in'
	);
}

if (!empty($_GET['url3']) && isset($_GET['email'])) {
	zxc_send_pdf_mail(
		'url3',
		'NO  DUE',
		'Dear customer,<br><br>Please find attached the NO DUES CERTIFICATE for the recently cleared loan.<br><br>Best regards<br>Creditlab.in'
	);
}

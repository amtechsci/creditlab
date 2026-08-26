<?php
/**
 * Daily MariaDB backup → S3 + email notification
 *
 * Uploads a gzipped mysqldump to
 *   s3://{bucket}/{BACKUP_S3_PREFIX}{dbname}_YYYY-MM-DD_HHMMSS.sql.gz
 * Emails BACKUP_EMAIL with size + 7-day presigned download URL (no attachment).
 *
 * Default prefix is uploads/db-backups/ so existing EC2 role PutObject on uploads/* works.
 * Prefer a dedicated backups/ prefix once IAM allows it.
 *
 * Cron (Asia/Kolkata 02:15 daily), as www-data:
 *   15 2 * * * www-data /usr/bin/php /var/www/creditlab.in/cron/daily_db_backup.php >> /var/log/creditlab-db-backup.log 2>&1
 *
 * Manual test:
 *   sudo -u www-data /usr/bin/php /var/www/creditlab.in/cron/daily_db_backup.php
 *
 * Env (.env):
 *   BACKUP_EMAIL=amproapk@gmail.com
 *   BACKUP_S3_PREFIX=uploads/db-backups/
 *   BACKUP_RETENTION_DAYS=30
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	echo "CLI only\n";
	exit(1);
}

date_default_timezone_set('Asia/Kolkata');

define('CREDITLAB_SKIP_SESSION', true);

require_once __DIR__ . '/../lib/env.php';
creditlab_load_env();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config_s3.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../zxc/class/class.phpmailer.php';
require_once __DIR__ . '/../lib/s3_aws_sdk.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$workDir = '/tmp/creditlab-db-backups';
$logFile = $workDir . '/backup.log';
$recipient = env('BACKUP_EMAIL', 'amproapk@gmail.com');
// Default under uploads/ — creditlab-ec2-s3-role typically allows PutObject there, not backups/
$s3Prefix = rtrim(env('BACKUP_S3_PREFIX', 'uploads/db-backups/'), '/') . '/';
$retentionDays = max(1, (int) env('BACKUP_RETENTION_DAYS', '30'));
$mysqldumpBin = env('BACKUP_MYSQLDUMP', '/usr/bin/mysqldump');

function backup_log(string $msg): void
{
	global $logFile, $workDir;
	$line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
	echo $line;
	if (!is_dir($workDir)) {
		@mkdir($workDir, 0700, true);
	}
	@file_put_contents($logFile, $line, FILE_APPEND);
}

function backup_human_size(int $bytes): string
{
	$units = ['B', 'KB', 'MB', 'GB'];
	$i = 0;
	$n = (float) $bytes;
	while ($n >= 1024 && $i < count($units) - 1) {
		$n /= 1024;
		$i++;
	}
	return sprintf('%.2f %s', $n, $units[$i]);
}

function backup_send_mail(string $to, string $subject, string $htmlBody, string $textBody): bool
{
	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->Host = MAIL_SMTP_HOST;
	$mail->Port = (string) MAIL_SMTP_PORT;
	$mail->SMTPAuth = true;
	$mail->Username = MAIL_SMTP_USER;
	$mail->Password = MAIL_SMTP_PASSWORD;
	$mail->SMTPSecure = MAIL_SMTP_SECURE;
	$mail->From = MAIL_SMTP_USER;
	$mail->FromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'CreditLab';
	$mail->AddAddress($to);
	$mail->IsHTML(true);
	$mail->Subject = $subject;
	$mail->Body = $htmlBody;
	$mail->AltBody = $textBody;
	$ok = $mail->Send();
	if (!$ok) {
		backup_log('EMAIL ERROR: ' . $mail->ErrorInfo);
	}
	return (bool) $ok;
}

backup_log('=== Daily DB backup start ===');

$dbHost = env('DB_HOST', '127.0.0.1');
$dbUser = env('DB_USER', 'root');
$dbPass = env('DB_PASSWORD', '');
$dbName = env('DB_NAME', 'credit');

if ($dbHost === 'localhost') {
	$dbHost = '127.0.0.1';
}

if (!is_executable($mysqldumpBin)) {
	$alt = trim((string) shell_exec('command -v mysqldump 2>/dev/null'));
	if ($alt !== '' && is_executable($alt)) {
		$mysqldumpBin = $alt;
	} else {
		backup_log("FATAL: mysqldump not found at {$mysqldumpBin}");
		backup_send_mail(
			$recipient,
			'[CreditLab] DB backup FAILED — mysqldump missing',
			'<p>mysqldump binary not found on server.</p>',
			'mysqldump binary not found on server.'
		);
		exit(1);
	}
}

if (!is_dir($workDir) && !mkdir($workDir, 0700, true) && !is_dir($workDir)) {
	backup_log("FATAL: cannot create {$workDir}");
	exit(1);
}

$stamp = date('Y-m-d_His');
$baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $dbName) . '_' . $stamp . '.sql.gz';
$localGz = $workDir . '/' . $baseName;
$s3Key = $s3Prefix . $baseName;
$cnfFile = $workDir . '/my.cnf.' . getmypid();

$passEscaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $dbPass);
$cnf = "[client]\n"
	. 'host=' . $dbHost . "\n"
	. 'user=' . $dbUser . "\n"
	. 'password="' . $passEscaped . "\"\n";

if (file_put_contents($cnfFile, $cnf) === false) {
	backup_log('FATAL: cannot write defaults file');
	exit(1);
}
chmod($cnfFile, 0600);

// MariaDB-compatible dump flags; pipefail so mysqldump errors are not masked by gzip
$inner = sprintf(
	'%s --defaults-extra-file=%s --single-transaction --quick --routines --triggers %s | gzip -c > %s',
	escapeshellarg($mysqldumpBin),
	escapeshellarg($cnfFile),
	escapeshellarg($dbName),
	escapeshellarg($localGz)
);
$dumpCmd = 'bash -lc ' . escapeshellarg('set -o pipefail; ' . $inner);

backup_log("Dumping database `{$dbName}` …");
exec($dumpCmd . ' 2>&1', $dumpOut, $dumpCode);
@unlink($cnfFile);

if ($dumpCode !== 0 || !is_file($localGz) || filesize($localGz) < 64) {
	$err = implode("\n", $dumpOut);
	backup_log("FATAL: mysqldump failed (exit {$dumpCode}): {$err}");
	@unlink($localGz);
	backup_send_mail(
		$recipient,
		'[CreditLab] DB backup FAILED — dump error',
		'<p>mysqldump failed for <code>' . htmlspecialchars($dbName) . '</code>.</p><pre>' . htmlspecialchars($err) . '</pre>',
		"mysqldump failed for {$dbName}: {$err}"
	);
	exit(1);
}

$sizeBytes = (int) filesize($localGz);
$sizeHuman = backup_human_size($sizeBytes);
backup_log("Dump OK: {$localGz} ({$sizeHuman})");

$s3 = new S3Client(s3_client_config());
$uploadOk = false;
$uploadErr = '';

try {
	$s3->putObject([
		'Bucket' => S3_BUCKET,
		'Key' => $s3Key,
		'SourceFile' => $localGz,
		'ContentType' => 'application/gzip',
		'ServerSideEncryption' => 'AES256',
		'Metadata' => [
			'dbname' => $dbName,
			'host' => gethostname() ?: 'unknown',
			'created' => date('c'),
		],
	]);
	$uploadOk = true;
	backup_log('S3 upload OK: s3://' . S3_BUCKET . '/' . $s3Key);
} catch (AwsException $e) {
	$uploadErr = $e->getMessage();
	backup_log('FATAL: S3 upload failed: ' . $uploadErr);
}

@unlink($localGz);

$presignUrl = '';
if ($uploadOk) {
	try {
		$cmd = $s3->getCommand('GetObject', [
			'Bucket' => S3_BUCKET,
			'Key' => $s3Key,
		]);
		$request = $s3->createPresignedRequest($cmd, '+7 days');
		$presignUrl = (string) $request->getUri();
	} catch (Throwable $e) {
		backup_log('WARN: presign failed: ' . $e->getMessage());
	}
}

$deleted = 0;
if ($uploadOk && $retentionDays > 0) {
	try {
		$cutoff = time() - ($retentionDays * 86400);
		$paginator = $s3->getPaginator('ListObjectsV2', [
			'Bucket' => S3_BUCKET,
			'Prefix' => $s3Prefix,
		]);
		foreach ($paginator as $page) {
			foreach ($page['Contents'] ?? [] as $obj) {
				$key = (string) ($obj['Key'] ?? '');
				if ($key === '' || $key === $s3Key) {
					continue;
				}
				$lm = isset($obj['LastModified']) ? strtotime((string) $obj['LastModified']) : false;
				if ($lm !== false && $lm < $cutoff) {
					$s3->deleteObject(['Bucket' => S3_BUCKET, 'Key' => $key]);
					$deleted++;
					backup_log("Retention deleted: {$key}");
				}
			}
		}
		if ($deleted > 0) {
			backup_log("Retention: removed {$deleted} object(s) older than {$retentionDays} days");
		}
	} catch (Throwable $e) {
		backup_log('WARN: retention cleanup failed: ' . $e->getMessage());
	}
}

$hostLabel = gethostname() ?: 'server';
$s3Uri = 's3://' . S3_BUCKET . '/' . $s3Key;

if ($uploadOk) {
	$subject = '[CreditLab] Daily DB backup OK — ' . $dbName . ' (' . $sizeHuman . ')';
	$html = '<html><body>'
		. '<h2>CreditLab daily database backup</h2>'
		. '<p><strong>Status:</strong> Success</p>'
		. '<ul>'
		. '<li><strong>Database:</strong> ' . htmlspecialchars($dbName) . '</li>'
		. '<li><strong>Host:</strong> ' . htmlspecialchars($hostLabel) . '</li>'
		. '<li><strong>When:</strong> ' . htmlspecialchars(date('Y-m-d H:i:s T')) . '</li>'
		. '<li><strong>Size:</strong> ' . htmlspecialchars($sizeHuman) . '</li>'
		. '<li><strong>S3:</strong> <code>' . htmlspecialchars($s3Uri) . '</code></li>'
		. '<li><strong>Retention:</strong> ' . (int) $retentionDays . ' days'
		. ($deleted > 0 ? ' (deleted ' . (int) $deleted . ' old)' : '') . '</li>'
		. '</ul>';
	if ($presignUrl !== '') {
		$html .= '<p><strong>Download (valid 7 days):</strong><br><a href="'
			. htmlspecialchars($presignUrl) . '">' . htmlspecialchars($presignUrl) . '</a></p>';
	}
	$html .= '<p>The dump is stored privately in S3 (not attached — too large for email).</p>'
		. '</body></html>';
	$text = "CreditLab daily DB backup OK\nDB={$dbName}\nSize={$sizeHuman}\n{$s3Uri}\n{$presignUrl}\n";
	$mailOk = backup_send_mail($recipient, $subject, $html, $text);
	backup_log($mailOk ? "Email sent to {$recipient}" : "Email FAILED to {$recipient}");
	backup_log('=== Daily DB backup done ===');
	exit(0);
}

$subject = '[CreditLab] Daily DB backup FAILED — S3 upload';
$html = '<html><body><h2>Backup failed</h2><p>Dump succeeded but S3 upload failed.</p>'
	. '<p><code>' . htmlspecialchars($uploadErr) . '</code></p></body></html>';
backup_send_mail($recipient, $subject, $html, "S3 upload failed: {$uploadErr}");
backup_log('=== Daily DB backup FAILED ===');
exit(1);

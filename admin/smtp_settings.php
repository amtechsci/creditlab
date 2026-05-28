<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/smtp_settings.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

creditlab_ensure_smtp_settings_table();
$settings = creditlab_get_smtp_settings();
$flash = '';

if (isset($_POST['save_smtp'])) {
	$ok = creditlab_save_smtp_settings([
		'smtp_host' => towreal($_POST['smtp_host'] ?? ''),
		'smtp_port' => (int) ($_POST['smtp_port'] ?? 465),
		'smtp_user' => towreal($_POST['smtp_user'] ?? ''),
		'smtp_password' => $_POST['smtp_password'] ?? '',
		'smtp_secure' => towreal($_POST['smtp_secure'] ?? 'ssl'),
		'from_name' => towreal($_POST['from_name'] ?? 'CreditLab'),
	]);

	if ($ok) {
		$settings = creditlab_get_smtp_settings();
		$flash = 'success:SMTP settings saved successfully.';
	} else {
		$flash = 'error:Failed to save SMTP settings.';
	}
}

if (isset($_POST['test_smtp'])) {
	require_once __DIR__ . '/../config/mail.php';

	$testEmail = trim($_POST['test_email'] ?? '');
	if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
		$flash = 'error:Enter a valid test email address.';
	} elseif (!creditlab_smtp_password_is_set()) {
		$flash = 'error:SMTP password is not configured.';
	} else {
		$mail = new PHPMailer(true);
		try {
			$mail->isSMTP();
			$mail->Host = MAIL_SMTP_HOST;
			$mail->Port = (string) MAIL_SMTP_PORT;
			$mail->SMTPAuth = true;
			$mail->Username = MAIL_SMTP_USER;
			$mail->Password = MAIL_SMTP_PASSWORD;
			if (MAIL_SMTP_SECURE !== '') {
				$mail->SMTPSecure = MAIL_SMTP_SECURE;
			}
			$mail->Timeout = 10;
			$mail->setFrom(MAIL_SMTP_USER, MAIL_FROM_NAME);
			$mail->addAddress($testEmail);
			$mail->Subject = 'CreditLab SMTP test';
			$mail->isHTML(true);
			$mail->Body = '<p>This is a test email from CreditLab SMTP settings.</p><p>Sent at ' . date('Y-m-d H:i:s') . '</p>';

			if ($mail->send()) {
				$flash = 'success:Test email sent to ' . htmlspecialchars($testEmail, ENT_QUOTES, 'UTF-8') . '.';
			} else {
				$flash = 'error:Test email failed.';
			}
		} catch (Throwable $e) {
			$flash = 'error:Test email failed: ' . $e->getMessage();
		}
	}
}

$updatedAt = '';
$row = towfetch(towquery('SELECT updated_at FROM smtp_settings WHERE id = 1 LIMIT 1'));
if ($row && !empty($row['updated_at'])) {
	$updatedAt = $row['updated_at'];
}

$flashType = '';
$flashMessage = '';
if ($flash !== '') {
	[$flashType, $flashMessage] = explode(':', $flash, 2);
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>SMTP Settings - Admin</title>
	<style>
		.smtp-card {
			margin-bottom: 24px;
			padding: 24px;
			border: 1px solid #ddd;
			border-radius: 8px;
			background: #fff;
		}
		.info-box {
			padding: 15px;
			background-color: #FFF3CD;
			border-left: 4px solid #FFC107;
			border-radius: 5px;
			margin-bottom: 24px;
		}
		.alert-success, .alert-error {
			padding: 12px 16px;
			border-radius: 5px;
			margin-bottom: 20px;
		}
		.alert-success {
			background: #E8F5E9;
			border-left: 4px solid #4CAF50;
			color: #2E7D32;
		}
		.alert-error {
			background: #FFEBEE;
			border-left: 4px solid #F44336;
			color: #C62828;
		}
	</style>
</head>
<body>
	<?php include_once 'Left_menu.php'; ?>
	<?php include_once 'welcome.php'; ?>

	<div class="all-content-wrapper">
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-12">
					<div class="breadcome-area">
						<div class="breadcome-list">
							<h2><i class="fa fa-envelope"></i> SMTP Settings</h2>
							<p>Manage outgoing email (loan agreements, no-due certificates, reports)</p>
						</div>
					</div>
				</div>
			</div>

			<?php if ($flashMessage !== ''): ?>
				<div class="row">
					<div class="col-lg-12">
						<div class="<?= $flashType === 'success' ? 'alert-success' : 'alert-error' ?>">
							<?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="row">
				<div class="col-lg-12">
					<div class="info-box">
						<strong>How it works:</strong> Settings saved here are used across the app (PDF mailer, cron reports, etc.).
						Values from <code>.env</code> are used only as fallback when a field is empty in the database.
						<?php if ($updatedAt !== ''): ?>
							<br><strong>Last updated:</strong> <?= htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8') ?>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-8">
					<div class="smtp-card">
						<form method="post">
							<div class="form-group">
								<label>SMTP Host</label>
								<input type="text" name="smtp_host" class="form-control" required
									value="<?= htmlspecialchars($settings['smtp_host'], ENT_QUOTES, 'UTF-8') ?>">
							</div>

							<div class="form-group">
								<label>SMTP Port</label>
								<input type="number" name="smtp_port" class="form-control" min="1" max="65535" required
									value="<?= (int) $settings['smtp_port'] ?>">
							</div>

							<div class="form-group">
								<label>SMTP Username / From Email</label>
								<input type="email" name="smtp_user" class="form-control" required
									value="<?= htmlspecialchars($settings['smtp_user'], ENT_QUOTES, 'UTF-8') ?>">
							</div>

							<div class="form-group">
								<label>SMTP Password</label>
								<input type="password" name="smtp_password" class="form-control" autocomplete="new-password"
									placeholder="<?= creditlab_smtp_password_is_set() ? 'Leave blank to keep current password' : 'Enter SMTP password' ?>">
							</div>

							<div class="form-group">
								<label>Encryption</label>
								<select name="smtp_secure" class="form-control">
									<option value="ssl" <?= $settings['smtp_secure'] === 'ssl' ? 'selected' : '' ?>>SSL (port 465)</option>
									<option value="tls" <?= $settings['smtp_secure'] === 'tls' ? 'selected' : '' ?>>TLS (port 587)</option>
									<option value="" <?= $settings['smtp_secure'] === '' ? 'selected' : '' ?>>None</option>
								</select>
							</div>

							<div class="form-group">
								<label>From Name</label>
								<input type="text" name="from_name" class="form-control" required
									value="<?= htmlspecialchars($settings['from_name'], ENT_QUOTES, 'UTF-8') ?>">
							</div>

							<button type="submit" name="save_smtp" class="btn btn-primary">
								<i class="fa fa-save"></i> Save SMTP Settings
							</button>
						</form>
					</div>
				</div>

				<div class="col-lg-4">
					<div class="smtp-card">
						<h4>Send Test Email</h4>
						<p class="text-muted">Verify the current settings without saving again.</p>
						<form method="post">
							<div class="form-group">
								<label>Test recipient</label>
								<input type="email" name="test_email" class="form-control" required
									placeholder="admin@example.com"
									value="<?= htmlspecialchars($user_email ?? '', ENT_QUOTES, 'UTF-8') ?>">
							</div>
							<button type="submit" name="test_smtp" class="btn btn-success">
								<i class="fa fa-paper-plane"></i> Send Test
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php include_once 'foot.php'; ?>
</body>
</html>

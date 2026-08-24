<?php
require_once __DIR__ . '/../lib/smtp_settings.php';
require_once __DIR__ . '/../lib/lsp_partners.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function creditlab_admin_settings_redirect($tab, $type, $message) {
    $_SESSION['settings_flash'] = ['type' => $type, 'message' => $message];
    header('Location: settings.php?tab=' . urlencode($tab));
    exit;
}

function server_health_tail_file(string $path, int $lines = 80): string
{
    if (!is_readable($path)) {
        return "(not readable: $path)";
    }
    $content = @file($path, FILE_IGNORE_NEW_LINES);
    if ($content === false) {
        return "(failed to read: $path)";
    }
    return implode("\n", array_slice($content, -$lines));
}

function server_health_try_load(string $path): string
{
    return is_readable($path) ? server_health_tail_file($path, 80) : '';
}

$allowed_tabs = ['smtp', 'whatsapp', 'pdf', 'lsp', 'health'];
$active_tab = isset($_GET['tab']) ? strtolower(trim($_GET['tab'])) : 'smtp';
if (!in_array($active_tab, $allowed_tabs, true)) {
    $active_tab = 'smtp';
}

$pdf_types = [
    'grievanceredressal' => ['name' => 'Grievance Redressal Policy', 'filename' => 'grievanceredressal.pdf'],
    'policy' => ['name' => 'Privacy Policy', 'filename' => 'policy.pdf'],
    'fair_practice_code' => ['name' => 'Fair Practice Code', 'filename' => 'FairPracticeCodeSMPL.pdf'],
    'it_policy' => ['name' => 'IT Policy', 'filename' => 'it_policy.pdf'],
    'fees_policy' => ['name' => 'Fees Policy', 'filename' => 'fees_policy.pdf'],
    'refund_cancellation' => ['name' => 'Refund & Cancellation Policy', 'filename' => 'RefundCancellationPolicy.pdf'],
];

$page_groups = [
    1 => [
        'name' => 'Start to Loan Apply Pages',
        'description' => 'Shown to users during registration, profile setup, and loan application',
        'pages' => 'Index, Welcome, Registration, Profile, Apply',
    ],
    2 => [
        'name' => 'Disbursal & Active Loan Pages',
        'description' => 'Shown to users after loan approval and during active loan period',
        'pages' => 'Dashboard, Loan Agreement, Payment, Disbursal',
    ],
    3 => [
        'name' => 'Account Manager & Recovery Pages',
        'description' => 'Shown to users with overdue loans or in recovery',
        'pages' => 'Account Manager Dashboard, Recovery Pages',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../db.php';
    if (!isset($admin)) {
        header('location:/account/login.php');
        exit;
    }
    creditlab_ensure_smtp_settings_table();
}

if (isset($_POST['save_smtp'])) {
    $ok = creditlab_save_smtp_settings([
        'smtp_host' => towreal($_POST['smtp_host'] ?? ''),
        'smtp_port' => (int) ($_POST['smtp_port'] ?? 465),
        'smtp_user' => towreal($_POST['smtp_user'] ?? ''),
        'smtp_password' => $_POST['smtp_password'] ?? '',
        'smtp_secure' => towreal($_POST['smtp_secure'] ?? 'ssl'),
        'from_name' => towreal($_POST['from_name'] ?? 'CreditLab'),
    ]);
    creditlab_admin_settings_redirect(
        'smtp',
        $ok ? 'success' : 'error',
        $ok ? 'SMTP settings saved successfully.' : 'Failed to save SMTP settings.'
    );
}

if (isset($_POST['test_smtp'])) {
    require_once __DIR__ . '/../config/mail.php';

    $testEmail = trim($_POST['test_email'] ?? '');
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        creditlab_admin_settings_redirect('smtp', 'error', 'Enter a valid test email address.');
    }
    if (!creditlab_smtp_password_is_set()) {
        creditlab_admin_settings_redirect('smtp', 'error', 'SMTP password is not configured.');
    }

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
            creditlab_admin_settings_redirect('smtp', 'success', 'Test email sent to ' . $testEmail . '.');
        }
        creditlab_admin_settings_redirect('smtp', 'error', 'Test email failed.');
    } catch (Throwable $e) {
        creditlab_admin_settings_redirect('smtp', 'error', 'Test email failed: ' . $e->getMessage());
    }
}

if (isset($_POST['update_whatsapp'])) {
    $page_id = (int) ($_POST['page_id'] ?? 0);
    $wa_phone = towreal($_POST['wa_phone'] ?? '');

    if (!preg_match('/^[6-9][0-9]{9}$/', $wa_phone)) {
        creditlab_admin_settings_redirect('whatsapp', 'error', 'Invalid phone number. Must be 10 digits starting with 6-9.');
    }
    if (!in_array($page_id, [1, 2, 3], true)) {
        creditlab_admin_settings_redirect('whatsapp', 'error', 'Invalid page group.');
    }

    $update_query = "UPDATE `whatsapp_no` SET `wa_phone` = '$wa_phone' WHERE `page_id` = '$page_id'";
    if (towquery($update_query)) {
        creditlab_admin_settings_redirect('whatsapp', 'success', 'WhatsApp number updated successfully.');
    }
    creditlab_admin_settings_redirect('whatsapp', 'error', 'Error updating WhatsApp number.');
}

if (isset($_POST['update_pdf'])) {
    $pdf_type = towreal($_POST['pdf_type'] ?? '');
    $allowed_pdfs = [];
    foreach ($pdf_types as $key => $info) {
        $allowed_pdfs[$key] = $info['filename'];
    }

    if (!isset($allowed_pdfs[$pdf_type])) {
        creditlab_admin_settings_redirect('pdf', 'error', 'Invalid PDF type.');
    }

    $pdf_filename = $allowed_pdfs[$pdf_type];
    $target_file = __DIR__ . '/../' . $pdf_filename;

    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        creditlab_admin_settings_redirect('pdf', 'error', 'Please select a PDF file to upload.');
    }

    $uploaded_file = $_FILES['pdf_file'];
    $file_ext = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'pdf') {
        creditlab_admin_settings_redirect('pdf', 'error', 'Only PDF files are allowed.');
    }
    if ($uploaded_file['size'] > 10 * 1024 * 1024) {
        creditlab_admin_settings_redirect('pdf', 'error', 'File size must be less than 10MB.');
    }

    $backup_file = null;
    if (file_exists($target_file)) {
        $backup_file = $target_file . '.backup.' . date('YmdHis');
        @copy($target_file, $backup_file);
    }

    if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
        creditlab_admin_settings_redirect('pdf', 'success', 'PDF file updated successfully. Existing links now use the new file.');
    }

    if ($backup_file && file_exists($backup_file)) {
        @copy($backup_file, $target_file);
    }
    creditlab_admin_settings_redirect('pdf', 'error', 'Error uploading file. Please check file permissions.');
}

if (isset($_POST['save_partner'])) {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $ok = creditlab_save_lsp_partner([
        'name' => $_POST['name'] ?? '',
        'category' => $_POST['category'] ?? '',
        'status' => $_POST['status'] ?? 'Active',
        'sort_order' => $_POST['sort_order'] ?? 0,
        'active' => isset($_POST['active']) ? 1 : 0,
    ], $id > 0 ? $id : null);
    creditlab_admin_settings_redirect(
        'lsp',
        $ok ? 'success' : 'error',
        $ok
            ? ($id > 0 ? 'Partner updated successfully.' : 'Partner added successfully.')
            : 'Could not save partner. Please fill all required fields.'
    );
}

if (isset($_POST['delete_partner'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $ok = $id > 0 && creditlab_delete_lsp_partner($id);
    creditlab_admin_settings_redirect(
        'lsp',
        $ok ? 'success' : 'error',
        $ok ? 'Partner deleted successfully.' : 'Could not delete partner.'
    );
}

include_once 'head.php';
creditlab_ensure_smtp_settings_table();

$settings = creditlab_get_smtp_settings();
$updatedAt = '';
$row = towfetch(towquery('SELECT updated_at FROM smtp_settings WHERE id = 1 LIMIT 1'));
if ($row && !empty($row['updated_at'])) {
    $updatedAt = $row['updated_at'];
}

$whatsapp_numbers = [];
$query = towquery("SELECT * FROM `whatsapp_no` ORDER BY `page_id` ASC");
if ($query) {
    while ($wa_row = towfetch($query)) {
        $whatsapp_numbers[$wa_row['page_id']] = $wa_row;
    }
}

$flashType = '';
$flashMessage = '';
if (!empty($_SESSION['settings_flash']) && is_array($_SESSION['settings_flash'])) {
    $flashType = $_SESSION['settings_flash']['type'] ?? '';
    $flashMessage = $_SESSION['settings_flash']['message'] ?? '';
    unset($_SESSION['settings_flash']);
}

$edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit_partner = ($active_tab === 'lsp' && $edit_id > 0) ? creditlab_get_lsp_partner($edit_id) : null;
$partners = creditlab_get_lsp_partners(false);

$dbStart = microtime(true);
$dbOk = isset($db) && @mysqli_ping($db);
$dbMs = round((microtime(true) - $dbStart) * 1000, 2);
$pendingLoans = 0;
$activeUsers = 0;
if ($dbOk) {
    $r = towfetch(towquery("SELECT COUNT(*) AS c FROM loan_apply WHERE status IN ('pending','follow up')"));
    $pendingLoans = (int) ($r['c'] ?? 0);
    $r = towfetch(towquery("SELECT COUNT(*) AS c FROM user WHERE active=1"));
    $activeUsers = (int) ($r['c'] ?? 0);
}
$logCandidates = [
    'Nginx error' => '/var/log/nginx/error.log',
    'PHP-FPM error' => '/var/log/php8.3-fpm.log',
    'PHP-FPM alt' => '/var/log/php-fpm/error.log',
    'PHP slow log' => '/var/log/php8.3-fpm-slow.log',
    'MariaDB error' => '/var/log/mysql/error.log',
    'Syslog' => '/var/log/syslog',
];
$logSections = [];
foreach ($logCandidates as $label => $path) {
    $text = server_health_try_load($path);
    if ($text !== '') {
        $logSections[$label . " ($path)"] = $text;
    }
}
$load = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
?>
<body>
<?php include_once 'Left_menu.php'; ?>
<?php include_once 'welcome.php'; ?>
<?php include_once 'm_menu.php'; ?>

            <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <h2 style="margin:0 0 6px;"><i class="fa fa-cog"></i> Settings</h2>
                                <p style="margin:0;color:#666;">SMTP, WhatsApp, PDFs, LSP partners, and server health</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .settings-wrap { padding-bottom: 40px; }
            .settings-nav.nav-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 0;
                margin: 0 0 22px;
                padding: 0;
                list-style: none;
                border: 0;
                border-bottom: 2px solid #e8ecef;
                background: #fff;
            }
            .settings-nav > li { float: none; margin: 0; }
            .settings-nav > li > a {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 14px 22px;
                color: #6b7280;
                font-weight: 600;
                font-size: 15px;
                text-decoration: none;
                border: 0 !important;
                border-bottom: 3px solid transparent !important;
                margin-bottom: -2px;
                background: transparent !important;
                border-radius: 0;
            }
            .settings-nav > li > a:hover,
            .settings-nav > li > a:focus {
                background: transparent !important;
                color: #00a57d;
                text-decoration: none;
            }
            .settings-nav > li.active > a,
            .settings-nav > li.active > a:hover,
            .settings-nav > li.active > a:focus {
                color: #00a57d;
                background: transparent !important;
                border-bottom-color: #00c195 !important;
            }
            .settings-card {
                background: #fff;
                border: 1px solid #e6e8eb;
                border-radius: 10px;
                padding: 22px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(16,24,40,.04);
            }
            .settings-card h3,
            .settings-card h4 {
                margin-top: 0;
                margin-bottom: 14px;
                font-size: 18px;
                font-weight: 700;
            }
            .settings-info {
                padding: 14px 16px;
                background: #FFF8E1;
                border-left: 4px solid #FFC107;
                border-radius: 6px;
                margin-bottom: 20px;
                color: #5d4e16;
            }
            .settings-alert-success,
            .settings-alert-error {
                padding: 12px 16px;
                border-radius: 6px;
                margin-bottom: 20px;
            }
            .settings-alert-success {
                background: #E8F5E9;
                border-left: 4px solid #4CAF50;
                color: #1B5E20;
            }
            .settings-alert-error {
                background: #FFEBEE;
                border-left: 4px solid #F44336;
                color: #B71C1C;
            }
            .wa-card { border-top: 4px solid #25D366; }
            .wa-card h3 { color: #128C7E; }
            .wa-current {
                padding: 14px 16px;
                background: #E8F5E9;
                border-left: 4px solid #25D366;
                border-radius: 6px;
                margin-bottom: 16px;
            }
            .wa-pages {
                padding: 10px 12px;
                background: #f6f7f8;
                border-radius: 6px;
                margin-bottom: 14px;
                color: #555;
                font-size: 13px;
            }
            .pdf-meta {
                padding: 12px 14px;
                background: #f7f8fa;
                border-radius: 6px;
                margin-bottom: 14px;
                line-height: 1.7;
            }
            .pdf-meta a { color: #006DF0; }
            .status-ok { color: #2E7D32; font-weight: 600; }
            .status-missing { color: #C62828; font-weight: 600; }
            .settings-btn-wa {
                background: #25D366;
                border-color: #25D366;
                color: #fff;
            }
            .settings-btn-wa:hover,
            .settings-btn-wa:focus {
                background: #1EBE57;
                border-color: #1EBE57;
                color: #fff;
            }
            .preview-chip {
                display: inline-block;
                background: #25D366;
                color: #fff;
                padding: 6px 12px;
                border-radius: 5px;
                font-weight: 600;
            }
            .health-pre {
                background: #111;
                color: #eee;
                padding: 12px;
                max-height: 400px;
                overflow: auto;
                font-size: 11px;
            }
            .health-ssh {
                background: #f5f5f5;
                padding: 12px;
                overflow: auto;
            }
            .lsp-actions form { display: inline; }
            @media (max-width: 767px) {
                .settings-nav > li > a { padding: 12px 14px; font-size: 13px; }
            }
        </style>

        <div class="single-pro-review-area mt-t-30 mg-b-15 settings-wrap">
            <div class="container-fluid">
                <?php if ($flashMessage !== ''): ?>
                    <div class="<?= $flashType === 'success' ? 'settings-alert-success' : 'settings-alert-error' ?>">
                        <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <ul class="nav nav-tabs settings-nav" role="tablist">
                    <li class="<?= $active_tab === 'smtp' ? 'active' : '' ?>">
                        <a href="#smtp" data-toggle="tab"><i class="fa fa-envelope"></i> SMTP</a>
                    </li>
                    <li class="<?= $active_tab === 'whatsapp' ? 'active' : '' ?>">
                        <a href="#whatsapp" data-toggle="tab"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                    </li>
                    <li class="<?= $active_tab === 'pdf' ? 'active' : '' ?>">
                        <a href="#pdf" data-toggle="tab"><i class="fa fa-file-pdf-o"></i> PDF</a>
                    </li>
                    <li class="<?= $active_tab === 'lsp' ? 'active' : '' ?>">
                        <a href="#lsp" data-toggle="tab"><i class="fa fa-handshake-o"></i> LSP Partners</a>
                    </li>
                    <li class="<?= $active_tab === 'health' ? 'active' : '' ?>">
                        <a href="#health" data-toggle="tab"><i class="fa fa-heartbeat"></i> Server Health</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade <?= $active_tab === 'smtp' ? 'in active' : '' ?>" id="smtp">
                        <div class="settings-info">
                            <strong>How it works:</strong> Settings saved here are used across the app (PDF mailer, cron reports, etc.).
                            Values from <code>.env</code> are used only as fallback when a field is empty in the database.
                            <?php if ($updatedAt !== ''): ?>
                                <br><strong>Last updated:</strong> <?= htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-lg-8 col-md-7 col-sm-12 col-xs-12">
                                <div class="settings-card">
                                    <h4>Outgoing email</h4>
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
                                        <button type="submit" name="save_smtp" class="btn btn-success">
                                            <i class="fa fa-save"></i> Save SMTP Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-5 col-sm-12 col-xs-12">
                                <div class="settings-card">
                                    <h4>Send test email</h4>
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

                    <div role="tabpanel" class="tab-pane fade <?= $active_tab === 'whatsapp' ? 'in active' : '' ?>" id="whatsapp">
                        <div class="settings-info">
                            <strong><i class="fa fa-info-circle"></i> How it works:</strong>
                            Users see different WhatsApp numbers based on which section they are in, so queries reach the right team.
                            The user's Customer ID (CLID) is included automatically in the WhatsApp message.
                        </div>

                        <?php foreach ($page_groups as $page_id => $group_info): ?>
                            <?php $current = isset($whatsapp_numbers[$page_id]) ? $whatsapp_numbers[$page_id] : null; ?>
                            <div class="settings-card wa-card">
                                <h3><i class="fab fa-whatsapp"></i> <?= htmlspecialchars($group_info['name']) ?></h3>
                                <div class="wa-pages">
                                    <strong>Description:</strong> <?= htmlspecialchars($group_info['description']) ?><br>
                                    <strong>Pages:</strong> <?= htmlspecialchars($group_info['pages']) ?>
                                </div>
                                <?php if ($current): ?>
                                    <div class="wa-current">
                                        <strong>Current number:</strong>
                                        <span style="font-size:20px;color:#1565C0;">+91 <?= htmlspecialchars($current['wa_phone']) ?></span>
                                        <div style="margin-top:8px;">
                                            <a href="https://wa.me/91<?= htmlspecialchars($current['wa_phone']) ?>?text=Test%20message%20from%20Admin" target="_blank" rel="noopener" style="color:#25D366;font-weight:700;">
                                                <i class="fab fa-whatsapp"></i> Test this number on WhatsApp
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="settings-alert-error">No WhatsApp number configured for this page group.</div>
                                <?php endif; ?>
                                <form method="post" class="wa-update-form">
                                    <input type="hidden" name="page_id" value="<?= (int) $page_id ?>">
                                    <div class="form-group">
                                        <label>Update WhatsApp number</label>
                                        <div class="input-group">
                                            <span class="input-group-addon">+91</span>
                                            <input
                                                type="text"
                                                name="wa_phone"
                                                class="form-control"
                                                placeholder="Enter 10-digit mobile number"
                                                pattern="[6-9][0-9]{9}"
                                                maxlength="10"
                                                value="<?= $current ? htmlspecialchars($current['wa_phone']) : '' ?>"
                                                required
                                            >
                                        </div>
                                        <small class="help-block">Must be a valid 10-digit Indian mobile number (starting with 6–9).</small>
                                    </div>
                                    <button type="submit" name="update_whatsapp" class="btn settings-btn-wa">
                                        <i class="fa fa-save"></i> Update WhatsApp Number
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>

                        <div class="settings-card" style="background:#f7f8fa;">
                            <h4>Preview: how users see it</h4>
                            <p>
                                Users will see:
                                <span class="preview-chip">Contact us on whatsapp <img src="/ws.svg" alt="" style="width:18px;vertical-align:middle;"></span>
                            </p>
                            <p class="text-muted" style="margin-bottom:0;">
                                When clicked, WhatsApp opens with their Customer ID pre-filled:
                                <em>"CLID : CL12345 I need Help in ..."</em>
                            </p>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane fade <?= $active_tab === 'pdf' ? 'in active' : '' ?>" id="pdf">
                        <div class="settings-info">
                            <strong>How it works:</strong> Uploading a file replaces the document of the same name on the site.
                            Maximum size is 10MB. Existing public links keep working with the new file.
                        </div>
                        <div class="row">
                            <?php foreach ($pdf_types as $pdf_type => $pdf_info): ?>
                                <?php
                                $pdf_name = $pdf_info['name'];
                                $pdf_filename = $pdf_info['filename'];
                                $base_url = getAppUrl();
                                $current_url = $base_url . '/' . $pdf_filename;
                                $file_path = __DIR__ . '/../' . $pdf_filename;
                                $file_exists = file_exists($file_path);
                                $file_size = $file_exists ? filesize($file_path) : 0;
                                $file_date = $file_exists ? date('Y-m-d H:i:s', filemtime($file_path)) : 'Not found';
                                ?>
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <div class="settings-card">
                                        <h4><i class="fa fa-file-pdf-o" style="color:#C62828;"></i> <?= htmlspecialchars($pdf_name) ?></h4>
                                        <div class="pdf-meta">
                                            <strong>File name:</strong> <?= htmlspecialchars($pdf_filename) ?><br>
                                            <strong>Current URL:</strong>
                                            <a href="<?= htmlspecialchars($current_url) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($current_url) ?></a><br>
                                            <strong>Status:</strong>
                                            <?php if ($file_exists): ?>
                                                <span class="status-ok">File exists</span><br>
                                                <strong>Size:</strong> <?= number_format($file_size / 1024, 2) ?> KB<br>
                                                <strong>Last modified:</strong> <?= htmlspecialchars($file_date) ?>
                                            <?php else: ?>
                                                <span class="status-missing">File not found</span>
                                            <?php endif; ?>
                                        </div>
                                        <form method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="pdf_type" value="<?= htmlspecialchars($pdf_type) ?>">
                                            <div class="form-group">
                                                <label>Upload new PDF</label>
                                                <input type="file" name="pdf_file" accept=".pdf,application/pdf" class="form-control" required>
                                                <small class="help-block">This replaces <?= htmlspecialchars($pdf_filename) ?> for all existing links.</small>
                                            </div>
                                            <button type="submit" name="update_pdf" class="btn btn-success">
                                                <i class="fa fa-upload"></i> Upload &amp; Replace
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane fade <?= $active_tab === 'lsp' ? 'in active' : '' ?>" id="lsp">
                        <div class="settings-info">
                            Manage the partner list shown on
                            <a href="/lsp.php" target="_blank" rel="noopener">creditlab.in/lsp.php</a>.
                        </div>
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="settings-card">
                                    <h4><?= $edit_partner ? 'Edit partner' : 'Add partner' ?></h4>
                                    <form method="post">
                                        <?php if ($edit_partner) { ?>
                                        <input type="hidden" name="id" value="<?= (int) $edit_partner['id'] ?>">
                                        <?php } ?>
                                        <div class="form-group">
                                            <label>Name of Partner</label>
                                            <input type="text" name="name" class="form-control" required
                                                value="<?= htmlspecialchars($edit_partner['name'] ?? '') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Category / Activities</label>
                                            <input type="text" name="category" class="form-control" required
                                                value="<?= htmlspecialchars($edit_partner['category'] ?? '') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <?php
                                                $statuses = ['Active', 'Inactive'];
                                                $current_status = $edit_partner['status'] ?? 'Active';
                                                foreach ($statuses as $status) {
                                                    $selected = $current_status === $status ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($status) . '" ' . $selected . '>' . htmlspecialchars($status) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Sort order</label>
                                            <input type="number" name="sort_order" class="form-control" min="1"
                                                value="<?= (int) ($edit_partner['sort_order'] ?? (count($partners) + 1)) ?>">
                                        </div>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="active" value="1"
                                                    <?= !isset($edit_partner['active']) || (int) $edit_partner['active'] === 1 ? 'checked' : '' ?>>
                                                Show on public page
                                            </label>
                                        </div>
                                        <button type="submit" name="save_partner" class="btn btn-success" style="margin-top:10px;">
                                            <?= $edit_partner ? 'Update partner' : 'Add partner' ?>
                                        </button>
                                        <?php if ($edit_partner) { ?>
                                        <a href="settings.php?tab=lsp" class="btn btn-default" style="margin-top:10px;">Cancel edit</a>
                                        <?php } ?>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="settings-card">
                            <h4>All partners</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>S.no</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Sort</th>
                                            <th>Visible</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$partners) { ?>
                                        <tr><td colspan="7">No partners added yet.</td></tr>
                                        <?php } ?>
                                        <?php foreach ($partners as $i => $partner) { ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= htmlspecialchars($partner['name']) ?></td>
                                            <td><?= htmlspecialchars($partner['category']) ?></td>
                                            <td><?= htmlspecialchars($partner['status']) ?></td>
                                            <td><?= (int) $partner['sort_order'] ?></td>
                                            <td><?= (int) $partner['active'] === 1 ? 'Yes' : 'No' ?></td>
                                            <td class="lsp-actions">
                                                <a href="settings.php?tab=lsp&amp;edit=<?= (int) $partner['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete this partner?');">
                                                    <input type="hidden" name="id" value="<?= (int) $partner['id'] ?>">
                                                    <button type="submit" name="delete_partner" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane fade <?= $active_tab === 'health' ? 'in active' : '' ?>" id="health">
                        <div class="settings-info">
                            Use this when users report timeouts. A 504 means Nginx gave up waiting for PHP-FPM — workers are usually blocked or the pool is exhausted.
                        </div>
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="settings-card">
                                    <h4>PHP runtime</h4>
                                    <ul>
                                        <li><strong>PHP version:</strong> <?= htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?></li>
                                        <li><strong>max_execution_time:</strong> <?= (int) ini_get('max_execution_time') ?>s</li>
                                        <li><strong>memory_limit:</strong> <?= htmlspecialchars((string) ini_get('memory_limit'), ENT_QUOTES, 'UTF-8') ?></li>
                                        <li><strong>pm.max_children:</strong> <?= htmlspecialchars(ini_get('pm.max_children') ?: 'n/a (check pool conf)', ENT_QUOTES, 'UTF-8') ?></li>
                                        <li><strong>fastcgi_finish_request:</strong> <?= function_exists('fastcgi_finish_request') ? 'yes' : 'no' ?></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="settings-card">
                                    <h4>Load &amp; database</h4>
                                    <ul>
                                        <li><strong>Load average:</strong> <?= $load ? implode(' / ', array_map('number_format', $load)) : 'unavailable' ?></li>
                                        <li><strong>DB ping:</strong> <?= $dbOk ? 'OK (' . $dbMs . ' ms)' : 'FAILED' ?></li>
                                        <li><strong>Pending/follow-up loans:</strong> <?= $pendingLoans ?></li>
                                        <li><strong>Active users:</strong> <?= $activeUsers ?></li>
                                        <li><strong>Stale sweep lock:</strong> <?= is_file(sys_get_temp_dir() . '/creditlab_stale_loan_sweep.lock') ? date('Y-m-d H:i:s', filemtime(sys_get_temp_dir() . '/creditlab_stale_loan_sweep.lock')) : 'never' ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="settings-card">
                            <h4>Common 504 causes in this app</h4>
                            <ol>
                                <li><strong>PHP-FPM pool full</strong> — all workers busy on slow requests (SMS, PDF mail, heavy admin pages).</li>
                                <li><strong>Upstream timeout</strong> — Nginx <code>fastcgi_read_timeout</code> shorter than PHP work time.</li>
                                <li><strong>Row locks</strong> — staff <code>head.php</code> doing mass UPDATEs (now throttled to 1×/hour).</li>
                                <li><strong>Blocking HTTP</strong> — <code>file_get_contents</code> to <code>/zxc/</code> waiting for PDF+SMTP (now 3s trigger).</li>
                            </ol>
                            <h4>SSH commands (run on production)</h4>
                            <pre class="health-ssh"># 504 errors with upstream timed out
sudo tail -100 /var/log/nginx/error.log | grep -E '504|upstream timed out|connect\(\) failed'

# PHP-FPM pool status (if pm.status_path enabled)
curl -s http://127.0.0.1/status?full | head -40

# Active / stuck PHP workers
ps aux | grep 'php-fpm: pool' | wc -l
sudo tail -50 /var/log/php8.3-fpm-slow.log

# MariaDB locks
sudo mysql -e "SHOW FULL PROCESSLIST;"
sudo mysql -e "SHOW ENGINE INNODB STATUS\G" | grep -A30 'LATEST DETECTED DEADLOCK'

# Live load
uptime && free -h</pre>
                        </div>
                        <?php if (empty($logSections)): ?>
                            <div class="settings-alert-error">Could not read server logs from PHP (permission denied). Use SSH commands above on the production server.</div>
                        <?php else: ?>
                            <?php foreach ($logSections as $title => $text): ?>
                                <div class="settings-card">
                                    <h4><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> <small>(last 80 lines)</small></h4>
                                    <pre class="health-pre"><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></pre>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<?php include_once 'foot.php'; ?>
<script>
    (function () {
        var tabLinks = document.querySelectorAll('.settings-nav a[data-toggle="tab"]');
        for (var i = 0; i < tabLinks.length; i++) {
            tabLinks[i].addEventListener('click', function () {
                var id = this.getAttribute('href').replace('#', '');
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', 'settings.php?tab=' + encodeURIComponent(id));
                }
            });
        }

        document.querySelectorAll('input[name="wa_phone"]').forEach(function (input) {
            input.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            });
        });

        document.querySelectorAll('.wa-update-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var phoneInput = this.querySelector('input[name="wa_phone"]');
                var phone = phoneInput ? phoneInput.value : '';
                if (!/^[6-9][0-9]{9}$/.test(phone)) {
                    e.preventDefault();
                    alert('Invalid phone number. Must start with 6, 7, 8, or 9 and be 10 digits.');
                    return false;
                }
                if (!confirm('Update this WhatsApp number?')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    })();
</script>
</body>
</html>

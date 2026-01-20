<?php
/**
 * Auto Report Email Cron Script
 * 
 * This script runs on:
 * - 15th day of every month at 11:58 PM
 * - Last day of every month at 11:58 PM (30th or 31st as per calendar)
 * 
 * It generates all reports and emails them to support@creditlab.in
 */

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Include database connection
require_once __DIR__ . '/../db.php';

// Include PHPMailer
require_once __DIR__ . '/../zxc/class/class.phpmailer.php';

// Include S3 helper
require_once __DIR__ . '/../lib/s3_aws_sdk.php';

// Configuration
$recipient_email = 'support@creditlab.in';
$from_email = 'documents@creditlab.in';
$from_name = 'CreditLab Reports';

// Check for test/force mode (allows running on any day for testing)
$force_mode = isset($_GET['force']) || isset($argv[1]) && $argv[1] === 'force';
$test_mode = isset($_GET['test']) || isset($argv[1]) && $argv[1] === 'test';

// Get current date
$current_date = date('Y-m-d');
$current_day = (int)date('d');
$current_month = (int)date('m');
$current_year = (int)date('Y');

// Log every execution attempt
$log_file = __DIR__ . '/cron_log.txt';
$log_message = "[" . date('Y-m-d H:i:s') . "] ========================================\n";
$log_message .= "[" . date('Y-m-d H:i:s') . "] Script executed - Day: $current_day, Month: $current_month, Year: $current_year\n";
$log_message .= "[" . date('Y-m-d H:i:s') . "] Force mode: " . ($force_mode ? 'YES' : 'NO') . ", Test mode: " . ($test_mode ? 'YES' : 'NO') . "\n";

// Check database connectivity
if (!isset($db) || !@mysqli_ping($db)) {
    $log_message .= "[" . date('Y-m-d H:i:s') . "] ERROR: Database connection failed or lost\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    exit(1);
}
$log_message .= "[" . date('Y-m-d H:i:s') . "] Database connection: OK\n";

// Check if it's the 15th or last day of the month
$is_15th = ($current_day == 15);
$is_last_day = ($current_day == date('t')); // date('t') returns the number of days in the current month
$log_message .= "[" . date('Y-m-d H:i:s') . "] Is 15th: " . ($is_15th ? 'YES' : 'NO') . ", Is Last Day: " . ($is_last_day ? 'YES' : 'NO') . "\n";

if (!$is_15th && !$is_last_day && !$force_mode && !$test_mode) {
    // Not a scheduled day, log and exit
    $log_message .= "[" . date('Y-m-d H:i:s') . "] Not a scheduled day (15th or last day), exiting.\n";
    $log_message .= "[" . date('Y-m-d H:i:s') . "] Next scheduled run: 15th or " . date('t') . " of " . date('F Y') . "\n";
    $log_message .= "[" . date('Y-m-d H:i:s') . "] To force run: php auto_report_email.php force\n";
    $log_message .= "[" . date('Y-m-d H:i:s') . "] To test run: php auto_report_email.php test\n\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    exit(0);
}

if ($force_mode || $test_mode) {
    $log_message .= "[" . date('Y-m-d H:i:s') . "] " . ($force_mode ? 'FORCE' : 'TEST') . " mode enabled - proceeding despite date check\n";
}

$log_message .= "[" . date('Y-m-d H:i:s') . "] Proceeding with report generation.\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

// Determine date range based on the day
if ($test_mode) {
    // Test mode: Use last 7 days for testing
    $from_date = date('Y-m-d', strtotime('-7 days'));
    $to_date = date('Y-m-d');
    $report_period = "TEST MODE - Last 7 days (" . $from_date . " to " . $to_date . ")";
    $log_message .= "[" . date('Y-m-d H:i:s') . "] TEST MODE: Using date range $from_date to $to_date\n";
} elseif ($is_15th || ($force_mode && $current_day <= 15)) {
    // For 15th: Report from 1st day of month (00:02 AM) to 15th day (11:58 PM)
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-15');
    $report_period = "1st to 15th of " . date('F Y') . " (00:02 AM to 11:58 PM)";
} else {
    // For last day: Report from 16th day (00:02 AM) to last day (11:58 PM)
    $from_date = date('Y-m-16');
    $to_date = date('Y-m-t'); // Last day of current month
    $report_period = "16th to last day of " . date('F Y') . " (00:02 AM to 11:58 PM)";
}

// Ensure download_links table exists
$log_message = "[" . date('Y-m-d H:i:s') . "] Creating/checking download_links table...\n";
file_put_contents($log_file, $log_message, FILE_APPEND);
$table_result = createDownloadLinksTable();
if ($table_result) {
    $log_message = "[" . date('Y-m-d H:i:s') . "] Table check/creation successful.\n";
} else {
    $log_message = "[" . date('Y-m-d H:i:s') . "] WARNING: Table check/creation may have failed.\n";
}
file_put_contents($log_file, $log_message, FILE_APPEND);

// Log the execution
$log_message = "[" . date('Y-m-d H:i:s') . "] Auto Report Email Cron Started - Period: $report_period\n";
$log_message .= "[" . date('Y-m-d H:i:s') . "] Date Range: $from_date to $to_date\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

// List of all reports to generate
$reports = [
    'disbursal' => 'Disbursal Report',
    'cleared' => 'Cleared Report',
    'default' => 'Default Report',
    'part_payment' => 'Part Payment Report',
    'settlement' => 'Settlement Report',
    'bs_repayment' => 'BS Repayment Report',
    'bs_disbursal' => 'BS Disbursal Report',
    'applied' => 'Applied Report',
    'recoveryagency' => 'Recovery Agency Report'
];

// Generate all reports and upload to S3
$attachments = [];
$errors = [];
$download_links = []; // Store database IDs for updating email status

foreach ($reports as $report_key => $report_name) {
    try {
        $csv_file = generateReport($report_key, $from_date, $to_date);
        if ($csv_file && file_exists($csv_file)) {
            $file_name = $report_name . ' - ' . date('Y-m-d') . '.csv';
            
            // Upload to S3
            $s3_path = 'reports/' . date('Y/m/') . $report_key . '_' . date('Y-m-d') . '_' . time() . '.csv';
            list($s3_success, $s3_result) = s3_upload_file($csv_file, $s3_path, 'text/csv');
            
            // If upload fails, try using uploadString instead
            if (!$s3_success) {
                $csv_content = file_get_contents($csv_file);
                list($s3_success, $s3_result) = s3_upload_string($csv_content, $s3_path, 'text/csv');
            }
            
            if ($s3_success) {
                // Get S3 URL
                $s3_url = '';
                $s3_key = '';
                
                if (isset($s3_result['ObjectURL'])) {
                    $s3_url = $s3_result['ObjectURL'];
                } elseif (isset($s3_result['@metadata']['effectiveUri'])) {
                    $s3_url = $s3_result['@metadata']['effectiveUri'];
                } else {
                    // Build URL manually if not provided
                    require_once __DIR__ . '/../config_s3.php';
                    $s3_url = 'https://' . S3_BUCKET . '.s3.' . S3_REGION . '.amazonaws.com/' . S3_PREFIX . ltrim($s3_path, '/');
                }
                
                // Get S3 key (ensure proper prefix)
                require_once __DIR__ . '/../config_s3.php';
                $s3_key = S3_PREFIX . ltrim($s3_path, '/');
                
                // Save to database
                $db_id = saveDownloadLink($report_key, $report_name, $s3_url, $s3_key, $file_name, $current_date, $from_date, $to_date, $report_period);
                
                if ($db_id) {
                    $download_links[] = [
                        'id' => $db_id,
                        'report_type' => $report_key,
                        'report_name' => $report_name
                    ];
                    
                    $attachments[] = [
                        'file' => $csv_file,
                        'name' => $file_name,
                        'db_id' => $db_id
                    ];
                    
                    $log_message = "[" . date('Y-m-d H:i:s') . "] Generated and uploaded to S3: $report_name (ID: $db_id)\n";
                    $log_message .= "[" . date('Y-m-d H:i:s') . "] S3 URL: $s3_url\n";
                    file_put_contents($log_file, $log_message, FILE_APPEND);
                } else {
                    $errors[] = "Failed to save download link to database: $report_name";
                    $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: Generated $report_name but failed to save to database\n";
                    $log_message .= "[" . date('Y-m-d H:i:s') . "] S3 upload succeeded but database insert failed\n";
                    file_put_contents($log_file, $log_message, FILE_APPEND);
                }
            } else {
                $error_detail = is_string($s3_result) ? $s3_result : (is_array($s3_result) ? json_encode($s3_result) : 'Unknown error');
                $errors[] = "Failed to upload to S3: $report_name - " . $error_detail;
                $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: Failed to upload $report_name to S3\n";
                $log_message .= "[" . date('Y-m-d H:i:s') . "] Error details: $error_detail\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
            }
        } else {
            $errors[] = "Failed to generate: $report_name";
            $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: Failed to generate $report_name - No CSV file created\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
        }
    } catch (Exception $e) {
        $errors[] = "Error generating $report_name: " . $e->getMessage();
        $log_message = "[" . date('Y-m-d H:i:s') . "] EXCEPTION: $report_name - " . $e->getMessage() . "\n";
        $log_message .= "[" . date('Y-m-d H:i:s') . "] Stack trace: " . $e->getTraceAsString() . "\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

// Send email with all attachments (skip in test mode)
$email_sent = false;
if (!empty($attachments)) {
    if ($test_mode) {
        $log_message = "[" . date('Y-m-d H:i:s') . "] TEST MODE: Skipping email send\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
        $email_sent = false; // Don't send email in test mode
    } else {
        $email_sent = sendEmailWithAttachments($recipient_email, $from_email, $from_name, $report_period, $attachments, $errors, $download_links);
    }
    
    if ($email_sent) {
        // Update database records to mark email as sent
        foreach ($download_links as $link) {
            updateEmailStatus($link['id'], 1);
        }
        $log_message = "[" . date('Y-m-d H:i:s') . "] Email sent successfully to $recipient_email\n";
        $log_message .= "[" . date('Y-m-d H:i:s') . "] Updated " . count($download_links) . " database records with email_sent=1\n";
    } else {
        if ($test_mode) {
            $log_message = "[" . date('Y-m-d H:i:s') . "] TEST MODE: Email not sent (intentionally skipped)\n";
            $log_message .= "[" . date('Y-m-d H:i:s') . "] All reports saved in database for manual retrieval\n";
        } else {
            $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: Failed to send email to $recipient_email - Reports saved in database for manual retrieval\n";
            $log_message .= "[" . date('Y-m-d H:i:s') . "] Check PHPMailer error details in log above\n";
        }
    }
    file_put_contents($log_file, $log_message, FILE_APPEND);
} else {
    $log_message = "[" . date('Y-m-d H:i:s') . "] WARNING: No reports generated, email not sent\n";
    $log_message .= "[" . date('Y-m-d H:i:s') . "] Total errors: " . count($errors) . "\n";
    if (!empty($errors)) {
        foreach ($errors as $error) {
            $log_message .= "[" . date('Y-m-d H:i:s') . "] Error: $error\n";
        }
    }
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Clean up temporary files
foreach ($attachments as $attachment) {
    if (file_exists($attachment['file'])) {
        @unlink($attachment['file']);
    }
}

$log_message = "[" . date('Y-m-d H:i:s') . "] Auto Report Email Cron Completed\n";
$log_message .= "[" . date('Y-m-d H:i:s') . "] Summary - Reports generated: " . count($attachments) . ", Errors: " . count($errors) . ", Email sent: " . ($email_sent ? 'YES' : 'NO') . "\n";
$log_message .= "[" . date('Y-m-d H:i:s') . "] Database records created: " . count($download_links) . "\n\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

exit(0);

/**
 * Create download_links table if it doesn't exist
 */
function createDownloadLinksTable() {
    global $log_file;
    
    $sql = "CREATE TABLE IF NOT EXISTS `download_links` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `report_type` varchar(50) NOT NULL COMMENT 'Type of report (disbursal, cleared, default, etc.)',
      `report_name` varchar(255) NOT NULL COMMENT 'Human-readable report name',
      `s3_url` text NOT NULL COMMENT 'S3 URL of the report file',
      `s3_key` varchar(500) NOT NULL COMMENT 'S3 object key',
      `file_name` varchar(255) NOT NULL COMMENT 'Original file name',
      `report_date` date NOT NULL COMMENT 'Date for which the report was generated',
      `from_date` date NOT NULL COMMENT 'Start date of the report period',
      `to_date` date NOT NULL COMMENT 'End date of the report period',
      `report_period` varchar(100) NOT NULL COMMENT 'Report period description',
      `email_sent` tinyint(1) DEFAULT 0 COMMENT 'Whether email was sent successfully (1=yes, 0=no)',
      `email_sent_at` datetime DEFAULT NULL COMMENT 'When email was sent',
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When record was created',
      PRIMARY KEY (`id`),
      KEY `idx_report_date` (`report_date`),
      KEY `idx_report_type` (`report_type`),
      KEY `idx_email_sent` (`email_sent`),
      KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores S3 URLs for automated report downloads'";
    
    $result = towquery($sql);
    
    if (!$result) {
        $error_msg = "[" . date('Y-m-d H:i:s') . "] ERROR: Failed to create/check download_links table: " . (function_exists('mysqli_error') && isset($GLOBALS['db']) ? mysqli_error($GLOBALS['db']) : 'Unknown error') . "\n";
        if (isset($log_file)) {
            file_put_contents($log_file, $error_msg, FILE_APPEND);
        }
        return false;
    }
    
    return true;
}

/**
 * Generate a report CSV file
 */
function generateReport($report_type, $from_date, $to_date) {
    $temp_dir = sys_get_temp_dir();
    $csv_file = $temp_dir . '/creditlab_' . $report_type . '_' . date('Y-m-d_His') . '.csv';
    
    // Map report types to their file paths
    $report_files = [
        'disbursal' => __DIR__ . '/../downloader/disbursal.php',
        'cleared' => __DIR__ . '/../downloader/cleared.php',
        'default' => __DIR__ . '/../downloader/default.php',
        'part_payment' => __DIR__ . '/../downloader/part_payment.php',
        'settlement' => __DIR__ . '/../downloader/settlement.php',
        'bs_repayment' => __DIR__ . '/../downloader/bs_repayment.php',
        'bs_disbursal' => __DIR__ . '/../downloader/bs_disbursal.php',
        'applied' => __DIR__ . '/../downloader/applied.php',
        'recoveryagency' => __DIR__ . '/../downloader/recoveryagency.php'
    ];
    
    if (!isset($report_files[$report_type])) {
        return false;
    }
    
    $report_file = $report_files[$report_type];
    
    if (!file_exists($report_file)) {
        return false;
    }
    
    // Set GET parameters for date range
    $_GET['from_date'] = $from_date;
    $_GET['to_date'] = $to_date;
    
    // Set a flag to indicate we're running from cron (if not already defined)
    if (!defined('CRON_MODE')) {
        define('CRON_MODE', true);
    }
    
    // Read the report file content
    $file_content = file_get_contents($report_file);
    
    // Calculate absolute paths for includes
    // The report files are in downloader/ and include ../db.php
    // So db.php is at the root level, which from cron/ is ../db.php
    $db_path = realpath(__DIR__ . '/../db.php');
    if (!$db_path || !file_exists($db_path)) {
        global $log_file;
        $error_msg = "[" . date('Y-m-d H:i:s') . "] ERROR: Cannot find db.php at " . __DIR__ . "/../db.php\n";
        if (isset($log_file)) {
            file_put_contents($log_file, $error_msg, FILE_APPEND);
        }
        return false;
    }
    $downloader_dir = dirname($report_file);
    $parent_dir = dirname($downloader_dir);
    
    // Replace relative include paths with absolute paths
    // Convert all includes to include_once/require_once to prevent redeclaration errors
    // Match patterns like: include '../db.php'; or include "../db.php";
    $modified_content = preg_replace_callback(
        "/(include|require|include_once|require_once)\s+['\"]\.\.\/([^'\"]+)['\"]\s*;/i",
        function($matches) use ($parent_dir) {
            $original_type = strtolower($matches[1]);
            $relative_path = $matches[2];
            $absolute_path = realpath($parent_dir . '/' . $relative_path);
            
            // Convert include/require to include_once/require_once to prevent redeclaration
            if ($original_type === 'include' || $original_type === 'require') {
                $include_type = $original_type . '_once';
            } else {
                $include_type = $original_type;
            }
            
            if ($absolute_path && file_exists($absolute_path)) {
                return $include_type . " '" . addslashes($absolute_path) . "';";
            }
            // If path doesn't resolve, try to construct it anyway
            $constructed_path = $parent_dir . '/' . $relative_path;
            return $include_type . " '" . addslashes($constructed_path) . "';";
        },
        $file_content
    );
    
    // Replace php://output with our file path
    $modified_content = str_replace("fopen('php://output', 'w')", "fopen('" . addslashes($csv_file) . "', 'w')", $modified_content);
    $modified_content = str_replace('fopen("php://output", "w")', 'fopen("' . addslashes($csv_file) . '", "w")', $modified_content);
    
    // Remove or comment out header() calls
    $modified_content = preg_replace('/header\s*\([^)]+\)\s*;/i', '// header() removed for cron execution', $modified_content);
    
    // Remove exit statements that would stop execution
    $modified_content = preg_replace('/exit\s*\([^)]*\)\s*;/i', '// exit() removed for cron execution', $modified_content);
    $modified_content = preg_replace('/exit\s*;/i', '// exit removed for cron execution', $modified_content);
    
    // Create a temporary file with modified content
    $temp_script = $temp_dir . '/temp_report_' . $report_type . '_' . time() . '_' . rand(1000, 9999) . '.php';
    file_put_contents($temp_script, $modified_content);
    
    // Capture any output
    ob_start();
    
    // Execute the modified script
    // All relative paths have been converted to absolute paths, so we can include from anywhere
    try {
        include $temp_script;
    } catch (Exception $e) {
        ob_end_clean();
        @unlink($temp_script);
        return false;
    }
    
    ob_end_clean();
    
    // Clean up temporary script
    @unlink($temp_script);
    
    // Check if CSV file was created and has content
    if (file_exists($csv_file) && filesize($csv_file) > 0) {
        return $csv_file;
    }
    
    return false;
}

/**
 * Save download link to database
 */
function saveDownloadLink($report_type, $report_name, $s3_url, $s3_key, $file_name, $report_date, $from_date, $to_date, $report_period) {
    global $db;
    
    $report_type = towreal($report_type);
    $report_name = towreal($report_name);
    $s3_url = towreal($s3_url);
    $s3_key = towreal($s3_key);
    $file_name = towreal($file_name);
    $report_period = towreal($report_period);
    
    $sql = "INSERT INTO `download_links` 
            (`report_type`, `report_name`, `s3_url`, `s3_key`, `file_name`, `report_date`, `from_date`, `to_date`, `report_period`, `created_at`) 
            VALUES 
            ('$report_type', '$report_name', '$s3_url', '$s3_key', '$file_name', '$report_date', '$from_date', '$to_date', '$report_period', NOW())";
    
    $result = towquery($sql);
    
    if ($result) {
        return mysqli_insert_id($db);
    }
    
    return false;
}

/**
 * Update email status in database
 */
function updateEmailStatus($id, $email_sent) {
    $id = (int)$id;
    $email_sent = (int)$email_sent;
    $now = date('Y-m-d H:i:s');
    
    $sql = "UPDATE `download_links` 
            SET `email_sent` = $email_sent, `email_sent_at` = '$now' 
            WHERE `id` = $id";
    
    return towquery($sql);
}

/**
 * Send email with attachments
 */
function sendEmailWithAttachments($to_email, $from_email, $from_name, $report_period, $attachments, $errors, $download_links = []) {
    global $recipient_email;
    
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->Port = 465;
    $mail->SMTPAuth = true;
    $mail->Username = 'documents@creditlab.in';
    $mail->Password = 'Sc13olnh&';
    $mail->SMTPSecure = 'ssl';
    $mail->From = $from_email;
    $mail->FromName = $from_name;
    $mail->AddAddress($to_email);
    $mail->WordWrap = 50;
    $mail->IsHTML(true);
    
    // Subject
    $mail->Subject = 'CreditLab Automated Reports - ' . $report_period;
    
    // Body
    $body = '<html><body>';
    $body .= '<h2>CreditLab Automated Reports</h2>';
    $body .= '<p><strong>Report Period:</strong> ' . $report_period . '</p>';
    $body .= '<p><strong>Generated On:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    $body .= '<p><strong>Total Reports:</strong> ' . count($attachments) . '</p>';
    
    if (!empty($errors)) {
        $body .= '<h3>Errors/Warnings:</h3><ul>';
        foreach ($errors as $error) {
            $body .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $body .= '</ul>';
    }
    
    $body .= '<h3>Attached Reports:</h3><ul>';
    foreach ($attachments as $attachment) {
        $body .= '<li>' . htmlspecialchars($attachment['name']) . '</li>';
    }
    $body .= '</ul>';
    
    // Add S3 download links section
    if (!empty($download_links)) {
        $body .= '<h3>Download Links (S3):</h3>';
        $body .= '<p>If email attachments fail, you can download reports from the following links:</p>';
        $body .= '<ul>';
        // Get all download links from database for this batch
        $link_ids = array_column($download_links, 'id');
        if (!empty($link_ids)) {
            $ids_str = implode(',', array_map('intval', $link_ids));
            $sql = "SELECT id, report_name, s3_url FROM `download_links` WHERE id IN ($ids_str)";
            $result = towquery($sql);
            
            if ($result) {
                while ($row = towfetch($result)) {
                    $body .= '<li><strong>' . htmlspecialchars($row['report_name']) . ':</strong> ';
                    $body .= '<a href="' . htmlspecialchars($row['s3_url']) . '">Download from S3</a></li>';
                }
            }
        }
        $body .= '</ul>';
        $body .= '<p><em>Note: All reports are also saved in the database table "download_links" for backup access.</em></p>';
    }
    
    $body .= '<p>Please find all reports attached to this email.</p>';
    $body .= '<p>Best regards,<br>CreditLab Automated System</p>';
    $body .= '</body></html>';
    
    $mail->Body = $body;
    
    // Add attachments
    foreach ($attachments as $attachment) {
        if (file_exists($attachment['file'])) {
            $mail->AddAttachment($attachment['file'], $attachment['name']);
        }
    }
    
    // Send email
    if ($mail->Send()) {
        return true;
    } else {
        // Log error
        global $log_file;
        $error_log = "[" . date('Y-m-d H:i:s') . "] Email Error: " . $mail->ErrorInfo . "\n";
        $error_log .= "[" . date('Y-m-d H:i:s') . "] SMTP Host: " . $mail->Host . ", Port: " . $mail->Port . "\n";
        $error_log .= "[" . date('Y-m-d H:i:s') . "] From: " . $mail->From . ", To: " . $to_email . "\n";
        file_put_contents($log_file, $error_log, FILE_APPEND);
        return false;
    }
}

/**
 * Get download link data from database
 */
function getDownloadLinkData($id) {
    $id = (int)$id;
    $sql = "SELECT * FROM `download_links` WHERE `id` = $id LIMIT 1";
    $result = towquery($sql);
    
    if ($result) {
        return towfetch($result);
    }
    
    return false;
}

?>


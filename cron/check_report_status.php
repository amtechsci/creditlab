<?php
/**
 * Diagnostic script to check auto_report_email.php status
 * 
 * Usage: php check_report_status.php
 */

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Include database connection
require_once __DIR__ . '/../db.php';

echo "========================================\n";
echo "CreditLab Auto Report Email - Status Check\n";
echo "========================================\n\n";

// Check database connection
echo "1. Database Connection: ";
if (isset($db) && @mysqli_ping($db)) {
    echo "OK\n";
} else {
    echo "FAILED\n";
    exit(1);
}

// Check if download_links table exists
echo "2. Checking download_links table: ";
$sql = "SHOW TABLES LIKE 'download_links'";
$result = towquery($sql);
if ($result && towfetch($result)) {
    echo "EXISTS\n";
} else {
    echo "NOT FOUND - Table will be created on first run\n";
}

// Count records in download_links
echo "3. Records in download_links table: ";
$sql = "SELECT COUNT(*) as total FROM download_links";
$result = towquery($sql);
if ($result) {
    $row = towfetch($result);
    $total = $row['total'] ?? 0;
    echo "$total\n";
    
    if ($total > 0) {
        echo "\n   Recent records:\n";
        $sql = "SELECT id, report_type, report_name, report_date, from_date, to_date, email_sent, email_sent_at, created_at 
                FROM download_links 
                ORDER BY created_at DESC 
                LIMIT 5";
        $result = towquery($sql);
        if ($result) {
            while ($row = towfetch($result)) {
                echo "   - ID: {$row['id']}, Type: {$row['report_type']}, Date: {$row['report_date']}, ";
                echo "Email Sent: " . ($row['email_sent'] ? 'YES' : 'NO');
                if ($row['email_sent_at']) {
                    echo " at {$row['email_sent_at']}";
                }
                echo "\n";
            }
        }
    }
} else {
    echo "ERROR querying table\n";
}

// Check cron log file
echo "\n4. Cron log file: ";
$log_file = __DIR__ . '/cron_log.txt';
if (file_exists($log_file)) {
    $size = filesize($log_file);
    echo "EXISTS (" . number_format($size) . " bytes)\n";
    
    echo "\n   Last 20 lines of log:\n";
    $lines = file($log_file);
    if ($lines) {
        $last_lines = array_slice($lines, -20);
        foreach ($last_lines as $line) {
            echo "   " . trim($line) . "\n";
        }
    }
} else {
    echo "NOT FOUND - Log will be created on first run\n";
}

// Check cron output log
echo "\n5. Cron output log: ";
$output_log = __DIR__ . '/cron_output.log';
if (file_exists($output_log)) {
    $size = filesize($output_log);
    echo "EXISTS (" . number_format($size) . " bytes)\n";
    
    echo "\n   Last 10 lines of output:\n";
    $lines = file($output_log);
    if ($lines) {
        $last_lines = array_slice($lines, -10);
        foreach ($last_lines as $line) {
            echo "   " . trim($line) . "\n";
        }
    }
} else {
    echo "NOT FOUND\n";
}

// Check current date and next scheduled run
echo "\n6. Current Date & Next Scheduled Run:\n";
$current_day = (int)date('d');
$current_month = date('F');
$current_year = date('Y');
$last_day = date('t');

echo "   Current: " . date('Y-m-d H:i:s') . "\n";
echo "   Day: $current_day\n";

$is_15th = ($current_day == 15);
$is_last_day = ($current_day == $last_day);

if ($is_15th) {
    echo "   Status: TODAY IS 15TH - Script should run\n";
} elseif ($is_last_day) {
    echo "   Status: TODAY IS LAST DAY - Script should run\n";
} else {
    $days_to_15th = 15 - $current_day;
    $days_to_last = $last_day - $current_day;
    
    if ($current_day < 15) {
        echo "   Status: Next run on 15th (in $days_to_15th days)\n";
    } else {
        echo "   Status: Next run on last day (in $days_to_last days)\n";
    }
}

// Check required files
echo "\n7. Required Files Check:\n";
$required_files = [
    __DIR__ . '/../db.php' => 'Database connection',
    __DIR__ . '/../zxc/class/class.phpmailer.php' => 'PHPMailer',
    __DIR__ . '/../lib/s3_aws_sdk.php' => 'S3 helper',
    __DIR__ . '/../config_s3.php' => 'S3 config',
];

foreach ($required_files as $file => $desc) {
    echo "   $desc: ";
    if (file_exists($file)) {
        echo "EXISTS\n";
    } else {
        echo "MISSING\n";
    }
}

// Check report downloader files
echo "\n8. Report Downloader Files:\n";
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

$missing_reports = [];
foreach ($report_files as $name => $file) {
    if (file_exists($file)) {
        echo "   $name: EXISTS\n";
    } else {
        echo "   $name: MISSING\n";
        $missing_reports[] = $name;
    }
}

if (!empty($missing_reports)) {
    echo "\n   WARNING: Missing report files: " . implode(', ', $missing_reports) . "\n";
}

echo "\n========================================\n";
echo "Status Check Complete\n";
echo "========================================\n";
echo "\nTo test the script manually:\n";
echo "  php auto_report_email.php test    (test mode - last 7 days, no email)\n";
echo "  php auto_report_email.php force   (force mode - run on any day)\n";
echo "\n";

?>

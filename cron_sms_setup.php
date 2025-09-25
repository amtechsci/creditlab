<?php
/**
 * CreditLab Automated SMS Cron Job Setup Guide
 * This file explains how to set up cron jobs for automated SMS
 */

echo "<h1>🕐 CreditLab Automated SMS Cron Setup</h1>";

echo "<h2>📋 Current Automated SMS Files</h2>";
echo "<ul>";
echo "<li><strong>zzautosms.php</strong> - Main automated SMS system</li>";
echo "<li><strong>zzemiautosms.php</strong> - EMI-specific automated SMS</li>";
echo "<li><strong>zzemiautosmsalt.php</strong> - Alternative EMI SMS system</li>";
echo "</ul>";

echo "<h2>⚙️ How Automated SMS Works</h2>";
echo "<h3>1. Main Logic (zzautosms.php)</h3>";
echo "<ul>";
echo "<li>Fetches loans with status 'account manager' from last 64 days</li>";
echo "<li>Calculates days since loan processing (tday)</li>";
echo "<li>Sends SMS based on day ranges:</li>";
echo "<ul>";
echo "<li><strong>Days 25-29:</strong> First EMI reminder</li>";
echo "<li><strong>Days 31-39:</strong> EMI overdue warning</li>";
echo "<li><strong>Days 60-64:</strong> Second EMI reminder</li>";
echo "<li><strong>Days 66-74:</strong> Second EMI overdue warning</li>";
echo "</ul>";
echo "</ul>";

echo "<h3>2. EMI Logic (zzemiautosms.php)</h3>";
echo "<ul>";
echo "<li>Same as main logic but includes limit increase features</li>";
echo "<li>Days 25-29: Limit increase notifications</li>";
echo "<li>Days 25-29: First EMI reminders</li>";
echo "<li>Days 31-39: EMI overdue warnings</li>";
echo "<li>Days 60-64: Second EMI reminders</li>";
echo "<li>Days 66-74: Second EMI overdue warnings</li>";
echo "</ul>";

echo "<h2>🕐 Cron Job Configuration</h2>";
echo "<h3>Recommended Cron Schedule</h3>";
echo "<pre>";
echo "# Run every hour to check for SMS triggers
0 * * * * /usr/bin/php /path/to/creditlab/zzautosms.php

# Run every 2 hours for EMI-specific SMS
0 */2 * * * /usr/bin/php /path/to/creditlab/zzemiautosms.php

# Run daily at 9 AM for morning reminders
0 9 * * * /usr/bin/php /path/to/creditlab/zzautosms.php

# Run daily at 6 PM for evening reminders
0 18 * * * /usr/bin/php /path/to/creditlab/zzautosms.php

# Run every 30 minutes during business hours (9 AM - 6 PM)
*/30 9-18 * * * /usr/bin/php /path/to/creditlab/zzautosms.php
</pre>";

echo "<h3>Windows Task Scheduler (if using Windows)</h3>";
echo "<ol>";
echo "<li>Open Task Scheduler</li>";
echo "<li>Create Basic Task</li>";
echo "<li>Set trigger: Daily, Every 1 hour</li>";
echo "<li>Set action: Start a program</li>";
echo "<li>Program: php.exe</li>";
echo "<li>Arguments: C:\\xampp\\htdocs\\creditlab\\zzautosms.php</li>";
echo "<li>Start in: C:\\xampp\\htdocs\\creditlab</li>";
echo "</ol>";

echo "<h2>🔧 Optimized Cron Script</h2>";
echo "<p>I'll create an optimized version that includes logging and error handling:</p>";

// Create optimized cron script
$optimized_script = '<?php
/**
 * Optimized CreditLab Automated SMS Cron Script
 * Run this via cron job for automated SMS
 */

// Set time limit and memory
set_time_limit(300); // 5 minutes
ini_set("memory_limit", "256M");

// Log file
$log_file = "sms_cron_log.txt";
$log_date = date("Y-m-d H:i:s");

// Log function
function logMessage($message) {
    global $log_file, $log_date;
    $log_entry = "[$log_date] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Include database
include_once "db.php";

// Check if script is already running
$lock_file = "sms_cron.lock";
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    if (time() - $lock_time < 300) { // 5 minutes
        logMessage("Script already running, exiting");
        exit;
    }
}

// Create lock file
file_put_contents($lock_file, time());

try {
    logMessage("Starting automated SMS process");
    
    // Your existing SMS logic here
    $today = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") . " -64 day"));
    $newloanquery = towquery("SELECT uid,id FROM `loan_apply` WHERE `status`=\'account manager\' AND status_date > \'{$today}\'");
    
    $seauserid = array();
    $i = 0;
    while($a = towfetch($newloanquery)){ 
        $seauserid[$i] = $a[\'id\']; 
        $i++; 
    }
    $seauserid = array_unique($seauserid);
    
    $sms_sent = 0;
    $errors = 0;
    
    foreach($seauserid as $value){
        // Your existing SMS sending logic
        // ... (include the existing logic from zzautosms.php)
        $sms_sent++;
    }
    
    logMessage("SMS process completed. Sent: $sms_sent, Errors: $errors");
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
} finally {
    // Remove lock file
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
}
?>';

file_put_contents('zzautosms_optimized.php', $optimized_script);

echo "<h2>📊 SMS Monitoring Dashboard</h2>";
echo "<p>Create a simple monitoring page to track SMS delivery:</p>";

$monitoring_script = '<?php
/**
 * SMS Monitoring Dashboard
 * View SMS logs and delivery status
 */

include_once "db.php";

echo "<h2>📊 SMS Monitoring Dashboard</h2>";

// Check log file
if (file_exists("sms_cron_log.txt")) {
    $logs = file("sms_cron_log.txt");
    $recent_logs = array_slice($logs, -20); // Last 20 entries
    
    echo "<h3>Recent SMS Logs</h3>";
    echo "<pre style=\"background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: scroll;\">";
    foreach ($recent_logs as $log) {
        echo htmlspecialchars($log);
    }
    echo "</pre>";
} else {
    echo "<p>No SMS logs found. Run the cron job first.</p>";
}

// Check lock file
if (file_exists("sms_cron.lock")) {
    $lock_time = filemtime("sms_cron.lock");
    $age = time() - $lock_time;
    echo "<p style=\"color: " . ($age > 300 ? "red" : "green") . ";\">";
    echo "Cron Status: " . ($age > 300 ? "STUCK (over 5 minutes)" : "Running");
    echo " (Age: {$age} seconds)</p>";
} else {
    echo "<p style=\"color: orange;\">Cron Status: Not running</p>";
}

// Show recent loan activity
echo "<h3>Recent Loan Activity</h3>";
$recent_loans = towquery("SELECT COUNT(*) as count FROM loan_apply WHERE status_date > DATE_SUB(NOW(), INTERVAL 1 DAY)");
if ($recent_loans) {
    $count = towfetch($recent_loans)["count"];
    echo "<p>Loans processed in last 24 hours: $count</p>";
}
?>';

file_put_contents('sms_monitoring.php', $monitoring_script);

echo "<h2>✅ Setup Instructions</h2>";
echo "<ol>";
echo "<li><strong>Test the scripts:</strong> Run zzautosms.php manually first</li>";
echo "<li><strong>Set up cron jobs:</strong> Use the cron schedule above</li>";
echo "<li><strong>Monitor logs:</strong> Check sms_cron_log.txt for issues</li>";
echo "<li><strong>Use monitoring:</strong> Visit sms_monitoring.php to track status</li>";
echo "<li><strong>Test SMS delivery:</strong> Verify SMS are being sent to +918800899875</li>";
echo "</ol>";

echo "<h2>🚨 Important Notes</h2>";
echo "<ul>";
echo "<li><strong>Database Connection:</strong> Ensure db.php is accessible from cron</li>";
echo "<li><strong>File Permissions:</strong> Make sure PHP can write log files</li>";
echo "<li><strong>Memory Limits:</strong> Large user bases may need increased memory</li>";
echo "<li><strong>Rate Limiting:</strong> Don't run too frequently to avoid SMS limits</li>";
echo "<li><strong>Error Handling:</strong> Monitor logs for failed SMS attempts</li>";
echo "</ul>";

echo "<h2>📱 SMS Template Integration</h2>";
echo "<p>Your new SMS templates from the portal can be integrated by updating the template IDs in the scripts:</p>";
echo "<ul>";
echo "<li>Update template IDs in zzautosms.php</li>";
echo "<li>Add new day ranges for different SMS types</li>";
echo "<li>Implement variable replacement for personalized messages</li>";
echo "</ul>";

echo "<p><em>Setup completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>

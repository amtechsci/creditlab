<?php
/**
 * SMS Monitoring Dashboard
 * View SMS logs and delivery status
 */

include_once 'db.php';

echo "<h2>📊 CreditLab SMS Monitoring Dashboard</h2>";
echo "<p><strong>Last Updated:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check log file
if (file_exists("sms_cron_log.txt")) {
    $logs = file("sms_cron_log.txt");
    $recent_logs = array_slice($logs, -50); // Last 50 entries
    
    echo "<h3>📝 Recent SMS Logs (Last 50 entries)</h3>";
    echo "<div style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow-y: scroll; border: 1px solid #ddd;'>";
    foreach ($recent_logs as $log) {
        $log = trim($log);
        if (strpos($log, 'Error') !== false) {
            echo "<div style='color: red;'>" . htmlspecialchars($log) . "</div>";
        } elseif (strpos($log, 'SMS Sent') !== false) {
            echo "<div style='color: green;'>" . htmlspecialchars($log) . "</div>";
        } else {
            echo "<div>" . htmlspecialchars($log) . "</div>";
        }
    }
    echo "</div>";
    
    // Show log statistics
    $total_logs = count($logs);
    $error_logs = 0;
    $success_logs = 0;
    
    foreach($logs as $log) {
        if (strpos($log, 'Error') !== false) $error_logs++;
        if (strpos($log, 'SMS Sent') !== false) $success_logs++;
    }
    
    echo "<h3>📈 SMS Statistics</h3>";
    echo "<div style='display: flex; gap: 20px;'>";
    echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Total Logs:</strong> $total_logs";
    echo "</div>";
    echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Successful SMS:</strong> $success_logs";
    echo "</div>";
    echo "<div style='background: #ffe8e8; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Errors:</strong> $error_logs";
    echo "</div>";
    echo "</div>";
    
} else {
    echo "<p style='color: orange;'>⚠️ No SMS logs found. Run the cron job first.</p>";
}

// Check lock file
echo "<h3>🔒 Cron Status</h3>";
if (file_exists("sms_cron.lock")) {
    $lock_time = filemtime("sms_cron.lock");
    $age = time() - $lock_time;
    $age_minutes = round($age / 60, 1);
    
    if ($age > 300) { // 5 minutes
        echo "<p style='color: red; background: #ffe8e8; padding: 10px; border-radius: 5px;'>";
        echo "🚨 <strong>CRON STUCK!</strong> Script has been running for {$age_minutes} minutes (over 5 minutes)";
        echo "</p>";
    } else {
        echo "<p style='color: green; background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
        echo "✅ <strong>CRON RUNNING</strong> - Script running for {$age_minutes} minutes";
        echo "</p>";
    }
} else {
    echo "<p style='color: orange; background: #fff8e8; padding: 10px; border-radius: 5px;'>";
    echo "⏸️ <strong>CRON NOT RUNNING</strong> - No active cron job detected";
    echo "</p>";
}

// Show recent loan activity
echo "<h3>📊 Recent Loan Activity</h3>";
$recent_loans = towquery("SELECT COUNT(*) as count FROM loan_apply WHERE status_date > DATE_SUB(NOW(), INTERVAL 1 DAY)");
if ($recent_loans) {
    $count = towfetch($recent_loans)["count"];
    echo "<p>Loans processed in last 24 hours: <strong>$count</strong></p>";
}

// Show active loans that might need SMS
$active_loans = towquery("SELECT COUNT(*) as count FROM loan_apply WHERE status='account manager'");
if ($active_loans) {
    $active_count = towfetch($active_loans)["count"];
    echo "<p>Active loans (account manager status): <strong>$active_count</strong></p>";
}

// Show loans by day range
echo "<h3>📅 Loans by Day Range (for SMS targeting)</h3>";
$day_ranges = [
    "25-29 days" => "SELECT COUNT(*) as count FROM loan_apply la 
                     INNER JOIN loan l ON l.lid = la.id 
                     WHERE la.status='account manager' 
                     AND DATEDIFF(NOW(), l.processed_date) BETWEEN 25 AND 29",
    "31-39 days" => "SELECT COUNT(*) as count FROM loan_apply la 
                     INNER JOIN loan l ON l.lid = la.id 
                     WHERE la.status='account manager' 
                     AND DATEDIFF(NOW(), l.processed_date) BETWEEN 31 AND 39",
    "60-64 days" => "SELECT COUNT(*) as count FROM loan_apply la 
                     INNER JOIN loan l ON l.lid = la.id 
                     WHERE la.status='account manager' 
                     AND DATEDIFF(NOW(), l.processed_date) BETWEEN 60 AND 64",
    "66-74 days" => "SELECT COUNT(*) as count FROM loan_apply la 
                     INNER JOIN loan l ON l.lid = la.id 
                     WHERE la.status='account manager' 
                     AND DATEDIFF(NOW(), l.processed_date) BETWEEN 66 AND 74"
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;'>";
foreach ($day_ranges as $range => $query) {
    $result = towquery($query);
    if ($result) {
        $count = towfetch($result)["count"];
        $color = $count > 0 ? "#e8f5e8" : "#f5f5f5";
        echo "<div style='background: $color; padding: 10px; border-radius: 5px; text-align: center;'>";
        echo "<strong>$range</strong><br>";
        echo "<span style='font-size: 24px;'>$count</span> loans";
        echo "</div>";
    }
}
echo "</div>";

// Manual test button
echo "<h3>🧪 Manual Testing</h3>";
echo "<form method='post' style='margin: 20px 0;'>";
echo "<button type='submit' name='test_cron' style='background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
echo "🚀 Run SMS Cron Manually";
echo "</button>";
echo "</form>";

if (isset($_POST['test_cron'])) {
    echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Running SMS cron manually...</strong><br>";
    
    // Run the optimized SMS script
    ob_start();
    include 'zzautosms_optimized.php';
    $output = ob_get_clean();
    
    echo "<pre style='background: #f9f9f9; padding: 10px; margin: 10px 0;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    echo "<p style='color: green;'>✅ Manual cron execution completed. Check logs above for details.</p>";
    echo "</div>";
}

// Cron job setup instructions
echo "<h3>⚙️ Cron Job Setup</h3>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; border-left: 4px solid #007cba;'>";
echo "<h4>Linux/Unix Cron Jobs:</h4>";
echo "<pre style='background: #f9f9f9; padding: 10px; border-radius: 3px;'>";
echo "# Run every hour
0 * * * * /usr/bin/php " . realpath('zzautosms_optimized.php') . "

# Run every 2 hours during business hours
0 9-18/2 * * * /usr/bin/php " . realpath('zzautosms_optimized.php') . "
</pre>";

echo "<h4>Windows Task Scheduler:</h4>";
echo "<ol>";
echo "<li>Open Task Scheduler</li>";
echo "<li>Create Basic Task</li>";
echo "<li>Set trigger: Daily, Every 1 hour</li>";
echo "<li>Set action: Start a program</li>";
echo "<li>Program: php.exe</li>";
echo "<li>Arguments: " . realpath('zzautosms_optimized.php') . "</li>";
echo "<li>Start in: " . dirname(realpath('zzautosms_optimized.php')) . "</li>";
echo "</ol>";
echo "</div>";

echo "<p><em>Dashboard refreshed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>

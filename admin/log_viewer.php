<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

include_once 'head.php';

// Function to get all log files
function getLogFiles($directory) {
    $logFiles = [];
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                $filePath = $directory . '/' . $file;
                $logFiles[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath),
                    'type' => strpos($file, 'enach_cron') !== false ? 'cron' : (strpos($file, 'webhook') !== false ? 'webhook' : 'cron')
                ];
            }
        }
        // Sort by modification time (newest first)
        usort($logFiles, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
    }
    return $logFiles;
}

// Function to read log file content
function readLogFile($filePath, $lines = 100) {
    if (!file_exists($filePath)) {
        return ['error' => 'Log file not found'];
    }
    
    $content = file_get_contents($filePath);
    $logLines = explode("\n", $content);
    
    // Get last N lines
    $logLines = array_slice($logLines, -$lines);
    
    return [
        'content' => implode("\n", $logLines),
        'total_lines' => count(explode("\n", file_get_contents($filePath))),
        'showing_lines' => count($logLines)
    ];
}

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Get log files from both directories
$cronLogs = getLogFiles('../payment/logs');
$webhookLogs = getLogFiles('../logs');
$allLogs = array_merge($cronLogs, $webhookLogs);

// Debug: Check if directories exist
$cronDirExists = is_dir('../payment/logs');
$webhookDirExists = is_dir('../logs');

// Handle log file viewing
$selectedLog = null;
$logContent = null;
if (isset($_GET['view']) && isset($_GET['file'])) {
    $selectedFile = $_GET['file'];
    
    // Check both log directories
    $filePath = null;
    if (file_exists('../payment/logs/' . $selectedFile)) {
        $filePath = '../payment/logs/' . $selectedFile;
    } elseif (file_exists('../logs/' . $selectedFile)) {
        $filePath = '../logs/' . $selectedFile;
    }
    
    if ($filePath && file_exists($filePath)) {
        $selectedLog = $selectedFile;
        $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;
        $logContent = readLogFile($filePath, $lines);
    }
}

// Handle log download
if (isset($_GET['download']) && isset($_GET['file'])) {
    $downloadFile = $_GET['file'];
    
    // Check both log directories
    $filePath = null;
    if (file_exists('../payment/logs/' . $downloadFile)) {
        $filePath = '../payment/logs/' . $downloadFile;
    } elseif (file_exists('../logs/' . $downloadFile)) {
        $filePath = '../logs/' . $downloadFile;
    }
    
    if ($filePath && file_exists($filePath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadFile . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}

// Get log statistics
$totalCronLogs = count(array_filter($allLogs, function($log) { return $log['type'] === 'cron'; }));
$totalWebhookLogs = count(array_filter($allLogs, function($log) { return $log['type'] === 'webhook'; }));
$totalLogSize = array_sum(array_column($allLogs, 'size'));

// Get recent log activity (last 7 days)
$recentLogs = array_filter($allLogs, function($log) {
    return $log['modified'] > (time() - (7 * 24 * 60 * 60));
});

// Get log statistics by date
$logsByDate = [];
foreach ($allLogs as $log) {
    $date = date('Y-m-d', $log['modified']);
    if (!isset($logsByDate[$date])) {
        $logsByDate[$date] = ['cron' => 0, 'webhook' => 0, 'size' => 0];
    }
    $logsByDate[$date][$log['type']]++;
    $logsByDate[$date]['size'] += $log['size'];
}
ksort($logsByDate);
?>

<body>
    <!-- Start Left menu area -->
    <?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>

    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="breadcome-list">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                <div class="breadcome-heading">
                                    <h4>Log Viewer</h4>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                <ul class="breadcome-menu">
                                    <li><a href="index.php">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Log Viewer</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="single-pro-review-area mt-t-30 mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
                        
                        <!-- Debug Information -->
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="alert alert-info">
                                    <strong>Debug Info:</strong> 
                                    Cron logs directory (../payment/logs): <?= $cronDirExists ? '✅ Found' : '❌ Not found' ?> | 
                                    Webhook logs directory (../logs): <?= $webhookDirExists ? '✅ Found' : '❌ Not found' ?> |
                                    Total log files found: <?= count($allLogs) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                <div class="hpanel hblue contact-panel">
                                    <div class="panel-body">
                                        <div class="social-media-inner">
                                            <div class="contact-left">
                                                <h4>Cron Logs</h4>
                                                <p><?= $totalCronLogs ?> files</p>
                                            </div>
                                            <div class="contact-right">
                                                <i class="fa fa-clock-o" aria-hidden="true"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                <div class="hpanel hgreen contact-panel">
                                    <div class="panel-body">
                                        <div class="social-media-inner">
                                            <div class="contact-left">
                                                <h4>Webhook Logs</h4>
                                                <p><?= $totalWebhookLogs ?> files</p>
                                            </div>
                                            <div class="contact-right">
                                                <i class="fa fa-exchange" aria-hidden="true"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                <div class="hpanel hred contact-panel">
                                    <div class="panel-body">
                                        <div class="social-media-inner">
                                            <div class="contact-left">
                                                <h4>Total Logs</h4>
                                                <p><?= count($allLogs) ?> files</p>
                                            </div>
                                            <div class="contact-right">
                                                <i class="fa fa-file-text-o" aria-hidden="true"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                <div class="hpanel hyellow contact-panel">
                                    <div class="panel-body">
                                        <div class="social-media-inner">
                                            <div class="contact-left">
                                                <h4>Total Size</h4>
                                                <p><?= formatFileSize($totalLogSize) ?></p>
                                            </div>
                                            <div class="contact-right">
                                                <i class="fa fa-hdd-o" aria-hidden="true"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity Table -->
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="review-content-section">
                                    <h4>Recent Log Activity (Last 7 Days)</h4>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Cron Logs</th>
                                                    <th>Webhook Logs</th>
                                                    <th>Total Size</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $recentDates = array_slice(array_keys($logsByDate), -7, 7, true);
                                                foreach ($recentDates as $date): 
                                                    $stats = $logsByDate[$date];
                                                ?>
                                                <tr>
                                                    <td><?= $date ?></td>
                                                    <td>
                                                        <span class="badge badge-primary"><?= $stats['cron'] ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-success"><?= $stats['webhook'] ?></span>
                                                    </td>
                                                    <td><?= formatFileSize($stats['size']) ?></td>
                                                    <td>
                                                        <?php if (file_exists('../payment/logs/enach_cron_'.$date.'.log')): ?>
                                                            <a href="?view=1&file=enach_cron_<?= $date ?>.log" class="btn btn-xs btn-primary">View Cron</a>
                                                        <?php endif; ?>
                                                        <?php if (file_exists('../logs/webhook_'.$date.'.log')): ?>
                                                            <a href="?view=1&file=webhook_<?= $date ?>.log" class="btn btn-xs btn-success">View Webhook</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                
                                                <?php if (empty($recentDates)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No recent log activity</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                <div class="review-content-section">
                                    <div class="chat-discussion">
                                        <h4>Available Log Files</h4>
                                        
                                        <!-- Search and Filter -->
                                        <div class="row" style="margin-bottom: 15px;">
                                            <div class="col-md-6">
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-primary" onclick="filterLogs('all')">All</button>
                                                    <button type="button" class="btn btn-default" onclick="filterLogs('cron')">Cron</button>
                                                    <button type="button" class="btn btn-default" onclick="filterLogs('webhook')">Webhook</button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" id="logSearch" placeholder="Search log files..." onkeyup="searchLogs()">
                                            </div>
                                        </div>

                                        <!-- Log Files List -->
                                        <div class="chat-discussion" style="max-height: 500px; overflow-y: auto;">
                                            <?php foreach ($allLogs as $log): ?>
                                                <div class="chat-message log-file-item" data-type="<?= $log['type'] ?>">
                                                    <div class="profile-pic">
                                                        <?php if ($log['type'] === 'cron'): ?>
                                                            <i class="fa fa-clock-o" style="color: #337ab7;"></i>
                                                        <?php elseif ($log['type'] === 'webhook'): ?>
                                                            <i class="fa fa-exchange" style="color: #5cb85c;"></i>
                                                        <?php else: ?>
                                                            <i class="fa fa-file-text-o" style="color: #f0ad4e;"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="message">
                                                        <div class="message-body">
                                                            <h5>
                                                                <a href="?view=1&file=<?= urlencode($log['name']) ?>" 
                                                                   class="log-file-link <?= $selectedLog === $log['name'] ? 'active' : '' ?>">
                                                                    <?= htmlspecialchars($log['name']) ?>
                                                                </a>
                                                            </h5>
                                                            <p class="small">
                                                                <?= formatFileSize($log['size']) ?> | 
                                                                <?= date('Y-m-d H:i:s', $log['modified']) ?>
                                                            </p>
                                                            <div class="btn-group btn-group-xs">
                                                                <a href="?view=1&file=<?= urlencode($log['name']) ?>" 
                                                                   class="btn btn-primary btn-xs">View</a>
                                                                <a href="?download=1&file=<?= urlencode($log['name']) ?>" 
                                                                   class="btn btn-success btn-xs">Download</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <?php if (empty($allLogs)): ?>
                                                <div class="text-center" style="padding: 20px;">
                                                    <i class="fa fa-info-circle fa-3x" style="color: #ccc;"></i>
                                                    <p>No log files found in the logs directory.</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                                <div class="review-content-section">
                                    <?php if ($selectedLog && $logContent): ?>
                                        <div class="chat-discussion">
                                            <h4>
                                                Viewing: <?= htmlspecialchars($selectedLog) ?>
                                                <div class="pull-right">
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                            Lines: <?= $logContent['showing_lines'] ?> <span class="caret"></span>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a href="?view=1&file=<?= urlencode($selectedLog) ?>&lines=50">Last 50 lines</a></li>
                                                            <li><a href="?view=1&file=<?= urlencode($selectedLog) ?>&lines=100">Last 100 lines</a></li>
                                                            <li><a href="?view=1&file=<?= urlencode($selectedLog) ?>&lines=500">Last 500 lines</a></li>
                                                            <li><a href="?view=1&file=<?= urlencode($selectedLog) ?>&lines=1000">Last 1000 lines</a></li>
                                                        </ul>
                                                    </div>
                                                    <a href="?view=1&file=<?= urlencode($selectedLog) ?>&autorefresh=<?= isset($_GET['autorefresh']) && $_GET['autorefresh'] == '1' ? '0' : '1' ?>" 
                                                       class="btn btn-<?= isset($_GET['autorefresh']) && $_GET['autorefresh'] == '1' ? 'warning' : 'default' ?> btn-sm">
                                                        <i class="fa fa-<?= isset($_GET['autorefresh']) && $_GET['autorefresh'] == '1' ? 'pause' : 'play' ?>"></i> 
                                                        <?= isset($_GET['autorefresh']) && $_GET['autorefresh'] == '1' ? 'Stop Auto-Refresh' : 'Start Auto-Refresh' ?>
                                                    </a>
                                                    <a href="?download=1&file=<?= urlencode($selectedLog) ?>" class="btn btn-success btn-sm">
                                                        <i class="fa fa-download"></i> Download
                                                    </a>
                                                </div>
                                            </h4>
                                            <p class="text-muted">
                                                Showing last <?= $logContent['showing_lines'] ?> lines of <?= $logContent['total_lines'] ?> total lines
                                            </p>
                                            
                                            <div class="log-content" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; max-height: 600px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.4;">
                                                <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars($logContent['content']) ?></pre>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center" style="padding: 50px;">
                                            <i class="fa fa-file-text-o fa-5x" style="color: #ccc;"></i>
                                            <h4>Select a log file to view</h4>
                                            <p class="text-muted">Choose a log file from the list on the left to view its contents.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once 'foot.php'; ?>

    <script>
    let currentFilter = 'all';
    let currentSearch = '';

    // Filter logs by type
    function filterLogs(type) {
        currentFilter = type;
        const logItems = document.querySelectorAll('.log-file-item');
        const buttons = document.querySelectorAll('.btn-group button');
        
        // Update button states
        buttons.forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-default');
        });
        event.target.classList.remove('btn-default');
        event.target.classList.add('btn-primary');
        
        // Apply both filter and search
        applyFilters();
    }

    // Search logs by name
    function searchLogs() {
        currentSearch = document.getElementById('logSearch').value.toLowerCase();
        applyFilters();
    }

    // Apply both type filter and search
    function applyFilters() {
        const logItems = document.querySelectorAll('.log-file-item');
        
        logItems.forEach(item => {
            const logName = item.querySelector('.log-file-link').textContent.toLowerCase();
            const logType = item.dataset.type;
            
            const typeMatch = currentFilter === 'all' || logType === currentFilter;
            const searchMatch = currentSearch === '' || logName.includes(currentSearch);
            
            if (typeMatch && searchMatch) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Auto-refresh log content every 30 minutes if viewing a log (optional)
    <?php if ($selectedLog && isset($_GET['autorefresh']) && $_GET['autorefresh'] == '1'): ?>
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            location.reload();
        }
    }, 1800000); // 30 minutes (30 * 60 * 1000 milliseconds)
    <?php endif; ?>

    // Highlight active log file
    document.addEventListener('DOMContentLoaded', function() {
        const activeLink = document.querySelector('.log-file-link.active');
        if (activeLink) {
            activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
    </script>

    <style>
    .log-file-item {
        border-bottom: 1px solid #eee;
        padding: 10px 0;
    }
    
    .log-file-item:hover {
        background-color: #f8f9fa;
    }
    
    .log-file-link {
        text-decoration: none;
        color: #333;
    }
    
    .log-file-link:hover {
        text-decoration: none;
        color: #337ab7;
    }
    
    .log-file-link.active {
        color: #337ab7;
        font-weight: bold;
    }
    
    .log-content pre {
        background: transparent;
        border: none;
        padding: 0;
        margin: 0;
    }
    
    .contact-panel .panel-body {
        padding: 20px;
    }
    
    .social-media-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .contact-left h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
    }
    
    .contact-left p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
    
    .contact-right i {
        font-size: 24px;
        opacity: 0.7;
    }
    </style>

</body>
</html>

<?php
/**
 * Get Download Links from Database
 * 
 * Usage:
 * - Command line: php cron/get_download_links.php
 * - Browser (admin): use /admin/report_downloads.php instead
 * 
 * Query Parameters:
 * - date: Filter by report_date (format: Y-m-d)
 * - type: Filter by report_type
 * - email_sent: Filter by email_sent status (0 or 1)
 */

if (php_sapi_name() !== 'cli') {
	header('HTTP/1.1 403 Forbidden');
	header('Location: /admin/report_downloads.php');
	exit;
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/s3_aws_sdk.php';

// Get filters
$date_filter = isset($_GET['date']) ? $_GET['date'] : (isset($argv[1]) ? $argv[1] : null);
$type_filter = isset($_GET['type']) ? $_GET['type'] : (isset($argv[2]) ? $argv[2] : null);
$email_sent_filter = isset($_GET['email_sent']) ? $_GET['email_sent'] : (isset($argv[3]) ? $argv[3] : null);

// Build query
$sql = "SELECT * FROM `download_links` WHERE 1=1";

if ($date_filter) {
    $date_filter = towreal($date_filter);
    $sql .= " AND `report_date` = '$date_filter'";
}

if ($type_filter) {
    $type_filter = towreal($type_filter);
    $sql .= " AND `report_type` = '$type_filter'";
}

if ($email_sent_filter !== null && $email_sent_filter !== '') {
    $email_sent_filter = (int)$email_sent_filter;
    $sql .= " AND `email_sent` = $email_sent_filter";
}

$sql .= " ORDER BY `created_at` DESC LIMIT 100";

$result = towquery($sql);

// Check if running from command line or browser
$is_cli = php_sapi_name() === 'cli';

if ($is_cli) {
    // Command line output
    echo "=== Download Links ===\n\n";
    
    if ($result && townum($result) > 0) {
        while ($row = towfetch($result)) {
            echo "ID: " . $row['id'] . "\n";
            echo "Report: " . $row['report_name'] . " (" . $row['report_type'] . ")\n";
            echo "Date: " . $row['report_date'] . "\n";
            echo "Period: " . $row['report_period'] . "\n";
            echo "From: " . $row['from_date'] . " To: " . $row['to_date'] . "\n";
            // Generate presigned URL for command line
            $s3_key = $row['s3_key'];
            $key_for_presigned = $s3_key;
            if (empty($key_for_presigned) || !strpos($key_for_presigned, '/')) {
                if (preg_match('#/(uploads/.+)$#', $row['s3_url'], $matches)) {
                    $key_for_presigned = $matches[1];
                }
            }
            
            $download_url = $row['s3_url'];
            if (!empty($key_for_presigned)) {
                require_once __DIR__ . '/../lib/s3_aws_sdk.php';
                list($success, $presigned_url) = s3_get_file_url($key_for_presigned, '+7 days');
                if ($success) {
                    $download_url = $presigned_url;
                }
            }
            
            echo "S3 URL: " . $download_url . "\n";
            echo "  (Presigned URL, valid for 7 days)\n";
            echo "Email Sent: " . ($row['email_sent'] ? 'Yes' : 'No') . "\n";
            if ($row['email_sent_at']) {
                echo "Email Sent At: " . $row['email_sent_at'] . "\n";
            }
            echo "Created: " . $row['created_at'] . "\n";
            echo str_repeat("-", 80) . "\n\n";
        }
    } else {
        echo "No download links found.\n";
    }
} else {
    // Browser output
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Download Links - CreditLab</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .filter-form { margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px; }
            .filter-form input, .filter-form select { margin: 5px; padding: 5px; }
            .filter-form button { padding: 5px 15px; }
            .url-cell { max-width: 400px; word-break: break-all; }
            .status-yes { color: green; font-weight: bold; }
            .status-no { color: red; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>CreditLab Download Links</h1>
        
        <div class="filter-form">
            <form method="GET">
                <label>Date:</label>
                <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter ?? ''); ?>">
                
                <label>Report Type:</label>
                <select name="type">
                    <option value="">All</option>
                    <option value="disbursal" <?php echo ($type_filter == 'disbursal') ? 'selected' : ''; ?>>Disbursal</option>
                    <option value="cleared" <?php echo ($type_filter == 'cleared') ? 'selected' : ''; ?>>Cleared</option>
                    <option value="default" <?php echo ($type_filter == 'default') ? 'selected' : ''; ?>>Default</option>
                    <option value="part_payment" <?php echo ($type_filter == 'part_payment') ? 'selected' : ''; ?>>Part Payment</option>
                    <option value="settlement" <?php echo ($type_filter == 'settlement') ? 'selected' : ''; ?>>Settlement</option>
                    <option value="bs_repayment" <?php echo ($type_filter == 'bs_repayment') ? 'selected' : ''; ?>>BS Repayment</option>
                    <option value="bs_disbursal" <?php echo ($type_filter == 'bs_disbursal') ? 'selected' : ''; ?>>BS Disbursal</option>
                    <option value="applied" <?php echo ($type_filter == 'applied') ? 'selected' : ''; ?>>Applied</option>
                    <option value="recoveryagency" <?php echo ($type_filter == 'recoveryagency') ? 'selected' : ''; ?>>Recovery Agency</option>
                </select>
                
                <label>Email Sent:</label>
                <select name="email_sent">
                    <option value="">All</option>
                    <option value="1" <?php echo ($email_sent_filter === '1') ? 'selected' : ''; ?>>Yes</option>
                    <option value="0" <?php echo ($email_sent_filter === '0') ? 'selected' : ''; ?>>No</option>
                </select>
                
                <button type="submit">Filter</button>
                <a href="?"><button type="button">Clear</button></a>
            </form>
        </div>
        
        <?php
        if ($result && townum($result) > 0) {
            echo "<table>";
            echo "<tr>";
            echo "<th>ID</th>";
            echo "<th>Report Name</th>";
            echo "<th>Report Type</th>";
            echo "<th>Report Date</th>";
            echo "<th>Period</th>";
            echo "<th>Date Range</th>";
            echo "<th>S3 URL</th>";
            echo "<th>Email Sent</th>";
            echo "<th>Email Sent At</th>";
            echo "<th>Created At</th>";
            echo "</tr>";
            
            while ($row = towfetch($result)) {
                // Generate presigned URL for secure access (valid for 7 days)
                $download_url = $row['s3_url']; // Default to stored URL
                $s3_key = $row['s3_key'];
                
                // Use s3_key directly (it already has the correct prefix)
                // If s3_key is empty, try to extract from URL
                $key_for_presigned = $s3_key;
                if (empty($key_for_presigned)) {
                    // Try to extract from URL
                    if (preg_match('#/(uploads/.+)$#', $row['s3_url'], $matches)) {
                        $key_for_presigned = $matches[1];
                    }
                }
                
                // Generate presigned URL (s3_key already has prefix, so pass as-is)
                if (!empty($key_for_presigned)) {
                    list($success, $presigned_url) = s3_get_file_url($key_for_presigned, '+7 days');
                    if ($success) {
                        $download_url = $presigned_url;
                    }
                }
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['report_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['report_type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['report_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['report_period']) . "</td>";
                echo "<td>" . htmlspecialchars($row['from_date']) . " to " . htmlspecialchars($row['to_date']) . "</td>";
                echo "<td class='url-cell'><a href='" . htmlspecialchars($download_url) . "' target='_blank' title='Click to download (Presigned URL, valid for 7 days)'>Download</a><br><small style='color:#666;'>" . htmlspecialchars(substr($download_url, 0, 80)) . "...</small></td>";
                echo "<td class='" . ($row['email_sent'] ? 'status-yes' : 'status-no') . "'>" . ($row['email_sent'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($row['email_sent_at'] ? htmlspecialchars($row['email_sent_at']) : '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No download links found.</p>";
        }
        ?>
    </body>
    </html>
    <?php
}
?>


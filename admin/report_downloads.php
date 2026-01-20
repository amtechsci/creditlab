<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/s3_aws_sdk.php';

// Get filters
$date_filter = isset($_GET['date']) ? towreal($_GET['date']) : null;
$type_filter = isset($_GET['type']) ? towreal($_GET['type']) : null;
$email_sent_filter = isset($_GET['email_sent']) ? $_GET['email_sent'] : null;

// Build query
$sql = "SELECT * FROM `download_links` WHERE 1=1";

if ($date_filter) {
    $sql .= " AND `report_date` = '$date_filter'";
}

if ($type_filter) {
    $sql .= " AND `report_type` = '$type_filter'";
}

if ($email_sent_filter !== null && $email_sent_filter !== '') {
    $email_sent_filter = (int)$email_sent_filter;
    $sql .= " AND `email_sent` = $email_sent_filter";
}

$sql .= " ORDER BY `created_at` DESC LIMIT 100";

$result = towquery($sql);
?>
<body>
    <?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>
    <!-- Mobile Menu end -->
    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="breadcome-list">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <ul class="breadcome-menu">
                                    <li><a href="index.php">Home</a> <span class="bread-slash">/</span>
                                    </li>
                                    <li><span class="bread-blod">Report Downloads</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- Single pro tab review Start-->
    <div class="single-pro-review-area mt-t-30 mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
                        <div class="review-content-section">
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade active in" id="description">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <h2>CreditLab Report Downloads</h2>
                                                
                                                <div class="filter-form" style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
                                                    <form method="GET" class="form-inline">
                                                        <div class="form-group" style="margin-right: 15px;">
                                                            <label style="margin-right: 5px;">Date:</label>
                                                            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter ?? ''); ?>">
                                                        </div>
                                                        
                                                        <div class="form-group" style="margin-right: 15px;">
                                                            <label style="margin-right: 5px;">Report Type:</label>
                                                            <select name="type" class="form-control">
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
                                                        </div>
                                                        
                                                        <div class="form-group" style="margin-right: 15px;">
                                                            <label style="margin-right: 5px;">Email Sent:</label>
                                                            <select name="email_sent" class="form-control">
                                                                <option value="">All</option>
                                                                <option value="1" <?php echo ($email_sent_filter === '1') ? 'selected' : ''; ?>>Yes</option>
                                                                <option value="0" <?php echo ($email_sent_filter === '0') ? 'selected' : ''; ?>>No</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <button type="submit" class="btn btn-primary">Filter</button>
                                                        <a href="report_downloads.php" class="btn btn-default">Clear</a>
                                                    </form>
                                                </div>
                                                
                                                <?php
                                                if ($result && townum($result) > 0) {
                                                    echo '<div class="table-responsive">';
                                                    echo '<table class="table table-bordered table-striped">';
                                                    echo '<thead>';
                                                    echo '<tr>';
                                                    echo '<th>ID</th>';
                                                    echo '<th>Report Name</th>';
                                                    echo '<th>Report Type</th>';
                                                    echo '<th>Report Date</th>';
                                                    echo '<th>Period</th>';
                                                    echo '<th>Date Range</th>';
                                                    echo '<th>Download</th>';
                                                    echo '<th>Email Sent</th>';
                                                    echo '<th>Email Sent At</th>';
                                                    echo '<th>Created At</th>';
                                                    echo '</tr>';
                                                    echo '</thead>';
                                                    echo '<tbody>';
                                                    
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
                                                        
                                                        echo '<tr>';
                                                        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($row['report_name']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($row['report_type']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($row['report_date']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($row['report_period']) . '</td>';
                                                        echo '<td>' . htmlspecialchars($row['from_date']) . ' to ' . htmlspecialchars($row['to_date']) . '</td>';
                                                        echo '<td><a href="' . htmlspecialchars($download_url) . '" target="_blank" class="btn btn-success btn-sm" title="Click to download (Presigned URL, valid for 7 days)"><i class="fa fa-download"></i> Download</a></td>';
                                                        echo '<td><span class="' . ($row['email_sent'] ? 'text-success' : 'text-danger') . '">' . ($row['email_sent'] ? 'Yes' : 'No') . '</span></td>';
                                                        echo '<td>' . ($row['email_sent_at'] ? htmlspecialchars($row['email_sent_at']) : '-') . '</td>';
                                                        echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                                                        echo '</tr>';
                                                    }
                                                    
                                                    echo '</tbody>';
                                                    echo '</table>';
                                                    echo '</div>';
                                                } else {
                                                    echo '<div class="alert alert-info">No download links found.</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    include_once 'foot.php';
    ?>
</body>
</html>

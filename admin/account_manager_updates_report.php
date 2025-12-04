<?php
include_once 'head.php';

// Get filter parameters
$from_date = isset($_GET['from_date']) ? towreal($_GET['from_date']) : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? towreal($_GET['to_date']) : date('Y-m-d');
$filter_user = isset($_GET['filter_user']) ? towreal($_GET['filter_user']) : '';

// Validate dates
if (empty($from_date) || !strtotime($from_date)) {
    $from_date = date('Y-m-d', strtotime('-30 days'));
}
if (empty($to_date) || !strtotime($to_date)) {
    $to_date = date('Y-m-d');
}

// Build WHERE clause for date filtering
$date_where = "DATE(updated_at) >= '$from_date' AND DATE(updated_at) <= '$to_date'";

// Build WHERE clause for user filtering
$user_where = '';
if (!empty($filter_user)) {
    $filter_user_escaped = towreal($filter_user);
    $user_where = "AND updated_by = '$filter_user_escaped'";
}

// Get total updates count
$total_updates_query = "SELECT COUNT(*) as total FROM loan_acc_man WHERE $date_where $user_where";
$total_updates_result = towfetch(towquery($total_updates_query));
$total_updates = isset($total_updates_result['total']) ? $total_updates_result['total'] : 0;

// Get unique users count
$unique_users_query = "SELECT COUNT(DISTINCT updated_by) as unique_users FROM loan_acc_man WHERE $date_where $user_where AND updated_by IS NOT NULL AND updated_by != ''";
$unique_users_result = towfetch(towquery($unique_users_query));
$unique_users = isset($unique_users_result['unique_users']) ? $unique_users_result['unique_users'] : 0;

// Get updates grouped by user (for summary table)
$summary_query = "SELECT updated_by, COUNT(*) as update_count, 
                  MIN(updated_at) as first_update, 
                  MAX(updated_at) as last_update
                  FROM loan_acc_man 
                  WHERE $date_where $user_where AND updated_by IS NOT NULL AND updated_by != ''
                  GROUP BY updated_by 
                  ORDER BY update_count DESC";
$summary_results = towquery($summary_query);

// Get all updates with details (for detailed table)
$details_query = "SELECT lam.*, u.name as customer_name, u.mobile as customer_mobile, u.email as customer_email
                  FROM loan_acc_man lam
                  LEFT JOIN user u ON lam.uid = u.id
                  WHERE $date_where $user_where
                  ORDER BY lam.updated_at DESC
                  LIMIT 500";
$details_results = towquery($details_query);

// Get list of all users who have made updates (for filter dropdown)
$all_updaters_query = "SELECT DISTINCT updated_by FROM loan_acc_man WHERE updated_by IS NOT NULL AND updated_by != '' ORDER BY updated_by";
$all_updaters = towquery($all_updaters_query);
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
                                <h2>Account Manager Updates Report</h2>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <ul class="breadcome-menu">
                                    <li><a href="index.php">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Updates Report</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="analytics-sparkle-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                    <div class="analytics-sparkle-line reso-mg-b-30">
                        <div class="analytics-content">
                            <h5>Total Updates</h5>
                            <h2><span class="counter"><?=$total_updates;?></span></h2>
                            <span class="text-muted">From <?=date('d M Y', strtotime($from_date));?> to <?=date('d M Y', strtotime($to_date));?></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                    <div class="analytics-sparkle-line reso-mg-b-30">
                        <div class="analytics-content">
                            <h5>Unique Users</h5>
                            <h2><span class="counter"><?=$unique_users;?></span></h2>
                            <span class="text-muted">Users who made updates</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                    <div class="analytics-sparkle-line reso-mg-b-30">
                        <div class="analytics-content">
                            <h5>Date Range</h5>
                            <h2><span><?=date('d M', strtotime($from_date));?> - <?=date('d M', strtotime($to_date));?></span></h2>
                            <span class="text-muted"><?=round((strtotime($to_date) - strtotime($from_date)) / (60 * 60 * 24));?> days</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="single-pro-review-area mt-t-30 mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
                        <div class="card">
                            <div class="card-header">
                                <h4>Filters</h4>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="" class="form-inline">
                                    <div class="form-group" style="margin-right: 15px;">
                                        <label for="from_date" style="margin-right: 10px;">From Date:</label>
                                        <input type="date" name="from_date" id="from_date" class="form-control" value="<?=$from_date;?>" required>
                                    </div>
                                    <div class="form-group" style="margin-right: 15px;">
                                        <label for="to_date" style="margin-right: 10px;">To Date:</label>
                                        <input type="date" name="to_date" id="to_date" class="form-control" value="<?=$to_date;?>" required>
                                    </div>
                                    <div class="form-group" style="margin-right: 15px;">
                                        <label for="filter_user" style="margin-right: 10px;">Filter by User:</label>
                                        <select name="filter_user" id="filter_user" class="form-control">
                                            <option value="">All Users</option>
                                            <?php 
                                            while($updater = towfetch($all_updaters)) {
                                                $selected = ($filter_user == $updater['updated_by']) ? 'selected' : '';
                                                echo "<option value='".htmlspecialchars($updater['updated_by'])."' $selected>".htmlspecialchars($updater['updated_by'])."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success" style="margin-right: 10px;">Apply Filters</button>
                                    <a href="account_manager_updates_report.php" class="btn btn-default">Reset</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary by User -->
    <div class="single-pro-review-area mt-t-30 mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
                        <ul id="myTabedu1" class="tab-review-design">
                            <li class="active"><a href="#summary">Summary by User</a></li>
                            <li><a href="#details">All Updates Details</a></li>
                        </ul>
                        <div id="myTabContent" class="tab-content custom-product-edit">
                            <!-- Summary Tab -->
                            <div class="product-tab-list tab-pane fade active in" id="summary">
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="review-content-section">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Updated By</th>
                                                            <th>Total Updates</th>
                                                            <th>First Update</th>
                                                            <th>Last Update</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $row_num = 1;
                                                        while($summary = towfetch($summary_results)) {
                                                            $first_update = $summary['first_update'] ? date('d M Y H:i', strtotime($summary['first_update'])) : 'N/A';
                                                            $last_update = $summary['last_update'] ? date('d M Y H:i', strtotime($summary['last_update'])) : 'N/A';
                                                            ?>
                                                            <tr>
                                                                <td><?=$row_num++;?></td>
                                                                <td><strong><?=htmlspecialchars($summary['updated_by']);?></strong></td>
                                                                <td><span class="badge badge-primary" style="font-size: 14px;"><?=$summary['update_count'];?></span></td>
                                                                <td><?=$first_update;?></td>
                                                                <td><?=$last_update;?></td>
                                                                <td>
                                                                    <a href="?from_date=<?=$from_date;?>&to_date=<?=$to_date;?>&filter_user=<?=urlencode($summary['updated_by']);?>" class="btn btn-sm btn-info">View Details</a>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                        }
                                                        if($row_num == 1) {
                                                            echo "<tr><td colspan='6' class='text-center'>No updates found for the selected date range.</td></tr>";
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Details Tab -->
                            <div class="product-tab-list tab-pane fade" id="details">
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="review-content-section">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped" id="updatesTable">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Updated By</th>
                                                            <th>Customer Name</th>
                                                            <th>Loan ID</th>
                                                            <th>Customer Response</th>
                                                            <th>Commitment Date</th>
                                                            <th>Commitment Text</th>
                                                            <th>Default Type</th>
                                                            <th>Updated At</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $detail_num = 1;
                                                        while($detail = towfetch($details_results)) {
                                                            $updated_at = $detail['updated_at'] ? date('d M Y H:i:s', strtotime($detail['updated_at'])) : 'N/A';
                                                            $commitment_date = $detail['commitment_date'] ? date('d M Y', strtotime($detail['commitment_date'])) : 'N/A';
                                                            ?>
                                                            <tr>
                                                                <td><?=$detail_num++;?></td>
                                                                <td><strong><?=htmlspecialchars($detail['updated_by']);?></strong></td>
                                                                <td>
                                                                    <?php if($detail['customer_name']): ?>
                                                                        <a href="profile.php?id=<?=$detail['uid'];?>" target="_blank">
                                                                            <?=htmlspecialchars($detail['customer_name']);?>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        User ID: <?=$detail['uid'];?>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>CLL<?=$detail['lid'];?></td>
                                                                <td><?=htmlspecialchars($detail['customer_response']);?></td>
                                                                <td><?=$commitment_date;?></td>
                                                                <td><?=htmlspecialchars(substr($detail['commitment_text'], 0, 50));?><?=strlen($detail['commitment_text']) > 50 ? '...' : '';?></td>
                                                                <td><?=htmlspecialchars($detail['default_type']);?></td>
                                                                <td><?=$updated_at;?></td>
                                                            </tr>
                                                            <?php
                                                        }
                                                        if($detail_num == 1) {
                                                            echo "<tr><td colspan='9' class='text-center'>No updates found for the selected filters.</td></tr>";
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
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
    
    <script>
    // Initialize DataTable if available
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('#updatesTable').DataTable({
                "pageLength": 25,
                "order": [[ 8, "desc" ]], // Sort by Updated At column
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "No entries found",
                    "infoFiltered": "(filtered from _TOTAL_ total entries)"
                }
            });
        }
    });
    </script>
</body>
</html>


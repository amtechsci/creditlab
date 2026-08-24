<?php
include_once 'head.php';

$selected_date = isset($_GET['date']) ? towreal($_GET['date']) : date('Y-m-d');
$selected_user = isset($_GET['user']) ? towreal($_GET['user']) : '';
$view_mode = isset($_GET['view']) ? towreal($_GET['view']) : 'userwise';
if ($view_mode !== 'updates') {
    $view_mode = 'userwise';
}

if (empty($selected_date) || !strtotime($selected_date)) {
    $selected_date = date('Y-m-d');
}

$month_start = date('Y-m-01', strtotime($selected_date));
$month_end = $selected_date;
$day_start = $selected_date . ' 00:00:00';
$day_end = date('Y-m-d', strtotime($selected_date . ' +1 day')) . ' 00:00:00';
$month_start_ts = $month_start . ' 00:00:00';
$month_end_exclusive = date('Y-m-d', strtotime($month_end . ' +1 day')) . ' 00:00:00';

$responding_categories_lower = array_unique(array_map('strtolower', [
    'shall pay by eod',
    'shall pay tomorrow',
    'shall pay ontime',
    'shall pay on time',
    'need extension',
    'called back',
    'shall pay part payment',
    'Sell pay part payment',
    'already paid',
    'sms sent by mobile',
]));
$not_responding_categories_lower = array_unique(array_map('strtolower', [
    'call not answering',
    'cutting call',
    'Cut the call',
    'cutting the call',
    'switched off',
    'Mobile switched off',
    'out of coverage',
    'Out of coverage area',
    'number not working',
    'wrong number',
    'Wrong no',
    'call answered but no proper response',
    'Call lifted by others',
]));
$repayment_responding_lower = [
    'shall pay by eod',
    'shall pay tomorrow',
    'shall pay ontime',
    'shall pay on time',
    'need extension',
    'called back',
    'shall pay part payment',
    'already paid',
    'sms sent by mobile',
];

$from_date = isset($_GET['from_date']) ? towreal($_GET['from_date']) : $month_start;
$to_date = isset($_GET['to_date']) ? towreal($_GET['to_date']) : $month_end;
if (empty($from_date) || !strtotime($from_date)) {
    $from_date = $month_start;
}
if (empty($to_date) || !strtotime($to_date)) {
    $to_date = $month_end;
}
$from_ts = $from_date . ' 00:00:00';
$to_exclusive = date('Y-m-d', strtotime($to_date . ' +1 day')) . ' 00:00:00';

$selected_user_escaped = !empty($selected_user) ? towreal($selected_user) : '';

function performance_dashboard_sql_in(array $values): string
{
    $parts = [];
    foreach ($values as $value) {
        $parts[] = "'" . towreal($value) . "'";
    }
    return implode(',', $parts);
}

function performance_dashboard_fetch_all($result): array
{
    $rows = [];
    if ($result) {
        while ($row = towfetch($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function performance_dashboard_repayments_by_user(string $pay_start, string $pay_end, string $responding_in, ?string $lam_before = null): array
{
    $map = [];
    $lam_date_sql = $lam_before !== null ? "AND updated_at < '" . towreal($lam_before) . "'" : '';
    $query = "SELECT last_lam.updated_by,
                     COUNT(DISTINCT td.cllid) AS cnt,
                     COALESCE(SUM(td.transaction_amount), 0) AS total_amount
              FROM transaction_details td
              INNER JOIN (
                  SELECT lam.lid, lam.updated_by
                  FROM loan_acc_man lam
                  INNER JOIN (
                      SELECT lid, updated_by, MAX(id) AS max_id
                      FROM loan_acc_man
                      WHERE updated_by IS NOT NULL AND updated_by != ''
                        $lam_date_sql
                        AND lid IN (
                            SELECT cllid FROM transaction_details
                            WHERE transaction_date >= '$pay_start'
                              AND transaction_date < '$pay_end'
                              AND transaction_flow = 'full'
                        )
                      GROUP BY lid, updated_by
                  ) t ON lam.id = t.max_id
                  WHERE LOWER(TRIM(lam.customer_response)) IN ($responding_in)
              ) last_lam ON last_lam.lid = td.cllid
              WHERE td.transaction_date >= '$pay_start'
                AND td.transaction_date < '$pay_end'
                AND td.transaction_flow = 'full'
              GROUP BY last_lam.updated_by";
    foreach (performance_dashboard_fetch_all(towquery($query)) as $row) {
        $map[$row['updated_by']] = [
            'count' => (int) $row['cnt'],
            'amount' => (float) $row['total_amount'],
        ];
    }
    return $map;
}

$user_wise_rows = [];
$today_repay_by_user = [];
$monthly_repay_by_user = [];
$total_updates = 0;
$summary_rows = [];
$details_rows = [];
$account_managers = [];

$responding_in = performance_dashboard_sql_in($responding_categories_lower);
$not_responding_in = performance_dashboard_sql_in($not_responding_categories_lower);
$repay_responding_in = performance_dashboard_sql_in($repayment_responding_lower);

if ($view_mode === 'userwise') {
    $user_wise_query = "SELECT
                        lam.updated_by,
                        COUNT(*) as total_calls,
                        SUM(CASE WHEN LOWER(TRIM(lam.customer_response)) IN ($responding_in) THEN 1 ELSE 0 END) as responding_count,
                        SUM(CASE WHEN LOWER(TRIM(lam.customer_response)) IN ($not_responding_in) THEN 1 ELSE 0 END) as not_responding_count
                        FROM loan_acc_man lam
                        WHERE lam.updated_at >= '$day_start' AND lam.updated_at < '$day_end'
                        AND lam.updated_by IS NOT NULL AND lam.updated_by != ''
                        GROUP BY lam.updated_by
                        ORDER BY total_calls DESC";
    $user_wise_rows = performance_dashboard_fetch_all(towquery($user_wise_query));

    if (!empty($user_wise_rows)) {
        // Today: last follow-up by that user on loans cleared today (no date cap on last update).
        $today_repay_by_user = performance_dashboard_repayments_by_user(
            $day_start,
            $day_end,
            $repay_responding_in
        );
        $monthly_repay_by_user = performance_dashboard_repayments_by_user(
            $month_start_ts,
            $month_end_exclusive,
            $repay_responding_in,
            $month_end_exclusive
        );
    }
} else {
    $user_where_updates = '';
    if ($selected_user_escaped !== '') {
        $user_where_updates = "AND updated_by = '$selected_user_escaped'";
    }

    $total_updates_result = towfetch(towquery(
        "SELECT COUNT(*) as total FROM loan_acc_man
         WHERE updated_at >= '$from_ts' AND updated_at < '$to_exclusive' $user_where_updates"
    ));
    $total_updates = isset($total_updates_result['total']) ? (int) $total_updates_result['total'] : 0;

    $summary_query = "SELECT updated_by, COUNT(*) as update_count,
                      MIN(updated_at) as first_update,
                      MAX(updated_at) as last_update
                      FROM loan_acc_man
                      WHERE updated_at >= '$from_ts' AND updated_at < '$to_exclusive'
                        $user_where_updates
                        AND updated_by IS NOT NULL AND updated_by != ''
                      GROUP BY updated_by
                      ORDER BY update_count DESC";
    $summary_rows = performance_dashboard_fetch_all(towquery($summary_query));

    $details_query = "SELECT lam.id, lam.uid, lam.lid, lam.customer_response, lam.commitment_date,
                             lam.commitment_text, lam.default_type, lam.updated_at, lam.updated_by,
                             u.name as customer_name, u.mobile as customer_mobile, u.email as customer_email
                      FROM loan_acc_man lam
                      LEFT JOIN user u ON lam.uid = u.id
                      WHERE lam.updated_at >= '$from_ts' AND lam.updated_at < '$to_exclusive' $user_where_updates
                      ORDER BY lam.updated_at DESC
                      LIMIT 500";
    $details_rows = performance_dashboard_fetch_all(towquery($details_query));

    $account_managers = performance_dashboard_fetch_all(towquery(
        "SELECT DISTINCT updated_by FROM loan_acc_man
         WHERE updated_by IS NOT NULL AND updated_by != ''
         ORDER BY updated_by"
    ));
}

$userwise_url = 'performance_dashboard.php?view=userwise&date=' . urlencode($selected_date);
$updates_url = 'performance_dashboard.php?view=updates&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date);
if ($selected_user !== '') {
    $updates_url .= '&user=' . urlencode($selected_user);
}
?>

<body>
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
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <h2>Performance Dashboard</h2>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <ul class="breadcome-menu">
                                    <li><a href="index.php">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Performance</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="single-pro-review-area mt-t-30 mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
                        <ul id="myTabedu1" class="tab-review-design">
                            <li class="<?=$view_mode == 'userwise' ? 'active' : '';?>"><a href="<?= htmlspecialchars($userwise_url) ?>">User-wise Report</a></li>
                            <li class="<?=$view_mode == 'updates' ? 'active' : '';?>"><a href="<?= htmlspecialchars($updates_url) ?>">Updates Report</a></li>
                        </ul>
                        <div id="myTabContent" class="tab-content custom-product-edit">
                            <!-- User-wise Report Tab -->
                            <div class="product-tab-list tab-pane fade <?=$view_mode == 'userwise' ? 'active in' : '';?>" id="userwise">
                                <?php if ($view_mode !== 'userwise'): ?>
                                <p style="padding: 20px;">Open the User-wise Report tab to load this data.</p>
                                <?php else: ?>
                                <form method="GET" action="" class="form-inline" style="padding: 20px;">
                                    <input type="hidden" name="view" value="userwise">
                                    <div class="form-group" style="margin-right: 20px;">
                                        <label for="date" style="margin-right: 10px;">Date:</label>
                                        <input type="date" name="date" id="date" class="form-control" value="<?=$selected_date;?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </form>
                                
                                <div style="margin-top: 30px;">
                                    <h3 style="margin-bottom: 20px;">User-wise Performance Report (<?=date('d-M-Y', strtotime($selected_date));?>)</h3>
                                    <div class="row">
                                        <?php
                                        foreach ($user_wise_rows as $user_data) {
                                            $user_total = $user_data['total_calls'];
                                            $user_responding = $user_data['responding_count'];
                                            $user_not_responding = $user_data['not_responding_count'];
                                            $user_performance = ($user_total > 0) ? round(($user_responding / $user_total) * 100, 2) : 0;
                                            $by = $user_data['updated_by'];
                                            $repayment_data = [
                                                'today_count' => $today_repay_by_user[$by]['count'] ?? 0,
                                                'today_amount' => $today_repay_by_user[$by]['amount'] ?? 0,
                                                'monthly_count' => $monthly_repay_by_user[$by]['count'] ?? 0,
                                                'monthly_amount' => $monthly_repay_by_user[$by]['amount'] ?? 0,
                                            ];
                                            ?>
                                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12" style="margin-bottom: 30px;">
                                                <div class="product-payment-inner-st" style="border: 1px solid #ddd; border-radius: 5px; padding: 20px;">
                                                    <h3 style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">
                                                        <?=htmlspecialchars($user_data['updated_by']);?>
                                                    </h3>
                                                    <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div style="text-align: center; padding: 15px; background-color: #E3F2FD; border-radius: 5px; margin-bottom: 10px;">
                                                                <div style="width: 50px; height: 50px; background-color: #2196F3; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                                                                    <span style="color: white; font-weight: bold;">R</span>
                                                                </div>
                                                                <h5 style="margin: 0;">Responding</h5>
                                                                <h2 style="margin: 5px 0 0; color: #2196F3;"><?=$user_responding;?></h2>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div style="text-align: center; padding: 15px; background-color: #FFEBEE; border-radius: 5px; margin-bottom: 10px;">
                                                                <div style="width: 50px; height: 50px; background-color: #F44336; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                                                                    <span style="color: white; font-weight: bold;">NR</span>
                                                                </div>
                                                                <h5 style="margin: 0;">Not Responding</h5>
                                                                <h2 style="margin: 5px 0 0; color: #F44336;"><?=$user_not_responding;?></h2>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div style="text-align: center; padding: 15px; background-color: #FFF3E0; border-radius: 5px; margin-bottom: 10px;">
                                                                <div style="width: 50px; height: 50px; background-color: #FF9800; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                                                                    <span style="color: white; font-weight: bold;">T</span>
                                                                </div>
                                                                <h5 style="margin: 0;">Total</h5>
                                                                <h2 style="margin: 5px 0 0; color: #FF9800;"><?=$user_total;?></h2>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div style="text-align: center; padding: 15px; background-color: #E8F5E9; border-radius: 5px; margin-bottom: 10px;">
                                                                <div style="width: 50px; height: 50px; background-color: #4CAF50; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                                                                    <span style="color: white; font-weight: bold;">%</span>
                                                                </div>
                                                                <h5 style="margin: 0;">Performance</h5>
                                                                <h2 style="margin: 5px 0 0; color: #4CAF50;"><?=$user_performance;?>%</h2>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Repayments Section -->
                                                    <div style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
                                                        <h4 style="margin-bottom: 15px; color: #666;">Repayments</h4>
                                                        <div class="row">
                                                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                                <div style="padding: 10px; background-color: #f5f5f5; border-radius: 5px; margin-bottom: 10px;">
                                                                    <h6 style="margin: 0 0 5px 0; color: #666; font-size: 12px;">Today_Cleared Follow-up</h6>
                                                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                                                        <span style="font-size: 18px; font-weight: bold; color: #2196F3;"><?=$repayment_data['today_count'];?></span>
                                                                        <span style="font-size: 14px; color: #666;">₹<?=number_format($repayment_data['today_amount'], 2);?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                                <div style="padding: 10px; background-color: #f5f5f5; border-radius: 5px; margin-bottom: 10px;">
                                                                    <h6 style="margin: 0 0 5px 0; color: #666; font-size: 12px;">Monthly_Cleared Follow-up</h6>
                                                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                                                        <span style="font-size: 18px; font-weight: bold; color: #4CAF50;"><?=$repayment_data['monthly_count'];?></span>
                                                                        <span style="font-size: 14px; color: #666;">₹<?=number_format($repayment_data['monthly_amount'], 2);?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        if (empty($user_wise_rows)) {
                                            echo '<div class="col-lg-12"><div class="alert alert-info">No data found for the selected date.</div></div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Updates Report Tab -->
                            <div class="product-tab-list tab-pane fade <?=$view_mode == 'updates' ? 'active in' : '';?>" id="updates">
                                <?php if ($view_mode !== 'updates'): ?>
                                <p style="padding: 20px;">Open the Updates Report tab to load this data.</p>
                                <?php else: ?>
                                <form method="GET" action="" class="form-inline" style="padding: 20px;">
                                    <input type="hidden" name="view" value="updates">
                                    <div class="form-group" style="margin-right: 15px;">
                                        <label for="from_date" style="margin-right: 10px;">From Date:</label>
                                        <input type="date" name="from_date" id="from_date" class="form-control" value="<?=$from_date;?>" required>
                                    </div>
                                    <div class="form-group" style="margin-right: 15px;">
                                        <label for="to_date" style="margin-right: 10px;">To Date:</label>
                                        <input type="date" name="to_date" id="to_date" class="form-control" value="<?=$to_date;?>" required>
                                    </div>
                                    <div class="form-group" style="margin-right: 15px;">
                                        <label for="user" style="margin-right: 10px;">Filter by User:</label>
                                        <select name="user" id="user" class="form-control">
                                            <option value="">All Users</option>
                                            <?php foreach ($account_managers as $am): ?>
                                                <option value="<?=htmlspecialchars($am['updated_by']);?>" <?=$selected_user == $am['updated_by'] ? 'selected' : '';?>>
                                                    <?=htmlspecialchars($am['updated_by']);?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success" style="margin-right: 10px;">Apply Filters</button>
                                    <a href="performance_dashboard.php?view=updates" class="btn btn-default">Reset</a>
                                </form>
                                
                                <!-- Summary Statistics -->
                                <div class="analytics-sparkle-area" style="margin-top: 20px;">
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
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Summary by User -->
                                <div style="margin-top: 30px;">
                                    <h3 style="margin-bottom: 20px;">Summary by User</h3>
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
                                                foreach ($summary_rows as $summary) {
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
                                                            <a href="?view=updates&from_date=<?=$from_date;?>&to_date=<?=$to_date;?>&user=<?=urlencode($summary['updated_by']);?>" class="btn btn-sm btn-info">View Details</a>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                if ($row_num == 1) {
                                                    echo "<tr><td colspan='6' class='text-center'>No updates found for the selected date range.</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- All Updates Details -->
                                <div style="margin-top: 30px;">
                                    <h3 style="margin-bottom: 20px;">All Updates Details</h3>
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
                                                foreach ($details_rows as $detail) {
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
                                                if ($detail_num == 1) {
                                                    echo "<tr><td colspan='9' class='text-center'>No updates found for the selected filters.</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endif; ?>
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
    $(document).ready(function() {
        if ($.fn.DataTable && $('#updatesTable').length) {
            $('#updatesTable').DataTable({
                "pageLength": 25,
                "order": [[ 8, "desc" ]],
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

<?php
include_once 'head.php';

// Get filter parameters
$selected_date = isset($_GET['date']) ? towreal($_GET['date']) : date('Y-m-d');
$selected_user = isset($_GET['user']) ? towreal($_GET['user']) : '';
$view_mode = isset($_GET['view']) ? towreal($_GET['view']) : 'performance'; // 'performance' or 'updates'

// Validate date
if (empty($selected_date) || !strtotime($selected_date)) {
    $selected_date = date('Y-m-d');
}

// Calculate monthly date range (1st of current month to selected date)
$month_start = date('Y-m-01', strtotime($selected_date));
$month_end = $selected_date;

// Define responding and not responding categories (case-insensitive matching)
// Responding categories
$responding_categories = [
    'shall pay by eod',
    'shall pay by EOD',
    'Shall pay by eod',
    'Shall pay by EOD',
    'shall pay tomorrow',
    'Shall pay tomorrow',
    'shall pay ontime',
    'Shall pay ontime',
    'shall pay on time',
    'Shall pay on time',
    'need extension',
    'Need extension',
    'called back',
    'Called back',
    'shall pay part payment',
    'Shall pay part payment',
    'Sell pay part payment',
    'already paid',
    'Already paid',
    'Already Paid',
    'sms sent by mobile',
    'SMS sent by mobile',
    'SMS Sent by mobile'
];

// Not responding categories
$not_responding_categories = [
    'call not answering',
    'Call not answering',
    'cutting call',
    'Cutting call',
    'Cut the call',
    'cutting the call',
    'switched off',
    'Switched off',
    'Mobile switched off',
    'out of coverage',
    'Out of coverage',
    'Out of coverage area',
    'number not working',
    'Number not working',
    'wrong number',
    'Wrong number',
    'Wrong no',
    'call answered but no proper response',
    'Call answered but no proper response',
    'Call lifted by others'
];

// Build WHERE clause for user filtering
$user_where = '';
if (!empty($selected_user)) {
    $selected_user_escaped = towreal($selected_user);
    $user_where = "AND lam.updated_by = '$selected_user_escaped'";
}

// Get today's follow-up calls (loan_acc_man entries created today)
$today_where = "DATE(lam.updated_at) = '$selected_date' $user_where";
$today_followup_query = "SELECT COUNT(*) as count FROM loan_acc_man lam WHERE $today_where";
$today_followup_result = towfetch(towquery($today_followup_query));
$today_followup_calls = isset($today_followup_result['count']) ? (int)$today_followup_result['count'] : 0;

// Normalize categories to lowercase for comparison
$responding_categories_lower = array_map('strtolower', $responding_categories);
$not_responding_categories_lower = array_map('strtolower', $not_responding_categories);

// Get responding count for today
$responding_conditions = [];
foreach($responding_categories_lower as $cat) {
    $responding_conditions[] = "LOWER(TRIM(lam.customer_response)) = '" . towreal($cat) . "'";
}
$today_responding_query = "SELECT COUNT(*) as count FROM loan_acc_man lam 
                          WHERE $today_where 
                          AND (" . implode(" OR ", $responding_conditions) . ")";
$today_responding_result = towfetch(towquery($today_responding_query));
$today_responding = isset($today_responding_result['count']) ? (int)$today_responding_result['count'] : 0;

// Get not responding count for today
$not_responding_conditions = [];
foreach($not_responding_categories_lower as $cat) {
    $not_responding_conditions[] = "LOWER(TRIM(lam.customer_response)) = '" . towreal($cat) . "'";
}
$today_not_responding_query = "SELECT COUNT(*) as count FROM loan_acc_man lam 
                               WHERE $today_where 
                               AND (" . implode(" OR ", $not_responding_conditions) . ")";
$today_not_responding_result = towfetch(towquery($today_not_responding_query));
$today_not_responding = isset($today_not_responding_result['count']) ? (int)$today_not_responding_result['count'] : 0;

// Get monthly follow-up calls
$monthly_where = "DATE(lam.updated_at) >= '$month_start' AND DATE(lam.updated_at) <= '$month_end' $user_where";
$monthly_followup_query = "SELECT COUNT(*) as count FROM loan_acc_man lam WHERE $monthly_where";
$monthly_followup_result = towfetch(towquery($monthly_followup_query));
$monthly_followup_calls = isset($monthly_followup_result['count']) ? (int)$monthly_followup_result['count'] : 0;

// Get monthly responding count
$monthly_responding_query = "SELECT COUNT(*) as count FROM loan_acc_man lam 
                             WHERE $monthly_where 
                             AND (" . implode(" OR ", $responding_conditions) . ")";
$monthly_responding_result = towfetch(towquery($monthly_responding_query));
$monthly_responding = isset($monthly_responding_result['count']) ? (int)$monthly_responding_result['count'] : 0;

// Get monthly not responding count
$monthly_not_responding_query = "SELECT COUNT(*) as count FROM loan_acc_man lam 
                                 WHERE $monthly_where 
                                 AND (" . implode(" OR ", $not_responding_conditions) . ")";
$monthly_not_responding_result = towfetch(towquery($monthly_not_responding_query));
$monthly_not_responding = isset($monthly_not_responding_result['count']) ? (int)$monthly_not_responding_result['count'] : 0;

// Calculate performance percentage (fix NaN issue)
$today_performance = 0;
if ($today_followup_calls > 0 && $today_responding >= 0) {
    $today_performance = round(($today_responding / $today_followup_calls) * 100, 2);
}
$monthly_performance = 0;
if ($monthly_followup_calls > 0 && $monthly_responding >= 0) {
    $monthly_performance = round(($monthly_responding / $monthly_followup_calls) * 100, 2);
}

// Get repayment statistics
// Today cleared follow-up (loans cleared today where there was a follow-up entry)
$today_cleared_followup_query = "SELECT COUNT(DISTINCT td.cllid) as count, COALESCE(SUM(td.transaction_amount), 0) as total_amount
                                FROM transaction_details td
                                INNER JOIN loan_acc_man lam ON td.cllid = lam.lid
                                WHERE DATE(td.transaction_date) = '$selected_date'
                                AND td.transaction_flow = 'full'
                                AND DATE(lam.updated_at) = '$selected_date'";
if (!empty($selected_user)) {
    $today_cleared_followup_query .= " AND lam.updated_by = '$selected_user_escaped'";
}
$today_cleared_followup_result = towfetch(towquery($today_cleared_followup_query));
$today_cleared_followup_count = isset($today_cleared_followup_result['count']) ? (int)$today_cleared_followup_result['count'] : 0;
$today_cleared_followup_amount = isset($today_cleared_followup_result['total_amount']) ? (float)$today_cleared_followup_result['total_amount'] : 0;

// Today cleared auto (loans cleared today via autopay/enach)
$today_cleared_auto_query = "SELECT COUNT(DISTINCT td.cllid) as count, COALESCE(SUM(td.transaction_amount), 0) as total_amount
                            FROM transaction_details td
                            WHERE DATE(td.transaction_date) = '$selected_date'
                            AND td.transaction_flow = 'full'
                            AND td.cllid NOT IN (
                                SELECT DISTINCT lid FROM loan_acc_man 
                                WHERE DATE(updated_at) = '$selected_date'";
if (!empty($selected_user)) {
    $today_cleared_auto_query .= " AND updated_by = '$selected_user_escaped'";
}
$today_cleared_auto_query .= ")";
$today_cleared_auto_result = towfetch(towquery($today_cleared_auto_query));
$today_cleared_auto_count = isset($today_cleared_auto_result['count']) ? (int)$today_cleared_auto_result['count'] : 0;
$today_cleared_auto_amount = isset($today_cleared_auto_result['total_amount']) ? (float)$today_cleared_auto_result['total_amount'] : 0;

// Today part payment
$today_part_payment_query = "SELECT COUNT(DISTINCT td.cllid) as count, COALESCE(SUM(td.transaction_amount), 0) as total_amount
                             FROM transaction_details td
                             WHERE DATE(td.transaction_date) = '$selected_date'
                             AND td.transaction_flow = 'part'";
if (!empty($selected_user)) {
    $today_part_payment_query .= " AND td.cllid IN (
        SELECT DISTINCT lid FROM loan_acc_man 
        WHERE DATE(updated_at) = '$selected_date' AND updated_by = '$selected_user_escaped'
    )";
}
$today_part_payment_result = towfetch(towquery($today_part_payment_query));
$today_part_payment_count = isset($today_part_payment_result['count']) ? (int)$today_part_payment_result['count'] : 0;
$today_part_payment_amount = isset($today_part_payment_result['total_amount']) ? (float)$today_part_payment_result['total_amount'] : 0;

// Monthly cleared follow-up
$monthly_cleared_followup_query = "SELECT COUNT(DISTINCT td.cllid) as count, COALESCE(SUM(td.transaction_amount), 0) as total_amount
                                  FROM transaction_details td
                                  INNER JOIN loan_acc_man lam ON td.cllid = lam.lid
                                  WHERE DATE(td.transaction_date) >= '$month_start' AND DATE(td.transaction_date) <= '$month_end'
                                  AND td.transaction_flow = 'full'
                                  AND DATE(lam.updated_at) >= '$month_start' AND DATE(lam.updated_at) <= '$month_end'";
if (!empty($selected_user)) {
    $monthly_cleared_followup_query .= " AND lam.updated_by = '$selected_user_escaped'";
}
$monthly_cleared_followup_result = towfetch(towquery($monthly_cleared_followup_query));
$monthly_cleared_followup_count = isset($monthly_cleared_followup_result['count']) ? (int)$monthly_cleared_followup_result['count'] : 0;
$monthly_cleared_followup_amount = isset($monthly_cleared_followup_result['total_amount']) ? (float)$monthly_cleared_followup_result['total_amount'] : 0;

// Monthly cleared auto
$monthly_cleared_auto_query = "SELECT COUNT(DISTINCT td.cllid) as count, COALESCE(SUM(td.transaction_amount), 0) as total_amount
                              FROM transaction_details td
                              WHERE DATE(td.transaction_date) >= '$month_start' AND DATE(td.transaction_date) <= '$month_end'
                              AND td.transaction_flow = 'full'
                              AND td.cllid NOT IN (
                                  SELECT DISTINCT lid FROM loan_acc_man 
                                  WHERE DATE(updated_at) >= '$month_start' AND DATE(updated_at) <= '$month_end'";
if (!empty($selected_user)) {
    $monthly_cleared_auto_query .= " AND updated_by = '$selected_user_escaped'";
}
$monthly_cleared_auto_query .= ")";
$monthly_cleared_auto_result = towfetch(towquery($monthly_cleared_auto_query));
$monthly_cleared_auto_count = isset($monthly_cleared_auto_result['count']) ? (int)$monthly_cleared_auto_result['count'] : 0;
$monthly_cleared_auto_amount = isset($monthly_cleared_auto_result['total_amount']) ? (float)$monthly_cleared_auto_result['total_amount'] : 0;

// Monthly part payment
$monthly_part_payment_query = "SELECT COUNT(DISTINCT td.cllid) as count, COALESCE(SUM(td.transaction_amount), 0) as total_amount
                               FROM transaction_details td
                               WHERE DATE(td.transaction_date) >= '$month_start' AND DATE(td.transaction_date) <= '$month_end'
                               AND td.transaction_flow = 'part'";
if (!empty($selected_user)) {
    $monthly_part_payment_query .= " AND td.cllid IN (
        SELECT DISTINCT lid FROM loan_acc_man 
        WHERE DATE(updated_at) >= '$month_start' AND DATE(updated_at) <= '$month_end' AND updated_by = '$selected_user_escaped'
    )";
}
$monthly_part_payment_result = towfetch(towquery($monthly_part_payment_query));
$monthly_part_payment_count = isset($monthly_part_payment_result['count']) ? (int)$monthly_part_payment_result['count'] : 0;
$monthly_part_payment_amount = isset($monthly_part_payment_result['total_amount']) ? (float)$monthly_part_payment_result['total_amount'] : 0;

// Calculate totals
$today_total_cleared = $today_cleared_followup_count + $today_cleared_auto_count;
$monthly_total_cleared = $monthly_cleared_followup_count + $monthly_cleared_auto_count;

// Get user-wise performance data for selected date
$user_wise_query = "SELECT 
                    lam.updated_by,
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN (" . implode(" OR ", $responding_conditions) . ") THEN 1 ELSE 0 END) as responding_count,
                    SUM(CASE WHEN (" . implode(" OR ", $not_responding_conditions) . ") THEN 1 ELSE 0 END) as not_responding_count
                    FROM loan_acc_man lam
                    WHERE DATE(lam.updated_at) = '$selected_date'
                    AND lam.updated_by IS NOT NULL AND lam.updated_by != ''
                    GROUP BY lam.updated_by
                    ORDER BY total_calls DESC";
$user_wise_results = towquery($user_wise_query);

// Get updates summary for date range (for AM Updates Report functionality)
$from_date = isset($_GET['from_date']) ? towreal($_GET['from_date']) : $month_start;
$to_date = isset($_GET['to_date']) ? towreal($_GET['to_date']) : $month_end;
$date_where_updates = "DATE(updated_at) >= '$from_date' AND DATE(updated_at) <= '$to_date'";
$user_where_updates = '';
if (!empty($selected_user)) {
    $user_where_updates = "AND updated_by = '$selected_user_escaped'";
}

// Get total updates count
$total_updates_query = "SELECT COUNT(*) as total FROM loan_acc_man WHERE $date_where_updates $user_where_updates";
$total_updates_result = towfetch(towquery($total_updates_query));
$total_updates = isset($total_updates_result['total']) ? $total_updates_result['total'] : 0;

// Get updates grouped by user (for summary table)
$summary_query = "SELECT updated_by, COUNT(*) as update_count, 
                  MIN(updated_at) as first_update, 
                  MAX(updated_at) as last_update
                  FROM loan_acc_man 
                  WHERE $date_where_updates $user_where_updates AND updated_by IS NOT NULL AND updated_by != ''
                  GROUP BY updated_by 
                  ORDER BY update_count DESC";
$summary_results = towquery($summary_query);

// Get all updates with details (for detailed table)
$details_query = "SELECT lam.*, u.name as customer_name, u.mobile as customer_mobile, u.email as customer_email
                  FROM loan_acc_man lam
                  LEFT JOIN user u ON lam.uid = u.id
                  WHERE $date_where_updates $user_where_updates
                  ORDER BY lam.updated_at DESC
                  LIMIT 500";
$details_results = towquery($details_query);

// Get list of account managers for filter
$account_managers_query = "SELECT DISTINCT updated_by FROM loan_acc_man WHERE updated_by IS NOT NULL AND updated_by != '' ORDER BY updated_by";
$account_managers = towquery($account_managers_query);
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
                            <li class="<?=$view_mode == 'performance' ? 'active' : '';?>"><a href="#performance">Performance</a></li>
                            <li class="<?=$view_mode == 'updates' ? 'active' : '';?>"><a href="#updates">Updates Report</a></li>
                            <li class="<?=$view_mode == 'userwise' ? 'active' : '';?>"><a href="#userwise">User-wise Report</a></li>
                        </ul>
                        <div id="myTabContent" class="tab-content custom-product-edit">
                            <!-- Performance Tab -->
                            <div class="product-tab-list tab-pane fade <?=$view_mode == 'performance' ? 'active in' : '';?>" id="performance">
                                <form method="GET" action="" class="form-inline" style="padding: 20px;">
                                    <input type="hidden" name="view" value="performance">
                                    <div class="form-group" style="margin-right: 20px;">
                                        <label for="date" style="margin-right: 10px;">Date:</label>
                                        <input type="date" name="date" id="date" class="form-control" value="<?=$selected_date;?>" required>
                                    </div>
                                    <div class="form-group" style="margin-right: 20px;">
                                        <label for="user" style="margin-right: 10px;">Account Manager:</label>
                                        <select name="user" id="user" class="form-control">
                                            <option value="">All Account Managers</option>
                                            <?php 
                                            $account_managers_rewind = towquery($account_managers_query);
                                            while($am = towfetch($account_managers_rewind)): ?>
                                                <option value="<?=htmlspecialchars($am['updated_by']);?>" <?=$selected_user == $am['updated_by'] ? 'selected' : '';?>>
                                                    <?=htmlspecialchars($am['updated_by']);?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </form>
                                
                                <!-- Performance Metrics -->
                                <div style="margin-top: 20px;">
                                    <h3 style="margin-bottom: 20px;">Performance Metrics</h3>
                                    <div class="row">
                                        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Responding</h5>
                                                    <h2><span class="counter"><?=$today_responding;?></span></h2>
                                                    <span class="text-muted">Monthly: <?=$monthly_responding;?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Not Responding</h5>
                                                    <h2><span class="counter"><?=$today_not_responding;?></span></h2>
                                                    <span class="text-muted">Monthly: <?=$monthly_not_responding;?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Today_Follow-up Calls</h5>
                                                    <h2><span class="counter"><?=$today_followup_calls;?></span></h2>
                                                    <span class="text-muted">Monthly: <?=$monthly_followup_calls;?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30" style="background-color: #4CAF50; color: white;">
                                                <div class="analytics-content">
                                                    <h5>PERFORMANCE</h5>
                                                    <h2><span class="counter"><?=number_format($today_performance, 2);?>%</span></h2>
                                                    <span class="text-muted">Monthly: <?=number_format($monthly_performance, 2);?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Repayments Section -->
                                <div style="margin-top: 30px;">
                                    <h3 style="margin-bottom: 20px;">Repayments</h3>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Today_Cleared Follow-up</h5>
                                                    <h2><span class="counter"><?=$today_cleared_followup_count;?></span></h2>
                                                    <span class="text-muted">Amount: ₹<?=number_format($today_cleared_followup_amount, 2);?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Today_Cleared Auto</h5>
                                                    <h2><span class="counter"><?=$today_cleared_auto_count;?></span></h2>
                                                    <span class="text-muted">Amount: ₹<?=number_format($today_cleared_auto_amount, 2);?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Today_Part Payment</h5>
                                                    <h2><span class="counter"><?=$today_part_payment_count;?></span></h2>
                                                    <span class="text-muted">Amount: ₹<?=number_format($today_part_payment_amount, 2);?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Monthly_Cleared Follow-up</h5>
                                                    <h2><span class="counter"><?=$monthly_cleared_followup_count;?></span></h2>
                                                    <span class="text-muted">Amount: ₹<?=number_format($monthly_cleared_followup_amount, 2);?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Monthly_Cleared Auto</h5>
                                                    <h2><span class="counter"><?=$monthly_cleared_auto_count;?></span></h2>
                                                    <span class="text-muted">Amount: ₹<?=number_format($monthly_cleared_auto_amount, 2);?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Monthly_Part Payment</h5>
                                                    <h2><span class="counter"><?=$monthly_part_payment_count;?></span></h2>
                                                    <span class="text-muted">Amount: ₹<?=number_format($monthly_part_payment_amount, 2);?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30" style="background-color: #4CAF50; color: white;">
                                                <div class="analytics-content" style="text-align: center;">
                                                    <h3>TOTAL CLEARED: <?=$today_total_cleared;?></h3>
                                                    <span class="text-muted">Monthly Total: <?=$monthly_total_cleared;?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Passed to ULTRA RM Section -->
                                <div style="margin-top: 30px;">
                                    <h3 style="margin-bottom: 20px;">Passed to ULTRA RM</h3>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Monthly_Passed</h5>
                                                    <h2><span class="counter">0</span></h2>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>Monthly_Passed_Amount</h5>
                                                    <h2><span class="counter">₹0</span></h2>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                            <div class="analytics-sparkle-line reso-mg-b-30">
                                                <div class="analytics-content">
                                                    <h5>LTD_Passed</h5>
                                                    <h2><span class="counter">0</span></h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Updates Report Tab -->
                            <div class="product-tab-list tab-pane fade <?=$view_mode == 'updates' ? 'active in' : '';?>" id="updates">
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
                                            <?php 
                                            $account_managers_rewind2 = towquery($account_managers_query);
                                            while($am = towfetch($account_managers_rewind2)): ?>
                                                <option value="<?=htmlspecialchars($am['updated_by']);?>" <?=$selected_user == $am['updated_by'] ? 'selected' : '';?>>
                                                    <?=htmlspecialchars($am['updated_by']);?>
                                                </option>
                                            <?php endwhile; ?>
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
                                                $summary_results_rewind = towquery($summary_query);
                                                while($summary = towfetch($summary_results_rewind)) {
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
                                                if($row_num == 1) {
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
                                                $details_results_rewind = towquery($details_query);
                                                while($detail = towfetch($details_results_rewind)) {
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
                            
                            <!-- User-wise Report Tab -->
                            <div class="product-tab-list tab-pane fade <?=$view_mode == 'userwise' ? 'active in' : '';?>" id="userwise">
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
                                        $user_wise_results_rewind = towquery($user_wise_query);
                                        while($user_data = towfetch($user_wise_results_rewind)) {
                                            $user_total = $user_data['total_calls'];
                                            $user_responding = $user_data['responding_count'];
                                            $user_not_responding = $user_data['not_responding_count'];
                                            $user_performance = ($user_total > 0) ? round(($user_responding / $user_total) * 100, 2) : 0;
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
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        // Check if no data found
                                        $user_wise_check = towquery($user_wise_query);
                                        if(townum($user_wise_check) == 0) {
                                            echo '<div class="col-lg-12"><div class="alert alert-info">No data found for the selected date.</div></div>';
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

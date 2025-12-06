<?php
include_once 'head.php';

// Get filter parameters
$selected_date = isset($_GET['date']) ? towreal($_GET['date']) : date('Y-m-d');
$selected_user = isset($_GET['user']) ? towreal($_GET['user']) : '';

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

// Calculate performance percentage
$today_performance = $today_followup_calls > 0 ? round(($today_responding / $today_followup_calls) * 100, 2) : 0;
$monthly_performance = $monthly_followup_calls > 0 ? round(($monthly_responding / $monthly_followup_calls) * 100, 2) : 0;

// Get repayment statistics
// Today cleared follow-up (loans cleared today where there was a follow-up entry)
$today_cleared_followup_query = "SELECT COUNT(DISTINCT td.cllid) as count, SUM(td.transaction_amount) as total_amount
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
$today_cleared_auto_query = "SELECT COUNT(DISTINCT td.cllid) as count, SUM(td.transaction_amount) as total_amount
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
$today_part_payment_query = "SELECT COUNT(DISTINCT td.cllid) as count, SUM(td.transaction_amount) as total_amount
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
$monthly_cleared_followup_query = "SELECT COUNT(DISTINCT td.cllid) as count, SUM(td.transaction_amount) as total_amount
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
$monthly_cleared_auto_query = "SELECT COUNT(DISTINCT td.cllid) as count, SUM(td.transaction_amount) as total_amount
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
$monthly_part_payment_query = "SELECT COUNT(DISTINCT td.cllid) as count, SUM(td.transaction_amount) as total_amount
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
                        <form method="GET" action="" class="form-inline" style="padding: 20px;">
                            <div class="form-group" style="margin-right: 20px;">
                                <label for="date" style="margin-right: 10px;">Date:</label>
                                <input type="date" name="date" id="date" class="form-control" value="<?=$selected_date;?>" required>
                            </div>
                            <div class="form-group" style="margin-right: 20px;">
                                <label for="user" style="margin-right: 10px;">Account Manager:</label>
                                <select name="user" id="user" class="form-control">
                                    <option value="">All Account Managers</option>
                                    <?php while($am = towfetch($account_managers)): ?>
                                        <option value="<?=htmlspecialchars($am['updated_by']);?>" <?=$selected_user == $am['updated_by'] ? 'selected' : '';?>>
                                            <?=htmlspecialchars($am['updated_by']);?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Date Headers -->
    <div class="analytics-sparkle-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="analytics-sparkle-line reso-mg-b-30" style="background-color: #2196F3; color: white;">
                        <div class="analytics-content" style="text-align: center;">
                            <h3>TODAY REPAYMENTS: <?=date('d-M-Y', strtotime($selected_date));?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="analytics-sparkle-line reso-mg-b-30" style="background-color: #4CAF50; color: white;">
                        <div class="analytics-content" style="text-align: center;">
                            <h3>MONTHLY REPAYMENTS: <?=date('d-M-Y', strtotime($month_start));?> TO <?=date('d-M-Y', strtotime($month_end));?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Performance Metrics -->
    <div class="single-pro-review-area mt-t-30 mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
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
                                        <h2><span class="counter"><?=$today_performance;?>%</span></h2>
                                        <span class="text-muted">Monthly: <?=$monthly_performance;?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Repayments Section -->
    <div class="single-pro-review-area mt-t-30 mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
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
                </div>
            </div>
        </div>
    </div>
    
    <!-- Passed to ULTRA RM Section -->
    <div class="single-pro-review-area mt-t-30 mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
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
            </div>
        </div>
    </div>
    
    <?php
    include_once 'foot.php';
    ?>


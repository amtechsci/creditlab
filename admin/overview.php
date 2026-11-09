<?php
include_once 'head.php';

// Optimized aggregation queries
$disb_q = towfetch(towquery("SELECT COUNT(*) as cnt, COALESCE(SUM(processed_amount), 0) as amount, COALESCE(SUM(amount), 0) as principal FROM `loan`"));
$total_disb_count = (int)($disb_q['cnt'] ?? 0);
$total_disb_amount = (float)($disb_q['amount'] ?? 0);

$cle_q = towfetch(towquery("SELECT COUNT(*) as cnt, COALESCE(SUM(processed_amount), 0) as amount, COALESCE(SUM(amount), 0) as principal FROM `loan` WHERE `status_log`='cleared'"));
$total_cle_count = (int)($cle_q['cnt'] ?? 0);
$total_cle_amount = (float)($cle_q['amount'] ?? 0);

$def_q = towfetch(towquery("SELECT COUNT(*) as cnt, COALESCE(SUM(processed_amount), 0) as amount FROM `loan` WHERE `exhausted_period` > 65 AND (`status_log` != 'cleared' OR `status_log` IS NULL)"));
$total_def_count = (int)($def_q['cnt'] ?? 0);
$total_def_amount = (float)($def_q['amount'] ?? 0);

$active_q = towfetch(towquery("SELECT COUNT(*) as cnt, COALESCE(SUM(processed_amount), 0) as amount FROM `loan` WHERE `status_log` != 'cleared' OR `status_log` IS NULL"));
$total_active_count = (int)($active_q['cnt'] ?? 0);
$total_active_amount = (float)($active_q['amount'] ?? 0);

$repeat_q = towfetch(towquery("SELECT COUNT(DISTINCT l.id) as cnt, COALESCE(SUM(l.processed_amount), 0) as amount FROM `loan` l JOIN `user` u ON l.uid = u.id WHERE (l.status_log != 'cleared' OR l.status_log IS NULL) AND u.sloan > 0"));
$total_repeat_count = (int)($repeat_q['cnt'] ?? 0);
$total_repeat_amount = (float)($repeat_q['amount'] ?? 0);

// Rates
$recovery_rate = $total_disb_amount > 0 ? round(($total_cle_amount / $total_disb_amount) * 100, 1) : 0;
$default_rate = $total_disb_amount > 0 ? round(($total_def_amount / $total_disb_amount) * 100, 1) : 0;
$active_rate = $total_disb_amount > 0 ? round(($total_active_amount / $total_disb_amount) * 100, 1) : 0;
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
                    <ul class="breadcome-menu">
                        <li><a href="index.php"><i class="fa-solid fa-house" style="font-size:12px;"></i> Admin</a> <span class="bread-slash">/</span></li>
                        <li><span class="bread-blod">Portfolio Overview</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="single-pro-review-area mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
                        
                        <div class="cl-card-header">
                            <div class="cl-card-header-left">
                                <h2 class="cl-card-header-title">
                                    <i class="fa-solid fa-layer-group" style="color:var(--cl-brand); font-size:17px;"></i>
                                    Loan Portfolio Overview
                                </h2>
                                <p class="cl-card-header-desc">Aggregated metrics across all disbursed, cleared, active, and default loan accounts</p>
                            </div>
                            <div class="cl-card-header-actions">
                                <a href="downloader.php" class="btn btn-default"><i class="fa-solid fa-cloud-arrow-down"></i> Export Reports</a>
                                <a href="loanclearance.php" class="btn btn-primary"><i class="fa-solid fa-circle-check"></i> Loan Clearance</a>
                            </div>
                        </div>

                        <!-- Top 4 KPI Cards -->
                        <div class="row dash-stats">
                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                <div class="dash-stat">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                        <span>Total Disbursed</span>
                                        <div style="width:34px; height:34px; border-radius:8px; background:#dbeafe; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:15px;">
                                            <i class="fa-solid fa-money-bill-transfer"></i>
                                        </div>
                                    </div>
                                    <strong>₹<?= number_format($total_disb_amount, 2) ?></strong>
                                    <div style="font-size:12px; color:var(--cl-slate-500); margin-top:4px;">
                                        <strong><?= number_format($total_disb_count) ?></strong> total loans disbursed
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                <div class="dash-stat">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                        <span>Total Cleared</span>
                                        <div style="width:34px; height:34px; border-radius:8px; background:#dcfce7; color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:15px;">
                                            <i class="fa-solid fa-circle-check"></i>
                                        </div>
                                    </div>
                                    <strong style="color:#15803d;">₹<?= number_format($total_cle_amount, 2) ?></strong>
                                    <div style="font-size:12px; color:#16a34a; margin-top:4px; font-weight:600;">
                                        <?= $recovery_rate ?>% recovery rate (<?= number_format($total_cle_count) ?> loans)
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                <div class="dash-stat">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                        <span>Active Portfolio</span>
                                        <div style="width:34px; height:34px; border-radius:8px; background:#e0e7ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; font-size:15px;">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </div>
                                    </div>
                                    <strong style="color:#4338ca;">₹<?= number_format($total_active_amount, 2) ?></strong>
                                    <div style="font-size:12px; color:var(--cl-slate-500); margin-top:4px;">
                                        <strong><?= number_format($total_active_count) ?></strong> open loans (<?= $active_rate ?>%)
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                <div class="dash-stat">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                        <span>Default Portfolio (&gt;65 DPD)</span>
                                        <div style="width:34px; height:34px; border-radius:8px; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:15px;">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </div>
                                    </div>
                                    <strong style="color:#b91c1c;">₹<?= number_format($total_def_amount, 2) ?></strong>
                                    <div style="font-size:12px; color:#dc2626; margin-top:4px; font-weight:600;">
                                        <?= $default_rate ?>% default rate (<?= number_format($total_def_count) ?> loans)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Breakdown Table -->
                        <div class="table-responsive" style="margin-top:10px;">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Portfolio Category</th>
                                        <th style="text-align:center;">No. of Loans</th>
                                        <th style="text-align:center;">Portfolio Share</th>
                                        <th style="text-align:right;">Total Amount (₹)</th>
                                        <th style="text-align:right;">Avg Ticket Size (₹)</th>
                                        <th style="text-align:center;">Status</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600; color:var(--cl-slate-900);">
                                                <i class="fa-solid fa-money-bill-transfer" style="color:var(--cl-primary); width:18px;"></i> Total Disbursed Loans
                                            </div>
                                            <div style="font-size:11.5px; color:var(--cl-slate-500); margin-left:22px;">Cumulative disbursed loan lifecycle</div>
                                        </td>
                                        <td style="text-align:center; font-weight:600;"><?= number_format($total_disb_count) ?></td>
                                        <td style="text-align:center;"><span class="badge-modern" style="background:#e2e8f0; color:#334155;">100%</span></td>
                                        <td style="text-align:right; font-weight:700; color:var(--cl-slate-900);">₹<?= number_format($total_disb_amount, 2) ?></td>
                                        <td style="text-align:right; color:var(--cl-slate-600);">₹<?= $total_disb_count > 0 ? number_format($total_disb_amount / $total_disb_count, 2) : '0.00' ?></td>
                                        <td style="text-align:center;"><span class="dash-status" style="background:#dbeafe; color:#1d4ed8;">Disbursed</span></td>
                                        <td style="text-align:right;">
                                            <a href="disbursal.php" class="btn btn-xs btn-primary"><i class="fa-solid fa-arrow-right"></i> View</a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <div style="font-weight:600; color:var(--cl-slate-900);">
                                                <i class="fa-solid fa-circle-check" style="color:#16a34a; width:18px;"></i> Total Cleared Loans
                                            </div>
                                            <div style="font-size:11.5px; color:var(--cl-slate-500); margin-left:22px;">Fully settled loans with zero balance</div>
                                        </td>
                                        <td style="text-align:center; font-weight:600;"><?= number_format($total_cle_count) ?></td>
                                        <td style="text-align:center;"><span class="badge-modern" style="background:#dcfce7; color:#15803d;"><?= $total_disb_count > 0 ? round(($total_cle_count / $total_disb_count) * 100, 1) : 0 ?>%</span></td>
                                        <td style="text-align:right; font-weight:700; color:#15803d;">₹<?= number_format($total_cle_amount, 2) ?></td>
                                        <td style="text-align:right; color:var(--cl-slate-600);">₹<?= $total_cle_count > 0 ? number_format($total_cle_amount / $total_cle_count, 2) : '0.00' ?></td>
                                        <td style="text-align:center;"><span class="dash-status" style="background:#dcfce7; color:#15803d;">Cleared</span></td>
                                        <td style="text-align:right;">
                                            <a href="loanclearance.php" class="btn btn-xs btn-success"><i class="fa-solid fa-arrow-right"></i> View</a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <div style="font-weight:600; color:var(--cl-slate-900);">
                                                <i class="fa-solid fa-clock-rotate-left" style="color:#4f46e5; width:18px;"></i> Total Active Loans
                                            </div>
                                            <div style="font-size:11.5px; color:var(--cl-slate-500); margin-left:22px;">Currently running loans undergoing repayment</div>
                                        </td>
                                        <td style="text-align:center; font-weight:600;"><?= number_format($total_active_count) ?></td>
                                        <td style="text-align:center;"><span class="badge-modern" style="background:#e0e7ff; color:#4338ca;"><?= $total_disb_count > 0 ? round(($total_active_count / $total_disb_count) * 100, 1) : 0 ?>%</span></td>
                                        <td style="text-align:right; font-weight:700; color:#4338ca;">₹<?= number_format($total_active_amount, 2) ?></td>
                                        <td style="text-align:right; color:var(--cl-slate-600);">₹<?= $total_active_count > 0 ? number_format($total_active_amount / $total_active_count, 2) : '0.00' ?></td>
                                        <td style="text-align:center;"><span class="dash-status" style="background:#e0e7ff; color:#4338ca;">Active</span></td>
                                        <td style="text-align:right;">
                                            <a href="account_manager.php" class="btn btn-xs btn-primary"><i class="fa-solid fa-arrow-right"></i> View</a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <div style="font-weight:600; color:var(--cl-slate-900);">
                                                <i class="fa-solid fa-arrows-rotate" style="color:#0284c7; width:18px;"></i> Active Repeat Loans
                                            </div>
                                            <div style="font-size:11.5px; color:var(--cl-slate-500); margin-left:22px;">Active loans belonging to returning customers (sloan &gt; 0)</div>
                                        </td>
                                        <td style="text-align:center; font-weight:600;"><?= number_format($total_repeat_count) ?></td>
                                        <td style="text-align:center;"><span class="badge-modern" style="background:#e0f2fe; color:#0369a1;"><?= $total_disb_count > 0 ? round(($total_repeat_count / $total_disb_count) * 100, 1) : 0 ?>%</span></td>
                                        <td style="text-align:right; font-weight:700; color:#0284c7;">₹<?= number_format($total_repeat_amount, 2) ?></td>
                                        <td style="text-align:right; color:var(--cl-slate-600);">₹<?= $total_repeat_count > 0 ? number_format($total_repeat_amount / $total_repeat_count, 2) : '0.00' ?></td>
                                        <td style="text-align:center;"><span class="dash-status" style="background:#e0f2fe; color:#0369a1;">Repeat</span></td>
                                        <td style="text-align:right;">
                                            <a href="repeat_loan_d.php" class="btn btn-xs btn-primary"><i class="fa-solid fa-arrow-right"></i> View</a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <div style="font-weight:600; color:var(--cl-slate-900);">
                                                <i class="fa-solid fa-triangle-exclamation" style="color:#dc2626; width:18px;"></i> Total Default Loans
                                            </div>
                                            <div style="font-size:11.5px; color:var(--cl-slate-500); margin-left:22px;">Overdue loans with exhausted period &gt; 65 days</div>
                                        </td>
                                        <td style="text-align:center; font-weight:600; color:#dc2626;"><?= number_format($total_def_count) ?></td>
                                        <td style="text-align:center;"><span class="badge-modern" style="background:#fee2e2; color:#b91c1c;"><?= $total_disb_count > 0 ? round(($total_def_count / $total_disb_count) * 100, 1) : 0 ?>%</span></td>
                                        <td style="text-align:right; font-weight:700; color:#dc2626;">₹<?= number_format($total_def_amount, 2) ?></td>
                                        <td style="text-align:right; color:var(--cl-slate-600);">₹<?= $total_def_count > 0 ? number_format($total_def_amount / $total_def_count, 2) : '0.00' ?></td>
                                        <td style="text-align:center;"><span class="dash-status" style="background:#fee2e2; color:#b91c1c;">Default</span></td>
                                        <td style="text-align:right;">
                                            <a href="recovery_officer.php" class="btn btn-xs btn-danger"><i class="fa-solid fa-arrow-right"></i> View</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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

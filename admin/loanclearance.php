<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/admin_ui.php';

if (isset($_GET['pageno'])) {
    $pageno = intval($_GET['pageno']);
} else {
    $pageno = 1;
}
if ($pageno < 1) { $pageno = 1; }
$no_of_records_per_page = 50;
$offset = ($pageno - 1) * $no_of_records_per_page;

$count_row = towfetch(towquery("SELECT COUNT(*) AS c FROM `pay_ref`"));
$total_rows = (int)($count_row['c'] ?? 0);
$total_pages = $total_rows > 0 ? (int)ceil($total_rows / $no_of_records_per_page) : 1;

$newloanquery = towquery(
    "SELECT
        pr.id AS pay_ref_id,
        pr.uid AS pay_uid,
        pr.loan_id AS pay_loan_id,
        pr.utr_ref,
        pr.payment_type,
        u.id AS user_id,
        u.rcid,
        u.name,
        u.mobile,
        u.loan AS user_loan_flag,
        u.sloan AS user_sloan_flag,
        l.lid,
        l.processed_date,
        l.processed_amount
    FROM `pay_ref` pr
    LEFT JOIN `loan` l ON l.lid = TRIM(REPLACE(REPLACE(pr.loan_id, 'CLL', ''), 'cll', ''))
    LEFT JOIN `user` u ON u.id = COALESCE(NULLIF(pr.uid, 0), l.uid)
    ORDER BY pr.id DESC
    LIMIT $offset, $no_of_records_per_page"
);
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
                        <li><a href="loanclearance.php">Loans</a> <span class="bread-slash">/</span></li>
                        <li><span class="bread-blod">Loan Clearance</span></li>
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
                                    <i class="fa-solid fa-circle-check" style="color:var(--cl-brand); font-size:17px;"></i>
                                    Loan Clearance Logs
                                    <span class="cl-badge-pill"><?= number_format($total_rows) ?></span>
                                </h2>
                                <p class="cl-card-header-desc">Settlement and clearance payment transactions recorded in system</p>
                            </div>
                            <div class="cl-card-header-actions">
                                <a href="part_payment.php" class="btn btn-default"><i class="fa-solid fa-receipt"></i> Part Payment</a>
                                <a href="overview.php" class="btn btn-primary"><i class="fa-solid fa-layer-group"></i> Portfolio Overview</a>
                            </div>
                        </div>

                        <?= creditlab_render_table_legend() ?>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="loan_history_datatable">
                                <thead>
                                    <tr>                
                                        <th style="width:34px; text-align:center;"><input type="checkbox" id="selectAll"></th>   
                                        <th style="width:48px;">SL</th>    
                                        <th>Borrower / RCID</th>        
                                        <th>Mobile</th>    
                                        <th style="text-align:right;">Disbursed & Days</th>    
                                        <th>Loan ID & Reference</th>
                                        <th style="text-align:right; width:110px;">Actions</th>     
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $ii = $offset + 1;
                                    if ($newloanquery && townum($newloanquery) > 0) {
                                        while ($row = towfetch($newloanquery)) {
                                            $view_id = (int) ($row['user_id'] ?: $row['pay_uid']);
                                            $lid_num = preg_replace('/\D/', '', (string) ($row['lid'] ?: $row['pay_loan_id']));
                                            $processed_date = $row['processed_date'] ?? '';
                                            $exhausted = '—';
                                            if (!empty($processed_date) && $processed_date !== '0000-00-00') {
                                                $exhausted = (int) ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($processed_date . ' -1 day')))) / (60 * 60 * 24)) . ' d';
                                            }
                                    ?>
                                        <tr>
                                            <td style="text-align:center;">
                                                <input type="checkbox" name="check[]" value="<?= $view_id ?>">
                                            </td>
                                            <td style="color:var(--cl-slate-500); font-size:12px;"><?= $ii ?></td>
                                            <td data-title="Borrower">
                                                <div style="font-weight:600; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                    <?= creditlab_dash_text($row['name'] ?? '') ?>
                                                    <?php 
                                                        if (!empty($row['user_loan_flag'])) { echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; } 
                                                        if (!empty($row['user_sloan_flag'])) { echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; } 
                                                    ?>
                                                </div>
                                                <div style="margin-top:3px; font-size:11.5px; font-family:monospace; color:var(--cl-primary); font-weight:600;">
                                                    <?= creditlab_dash_text($row['rcid'] ?? '') ?>
                                                </div>
                                            </td>
                                            <td data-title="Mobile" style="font-family:monospace; font-size:12.5px; color:var(--cl-slate-800); white-space:nowrap;">
                                                <?= creditlab_dash_text($row['mobile'] ?? '') ?>
                                            </td>
                                            <td data-title="Amount" style="text-align:right; white-space:nowrap;">
                                                <div style="font-weight:700; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                    <?= creditlab_currency($row['processed_amount'] ?? 0) ?>
                                                </div>
                                                <div style="margin-top:3px; font-size:11.5px; color:var(--cl-slate-500);">
                                                    <span style="color:var(--cl-slate-400);">Tenure:</span> <?= $exhausted ?>
                                                </div>
                                            </td>
                                            <td data-title="Loan & Reference">
                                                <div style="display:flex; align-items:center; gap:6px;">
                                                    <span class="badge-modern" style="background:#e0f2fe; color:#0369a1; font-family:monospace; font-size:11.5px; font-weight:600;">
                                                        <?= $lid_num !== '' ? 'CLL' . htmlspecialchars($lid_num) : creditlab_dash_text($row['pay_loan_id'] ?? '') ?>
                                                    </span>
                                                    <?php if (!empty($row['payment_type'])): ?>
                                                        <span style="font-size:10.5px; text-transform:uppercase; color:var(--cl-slate-500); font-weight:600;">
                                                            <?= htmlspecialchars((string) $row['payment_type']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="margin-top:3px; font-family:monospace; font-size:11.5px; color:var(--cl-slate-600);">
                                                    <?= creditlab_dash_text($row['utr_ref'] ?? '') ?>
                                                </div>
                                            </td>
                                            <td data-title="Actions" style="text-align:right; white-space:nowrap;">
                                                <?php if ($view_id > 0): ?>
                                                    <a class="btn btn-xs btn-primary" href="profile.php?id=<?= $view_id ?>" target="_blank" style="padding:4px 10px;">
                                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color:var(--cl-slate-400);">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php 
                                            $ii++; 
                                        } 
                                    } else { 
                                    ?>
                                        <tr>
                                            <td colspan="10" style="text-align:center; padding:30px; color:var(--cl-slate-400);">No clearance records found.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>

                            <div style="padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; border-top:1px solid var(--cl-slate-100);">
                                <div style="font-size:12.5px; color:var(--cl-slate-500);">
                                    Showing <strong><?= min($total_rows, $offset + 1) ?></strong> to <strong><?= min($total_rows, $offset + $no_of_records_per_page) ?></strong> of <strong><?= number_format($total_rows) ?></strong> records
                                </div>
                                <?= creditlab_render_pagination($pageno, $total_pages, '?pageno=') ?>
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

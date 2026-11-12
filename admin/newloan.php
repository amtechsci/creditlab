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

$cnt_res = towfetch(towquery("SELECT COUNT(*) as cnt FROM `loan_apply` WHERE `status`='pending'"));
$total_rows = (int)($cnt_res['cnt'] ?? 0);
$total_pages = max(1, (int)ceil($total_rows / $no_of_records_per_page));

$newloanquery = mysqli_query($db, "
    SELECT 
        loan_apply.id as loan_apply_id,
        loan_apply.uid as loan_apply_uid,
        loan_apply.amount as loan_amount,
        loan_apply.processing_fees as loan_pfees,
        loan_apply.apply_date as loan_apply_date,
        loan_apply.status as loan_status,
        loan_apply.agreement as loan_agreement,
        user.id as u_id,
        user.rcid as u_rcid,
        user.name as u_name,
        user.email as u_email,
        user.mobile as u_mobile,
        user.approvenew as u_approvenew,
        user.status as u_status,
        user.loan as u_loan,
        user.sloan as u_sloan,
        user.conpanydocument,
        user.personaldocument,
        user.salarydocument,
        user.bankdocument,
        user.addressdocument,
        user.companyidcard,
        recovery_officer.name as officer_name
    FROM `loan_apply`
    LEFT JOIN `user` ON loan_apply.uid = user.id
    LEFT JOIN `recovery_officer` ON user.assign_recovery_officer = recovery_officer.id
    WHERE loan_apply.status = 'pending'
    ORDER BY loan_apply.id DESC
    LIMIT $offset, $no_of_records_per_page
");
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
                        <li><a href="newloan.php">Loans</a> <span class="bread-slash">/</span></li>
                        <li><span class="bread-blod">Applied Loans</span></li>
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
                                    <i class="fa-solid fa-file-invoice-dollar" style="color:var(--cl-brand); font-size:17px;"></i>
                                    Applied Loans
                                    <span class="cl-badge-pill"><?= number_format($total_rows) ?></span>
                                </h2>
                                <p class="cl-card-header-desc">New loan applications pending review and approval</p>
                            </div>
                            <div class="cl-card-header-actions">
                                <a href="loan.php" class="btn btn-default"><i class="fa-solid fa-hand-holding-dollar"></i> Approved Loans</a>
                                <a href="disbursal.php" class="btn btn-default"><i class="fa-solid fa-paper-plane"></i> Disbursals</a>
                                <a href="loanclearance.php" class="btn btn-primary"><i class="fa-solid fa-circle-check"></i> Loan Clearance</a>
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
                                        <th style="text-align:right;">Amount (Req / Disb)</th>    
                                        <th style="text-align:center;">Status & Plan</th> 
                                        <th>Officer & Date</th> 
                                        <th style="text-align:right; width:110px;">Actions</th>     
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($newloanquery && mysqli_num_rows($newloanquery) > 0): ?>
                                        <?php $ii = $offset + 1; while($row = mysqli_fetch_assoc($newloanquery)): ?>
                                            <?php
                                            $has_all_docs = (!empty($row['conpanydocument']) && !empty($row['personaldocument']) && !empty($row['salarydocument']) && !empty($row['bankdocument']) && !empty($row['addressdocument']) && !empty($row['companyidcard']));
                                            $total_req = (float)($row['loan_amount'] ?? 0) + (float)($row['loan_pfees'] ?? 0);
                                            ?>
                                            <tr>
                                                <td style="text-align:center;">
                                                    <input type="checkbox" name="check[]" value="<?= (int)($row['u_id'] ?? 0) ?>">
                                                </td>   
                                                <td style="color:var(--cl-slate-500); font-size:12px;"><?= $ii ?></td>
                                                <td data-title="Borrower">
                                                    <div style="font-weight:600; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                        <?= creditlab_dash_text($row['u_name'] ?? '') ?>
                                                        <?php 
                                                            if (!empty($row['u_loan'])) { echo "<span title='Has Active Loan (loan > 0)' class='cl-symbol cl-symbol-loan'>#</span>"; }
                                                            if (!empty($row['u_sloan'])) { echo "<span title='Special / Repeat Borrower (sloan > 0)' class='cl-symbol cl-symbol-sloan'>@</span>"; }
                                                            if ($has_all_docs) { echo " <span title='All Mandatory Documents Uploaded & Verified' class='cl-symbol cl-symbol-docs'>✔</span>"; }
                                                        ?>
                                                    </div>
                                                    <div style="margin-top:3px; font-size:11.5px; font-family:monospace; color:var(--cl-primary); font-weight:600;">
                                                        <?= creditlab_dash_text($row['u_rcid'] ?? '') ?>
                                                    </div>
                                                </td>
                                                <td data-title="Mobile">
                                                    <div style="font-family:monospace; font-size:12.5px; font-weight:500; color:var(--cl-slate-800); white-space:nowrap;">
                                                        <?= creditlab_dash_text($row['u_mobile'] ?? '') ?>
                                                    </div>
                                                </td>
                                                <td data-title="Amount" style="text-align:right; white-space:nowrap;">
                                                    <div style="font-weight:700; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                        <span style="font-size:10px; font-weight:600; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Req</span>
                                                        <?= creditlab_currency($total_req) ?>
                                                    </div>
                                                    <div style="margin-top:3px; font-size:12px; line-height:1.2;">
                                                        <span style="font-weight:600; color:#15803d;"><span style="font-size:10px; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Disb</span><?= creditlab_currency($row['loan_amount'] ?? 0) ?></span>
                                                        <?php if (!empty($row['loan_pfees']) && (float)$row['loan_pfees'] > 0): ?>
                                                            <span style="color:var(--cl-slate-300); margin:0 2px;">·</span>
                                                            <span style="color:var(--cl-slate-500); font-size:11px;">Fee <?= creditlab_currency($row['loan_pfees']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td data-title="Status & Plan" style="text-align:center; white-space:nowrap;">
                                                    <div>
                                                        <?= creditlab_status_badge($row['u_status'] ?? '', $row['loan_agreement'] ?? 0) ?>
                                                    </div>
                                                    <div style="margin-top:4px;">
                                                        <span style="font-size:11px; color:var(--cl-slate-500);">Plan:</span>
                                                        <span class="badge" style="background:#f1f5f9; color:#475569; font-size:10.5px; font-weight:600; padding:2px 6px; border-radius:4px;">
                                                            <?= creditlab_dash_text($row['u_approvenew'] ?? '', 'Standard') ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td data-title="Officer & Date" style="white-space:nowrap;">
                                                    <div style="font-size:12px; font-weight:500; color:var(--cl-slate-700); line-height:1.25;">
                                                        <i class="fa-regular fa-calendar" style="font-size:10.5px; color:var(--cl-slate-400); margin-right:3px;"></i>
                                                        <?= !empty($row['loan_apply_date']) ? date('d M Y, H:i', strtotime($row['loan_apply_date'])) : '—' ?>
                                                    </div>
                                                    <div style="margin-top:3px; font-size:11.5px; color:var(--cl-slate-500);">
                                                        <i class="fa-solid fa-user-tie" style="font-size:10px; color:var(--cl-slate-400); margin-right:3px;"></i>
                                                        <?= creditlab_dash_text($row['officer_name'] ?? '', 'Unassigned') ?>
                                                    </div>
                                                </td>
                                                <td data-title="Actions" style="text-align:right; white-space:nowrap;">
                                                    <a class="btn btn-xs btn-primary" href="profile.php?id=<?= (int)($row['u_id'] ?? 0) ?>" target="_blank" style="padding:4px 10px;">
                                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php $ii++; endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" style="text-align:center; padding:30px; color:var(--cl-slate-400);">No pending applied loans found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <div style="padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; border-top:1px solid var(--cl-slate-100);">
                                <div style="font-size:12.5px; color:var(--cl-slate-500);">
                                    Showing <strong><?= min($total_rows, $offset + 1) ?></strong> to <strong><?= min($total_rows, $offset + $no_of_records_per_page) ?></strong> of <strong><?= number_format($total_rows) ?></strong> applications
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

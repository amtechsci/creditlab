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
                        
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px; border-bottom:2px solid var(--cl-slate-200); padding-bottom:12px;">
                            <div>
                                <h3 style="margin:0; font-size:18px; font-weight:700; color:var(--cl-slate-900);">
                                    <i class="fa-solid fa-file-invoice-dollar" style="color:var(--cl-primary); margin-right:6px;"></i> Applied Loans (Pending)
                                </h3>
                                <p style="margin:4px 0 0; font-size:13px; color:var(--cl-slate-500);">
                                    Showing <?= number_format($total_rows) ?> new loan applications pending review and approval
                                </p>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <a href="disbursal.php" class="btn btn-default" style="border:1px solid var(--cl-slate-200); background:#fff; font-weight:600;">
                                    <i class="fa-solid fa-paper-plane" style="margin-right:4px;"></i> Disbursals
                                </a>
                                <a href="loanclearance.php" class="btn btn-primary">
                                    <i class="fa-solid fa-circle-check" style="margin-right:4px;"></i> Loan Clearance
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="loan_history_datatable">
                                <thead>
                                    <tr>
                                        <th style="width:36px; text-align:center;"><input type="checkbox" id="selectAll"></th>   
                                        <th style="width:55px;">SL NO</th>
                                        <th>RCID</th>        
                                        <th>Name</th>        
                                        <th>Mobile</th>    
                                        <th>EMI Plan</th>    
                                        <th style="text-align:right;">Req Amount</th>    
                                        <th style="text-align:right;">Disbursal Amt</th>    
                                        <th style="text-align:right;">P. Fee</th>    
                                        <th style="text-align:center;">User Status</th> 
                                        <th>Officer</th> 
                                        <th>Applied Date</th> 
                                        <th style="text-align:right;">Actions</th>     
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
                                                <td style="color:var(--cl-slate-500);"><?= $ii ?></td>
                                                <td data-title="CID">
                                                    <strong style="color:var(--cl-primary);"><?= creditlab_dash_text($row['u_rcid'] ?? '') ?></strong>
                                                </td>
                                                <td data-title="Name">
                                                    <span style="font-weight:600; color:var(--cl-slate-900);">
                                                        <?= creditlab_dash_text($row['u_name'] ?? '') ?>
                                                    </span>
                                                    <?php 
                                                        if (!empty($row['u_loan'])) { echo "<span title='Loan' style='color:#dc2626; font-weight:bold; margin-left:3px;'>#</span>"; }
                                                        if (!empty($row['u_sloan'])) { echo "<span title='Sloan' style='color:#dc2626; font-weight:bold; margin-left:2px;'>@</span>"; }
                                                        if ($has_all_docs) { echo " <span title='All Documents Verified' style='color:#16a34a; font-weight:bold; margin-left:4px;'><i class='fa-solid fa-circle-check'></i></span>"; }
                                                    ?>
                                                </td>
                                                <td data-title="Mobile" style="font-family:monospace; font-size:12.5px;"><?= creditlab_dash_text($row['u_mobile'] ?? '') ?></td>
                                                <td data-title="EMI Plan">
                                                    <span class="badge-modern" style="background:#f1f5f9; color:#475569;">
                                                        <?= creditlab_dash_text($row['u_approvenew'] ?? '', 'Standard') ?>
                                                    </span>
                                                </td>
                                                <td data-title="Req Amount" style="text-align:right; font-weight:700; color:var(--cl-slate-900);">
                                                    <?= creditlab_currency($total_req) ?>
                                                </td>
                                                <td data-title="Disbursal Amt" style="text-align:right; font-weight:600; color:#15803d;">
                                                    <?= creditlab_currency($row['loan_amount'] ?? 0) ?>
                                                </td>
                                                <td data-title="P. Fee" style="text-align:right; color:var(--cl-slate-600);">
                                                    <?= creditlab_currency($row['loan_pfees'] ?? 0) ?>
                                                </td>
                                                <td data-title="User Status" style="text-align:center;">
                                                    <?= creditlab_status_badge($row['u_status'] ?? '') ?>
                                                </td>
                                                <td data-title="Officer" style="font-size:12px; color:var(--cl-slate-600);">
                                                    <?= creditlab_dash_text($row['officer_name'] ?? '', 'Unassigned') ?>
                                                </td>
                                                <td data-title="Applied Date" style="font-size:12px; color:var(--cl-slate-500); white-space:nowrap;">
                                                    <?= creditlab_dash_text($row['loan_apply_date'] ?? '') ?>
                                                </td>
                                                <td data-title="Actions" style="text-align:right;">
                                                    <a class="btn btn-xs btn-primary" href="profile.php?id=<?= (int)($row['u_id'] ?? 0) ?>" target="_blank" style="padding:4px 10px;">
                                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php $ii++; endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="13" style="text-align:center; padding:30px; color:var(--cl-slate-400);">No pending applied loans found.</td>
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

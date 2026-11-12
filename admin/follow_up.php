<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $ress = mysqli_query($db,"SELECT * FROM `loan_apply` WHERE `status`='follow up' ORDER BY id DESC");
        $newloanquery =  mysqli_query($db,"SELECT * FROM `loan_apply` WHERE `status`='follow up' ORDER BY id DESC LIMIT $offset, $no_of_records_per_page");
        $total_rows = mysqli_num_rows($ress);
        $total_pages = ceil($total_rows / $no_of_records_per_page);
?>
<body>
    <?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>
        <div class="breadcome-area">
            <div class="container-fluid">
                <ul class="breadcome-menu">
                    <li><a href="index.php"><i class="fa-solid fa-house" style="font-size:12px;"></i> Admin</a> <span class="bread-slash">/</span></li>
                    <li><a href="newloan.php">Loans</a> <span class="bread-slash">/</span></li>
                    <li><span class="bread-blod">Follow Up</span></li>
                </ul>
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
                                <h3 class="cl-card-header-title">
                                    <i class="fa-solid fa-phone"></i> Follow Up
                                    <span class="cl-badge-pill"><?= number_format($total_rows) ?></span>
                                </h3>
                                <p class="cl-card-header-desc">Borrowers and applicants requiring follow-up communication or document completion</p>
                            </div>
                            <div class="cl-card-header-actions">
                                <a href="newloan.php" class="btn btn-default" style="border:1px solid var(--cl-slate-200); background:#fff; font-weight:600;">
                                    <i class="fa-solid fa-file-invoice-dollar" style="margin-right:4px;"></i> Applied Loans
                                </a>
                                <a href="disbursal.php" class="btn btn-primary" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                    <i class="fa-solid fa-paper-plane"></i> Disbursal
                                </a>
                            </div>
                        </div>

                        <?= creditlab_render_table_legend() ?>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="loan_history_datatable">
                                <thead>
                                    <tr>
                                        <th style="width:34px; text-align:center;"><input type='checkbox' id="selectAll"></th>   
                                        <th style="width:48px;">SL</th> 
                                        <th>Borrower / CID</th>        
                                        <th>Mobile</th>    
                                        <th style="text-align:right;">Amount (Req / Disb)</th>    
                                        <th style="text-align:center;">Status & Plan</th> 
                                        <th>Officer & Date</th> 
                                        <th style="text-align:right; width:110px;">Actions</th>     
                                    </tr>
                                </thead>
                                <tbody>
                                   <?php 
                                   $ii = $offset + 1; 
                                   while($loanfetch = towfetch($newloanquery)){ 
                                       extract($loanfetch, EXTR_PREFIX_ALL, "users");
                                       $a = towquery("SELECT * FROM user WHERE id=$users_uid;");
                                       $b = towfetch($a);
                                       extract($b, EXTR_PREFIX_ALL, "user");
                                       $total_req = (float)($users_amount ?? 0) + (float)($users_processing_fees ?? 0);
                                       $has_all_docs = (!empty($user_conpanydocument) && !empty($user_personaldocument) && !empty($user_salarydocument) && !empty($user_bankdocument) && !empty($user_addressdocument) && !empty($user_companyidcard));
                                   ?>
                                    <tr>
                                        <td style="text-align:center;"><input type='checkbox' name="check[]" value="<?=$user_id?>"></td>   
                                        <td style="color:var(--cl-slate-500); font-size:12px;"><?=$ii?></td>
                                        <td data-title="Borrower">
                                            <div style="font-weight:600; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                <?= creditlab_dash_text($user_name ?? '') ?>
                                                <?php 
                                                    if(isset($user_loan) && $user_loan > 0){ echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; }
                                                    if((isset($user_sloan) && $user_sloan > 0) || (isset($users_sloan) && $users_sloan > 0)){ echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; }
                                                    if($has_all_docs){ echo '<span class="cl-symbol-docs" title="All 6 Documents Uploaded (✔)">✔</span>'; }
                                                ?>
                                            </div>
                                            <div style="margin-top:3px; font-size:11.5px; font-family:monospace; color:var(--cl-primary); font-weight:600;">
                                                <?= creditlab_dash_text($user_rcid ?? '') ?>
                                            </div>
                                        </td>
                                        <td data-title="Mobile" style="white-space:nowrap;">
                                            <div style="font-family:monospace; font-size:12.5px; font-weight:500; color:var(--cl-slate-800);">
                                                <?= creditlab_dash_text($user_mobile ?? '') ?>
                                            </div>
                                        </td>
                                        <td data-title="Amount" style="text-align:right; white-space:nowrap;">
                                            <div style="font-weight:700; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                <span style="font-size:10px; font-weight:600; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Req</span>
                                                <?= creditlab_currency($total_req) ?>
                                            </div>
                                            <div style="margin-top:3px; font-size:12px; line-height:1.2;">
                                                <span style="font-weight:600; color:#15803d;"><span style="font-size:10px; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Disb</span><?= creditlab_currency($users_amount ?? 0) ?></span>
                                                <?php if (!empty($users_processing_fees) && (float)$users_processing_fees > 0): ?>
                                                    <span style="color:var(--cl-slate-300); margin:0 2px;">·</span>
                                                    <span style="color:var(--cl-slate-500); font-size:11px;">Fee <?= creditlab_currency($users_processing_fees) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-title="Status & Plan" style="text-align:center; white-space:nowrap;">
                                            <div>
                                                <?= creditlab_status_badge($user_status, $loanfetch['agreement'] ?? 0) ?>
                                            </div>
                                            <div style="margin-top:4px;">
                                                <span style="font-size:11px; color:var(--cl-slate-500);">Plan:</span>
                                                <span class="badge" style="background:#f1f5f9; color:#475569; font-size:10.5px; font-weight:600; padding:2px 6px; border-radius:4px;">
                                                    <?= creditlab_dash_text($user_approvenew ?? '', 'Standard') ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td data-title="Officer & Date" style="white-space:nowrap;">
                                            <div style="font-size:12px; font-weight:500; color:var(--cl-slate-700); line-height:1.25;">
                                                <i class="fa-regular fa-calendar" style="font-size:10.5px; color:var(--cl-slate-400); margin-right:3px;"></i>
                                                <?= !empty($users_apply_date) ? date('d M Y, H:i', strtotime($users_apply_date)) : '—' ?>
                                            </div>
                                            <div style="margin-top:3px; font-size:11.5px; color:var(--cl-slate-500);">
                                                <i class="fa-solid fa-user-tie" style="font-size:10px; color:var(--cl-slate-400); margin-right:3px;"></i>
                                                <?php 
                                                $recovery_officer = !empty($user_assign_recovery_officer) ? towfetch(towquery("SELECT `name` FROM `recovery_officer` WHERE `id`='$user_assign_recovery_officer'")) : null;
                                                echo !empty($recovery_officer['name']) ? htmlspecialchars($recovery_officer['name']) : 'Unassigned';
                                                ?>
                                            </div>
                                        </td>
                                        <td data-title="Actions" style="text-align:right; white-space:nowrap;">
                                            <a class="btn btn-xs btn-primary" href="profile.php?id=<?=$user_id?>" target="_blank" style="padding:4px 10px;">
                                                <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                            </a>
                                        </td>
                                    </tr>
                                <?php $ii++; } ?>
                                </tbody>
                            </table>
                        </div>

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
<?php
include_once 'foot.php';
?>
</body>
</html>

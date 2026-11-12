<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $ress = mysqli_query($db,"SELECT distinct loan.uid, loan.*, user.* FROM loan Left JOIN user ON loan.uid = user.id WHERE loan.advance_amount > 0 AND assign_account_manager=$user_id ORDER BY loan.id DESC");
        $newloanquery =  mysqli_query($db,"SELECT distinct loan.uid, loan.*, user.* FROM loan Left JOIN user ON loan.uid = user.id WHERE loan.advance_amount > 0 AND assign_account_manager=$user_id ORDER BY loan.id DESC LIMIT $offset, $no_of_records_per_page");
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
                    <li><span class="bread-blod">Part Payment</span></li>
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
                                    <i class="fa-solid fa-receipt"></i> Part Payment
                                    <span class="cl-badge-pill"><?= number_format($total_rows) ?></span>
                                </h3>
                                <p class="cl-card-header-desc">Borrowers with active partial repayment plans or installments logged</p>
                            </div>
                            <div class="cl-card-header-actions">
                                <a href="loanclearance.php" class="btn btn-primary" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                    <i class="fa-solid fa-circle-check"></i> Loan Clearance
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
                                        <th style="text-align:right;">Outstanding / Part Paid</th>    
                                        <th style="text-align:center;">Days</th>    
                                        <th>Assigned Staff</th>    
                                        <th style="text-align:center;">Status</th>    
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
                                   ?>
                                    <tr>
                                        <td style="text-align:center;"><input type='checkbox' name="check[]" value="<?=$user_id?>"></td>   
                                        <td style="color:var(--cl-slate-500); font-size:12px;"><?=$ii?></td>
                                        <td data-title="Borrower">
                                            <div style="font-weight:600; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                <?= creditlab_dash_text($user_name ?? '') ?>
                                                <?php if($user_loan > 0){ echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; }?>
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
                                        <td data-title="Financials" style="text-align:right; white-space:nowrap;">
                                            <div style="font-weight:700; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                <span style="font-size:10px; font-weight:600; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Outst.</span>
                                                <?= creditlab_currency($users_total_amount ?? 0) ?>
                                            </div>
                                            <div style="margin-top:3px; font-size:12px; line-height:1.2;">
                                                <span style="font-weight:600; color:#15803d;"><span style="font-size:10px; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Paid</span><?= creditlab_currency($users_advance_amount ?? 0) ?></span>
                                            </div>
                                        </td>
                                        <td data-title="Days" style="text-align:center; color:var(--cl-slate-600); font-weight:500;">
                                            <?= (int)($users_exhausted_period ?? 0) ?> d
                                        </td>
                                        <td data-title="Assigned Staff" style="font-size:12px; white-space:nowrap;">
                                            <?php 
                                            $account_manager = !empty($user_assign_account_manager) ? towfetch(towquery("SELECT `name` FROM `account_manager` WHERE `id`='$user_assign_account_manager'")) : null;
                                            $recovery_officer = !empty($user_assign_recovery_officer) ? towfetch(towquery("SELECT `name` FROM `recovery_officer` WHERE `id`='$user_assign_recovery_officer'")) : null;
                                            ?>
                                            <div style="color:var(--cl-slate-700);">
                                                <span style="font-size:10.5px; font-weight:600; color:var(--cl-slate-400);">AM:</span>
                                                <?= !empty($account_manager['name']) ? htmlspecialchars($account_manager['name']) : 'Unassigned' ?>
                                            </div>
                                            <div style="margin-top:2px; color:var(--cl-slate-500); font-size:11.5px;">
                                                <span style="font-size:10.5px; font-weight:600; color:var(--cl-slate-400);">RO:</span>
                                                <?= !empty($recovery_officer['name']) ? htmlspecialchars($recovery_officer['name']) : 'Unassigned' ?>
                                            </div>
                                        </td>
                                        <td data-title="Status" style="text-align:center; white-space:nowrap;">
                                            <?= creditlab_status_badge($user_status ?? '') ?>
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
  <ul class="pagination">
    <li class="page-item">
      <a class="page-link" href="<?php if($pageno <= 1){ echo '#'; } else { echo "?pageno=".($pageno - 1); } ?>" aria-label="Previous">
        <span aria-hidden="true">&laquo;</span>
        <span class="sr-only">Previous</span>
      </a>
    </li>
    <?php $i = 1; while($i <= $total_pages){?>
    <li class="page-item"><a class="page-link" href="?pageno=<?=$i;?>"><?=$i;?></a></li>
    <?php $i++; }?>
    <li class="page-item">
      <a class="page-link" href="<?php if($pageno >= $total_pages){ echo '#'; } else { echo "?pageno=".($pageno + 1); } ?>" aria-label="Next">
        <span aria-hidden="true">&raquo;</span>
        <span class="sr-only">Next</span>
      </a>
    </li>
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

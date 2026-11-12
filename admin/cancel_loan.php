<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $ress = mysqli_query($db,"SELECT loan_apply.*, user.sloan FROM loan_apply Left JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='cancel' ORDER BY sloan DESC");
        $newloanquery =  mysqli_query($db,"SELECT loan_apply.*, user.sloan FROM loan_apply Left JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='cancel' AND NOT user.status='HOLD' ORDER BY sloan DESC LIMIT $offset, $no_of_records_per_page");
        
        #$ress = mysqli_query($db,"SELECT * FROM `loan_apply` WHERE `status`='disbursal' ORDER BY id DESC");
        #$newloanquery =  mysqli_query($db,"SELECT * FROM `loan_apply` WHERE `status`='disbursal' ORDER BY id DESC LIMIT $offset, $no_of_records_per_page");
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
                    <li><span class="bread-blod">Cancelled Loans</span></li>
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
                                    <i class="fa-solid fa-ban"></i> Cancelled Loans
                                    <span class="cl-badge-pill"><?= number_format($total_rows) ?></span>
                                </h3>
                                <p class="cl-card-header-desc">Loan applications rejected or cancelled during underwriting or by customer request</p>
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
                                        <th>Contact</th>    
                                        <th style="text-align:right;">Loan Limit</th>    
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
                                                <?php if(isset($user_loan) && $user_loan > 0){ echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; } ?>
                                                <?php if((isset($user_sloan) && $user_sloan > 0) || (isset($users_sloan) && $users_sloan > 0)){ echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; } ?>
                                            </div>
                                            <div style="margin-top:3px; font-size:11.5px; font-family:monospace; color:var(--cl-primary); font-weight:600;">
                                                <?= creditlab_dash_text($user_rcid ?? '') ?>
                                            </div>
                                        </td>
                                        <td data-title="Contact" style="white-space:nowrap;">
                                            <div style="font-family:monospace; font-size:12.5px; font-weight:500; color:var(--cl-slate-800);">
                                                <?= creditlab_dash_text($user_mobile ?? '') ?>
                                            </div>
                                            <?php if (!empty($user_email)): ?>
                                                <div style="margin-top:2px; font-size:11px; color:var(--cl-slate-500); max-width:180px; overflow:hidden; text-overflow:ellipsis;">
                                                    <?= htmlspecialchars($user_email) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-title="Limit" style="text-align:right; font-weight:700; color:var(--cl-slate-900); white-space:nowrap;">
                                            <?= creditlab_currency($user_loan_limit ?? 0) ?>
                                        </td>
                                        <td data-title="Status" style="text-align:center; white-space:nowrap;">
                                            <?php
                                            if ($users_status == "disbursal" && ($loanfetch['agreement'] ?? 0) == 1) {
                                                echo '<span class="badge-modern badge-signed" style="background:#ff3377 !important; color:#ffffff !important; font-weight:700; padding:6px 12px; border-radius:12px; box-shadow:0 2px 6px rgba(255,51,119,0.3); display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-file-signature"></i> Signed (Disburse)</span>';
                                            } else {
                                                echo creditlab_status_badge($users_status, $loanfetch['agreement'] ?? 0);
                                            }
                                            ?>
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

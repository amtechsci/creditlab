<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $ress = mysqli_query($db,"SELECT * FROM user WHERE verify=0 ORDER BY id DESC");
        $usersquery =  mysqli_query($db,"SELECT * FROM user WHERE verify=3 OR verify=4 ORDER BY id DESC LIMIT $offset, $no_of_records_per_page");
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
                    <li><span class="bread-blod">Hold Loans</span></li>
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
                                    <i class="fa-solid fa-circle-pause"></i> Hold Loans
                                    <span class="cl-badge-pill"><?= number_format($total_rows) ?></span>
                                </h3>
                                <p class="cl-card-header-desc">Loan applications placed on temporary hold pending review</p>
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
                                        <th style="width:34px; text-align:center;"><input type="checkbox" id="selectAll"></th>   
                                        <th style="width:48px;">SL</th>
                                        <th>Borrower / CID</th>        
                                        <th>Mobile</th>    
                                        <th style="text-align:center;">Status</th>    
                                        <th style="text-align:right; width:110px;">Actions</th>     
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($usersquery && townum($usersquery) > 0): ?>
                                        <?php $ii = $offset + 1; while($loanfetch = towfetch($usersquery)): extract($loanfetch, EXTR_PREFIX_ALL, "users"); ?>
                                            <tr>
                                                <td style="text-align:center;"><input type="checkbox" name="check[]" value="<?=$users_id?>"></td>   
                                                <td style="color:var(--cl-slate-500); font-size:12px;"><?=$ii?></td>
                                                <td data-title="Borrower">
                                                    <div style="font-weight:600; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                        <?=creditlab_dash_text($users_name ?? '')?>
                                                        <?php 
                                                            if(isset($users_verify) && $users_verify == 3){ echo '<span style="color:#16a34a; font-weight:bold; margin-left:3px;" title="Verification Passed">+</span>'; }
                                                            if(isset($users_verify) && $users_verify == 4){ echo '<span style="color:#dc2626; font-weight:bold; margin-left:3px;" title="Verification Issue">-</span>'; }
                                                            if(!empty($users_loan)){ echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; }
                                                            if(!empty($users_sloan)){ echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; }
                                                        ?>
                                                    </div>
                                                    <div style="margin-top:3px; font-size:11.5px; font-family:monospace; color:var(--cl-primary); font-weight:600;">
                                                        <?=creditlab_dash_text($users_rcid ?? '')?>
                                                    </div>
                                                </td>
                                                <td data-title="Mobile" style="white-space:nowrap;">
                                                    <div style="font-family:monospace; font-size:12.5px; font-weight:500; color:var(--cl-slate-800);">
                                                        <?=creditlab_dash_text($users_mobile ?? '')?>
                                                    </div>
                                                </td>
                                                <td data-title="Status" style="text-align:center; white-space:nowrap;">
                                                    <?= creditlab_status_badge($users_status ?? '') ?>
                                                </td>
                                                <td data-title="Actions" style="text-align:right; white-space:nowrap;">
                                                    <a class="btn btn-xs btn-primary" href="profile.php?id=<?=$users_id?>" target="_blank" style="padding:4px 10px;">
                                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php $ii++; endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="text-align:center; padding:30px; color:var(--cl-slate-400);">No hold loans found.</td>
                                        </tr>
                                    <?php endif; ?>
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

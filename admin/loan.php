<?php
include_once 'head.php';
$loanquery = towquery("SELECT * FROM `loan_apply` WHERE `status`='account manager'");
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
                    <li><span class="bread-blod">Approved Loans</span></li>
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
                                    <i class="fa-solid fa-file-circle-check"></i> Approved Loans
                                    <span class="cl-badge-pill"><?= number_format(townum($loanquery)) ?></span>
                                </h3>
                                <p class="cl-card-header-desc">Active sanctioned loan accounts under account management</p>
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
                                        <th>Loan ID</th>        
                                        <th style="text-align:right;">Loan Amount</th>        
                                        <th>Loan Availed On</th>        
                                        <th style="text-align:center;">Status</th>    
                                        <th style="text-align:center;">Repay In</th>     
                                        <th style="text-align:right; width:110px;">Actions</th>     
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($loanquery && townum($loanquery) > 0): ?>
                                        <?php while($loanfetch = towfetch($loanquery)): extract($loanfetch); ?>
                                            <tr>
                                                <td data-title="Loan ID"><strong style="color:var(--cl-primary); font-family:monospace;">CLL<?=$id?></strong></td>   
                                                <td data-title="Loan Amount" style="text-align:right; font-weight:700; color:var(--cl-slate-900);"><?=creditlab_currency($amount ?? 0)?></td>
                                                <td data-title="Loan Availed On"><?=creditlab_dash_text($status_date ?? '')?></td> 
                                                <td data-title="Loan Status" style="text-align:center; white-space:nowrap;"><?=creditlab_status_badge($status ?? '')?></td>   
                                                <td data-title="Loan Repay In" style="text-align:center; color:var(--cl-slate-600);"><?=$days?> days</td>  
                                                <td data-title="Actions" style="text-align:right; white-space:nowrap;">
                                                    <a class="btn btn-xs btn-primary" href="profile.php?id=<?=(int)($uid ?? 0)?>" target="_blank" style="padding:4px 10px;">
                                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="text-align:center; padding:30px; color:var(--cl-slate-400);">No approved loans found.</td>
                                        </tr>
                                    <?php endif; ?>
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

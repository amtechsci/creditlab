<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
    $pageno = (int)$_GET['pageno'];
} else {
    $pageno = 1;
}
$no_of_records_per_page = 50;
$offset = ($pageno - 1) * $no_of_records_per_page;
$ress = mysqli_query($db, "SELECT * FROM user WHERE `sloan` > 0 AND status='cleared' ORDER BY user.id DESC");
$total_rows = mysqli_num_rows($ress);
$total_pages = ceil($total_rows / $no_of_records_per_page);
$usersquery = mysqli_query($db, "SELECT * FROM user WHERE `sloan` > 0 AND status='cleared' ORDER BY user.id DESC LIMIT $offset, $no_of_records_per_page");
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
                <li><a href="users.php">Users</a> <span class="bread-slash">/</span></li>
                <li><span class="bread-blod">Existing (No Loan)</span></li>
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
                                <i class="fa-solid fa-user-slash"></i> Existing Users (No Active Loan)
                                <span class="cl-badge-pill"><?= number_format($total_rows) ?></span>
                            </h3>
                            <p class="cl-card-header-desc">Cleared repeat borrowers currently eligible for instant repeat loans</p>
                        </div>
                        <div class="cl-card-header-actions">
                            <a href="users.php" class="btn btn-primary" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                <i class="fa-solid fa-user-check"></i> Approved Users
                            </a>
                        </div>
                    </div>

                    <?= creditlab_render_table_legend() ?>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="loan_history_datatable">
                            <thead>
                                <tr>                
                                    <th style="width:36px; text-align:center;"><input type="checkbox" id="selectAll"></th>   
                                    <th style="width:55px;">SL NO</th>
                                    <th>CID</th>        
                                    <th>Name</th>        
                                    <th>Email</th>        
                                    <th>Mobile</th>    
                                    <th style="text-align:right;">Loan Limit</th> 
                                    <th>Account Manager</th> 
                                    <th>Status</th>    
                                    <th style="text-align:right;">Actions</th>     
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($usersquery && townum($usersquery) > 0): ?>
                                    <?php $ii = $offset + 1; while($loanfetch = towfetch($usersquery)): extract($loanfetch, EXTR_PREFIX_ALL, "users"); ?>
                                        <tr>
                                            <td style="text-align:center;"><input type="checkbox" name="check[]" value="<?=$users_id?>"></td>   
                                            <td style="color:var(--cl-slate-500);"><?=$ii?></td>
                                            <td data-title="CID"><strong style="color:var(--cl-primary);"><?=creditlab_dash_text($users_rcid ?? '')?></strong></td>
                                            <td data-title="Name">
                                                <span style="font-weight:600; color:var(--cl-slate-900);"><?=creditlab_dash_text($users_name ?? '')?></span>
                                                <?php if(!empty($users_loan)){ echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; } ?>
                                                <?php if(!empty($users_sloan)){ echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; } ?>
                                            </td>
                                            <td data-title="Email" style="color:var(--cl-slate-600);"><?=creditlab_dash_text($users_email ?? '')?></td>
                                            <td data-title="Mobile" style="font-family:monospace; font-size:12.5px;"><?=creditlab_dash_text($users_mobile ?? '')?></td>
                                            <td data-title="Loan Limit" style="text-align:right; font-weight:600; color:var(--cl-slate-900);"><?=creditlab_currency($users_loan_limit ?? 0)?></td>
                                            <td data-title="Account Manager">
                                                <?php 
                                                $account_manager = towfetch(towquery("SELECT `name` FROM `account_manager` WHERE `id`='$users_assign_account_manager'"));
                                                echo isset($account_manager['name']) ? $account_manager['name'] : '<span style="color:var(--cl-slate-400);">Not Assigned</span>';
                                                ?>
                                            </td>
                                            <td data-title="Status" style="text-align:center;"><?= creditlab_status_badge($users_status ?? '') ?></td>
                                            <td data-title="Actions" style="text-align:right;">
                                                <a class="btn btn-xs btn-primary" href="profile.php?id=<?=$users_id?>" target="_blank" style="padding:4px 10px;">
                                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                                </a>
                                            </td>
                                        </tr>
                                    <?php $ii++; endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" style="text-align:center; padding:30px; color:var(--cl-slate-400);">No existing cleared users found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; border-top:1px solid var(--cl-slate-100);">
                        <div style="font-size:12.5px; color:var(--cl-slate-500);">
                            Showing <strong><?= min($total_rows, $offset + 1) ?></strong> to <strong><?= min($total_rows, $offset + $no_of_records_per_page) ?></strong> of <strong><?= number_format($total_rows) ?></strong> users
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

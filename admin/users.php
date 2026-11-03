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

$count_res = towfetch(towquery("SELECT COUNT(*) as cnt FROM user WHERE verify=1 AND status='Approved'"));
$total_rows = (int)($count_res['cnt'] ?? 0);
$total_pages = max(1, (int)ceil($total_rows / $no_of_records_per_page));

$usersquery = towquery("SELECT * FROM user WHERE verify=1 AND status='Approved' ORDER BY id DESC LIMIT $offset, $no_of_records_per_page");
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
                        <li><a href="users.php">Users</a> <span class="bread-slash">/</span></li>
                        <li><span class="bread-blod">Approved Users</span></li>
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
                                    <i class="fa-solid fa-user-check" style="color:var(--cl-primary); margin-right:6px;"></i> Approved Users
                                </h3>
                                <p style="margin:4px 0 0; font-size:13px; color:var(--cl-slate-500);">
                                    Showing <?= number_format($total_rows) ?> verified customers eligible for loans
                                </p>
                            </div>
                            <div>
                                <a href="newusers.php" class="btn btn-default" style="border:1px solid var(--cl-slate-200); background:#fff; font-weight:600;">
                                    <i class="fa-solid fa-user-clock" style="margin-right:4px;"></i> Just Registered
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="loan_history_datatable">
                                <thead>
                                    <tr>
                                        <th style="width:40px; text-align:center;"><input type="checkbox" id="selectAll"></th>   
                                        <th style="width:60px;">SL NO</th>  
                                        <th>RCID</th>        
                                        <th>Name</th>        
                                        <th>Email</th>        
                                        <th>Mobile</th>  
                                        <th style="text-align:right;">Loan Limit</th>   
                                        <th style="text-align:center;">Status</th>    
                                        <th style="text-align:right;">Actions</th>     
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($usersquery && townum($usersquery) > 0): ?>
                                        <?php $ii = $offset + 1; while($loanfetch = towfetch($usersquery)): extract($loanfetch, EXTR_PREFIX_ALL, "users"); ?>
                                            <tr>
                                                <td style="text-align:center;">
                                                    <input type="checkbox" name="check[]" value="<?= (int)$users_id ?>">
                                                </td>   
                                                <td style="color:var(--cl-slate-500);"><?= $ii ?></td>
                                                <td data-title="CID">
                                                    <strong style="color:var(--cl-primary);"><?= creditlab_dash_text($users_rcid ?? '') ?></strong>
                                                </td>
                                                <td data-title="Name">
                                                    <span style="font-weight:600; color:var(--cl-slate-900);"><?= creditlab_dash_text($users_name ?? '') ?></span>
                                                    <?php 
                                                        if (!empty($users_loan)) { echo "<span title='Loan' style='color:#dc2626; font-weight:bold; margin-left:3px;'>#</span>"; }
                                                        if (!empty($users_sloan)) { echo "<span title='Sloan' style='color:#dc2626; font-weight:bold; margin-left:2px;'>@</span>"; }
                                                    ?>
                                                </td>
                                                <td data-title="Email" style="color:var(--cl-slate-600);"><?= creditlab_dash_text($users_email ?? '') ?></td>
                                                <td data-title="Mobile" style="font-family:monospace; font-size:12.5px;"><?= creditlab_dash_text($users_mobile ?? '') ?></td>
                                                <td data-title="Loan Limit" style="text-align:right; font-weight:600; color:var(--cl-slate-900);">
                                                    <?= creditlab_currency($users_loan_limit ?? 0) ?>
                                                </td>
                                                <td data-title="Status" style="text-align:center;">
                                                    <?= creditlab_status_badge($users_status ?? '') ?>
                                                </td>
                                                <td data-title="Actions" style="text-align:right;">
                                                    <a class="btn btn-xs btn-primary" href="profile.php?id=<?= (int)$users_id ?>" target="_blank" style="padding:4px 10px;">
                                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php $ii++; endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" style="text-align:center; padding:30px; color:var(--cl-slate-400);">No approved users found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

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
    </div>
<?php
include_once 'foot.php';
?>
</body>
</html>

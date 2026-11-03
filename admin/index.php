<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/performance_dashboard.php';
require_once __DIR__ . '/../lib/admin_ui.php';

$show_performance = creditlab_performance_dashboard_is_active();
$pd = $show_performance ? creditlab_performance_dashboard_load() : [];

if (isset($_GET['pageno'])) {
    $pageno = intval($_GET['pageno']);
} else {
    $pageno = 1;
}
if ($pageno < 1) { $pageno = 1; }
$no_of_records_per_page = 50;
$offset = ($pageno - 1) * $no_of_records_per_page;
$usersquery = null;
$total_pages = 1;
$total_rows = 0;

if (!$show_performance) {
    $cnt_res = towfetch(towquery("SELECT COUNT(*) as cnt FROM user WHERE NOT active=2"));
    $total_rows = (int)($cnt_res['cnt'] ?? 0);
    $total_pages = max(1, (int)ceil($total_rows / $no_of_records_per_page));
    $usersquery = towquery("SELECT * FROM user WHERE NOT active=2 ORDER BY id DESC LIMIT " . $offset . ", " . $no_of_records_per_page);
}
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
                        <li><span class="bread-blod"><?= $show_performance ? 'Performance Dashboard' : 'Dashboard' ?></span></li>
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
                        <ul id="myTabedu1" class="tab-review-design">
                            <li class="<?= $show_performance ? '' : 'active' ?>"><a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
                            <li class="<?= $show_performance ? 'active' : '' ?>"><a href="index.php?tab=performance"><i class="fa-solid fa-chart-pie"></i> Performance Dashboard</a></li>
                        </ul>
                        
                        <div id="myTabContent" class="tab-content custom-product-edit">
                            <div class="product-tab-list tab-pane fade <?= $show_performance ? '' : 'active in' ?>" id="dashboard">
                                
                                <!-- Top Metric Cards -->
                                <div class="row dash-stats">
                                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                        <a class="dash-stat" href="users.php">
                                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                                <span>Verified Users</span>
                                                <div style="width:32px; height:32px; border-radius:8px; background:#dbeafe; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:14px;">
                                                    <i class="fa-solid fa-user-check"></i>
                                                </div>
                                            </div>
                                            <strong><?= number_format((int) ($verifyquery_count ?? 0)) ?></strong>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                        <a class="dash-stat" href="newusers.php">
                                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                                <span>New Users</span>
                                                <div style="width:32px; height:32px; border-radius:8px; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:14px;">
                                                    <i class="fa-solid fa-user-clock"></i>
                                                </div>
                                            </div>
                                            <strong><?= number_format((int) ($newquery_count ?? 0)) ?></strong>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                        <a class="dash-stat" href="loan.php">
                                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                                <span>Loan Approved</span>
                                                <div style="width:32px; height:32px; border-radius:8px; background:#dcfce7; color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:14px;">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </div>
                                            </div>
                                            <strong><?= number_format((int) ($loanquery_count ?? 0)) ?></strong>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                        <a class="dash-stat" href="newloan.php">
                                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                                <span>New Loan Applied</span>
                                                <div style="width:32px; height:32px; border-radius:8px; background:#e0e7ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; font-size:14px;">
                                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                                </div>
                                            </div>
                                            <strong><?= number_format((int) ($newloanquery_count ?? 0)) ?></strong>
                                        </a>
                                    </div>
                                </div>

                                <!-- Action Toolbar -->
                                <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px;">
                                    <div style="font-size:13px; color:var(--cl-slate-500); font-weight:500;">
                                        Total registered customers: <strong style="color:var(--cl-slate-800);"><?= number_format($total_rows) ?></strong>
                                    </div>
                                    <div style="display:flex; gap:8px; align-items:center;">
                                        <div style="position:relative;">
                                            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:11px; color:var(--cl-slate-400); font-size:13px;"></i>
                                            <input type="text" id="searchtext" class="form-control" placeholder="Search RCID, name, mobile, email..." style="padding-left:34px; width:280px; height:36px;">
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="search()" style="height:36px; padding:0 16px;">
                                            <i class="fa-solid fa-search"></i> Search
                                        </button>
                                    </div>
                                </div>

                                <!-- Data Table -->
                                <div class="table-responsive" id="searchtable">
                                    <table class="table table-bordered" id="loan_history_datatable">
                                        <thead>
                                            <tr>
                                                <th>RCID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Mobile</th>
                                                <th>Status</th>
                                                <th style="text-align:right;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($usersquery && townum($usersquery) > 0): ?>
                                                <?php while($loanfetch = towfetch($usersquery)): extract($loanfetch, EXTR_PREFIX_ALL, "users"); ?>
                                                    <tr>
                                                        <td data-title="RCID">
                                                            <strong style="color:var(--cl-primary);"><?= creditlab_dash_text($users_rcid ?? '') ?></strong>
                                                        </td>
                                                        <td data-title="Name">
                                                            <span style="font-weight:600; color:var(--cl-slate-800);">
                                                                <?= creditlab_dash_text($users_name ?? ''); ?>
                                                            </span>
                                                            <?php
                                                                if (!empty($users_loan)) { echo "<span title='Loan' style='color:#dc2626; font-weight:bold; margin-left:3px;'>#</span>"; }
                                                                if (!empty($users_sloan)) { echo "<span title='Sloan' style='color:#dc2626; font-weight:bold; margin-left:2px;'>@</span>"; }
                                                            ?>
                                                        </td>
                                                        <td data-title="Email" style="color:var(--cl-slate-600);"><?= creditlab_dash_text($users_email ?? '') ?></td>
                                                        <td data-title="Mobile" style="font-family:monospace; font-size:12.5px;"><?= creditlab_dash_text($users_mobile ?? '') ?></td>
                                                        <td data-title="Status"><?= creditlab_status_badge($users_status ?? '') ?></td>
                                                        <td data-title="Actions" style="text-align:right;">
                                                            <a class="btn btn-xs btn-primary" href="profile.php?id=<?= (int) $users_id ?>" target="_blank" style="padding:4px 10px;">
                                                                <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" style="text-align:center; padding:30px; color:var(--cl-slate-400);">No users found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>

                                    <!-- Pagination -->
                                    <div style="padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; border-top:1px solid var(--cl-slate-100);">
                                        <div style="font-size:12.5px; color:var(--cl-slate-500);">
                                            Showing page <strong><?= $pageno ?></strong> of <strong><?= $total_pages ?></strong>
                                        </div>
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination" style="margin:0;">
                                                <li class="page-item <?= $pageno <= 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="<?= $pageno <= 1 ? '#' : '?pageno=' . ($pageno - 1) ?>" aria-label="Previous">&laquo;</a>
                                                </li>
                                                <?php
                                                $win_start = max(1, $pageno - 2);
                                                $win_end = min($total_pages, $pageno + 2);
                                                if ($win_start > 1) {
                                                    echo '<li class="page-item"><a class="page-link" href="?pageno=1">1</a></li>';
                                                    if ($win_start > 2) { echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
                                                }
                                                for ($i = $win_start; $i <= $win_end; $i++) {
                                                    $active = $i == $pageno ? ' active' : '';
                                                    echo '<li class="page-item' . $active . '"><a class="page-link" href="?pageno=' . $i . '">' . $i . '</a></li>';
                                                }
                                                if ($win_end < $total_pages) {
                                                    if ($win_end < $total_pages - 1) { echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
                                                    echo '<li class="page-item"><a class="page-link" href="?pageno=' . $total_pages . '">' . $total_pages . '</a></li>';
                                                }
                                                ?>
                                                <li class="page-item <?= $pageno >= $total_pages ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="<?= $pageno >= $total_pages ? '#' : '?pageno=' . ($pageno + 1) ?>" aria-label="Next">&raquo;</a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="product-tab-list tab-pane fade <?= $show_performance ? 'active in' : '' ?>" id="performance">
                                <?php if ($show_performance) { include __DIR__ . '/inc_performance_dashboard.php'; } ?>
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
<script>
    $(document).ready(function() {
        $('#searchtext').on('keydown', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                search();
            }
        });
        if ($.fn.DataTable && $('#updatesTable').length) {
            $('#updatesTable').DataTable({
                "pageLength": 25,
                "order": [[ 8, "desc" ]],
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "No entries found",
                    "infoFiltered": "(filtered from _TOTAL_ total entries)"
                }
            });
        }
    });
    function search(){
        var searchtext = $('#searchtext').val();
        $.post("searchtable.php",
            {
              search: searchtext
            },
            function(data,status) {
                $('#searchtable').html(data);
            });
    }
</script>
</body>
</html>

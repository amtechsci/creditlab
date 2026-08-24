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
        $offset = ($pageno-1) * $no_of_records_per_page;
        $usersquery = null;
        $total_pages = 1;
        if (!$show_performance) {
            $ress = towquery("SELECT * FROM user WHERE NOT active=2 ORDER BY id DESC");
            $usersquery =  towquery("SELECT * FROM user WHERE NOT active=2 ORDER BY id DESC LIMIT ".$offset.", ".$no_of_records_per_page);
            $total_rows = townum($ress);
            $total_pages = ceil($total_rows / $no_of_records_per_page);
        }
?>
<body>
<?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>
            <div class="breadcome-area" style="display:none;">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="single-pro-review-area mt-t-30 mg-b-15">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="product-payment-inner-st">
                            <ul id="myTabedu1" class="tab-review-design">
                                <li class="<?= $show_performance ? '' : 'active' ?>"><a href="index.php">Dashboard</a></li>
                                <li class="<?= $show_performance ? 'active' : '' ?>"><a href="index.php?tab=performance">Performance Dashboard</a></li>
                            </ul>
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade <?= $show_performance ? '' : 'active in' ?>" id="dashboard">
<style>
#dashboard .dash-stats { margin: 4px 0 16px; }
#dashboard .dash-stat {
    display: block;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 14px 12px;
    text-align: center;
    text-decoration: none;
    color: inherit;
    margin-bottom: 12px;
}
#dashboard .dash-stat:hover, #dashboard .dash-stat:focus {
    border-color: #006DF0;
    text-decoration: none;
    color: inherit;
}
#dashboard .dash-stat span { display: block; font-size: 12px; color: #6b7280; font-weight: 600; }
#dashboard .dash-stat strong { display: block; font-size: 22px; font-weight: 700; color: #111827; margin-top: 4px; }
#dashboard .dash-toolbar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}
#dashboard .dash-toolbar .form-control { height: 34px; max-width: 260px; }
#dashboard .dash-toolbar .btn { height: 34px; padding: 6px 14px; }
#dashboard .table th { background: #f3f4f6; font-size: 12px; }
#dashboard .table td { font-size: 13px; vertical-align: middle; }
#dashboard .dash-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
}
#dashboard .dash-muted { color: #9ca3af; }
#dashboard .pagination { margin: 12px 0 0; }
</style>
                <div class="row dash-stats">
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <a class="dash-stat" href="users.php">
                            <span>Verified users</span>
                            <strong><?= (int) ($verifyquery_count ?? 0) ?></strong>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <a class="dash-stat" href="newusers.php">
                            <span>New users</span>
                            <strong><?= (int) ($newquery_count ?? 0) ?></strong>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <a class="dash-stat" href="loan.php">
                            <span>Loan approved</span>
                            <strong><?= (int) ($loanquery_count ?? 0) ?></strong>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <a class="dash-stat" href="newloan.php">
                            <span>New loan applied</span>
                            <strong><?= (int) ($newloanquery_count ?? 0) ?></strong>
                        </a>
                    </div>
                </div>

                <div class="dash-toolbar">
                    <input type="text" id="searchtext" class="form-control" placeholder="Search RCID, name, mobile, email...">
                    <button type="button" class="btn btn-primary" onclick="search()">Search</button>
                </div>

                <div class="table-responsive" id="searchtable">
        <table class="table table-bordered" id="loan_history_datatable">
        <thead>
            <tr>
                                        <th>RCID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
        </thead>
        <tbody>
                                   <?php if ($usersquery) { while($loanfetch = towfetch($usersquery)){ extract($loanfetch,EXTR_PREFIX_ALL,"users"); ?>
                                    <tr>
                                        <td data-title="RCID"><?= creditlab_dash_text($users_rcid ?? '') ?></td>
                                        <td data-title="Name"><?php
                                            echo creditlab_dash_text($users_name ?? '');
                                            if (!empty($users_loan)) { echo "<span style='color:#dc2626'>#</span>"; }
                                            if (!empty($users_sloan)) { echo "<span style='color:#dc2626'>@</span>"; }
                                        ?></td>
                                        <td data-title="Email"><?= creditlab_dash_text($users_email ?? '') ?></td>
                                        <td data-title="Mobile"><?= creditlab_dash_text($users_mobile ?? '') ?></td>
                                        <td data-title="Status"><?= creditlab_status_badge($users_status ?? '') ?></td>
                                        <td data-title="Actions"><a class="btn btn-xs btn-primary" href="profile.php?id=<?= (int) $users_id ?>" target="_blank">View</a></td>
                                    </tr>
                                <?php } } ?>
            </tbody>
    </table>
							<nav aria-label="Page navigation">
  <ul class="pagination">
    <li class="page-item <?= $pageno <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="<?php if($pageno <= 1){ echo '#'; } else { echo "?pageno=".($pageno - 1); } ?>" aria-label="Previous">&laquo;</a>
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
        echo '<li class="page-item'.$active.'"><a class="page-link" href="?pageno='.$i.'">'.$i.'</a></li>';
    }
    if ($win_end < $total_pages) {
        if ($win_end < $total_pages - 1) { echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
        echo '<li class="page-item"><a class="page-link" href="?pageno='.$total_pages.'">'.$total_pages.'</a></li>';
    }
    ?>
    <li class="page-item <?= $pageno >= $total_pages ? 'disabled' : '' ?>">
      <a class="page-link" href="<?php if($pageno >= $total_pages){ echo '#'; } else { echo "?pageno=".($pageno + 1); } ?>" aria-label="Next">&raquo;</a>
    </li>
  </ul>
</nav>
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

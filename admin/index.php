<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/performance_dashboard.php';

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
            <div class="breadcome-area">
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
        <div class="analytics-sparkle-area">
                <div class="row text-center">
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <div class="analytics-sparkle-line reso-mg-b-30">
                            <a href="users.php"><div class="analytics-content">
                                <h5>Verify Users</h5>
                                <h2><span class="counter"><?= (int) ($verifyquery_count ?? 0) ?></span></h2>
                            </div></a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <div class="analytics-sparkle-line reso-mg-b-30">
                            <a href="newusers.php"><div class="analytics-content">
                                <h5>New Users</h5>
                                <h2><?= (int) ($newquery_count ?? 0) ?></h2>
                            </div></a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <div class="analytics-sparkle-line reso-mg-b-30">
                            <a href="loan.php"><div class="analytics-content">
                                <h5>Loan Approved</h5>
                                <h2><?= (int) ($loanquery_count ?? 0) ?></h2>
                            </div></a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <div class="analytics-sparkle-line reso-mg-b-30">
                            <a href="newloan.php"><div class="analytics-content">
                                <h5>New Loan Applied</h5>
                                <h2><?= (int) ($newloanquery_count ?? 0) ?></h2>
                            </div></a>
                        </div>
                    </div>
                </div>
                <br>
            
                
                <div class="row text-center">
                    <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                <div class="analytics-sparkle-line table-mg-t-pro dk-res-t-pro-30">
                    <div class="row">
                    <div class="col-md-8"></div>
                    <div class="col-md-3">
        <input type="text" id="searchtext" style="float:left;height:35px;border-radius:5%;border:1px solid #ddd;" class="form-control" placeholder="Search..."></div><div class="col-md-1"><span><button class="btn btn-primary" onclick="search()">Enter</button></span></div></div><br>

                <div class="table-responsive" id="searchtable">
        <table class="table table-bordered" id="loan_history_datatable">
        <thead class="thead-light">
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
                                        <td data-title="RCID"><?=$users_rcid?></td>
                                        <td data-title="Name"><?=$users_name?><?php if($users_loan > 0){echo "<span style='color:red'>#</span>";}?><?php if($users_sloan > 0){echo "<span style='color:red'>@</span>";}?></td>
                                        <td data-title="Email"><?=$users_email?></td>
                                        <td data-title="Mobile"><?=$users_mobile?></td>
                                        <td data-title="Status" style="color:white; background:<?php if($users_status == "default"){echo "red;";}elseif($users_status == "disbursal"){echo "green;";}else{echo "blue;";}?>"><?php if($users_status == "waiting"){echo "Just Registered";}else{echo $users_status;}?></td>
                                        <td data-title="Actions"><a class="btn btn-primary" href="profile.php?id=<?=$users_id?>" target="_blank">View</a></td>
                                    </tr>
                                <?php } } ?>
            </tbody>
    </table>
							<nav aria-label="Page navigation example">
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
  </ul>
</nav>
    </div>
    </div>
    </div></div>
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

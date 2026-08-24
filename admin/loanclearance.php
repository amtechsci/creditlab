<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $count_row = towfetch(towquery("SELECT COUNT(*) AS c FROM `pay_ref`"));
        $total_rows = (int) ($count_row['c'] ?? 0);
        $total_pages = $total_rows > 0 ? (int) ceil($total_rows / $no_of_records_per_page) : 1;
        $newloanquery = towquery(
            "SELECT
                pr.id AS pay_ref_id,
                pr.uid AS pay_uid,
                pr.loan_id AS pay_loan_id,
                pr.utr_ref,
                pr.payment_type,
                u.id AS user_id,
                u.rcid,
                u.name,
                u.mobile,
                u.loan AS user_loan_flag,
                u.sloan AS user_sloan_flag,
                l.lid,
                l.processed_date,
                l.processed_amount
            FROM `pay_ref` pr
            LEFT JOIN `loan` l ON l.lid = TRIM(REPLACE(REPLACE(pr.loan_id, 'CLL', ''), 'cll', ''))
            LEFT JOIN `user` u ON u.id = COALESCE(NULLIF(pr.uid, 0), l.uid)
            ORDER BY pr.id DESC
            LIMIT $offset, $no_of_records_per_page"
        );
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
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <ul class="breadcome-menu">
                                            <li><a href="../user">Home</a> <span class="bread-slash">/</span>
                                            </li>
                                            <li><span class="bread-blod">Account Manager</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Single pro tab review Start-->
        <div class="single-pro-review-area mt-t-30 mg-b-15">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="product-payment-inner-st">
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade active in" id="description">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <div id="dropzone1" class="pro-ad">
                                                    <form method="post" class="dropzone dropzone-custom needsclick add-professors" id="demo1-upload" enctype="multipart/form-data">
                                                            <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                                                <div class="table-responsive">
        <table class="table table-bordered" id="loan_history_datatable">
        <thead class="thead-light">
            <tr>                
                                        <th><input type='checkbox'></th>   
                                        <th>SLNO</th>    
                                        <th>CID</th>        
                                        <th>Name</th>         
                                        <th>Mobile</th>    
                                        <th>principal loan Amt</th>    
                                        <th>loan exhausted days</th>    
                                        <th>loan ID</th>
                                        <th>UTR / Type</th>
                                        <th>Actions</th>     
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php
                                   $ii = 1;
                                   if ($newloanquery) {
                                   while ($row = towfetch($newloanquery)) {
                                   $view_id = (int) ($row['user_id'] ?: $row['pay_uid']);
                                   $lid_num = preg_replace('/\D/', '', (string) ($row['lid'] ?: $row['pay_loan_id']));
                                   $processed_date = $row['processed_date'] ?? '';
                                   $exhausted = '-';
                                   if (!empty($processed_date) && $processed_date !== '0000-00-00') {
                                       $exhausted = (int) ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($processed_date . ' -1 day')))) / (60 * 60 * 24));
                                   }
                                   ?>
                                   <tr>
                                        <th><input type='checkbox' name="check[]" value="<?= $view_id ?>"></th>
                                        <th><?= $ii ?></th>
                                        <td data-title="CID"><?= htmlspecialchars((string) ($row['rcid'] ?? '')) ?></td>
                                        <td data-title="Name"><?= htmlspecialchars((string) ($row['name'] ?? '')) ?><?php if (!empty($row['user_loan_flag'])) { echo "<span style='color:red'>#</span>"; } ?><?php if (!empty($row['user_sloan_flag'])) { echo "<span style='color:red'>@</span>"; } ?></td>
                                        <td data-title="Mobile"><?= htmlspecialchars((string) ($row['mobile'] ?? '')) ?></td>
                                        <td data-title="Amount"><?= htmlspecialchars((string) ($row['processed_amount'] ?? '')) ?></td>
                                        <td data-title="Days"><?= $exhausted ?></td>
                                        <td data-title="Loan ID"><?= $lid_num !== '' ? 'CLL' . htmlspecialchars($lid_num) : htmlspecialchars((string) $row['pay_loan_id']) ?></td>
                                        <td data-title="UTR"><?= htmlspecialchars((string) ($row['utr_ref'] ?? '')) ?><?php if (!empty($row['payment_type'])) { echo '<br><small>' . htmlspecialchars((string) $row['payment_type']) . '</small>'; } ?></td>
                                        <td data-title="Actions"><?php if ($view_id > 0) { ?><a class="btn btn-primary" href="profile.php?id=<?= $view_id ?>" target="_blank">View</a><?php } else { echo '-'; } ?></td>
                                    </tr>
                                <?php $ii++; } } ?>
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
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><br>
       <?php
       include_once 'foot.php';
       ?>

</body>

</html>

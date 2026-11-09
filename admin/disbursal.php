<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $ress = mysqli_query($db,"SELECT loan_apply.*, user.sloan FROM loan_apply Left JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND sloan=0 ORDER BY sloan DESC");
        $newloanquery =  mysqli_query($db,"SELECT loan_apply.*, user.sloan FROM loan_apply Left JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND sloan=0 ORDER BY sloan DESC LIMIT $offset, $no_of_records_per_page");
        
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
                    <li><span class="bread-blod">Disbursal</span></li>
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
                                    <i class="fa-solid fa-paper-plane"></i> Disbursal
                                    <span class="cl-badge-pill"><?= number_format($total_rows) ?></span>
                                </h3>
                                <p class="cl-card-header-desc">Approved loans ready for banking disbursement and payout execution</p>
                            </div>
                            <div class="cl-card-header-actions">
                                <a href="repeat_loan_d.php" class="btn btn-default" style="border:1px solid var(--cl-slate-200); background:#fff; font-weight:600;">
                                    <i class="fa-solid fa-repeat" style="margin-right:4px;"></i> Repeat Loans
                                </a>
                                <a href="https://creditlab.in/downloader/z.php" class="btn btn-primary" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                    <i class="fa-solid fa-file-arrow-down"></i> Download Payout File
                                </a>
                            </div>
                        </div>

                        <?= creditlab_render_table_legend() ?>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="loan_history_datatable">
        <thead class="thead-light">
                                    <tr>
                                        <th><input type='checkbox'></th>   
                                        <th>SLNO</th> 
                                        <th>CID</th>        
                                        <th>Name</th>        
                                        <th>Email</th>        
                                        <th>Mobile</th>    
                                        <th>EMI/Not EMI</th>    
                                        <th>Req Amount</th>    
                                        <th>Disbursal Amount</th>    
                                        <th>P. fee</th>    
                                        <th>Status</th> 
                                        <th>Loan officer name</th> 
                                        <th>Loan applied date</th> 
                                        <th>Actions</th>     
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php $ii=1; while($loanfetch = towfetch($newloanquery)){ extract($loanfetch,EXTR_PREFIX_ALL,"users");
                                   $a = towquery("SELECT * FROM user WHERE id=$users_uid;");
                                   $b = towfetch($a);
                                   extract($b,EXTR_PREFIX_ALL,"user");
                                   ?>
                                    <tr>
                                        <th><input type='checkbox' name="check[]" value="<?=$user_id?>"></th>   
                                        <th><?=$ii?></th>
                                        <td data-title="CID"><strong><?=$user_rcid?></strong></td>
                                        <td data-title="Name">
                                            <span><?=$user_name?></span><?php 
                                                $has_loan = (isset($user_loan) && $user_loan > 0) || (isset($users_loan) && $users_loan > 0);
                                                $has_sloan = (isset($user_sloan) && $user_sloan > 0) || (isset($users_sloan) && $users_sloan > 0);
                                                if ($has_loan) { echo "<span class='cl-symbol cl-symbol-loan' title='Has Active Loan (loan > 0)'>#</span>"; }
                                                if ($has_sloan) { echo "<span class='cl-symbol cl-symbol-sloan' title='Special Loan / Repeat Customer (sloan > 0)'>@</span>"; }
                                            ?>
                                        </td>
                                        <td data-title="Email"><?=$user_email?></td>
                                        <td data-title="Mobile"><?=$user_mobile?></td>
                                        <td data-title="approvenew"><?=$user_approvenew?></td>
                                        <td data-title="amount"><?= creditlab_currency($users_amount + $users_processing_fees) ?></td>
                                        <td data-title="amount"><?= creditlab_currency($users_amount) ?></td>
                                        <td data-title="amount"><?= creditlab_currency($users_processing_fees) ?></td>
                                        <td data-title="Status" style="color:white; font-weight:700; background:<?php if($user_status == "default"){echo "red;";}elseif($user_status == "disbursal"){if(isset($loanfetch['agreement']) && (int)$loanfetch['agreement'] === 1){
                                        echo "#FFC0CB;"; 
                                        }else{
                                        echo "#008000;";
                                        }}else{echo "blue;";}?>"><?php 
                                            if($user_status == "disbursal" && isset($loanfetch['agreement']) && (int)$loanfetch['agreement'] === 1){
                                                echo 'Signed (Disburse)';
                                            } elseif($user_status == "waiting"){
                                                echo "Just Register";
                                            } else {
                                                echo $user_status;
                                            }
                                        ?></td>
                                        <td data-title="approvenew"><?php 
                                        $recovery_officer = towfetch(towquery("SELECT `name` FROM `recovery_officer` WHERE `id`='$user_assign_recovery_officer'"));
                                        echo isset($recovery_officer['name']) ? $recovery_officer['name'] : 'Not Assigned';
                                        ?></td>
                                        <td data-title="approvenew"><?=$users_apply_date?></td>
                                        <td data-title="Actions"><a class="btn btn-primary" href="profile.php?id=<?=$user_id?>" target="_blank">View</a></td>
                                    </tr>
                                <?php $ii++; } ?>
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

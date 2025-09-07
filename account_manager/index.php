<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $ress = mysqli_query($db,"SELECT uid FROM `loan_apply` WHERE `status`='account manager' ORDER BY id ASC");
        $today = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . " -64 day"));
        $newloanquery =  mysqli_query($db,"SELECT uid,id FROM `loan_apply` WHERE `status`='account manager' AND status_date > '{$today}' ORDER BY id ASC ");
        $renewloanquery =  mysqli_query($db,"SELECT uid,id FROM `loan_apply` WHERE `status`='account manager' AND status_date < '{$today}' ORDER BY id ASC");
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
                            <ul id="myTabedu1" class="tab-review-design">
                                <li class="active"><a href="#description">Daily follow ups (less than 65 days)</a></li>
                                <li><a href="#INFORMATION">Default (greater than 65 days)</a></li>
                            </ul>
                            <a href="https://creditlab.in/downloader/zz.php" class="btn btn-primary" style="color:#fff; float: right;">Download</a>
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
                                        <th>Alt Mobile</th>    
                                        <th>principal loan Amt</th>    
                                        <th>loan exhausted days</th>    
                                        <th>outstanding Amount</th>    
                                        <th>loan ID</th>    
                                        <th>Salary Date</th>    
                                        <th>Cst response</th>    
                                        <th>commitment date</th>    
                                        <th>updated date</th>    
                                        <th>Actions</th>     
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php 
                                   $seauserid = array();
                                   $i = 0;
                                   while($a = towfetch($newloanquery)){
                                       $seauserid[$i] = $a['id'];
                                       $i++;
                                   }
                                   $seauserid = array_unique($seauserid);
                                   $ii=1;
                                   $zz = [];
                                   foreach($seauserid as $value){
                                       $zz[] = $value;
                                   }
                                   $val = implode(',',$zz);
                                   $a = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi FROM user INNER JOIN loan ON loan.uid=user.id  WHERE loan.lid IN ($val) ORDER BY loan.id ASC  LIMIT $offset, $no_of_records_per_page");
                                   while($b = towfetch($a)){
                                   extract($b,EXTR_PREFIX_ALL,"user");
                                //   $lam = towfetch(towquery("SELECT * FROM `loan_acc_man` WHERE lid=".$user_lid." ORDER BY id DESC"));
                                   ?>
                                   <?php
// 1. Initialize empty arrays to hold the data from each row
$responses = [];
$commit_dates = [];
$updated_ats = [];

// 2. Execute the query to get the result set of the last 3 records
$query_result = towquery("SELECT customer_response, commitment_date, updated_at FROM `loan_acc_man` WHERE lid=".$user_lid." ORDER BY id DESC LIMIT 3");

// 3. Loop through each row of the result set
while ($row = towfetch($query_result)) {
    // Add the data from the current row into our arrays
    $responses[] = $row['customer_response'];
    $commit_dates[] = $row['commitment_date'];
    $updated_ats[] = $row['updated_at'];
}

// 4. Use implode() to concatenate the values with a line break
$concatenated_responses = implode("<br><br>", $responses);
$concatenated_dates = implode("<br><br>", $commit_dates);
$concatenated_updates = implode("<br><br>", $updated_ats);
?>
                                   <tr>
                                        <th><input type='checkbox' name="check[]" value="<?=$user_id?>"></th>   
                                        <th><?=$ii?></th> 
                                        <td data-title="CID"><?=$user_rcid?></td>
                                        <td data-title="Name"><?=$user_name?><?php if($user_loan > 0){echo "<span style='color:red'>#</span>";}?><?php if($users_sloan > 0){echo "<span style='color:red'>@</span>";}?><br>
                                        <?php if($user_member == 0){echo 'silver';} if($user_member == 1){echo 'gold';} if($user_member == 2){echo 'diamond';} if($user_member == 3){echo 'Platinum';}
                                                     if($user_member == 4){echo '<b style="color:red; font-size:22px;">RISKY</b>';}?></p>
                                        </td>
                                        <td data-title="Mobile"><?=$user_mobile?></td>
                                        <td data-title="Mobile"><?=$user_altmobile?></td>
                                        <td data-title="Mobile"><?=$user_processed_amount+$user_p_fee+($user_p_fee*0.18)?></td>
                                        <td data-title="Mobile"><?=ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',strtotime($user_processed_date." -1 day")))) / (60 * 60 * 24))?></td>
                                        <td data-title="Mobile"><?=$user_processed_amount+$user_p_fee+($user_p_fee*0.18)+$user_service_charge+$user_penality_charge?></td>
                                        <td data-title="Mobile">CLL<?=$user_lid?></td>
                                        <td data-title="Mobile"><?=$user_salary_date?></td>
                                        <td data-title="Customer Response"><?php echo $concatenated_responses; ?></td>
                                        <td data-title="Commitment Date"><?php echo $concatenated_dates; ?></td>
                                        <td data-title="Updated At"><?php echo $concatenated_updates; ?></td>
                                        <td data-title="Actions"><a class="btn btn-primary" href="profile.php?id=<?=$user_id?>" target="_blank">View</a></td>
                                    </tr>
                                <?php $ii++;} ?>
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
                                <div class="product-tab-list tab-pane fade" id="INFORMATION">
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
                                        <th>city</th>    
                                        <th>principal loan Amt</th>    
                                        <th>loan exhausted days</th>    
                                        <th>outstanding Amount</th>    
                                        <th>loan ID</th>    
                                        <th>St response</th>    
                                        <th>commitment date</th>    
                                        <th>updated date</th>    
                                        <th>Actions</th>     
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php 
                                   $reseauserid = array();
                                   $i = 0;
                                   while($aa = towfetch($renewloanquery)){
                                       $reseauserid[$i] = $aa['uid'];
                                       $i++;
                                   }
                                   $reseauserid = array_unique($reseauserid);
                                   foreach($reseauserid as $value){
                                   $a = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi, loan_acc_man.customer_response, loan_acc_man.commitment_date, loan_acc_man.updated_at FROM user INNER JOIN loan ON loan.uid=user.id LEFT JOIN loan_acc_man ON loan_acc_man.uid=user.id WHERE user.id=$value AND loan.status_log='account manager' ORDER BY loan.lid");
                                   if(townum($a) > 0){
                                   $b = towfetch($a);
                                   extract($b,EXTR_PREFIX_ALL,"user");
                                //   $lam = towfetch(towquery("SELECT * FROM `loan_acc_man` WHERE lid=".$user_lid." ORDER BY id DESC"));
                                   ?>
                                   <?php
// 1. Initialize empty arrays to hold the data from each row
$responses = [];
$commit_dates = [];
$updated_ats = [];

// 2. Execute the query to get the result set of the last 3 records
$query_result = towquery("SELECT customer_response, commitment_date, updated_at FROM `loan_acc_man` WHERE lid=".$user_lid." ORDER BY id DESC LIMIT 3");

// 3. Loop through each row of the result set
while ($row = towfetch($query_result)) {
    // Add the data from the current row into our arrays
    $responses[] = $row['customer_response'];
    $commit_dates[] = $row['commitment_date'];
    $updated_ats[] = $row['updated_at'];
}

// 4. Use implode() to concatenate the values with a line break
$concatenated_responses = implode("<br><br>", $responses);
$concatenated_dates = implode("<br><br>", $commit_dates);
$concatenated_updates = implode("<br><br>", $updated_ats);
?>
                                    <tr>
                                        <th><input type='checkbox' name="check[]" value="<?=$user_id?>"></th>   
                                        <th><?=$ii?></th> 
                                        <td data-title="CID"><?=$user_rcid?></td>
                                        <td data-title="Name"><?=$user_name?><?php if($user_loan > 0){echo "<span style='color:red'>#</span>";}?><?php if($users_sloan > 0){echo "<span style='color:red'>@</span>";}?></td>
                                        <td data-title="Mobile"><?=$user_mobile?></td>
                                        <td data-title="Mobile"></td>
                                        <td data-title="Mobile"><?=$user_processed_amount?></td>
                                        <td data-title="Mobile"><?=ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',strtotime($user_processed_date." -1 day")))) / (60 * 60 * 24))?></td>
                                        <td data-title="Mobile"><?=$user_total_amount?></td>
                                        <td data-title="Mobile">CLL<?=$user_lid?></td>
                                        <td data-title="Customer Response"><?php echo $concatenated_responses; ?></td>
                                        <td data-title="Commitment Date"><?php echo $concatenated_dates; ?></td>
                                        <td data-title="Updated At"><?php echo $concatenated_updates; ?></td>
                                        <!--<td data-title="Status" style="color:white; background:<?php #if($users_status == "default"){echo "red;";}elseif($users_status == "disbursal"){echo "green;";}else{echo "blue;";}?>"><?php #$user_status?></td>-->
                                        <td data-title="Actions"><a class="btn btn-primary" href="profile.php?id=<?=$user_id?>" target="_blank">View</a></td>
                                    </tr>
                                <?php $ii++;}} ?>
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

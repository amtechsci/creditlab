<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/staff_context.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'daily';
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;

        // Single source: all account-manager loans (same as admin/account_manager.php). Split by DPD so no IDs are missing.
        $daily_loans_all = [];
        $default_loans_all = [];
        $all_am_query = towquery("SELECT loan_apply.id as laid, user.id as uid FROM loan_apply INNER JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status = 'account manager'");
        $all_am_uids = [];
        while ($r = towfetch($all_am_query)) { $all_am_uids[] = $r['uid']; }
        $all_am_uids = array_unique($all_am_uids);

        foreach ($all_am_uids as $uid) {
            $q = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan_apply.follow_up_date, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi, loan_apply.days as loan_apply_days FROM user INNER JOIN loan ON loan.uid=user.id INNER JOIN loan_apply ON loan_apply.id=loan.lid WHERE user.id=$uid AND loan.status_log='account manager'");
            while ($b = towfetch($q)) {
                if (empty($b['processed_date'])) continue;
                $processed_date_str = date('Y-m-d', strtotime($b['processed_date'] . " -1 day"));
                $tday = ceil((strtotime(date('Y-m-d')) - strtotime($processed_date_str)) / (60 * 60 * 24));
                $loan_days_raw = isset($b['loan_apply_days']) ? (int)$b['loan_apply_days'] : 30;
                $loan_is_emi = isset($b['is_emi']) ? (int)$b['is_emi'] : 0;
                $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
                $dpd = $tday - $loan_days;
                $b['calculated_dpd'] = $dpd;
                if ($dpd <= 35) {
                    $daily_loans_all[] = $b;
                } else {
                    $default_loans_all[] = $b;
                }
            }
        }

        usort($daily_loans_all, function($a, $b) { return $b['calculated_dpd'] <=> $a['calculated_dpd']; });
        usort($default_loans_all, function($a, $b) { return $a['calculated_dpd'] <=> $b['calculated_dpd']; });

        $total_rows_daily = count($daily_loans_all);
        $total_pages_daily = ceil($total_rows_daily / $no_of_records_per_page);
        $daily_loans_paged = array_slice($daily_loans_all, ($active_tab == 'daily' ? $offset : 0), $no_of_records_per_page);

        $total_rows_default = count($default_loans_all);
        $total_pages_default = ceil($total_rows_default / $no_of_records_per_page);
        $default_loans_paged = array_slice($default_loans_all, ($active_tab == 'default' ? $offset : 0), $no_of_records_per_page);
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
                                <li class="<?= $active_tab == 'daily' ? 'active' : '' ?>"><a href="#description">Daily follow ups (less than 35 DPD)</a></li>
                                <li class="<?= $active_tab == 'default' ? 'active' : '' ?>"><a href="#INFORMATION">Default (greater than 35 DPD)</a></li>
                            </ul>
                            <?php if (creditlab_can_download_account_manager_data()) { ?>
                            <a href="<?=getAppUrl()?>/downloader/zz.php" class="btn btn-primary" style="color:#fff; float: right;">Download</a>
                            <?php } else { ?>
                            <button type="button" class="btn btn-primary" style="color:#fff; float: right;">Download</button>
                            <?php } ?>
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade <?= $active_tab == 'daily' ? 'active in' : '' ?>" id="description">
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
                                        <th>Total Loans</th>
                                        <th>principal loan Amt</th>    
                                        <th>loan exhausted days</th>    
                                        <th>DPD</th>    
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
                                   $ii = 1;
                                   foreach($daily_loans_paged as $b){
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

$loan_count_query = towquery("SELECT COUNT(*) AS total_loans FROM `loan` WHERE uid=".intval($user_uid));
$loan_count_row = $loan_count_query ? towfetch($loan_count_query) : null;
$loan_count = $loan_count_row ? (int)$loan_count_row['total_loans'] : 0;
$loan_count_style = $loan_count === 1 ? 'font-weight:bold;color:red;' : '';
$loan_count_markup = '<span style="'.$loan_count_style.'">No. of Loans: '.$loan_count.'</span>';

$salary_value = isset($user_salary) ? (float)$user_salary : 0.0;
$loan_limit_value = isset($user_loan_limit) ? (float)$user_loan_limit : 0.0;
$limit_percentage = $salary_value > 0 ? (($loan_limit_value / $salary_value) * 100) : null;
$limit_percentage_formatted = $limit_percentage !== null ? number_format($limit_percentage, 2) : null;
$limit_percentage_style = ($limit_percentage !== null && $limit_percentage > 15) ? 'font-weight:bold;color:red;' : '';
$limit_percentage_markup = $limit_percentage !== null
    ? '<span style="'.$limit_percentage_style.'">Limit vs Salary: '.$limit_percentage_formatted.'%</span>'
    : '<span>Limit vs Salary: N/A</span>';

$membership_label = '';
switch ((int)$user_member) {
    case 0:
        $membership_label = 'silver';
        break;
    case 1:
        $membership_label = 'gold';
        break;
    case 2:
        $membership_label = 'diamond';
        break;
    case 3:
        $membership_label = 'Platinum';
        break;
    case 4:
        $membership_label = '<b style="color:red; font-size:22px;">RISKY</b>';
        break;
}
?>
                                   <tr>
                                        <th><input type='checkbox' name="check[]" value="<?=$user_id?>"></th>   
                                        <th><?=$ii?></th> 
                                        <td data-title="CID"><?=$user_rcid?></td>
                                        <td data-title="Name"><?=$user_name?><?php if(isset($user_loan) && $user_loan > 0){echo "<span style='color:red'>#</span>";}?><?php if(isset($users_sloan) && $users_sloan > 0){echo "<span style='color:red'>@</span>";}?><br>
                                        <?=$membership_label?><br><?=$loan_count_markup?><br><?=$limit_percentage_markup?></td>
                                        <td data-title="Mobile"><?=$user_mobile?></td>
                                        <td data-title="Mobile"><?=$user_altmobile?></td>
                                        <td data-title="Total Loans"><?=$loan_count_markup?></td>
                                        <td data-title="Mobile"><?=$user_processed_amount+$user_p_fee+($user_p_fee*0.18)?></td>
                                        <td data-title="Mobile"><?=ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',strtotime($user_processed_date." -1 day")))) / (60 * 60 * 24))?></td>
                                        <td data-title="DPD"><?=isset($user_calculated_dpd) ? $user_calculated_dpd : 0?></td>
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
      <a class="page-link" href="<?php if($pageno <= 1){ echo '#'; } else { echo "?pageno=".($pageno - 1)."&tab=daily"; } ?>" aria-label="Previous">
        <span aria-hidden="true">&laquo;</span>
        <span class="sr-only">Previous</span>
      </a>
    </li>
    <?php $i = 1; while($i <= $total_pages_daily){?>
    <li class="page-item <?= ($pageno == $i) ? 'active' : '' ?>"><a class="page-link" href="?pageno=<?=$i;?>&tab=daily"><?=$i;?></a></li>
    <?php $i++; }?>
    <li class="page-item">
      <a class="page-link" href="<?php if($pageno >= $total_pages_daily){ echo '#'; } else { echo "?pageno=".($pageno + 1)."&tab=daily"; } ?>" aria-label="Next">
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
                                <div class="product-tab-list tab-pane fade <?= $active_tab == 'default' ? 'active in' : '' ?>" id="INFORMATION">
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
                                        <th>Total Loans</th>
                                        <th>principal loan Amt</th>    
                                        <th>loan exhausted days</th>    
                                        <th>DPD</th>    
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
                                   $ii = 1;
                                   foreach($default_loans_paged as $b){
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

$loan_count_query = towquery("SELECT COUNT(*) AS total_loans FROM `loan` WHERE uid=".intval($user_uid));
$loan_count_row = $loan_count_query ? towfetch($loan_count_query) : null;
$loan_count = $loan_count_row ? (int)$loan_count_row['total_loans'] : 0;
$loan_count_style = $loan_count === 1 ? 'font-weight:bold;color:red;' : '';
$loan_count_markup = '<span style="'.$loan_count_style.'">No. of Loans: '.$loan_count.'</span>';

$salary_value = isset($user_salary) ? (float)$user_salary : 0.0;
$loan_limit_value = isset($user_loan_limit) ? (float)$user_loan_limit : 0.0;
$limit_percentage = $salary_value > 0 ? (($loan_limit_value / $salary_value) * 100) : null;
$limit_percentage_formatted = $limit_percentage !== null ? number_format($limit_percentage, 2) : null;
$limit_percentage_style = ($limit_percentage !== null && $limit_percentage > 15) ? 'font-weight:bold;color:red;' : '';
$limit_percentage_markup = $limit_percentage !== null
    ? '<span style="'.$limit_percentage_style.'">Limit vs Salary: '.$limit_percentage_formatted.'%</span>'
    : '<span>Limit vs Salary: N/A</span>';

$membership_label = '';
switch ((int)$user_member) {
    case 0:
        $membership_label = 'silver';
        break;
    case 1:
        $membership_label = 'gold';
        break;
    case 2:
        $membership_label = 'diamond';
        break;
    case 3:
        $membership_label = 'Platinum';
        break;
    case 4:
        $membership_label = '<b style="color:red; font-size:22px;">RISKY</b>';
        break;
}
?>
                                    <tr>
                                        <th><input type='checkbox' name="check[]" value="<?=$user_id?>"></th>   
                                        <th><?=$ii?></th> 
                                        <td data-title="CID"><?=$user_rcid?></td>
                                        <td data-title="Name"><?=$user_name?><?php if(isset($user_loan) && $user_loan > 0){echo "<span style='color:red'>#</span>";}?><?php if(isset($users_sloan) && $users_sloan > 0){echo "<span style='color:red'>@</span>";}?><br>
                                        <?=$membership_label?><br><?=$loan_count_markup?><br><?=$limit_percentage_markup?></td>
                                        <td data-title="Mobile"><?=$user_mobile?></td>
                                        <td data-title="Mobile"></td>
                                        <td data-title="Total Loans"><?=$loan_count_markup?></td>
                                        <td data-title="Mobile"><?=$user_processed_amount?></td>
                                        <td data-title="Mobile"><?=ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',strtotime($user_processed_date." -1 day")))) / (60 * 60 * 24))?></td>
                                        <td data-title="DPD"><?=isset($user_calculated_dpd) ? $user_calculated_dpd : 0?></td>
                                        <td data-title="Mobile"><?=$user_total_amount?></td>
                                        <td data-title="Mobile">CLL<?=$user_lid?></td>
                                        <td data-title="Customer Response"><?php echo $concatenated_responses; ?></td>
                                        <td data-title="Commitment Date"><?php echo $concatenated_dates; ?></td>
                                        <td data-title="Updated At"><?php echo $concatenated_updates; ?></td>
                                        <!--<td data-title="Status" style="color:white; background:<?php #if($users_status == "default"){echo "red;";}elseif($users_status == "disbursal"){echo "green;";}else{echo "blue;";}?>"><?php #$user_status?></td>-->
                                        <td data-title="Actions"><a class="btn btn-primary" href="profile.php?id=<?=$user_id?>" target="_blank">View</a></td>
                                    </tr>
                                <?php $ii++;} ?>
            </tbody>
    </table>
							<nav aria-label="Page navigation example">
  <ul class="pagination">
    <li class="page-item">
      <a class="page-link" href="<?php if($pageno <= 1){ echo '#'; } else { echo "?pageno=".($pageno - 1)."&tab=default"; } ?>" aria-label="Previous">
        <span aria-hidden="true">&laquo;</span>
        <span class="sr-only">Previous</span>
      </a>
    </li>
    <?php $i = 1; while($i <= $total_pages_default){?>
    <li class="page-item <?= ($pageno == $i) ? 'active' : '' ?>"><a class="page-link" href="?pageno=<?=$i;?>&tab=default"><?=$i;?></a></li>
    <?php $i++; }?>
    <li class="page-item">
      <a class="page-link" href="<?php if($pageno >= $total_pages_default){ echo '#'; } else { echo "?pageno=".($pageno + 1)."&tab=default"; } ?>" aria-label="Next">
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
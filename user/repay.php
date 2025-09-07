<?php
include_once 'head.php';
$loanquery = towquery("SELECT * FROM loan WHERE uid=$user_id AND status_log='account manager'");
?>
<body>
    <?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>
            <!-- Mobile Menu end -->
            <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <ul>
                                            <li><h4><?=$user_name?></h4>
                                            </li>
                                            <li><span class="bread-blod"><?=$user_mobile;?></span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <div class="rc">
                                        <ul class="breadcome-menua">
                                            <li>Your creditlab.in ID: <span class="bread-bloda"><?=$user_rcid;?></span>
                                            </li>
                                            
                                        </ul>
                                        <ul class="breadcome-menua">
                                            <li><span class="bread-blod"><?=$user_email;?></span>
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
        </div>
        <div class="container chat" style="background:#fff;">
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in</h4>
        </div>
        <br>
        <!-- Single pro tab review Start-->
        <div class="single-pro-review-area mt-t-30 mg-b-15">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="product-payment-inner-st">
                            <ul id="myTabedu1" class="tab-review-design">
                                <li class="active"><a href="#description">Repay Loan</a></li>
                                
                            </ul>
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade active in" id="description">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <div id="dropzone1" class="pro-ad">
                                                    <form method="post" class="dropzone dropzone-custom needsclick add-professors" id="demo1-upload" enctype="multipart/form-data">
                                            
                                                            <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                                                <div style="height: 36rem;">
    <div class="table-responsive">
        <table class="table table-bordered" id="loan_history_datatable">
        <thead class="thead-light">
            <tr>                
                                        <th>Loan ID</th>
                                        <th>Loan Availed On</th>  
                                        <th>Loan Amount</th> 
                                        <th>Days</th>    
                                        <th>Processing Fee</th>    
                                        <th>Service Charge</th>
                                        <th>Penalty Fee</th>
                                        <th>Total Amount</th>    
                                        <th>Repay Now</th>     
                                    </tr>
        </thead>
        <tbody>
                  <?php while($loanfetch = towfetch($loanquery)){ extract($loanfetch);
                  
                  $stop_date = date_create($processed_date);
                $sa = date_create(date('Y-m-d H:i:s'));
                $aa = date_diff($stop_date,$sa);
                $days = $aa->format("%a");
                
                $t = $processed_amount;
    $day =  $days;
    if(($day) <= 5 ){
        $fee = $t * $day / 100 * 0.6;
        $interest = "0.6%";
    }
    if(($day) > 5){
        if(($day) <= 10){
        $fee = $t * $day / 100 * 0.7;
        $interest = "0.7%"; 
        }else{
        $fee = $t * $day / 100 * 0.8;
        $interest = "0.8%";
        }}
        $total_amount = $fee + $p_fee + $processed_amount; 
        towquery("UPDATE `loan` SET `exhausted_period`='$days',`service_charge`=$fee,`penality_charge`=0,`total_amount`='$total_amount' WHERE lid=$lid");
        $loanquery = towquery("SELECT * FROM loan WHERE uid=$user_id AND status_log='account manager'");
        $loanfetch = towfetch($loanquery); extract($loanfetch);
                  ?>
                                    <tr>
                                        <td data-title="Loan ID">CLL<?=$lid?></td>   
                                <td data-title="Loan Amount"><?=$processed_date;?></td>
                                <td data-title="Loan Availed On"><?=$processed_amount?></td> 
                                <td data-title="Loan Repay Date"><?=$exhausted_period?> Days</td>   
                                <td data-title="Loan Repay Date"><?=$p_fee?></td>   
                                <td data-title="Loan Repay In"><?=$service_charge?></td>  
                                <td data-title="Loan Repay In"><?=$penality_charge?></td>  
                                <td data-title="Loan Repay In"><?=$total_amount?></td>  
                                <td data-title="Loan Repay In">n</td>  
                                 
                                    </tr>
                                <?php } ?>
            </tbody>
    </table>
    </div>
    


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
        </div>
       <?php
       include_once 'foot.php';
       ?>
        
</body>

</html>

<?php
include_once 'head.php';
?>
<?php
             $loan_amountc = towreal($_POST['amount']);
             $salary_date = towreal($_POST['salary_date']);
             $processed_date = date_create(towreal($_POST['processed_date']));
             $dis_date = date_format($processed_date,"Y-m-d");
             $dis_day = date_format($processed_date,"Y-m");
             $sal_day = date_create($dis_day."-".$salary_date);
             $sal_day = date_format($sal_day,"Y-m-d");
             $tax = $loan_amountc / 100 * 1.8;
             $di = strtotime($dis_date);
             $sa = strtotime($sal_day);
             $datediff = $sa - $di;
             $day_gap = round($datediff / (60 * 60 * 24));
             if($day_gap > 15){
                 $femi_date = $sal_day;
                 $semi_date = date('Y-m-d', strtotime( $femi_date . " +1 month"));
             }else{
                 $femi_date = date('Y-m-d', strtotime( $sal_day . " +1 month"));
                 $semi_date = date('Y-m-d', strtotime( $femi_date . " +1 month"));
                 $fe = strtotime($femi_date);
                 $se = strtotime($semi_date);
                 $fedatediff = $fe - $di;
                 $feday_gap = round($fedatediff / (60 * 60 * 24));
                 $femi_amount = ($loan_amountc/2) + ($loan_amountc*0.003*$feday_gap)+($loan_amountc*0.018/2);
                 $sedatediff = $se - $fe;
                 $seday_gap = round($sedatediff / (60 * 60 * 24));
                 $semi_amount = ($loan_amountc / 2) + ($loan_amountc * 0.003 * $seday_gap)+($loan_amountc*0.018/2);
             }
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
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <ul class="breadcome-menu">
                                            <li><a href="../user">Home</a> <span class="bread-slash">/</span>
                                            </li>
                                            <li><span class="bread-blod">Add Package</span>
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
        <br>
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
                                            <form action="" method="post">
                                            <div class="review-content-section">
                                                <div class="form-group">
                                                    <lable>Amount</lable>
                                                    <input type="number" name="amount" class="form-control">
                                                </div>
                                                <div class="form-group">
                                                    <lable>Salary Date</lable>
                                                    <input type="number" name="salary_date" class="form-control">
                                                </div>
                                                <div class="form-group">
                                                    <lable>Processed Date</lable>
                                                    <input type="date" name="processed_date" class="form-control">
                                                </div>
                                                <div class="form-group">
                                                    <input type="submit" name="submit" class="btn btn-primary">
                                                </div>
                                            </div>
                                            </form>
                                            <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>SL.NO</th>
                    <th>EMI Date </th>
                    <th>Amount to Repay (inclusive Gst)
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>1</td>
                    <td><?=$femi_date?></td>
                    <td>Rs. <?=$femi_amount?></td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td><?=$semi_date?></td>
                    <td>Rs. <?=$semi_amount?></td>
                  </tr>
                  
                </tbody>
              </table>
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

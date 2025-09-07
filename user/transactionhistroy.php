<?php
include_once 'head.php';
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
                                <li class="active"><a href="#description">Transaction Histroy</a></li>
                                
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
                                        <th>Trans Date</th>        
                                        <th>Transaction ID</th>        
                                        <th>Amount</th>    
                                        <th>Transaction Flow</th>     
                                    </tr>
        </thead>
        <tbody>
                  <?php 
                  $trans = towquery("SELECT * FROM `transaction_details` WHERE uid=$user_id");
                  if(townum($trans) > 0){
                      while($transf = towfetch($trans)){
                          extract($transf);
                  ?>
                                    <tr>
                                        <td>CLL<?=$cllid?></td>   
                                <td><?=$transaction_date?></td>
                                <td><?=$transaction_number?></td> 
                                <td><?=$transaction_amount?></td>   
                                <td><?=$transaction_flow?></td>
                                    </tr>
                                    <?php } }else{?>
                                    <tr>
                                        <td data-title="Loan ID">No data</td>   
                                <td data-title="Loan Amount">No data</td>
                                <td data-title="Loan Availed On">No data</td> 
                                <td data-title="Loan Repay Date">No data</td>   
                                <td data-title="Loan Repaid On">No data</td>  
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

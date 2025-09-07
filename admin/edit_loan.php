<?php
include_once 'head.php';
if (isset($_GET['cllid'])) {
            $cllid = $_GET['cllid'];
            $tra = towquery("SELECT * FROM transaction_details WHERE id='$cllid'");
                                   $traf = towfetch($tra);
                                   extract($traf,EXTR_PREFIX_ALL,"tra");
        }else {
            print_r("<script>window.location.replace('index.php');</script>");
        }
if(isset($_POST['tran_num'])){
    $tran_num = towreal($_POST['tran_num']);
    towquery("UPDATE `transaction_details` SET `transaction_number`='$tran_num' WHERE id=$tra_id");
    print_r("<script> alert('Transaction Number Updated');
    window.location.replace('profile.php?id=".$tra_uid."');</script>");
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
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <ul class="breadcome-menu">
                                            <li><a href="../user">Home</a> <span class="bread-slash">/</span>
                                            </li>
                                            <li><span class="bread-blod">Loans</span>
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
                                <li class="active"><a href="#description">Edit Transaction</a></li>
                            </ul>
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade active in" id="description">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <div id="dropzone1" class="pro-ad">
        <form method="post" class="dropzone dropzone-custom needsclick add-professors">
        <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
            <div class="table-responsive">
        <table class="table table-bordered" id="loan_history_datatable">
        <thead class="thead-light">
            <tr>                
                                        <th>CLLID</th>        
                                        <th>Transaction Number</th>       
                                        <th>Actions</th>     
                                    </tr>
        </thead>
        <tbody>
                                    <tr>
                                        <td data-title="Mobile"><?=$tra_cllid?></td>
                                        <td data-title="tra"><input type="text" value="<?=$tra_transaction_number?>" name="tran_num"></td>
                                        <td data-title="Actions"><input type="submit"></td>
                                    </tr>
                                <?php  ?>
            </tbody>
    </table>
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

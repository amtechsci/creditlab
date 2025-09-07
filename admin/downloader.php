<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $iss_amt = towfetch(towquery("SELECT SUM(transaction_amount) as triss FROM `transaction_details` WHERE transaction_flow='R4C To Customer'"))['triss'];
        $iss_lc = townum(towquery("SELECT `cllid` as trcou FROM `transaction_details` WHERE transaction_flow='R4C To Customer' GROUP BY `cllid`"));
        
        $rec_amt = towfetch(towquery("SELECT SUM(transaction_amount) as triss FROM `transaction_details` WHERE transaction_flow IN ('part','renew','full')"))['triss'];
        $rec_lc = townum(towquery("SELECT `cllid` as trcou FROM `transaction_details` WHERE transaction_flow IN ('part','renew','full') GROUP BY `cllid`"));
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
                                            <li><span class="bread-blod">Downloader</span>
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
                                <li class="active"><a href="#description">Downloader</a></li>
                                
                            </ul>
                            <div></div><br>
                            <a href="/downloader/disbursal.php" target="_blank"><button  style='margin-left:20px;' class=" btn btn-success">Disbursal</button></a>
                            <a href="/downloader/cleared.php" target="_blank"><button  style='margin-left:20px;' class=" btn btn-success">Cleared</button></a>
                            <a href="/downloader/default.php" target="_blank"><button  style='margin-left:20px;' class=" btn btn-success">Default</button></a>
                            <a href="/downloader/part_payment.php" target="_blank"><button  style='margin-left:20px;' class=" btn btn-success">Part payment</button></a>
                            <a href="/downloader/settlement.php" target="_blank"><button  style='margin-left:20px;' class=" btn btn-success">Settlement</button></a>
                            <a href="/downloader/bs_repayment.php" target="_blank"><button  style='margin-left:20px;' class=" btn btn-success">BS Repayment</button></a>
                            <a href="/downloader/bs_disbursal.php" target="_blank"><button  style='margin-left:20px;' class=" btn btn-success">BS Disbursal</button></a>
                            <a href="/downloader/applied.php" target="_blank"><button  style='margin-left:20px;' class=" btn btn-success">Applied</button></a>
                            <a href="/downloader/recoveryagency.php" target="_blank"><button  style='margin-left:20px;' class=" btn btn-success">recovery agency</button></a>
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

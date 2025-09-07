<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $disb = towfetch(towquery("SELECT SUM(processed_amount) as disb FROM `loan`"))['disb'];
        $cdisb = townum(towquery("SELECT processed_amount FROM `loan`"));
        
        $def = towfetch(towquery("SELECT SUM(processed_amount) as disb FROM `loan` WHERE exhausted_period > 65"))['disb'];
        $cdef = townum(towquery("SELECT processed_amount FROM `loan` WHERE exhausted_period > 65"));
        
        $cle = towfetch(towquery("SELECT SUM(processed_amount) as disb FROM `loan` WHERE status_log='cleared'"))['disb'];
        $ccle = townum(towquery("SELECT processed_amount FROM `loan` WHERE status_log='cleared'"));
        
        $disb = towfetch(towquery("SELECT SUM(processed_amount) as disb FROM `loan`"))['disb'];
        $cdisb = townum(towquery("SELECT processed_amount FROM `loan`"));
        
        $disb = towfetch(towquery("SELECT SUM(processed_amount) as disb FROM `loan`"))['disb'];
        $cdisb = townum(towquery("SELECT processed_amount FROM `loan`"));
        
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
                                            <li><span class="bread-blod">Overview</span>
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
                                <li class="active"><a href="#description">Overview</a></li>
                                
                            </ul>
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
                                        <th>Overview</th>
                                        <th>No. of loans</th>        
                                        <th>Amount (Rs)</th>      
                                    </tr>
        </thead>
        <tbody>
                                    <tr>  
                                        <th>Total Disbursed loans</th>
                                        <td data-title="CID"><?=$cdisb?></td>
                                        <td data-title="Name"><?=$disb?></td>
                                    </tr>
                                    <tr>  
                                        <th>Total Default loans</th>
                                        <td data-title="CID"><?=$cdef?></td>
                                        <td data-title="Name"><?=$def?></td>
                                    </tr>
                                    <tr>  
                                        <th>Total Cleared loans</th>
                                        <td data-title="CID"><?=$cle?></td>
                                        <td data-title="Name"><?=$ccle?></td>
                                    </tr>
                                    <tr>  
                                        <th>Total Active Repeat loans</th>
                                        <td data-title="CID">hjkn</td>
                                        <td data-title="Name">jj</td>
                                    </tr>
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
        </div>
       <?php
       include_once 'foot.php';
       ?>
        
</body>

</html>

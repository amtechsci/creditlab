<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $iss_result = towfetch(towquery("SELECT SUM(transaction_amount) as triss FROM `transaction_details` WHERE transaction_flow='R4C To Customer'"));
        $iss_amt = isset($iss_result['triss']) ? $iss_result['triss'] : 0;
        $iss_lc = townum(towquery("SELECT `cllid` as trcou FROM `transaction_details` WHERE transaction_flow='R4C To Customer' GROUP BY `cllid`"));
        
        $rec_result = towfetch(towquery("SELECT SUM(transaction_amount) as triss FROM `transaction_details` WHERE transaction_flow IN ('part','renew','full')"));
        $rec_amt = isset($rec_result['triss']) ? $rec_result['triss'] : 0;
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
                            
                            <!-- Date Range Selector -->
                            <div style="margin: 20px; padding: 20px; background-color: #f5f5f5; border-radius: 5px;">
                                <h4 style="margin-bottom: 15px;">Select Date Range for Reports</h4>
                                <form id="dateRangeForm" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                    <div>
                                        <label for="from_date" style="display: block; margin-bottom: 5px; font-weight: bold;">From Date:</label>
                                        <input type="date" id="from_date" name="from_date" class="form-control" style="width: 200px;" value="<?php echo isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01'); ?>">
                                    </div>
                                    <div>
                                        <label for="to_date" style="display: block; margin-bottom: 5px; font-weight: bold;">To Date:</label>
                                        <input type="date" id="to_date" name="to_date" class="form-control" style="width: 200px;" value="<?php echo isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d'); ?>">
                                    </div>
                                    <div style="margin-top: 25px;">
                                        <button type="button" onclick="updateDownloadLinks()" class="btn btn-primary">Apply Date Range</button>
                                        <button type="button" onclick="resetDateRange()" class="btn btn-secondary">Reset</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div id="downloadButtons" style="margin-top: 20px;">
                                <a href="/downloader/disbursal.php" target="_blank" class="download-link" data-file="disbursal.php"><button style='margin-left:20px;' class="btn btn-success">Disbursal</button></a>
                                <a href="/downloader/cleared.php" target="_blank" class="download-link" data-file="cleared.php"><button style='margin-left:20px;' class="btn btn-success">Cleared</button></a>
                                <a href="/downloader/default.php" target="_blank" class="download-link" data-file="default.php"><button style='margin-left:20px;' class="btn btn-success">Default</button></a>
                                <a href="/downloader/part_payment.php" target="_blank" class="download-link" data-file="part_payment.php"><button style='margin-left:20px;' class="btn btn-success">Part payment</button></a>
                                <a href="/downloader/settlement.php" target="_blank" class="download-link" data-file="settlement.php"><button style='margin-left:20px;' class="btn btn-success">Settlement</button></a>
                                <a href="/downloader/bs_repayment.php" target="_blank" class="download-link" data-file="bs_repayment.php"><button style='margin-left:20px;' class="btn btn-success">BS Repayment</button></a>
                                <a href="/downloader/bs_disbursal.php" target="_blank" class="download-link" data-file="bs_disbursal.php"><button style='margin-left:20px;' class="btn btn-success">BS Disbursal</button></a>
                                <a href="/downloader/applied.php" target="_blank" class="download-link" data-file="applied.php"><button style='margin-left:20px;' class="btn btn-success">Applied</button></a>
                                <a href="/downloader/recoveryagency.php" target="_blank" class="download-link" data-file="recoveryagency.php"><button style='margin-left:20px;' class="btn btn-success">recovery agency</button></a>
                            </div>
                            
                            <script>
                                function updateDownloadLinks() {
                                    var fromDate = document.getElementById('from_date').value;
                                    var toDate = document.getElementById('to_date').value;
                                    
                                    if (!fromDate || !toDate) {
                                        alert('Please select both From Date and To Date');
                                        return;
                                    }
                                    
                                    if (new Date(fromDate) > new Date(toDate)) {
                                        alert('From Date cannot be greater than To Date');
                                        return;
                                    }
                                    
                                    var links = document.querySelectorAll('.download-link');
                                    links.forEach(function(link) {
                                        var currentHref = link.getAttribute('data-file');
                                        var newHref = '/downloader/' + currentHref + '?from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate);
                                        link.setAttribute('href', newHref);
                                    });
                                    
                                    alert('Date range applied! Click any download button to get reports for the selected date range.');
                                }
                                
                                function resetDateRange() {
                                    document.getElementById('from_date').value = '<?php echo date('Y-m-01'); ?>';
                                    document.getElementById('to_date').value = '<?php echo date('Y-m-d'); ?>';
                                    updateDownloadLinks();
                                }
                                
                                // Initialize links on page load
                                window.onload = function() {
                                    <?php if (isset($_GET['from_date']) && isset($_GET['to_date'])): ?>
                                    updateDownloadLinks();
                                    <?php endif; ?>
                                };
                            </script>
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

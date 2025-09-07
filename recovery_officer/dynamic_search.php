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
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <ul class="breadcome-menu">
                                            <li><a href="../user">Home</a> <span class="bread-slash">/</span>
                                            </li>
                                            <li><span class="bread-blod">Dynamic Search</span>
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
                <div class="row text-center">
                    <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                <div class="analytics-sparkle-line table-mg-t-pro dk-res-t-pro-30">
                    <div class="row">
                    <div class="col-md-8"></div>
                    <div class="col-md-3">
        <input type="text" id="searchtext" style="float:left;height:35px;border-radius:5%;border:1px solid #ddd;" class="form-control" placeholder="Search..."></div><div class="col-md-1"><span><button class="btn btn-primary" onclick="search()">Enter</button></span></div></div><br>

                <div class="table-responsive" id="searchtable">
        <table class="table table-bordered" id="loan_history_datatable">
        <thead class="thead-light">
            <tr>                
                                        <th>RCID</th>        
                                        <th>Name</th>        
                                        <th>Email</th>        
                                        <th>Mobile</th>    
                                        <th>Status</th>    
                                        <th>Actions</th>     
                                    </tr>
        </thead>
        <tbody>
            </tbody>
    </table>
							
    </div>
    </div>
    </div></div>
            </div>
        </div><br>
       <?php
       include_once 'foot.php';
       ?>
<script>
    function search(){
        var dsearchtext = $('#searchtext').val();
        $.post("dsearchtable.php",
            {
              search: dsearchtext
            },
             function(data,status) {
                 $('#searchtable').html(data);
             });
    }
</script>
</body>

</html>

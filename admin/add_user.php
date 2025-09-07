<?php
include_once 'head.php';
if(isset($_POST['submit'])){
    $extract = towrealarray($_POST);
    extract($extract);
    if($emp_type == "account_manager"){
        $rcid = "RC".date('ymdHis');
    $reg_date = date('Y-m-d H:i:s');
    $a = towquery("INSERT INTO `account_manager`(`name`, `email`, `mobile`, `password`, `reg_date`) VALUES ('$name','$email','$mobile','$password','$reg_date')");
        print_r("<script>alert('User Added'); window.location.replace('add_user.php');</script>");
    }elseif($emp_type == "recovery_officer"){
        $rcid = "RC".date('ymdHis');
    $reg_date = date('Y-m-d H:i:s');
    $a = towquery("INSERT INTO `recovery_officer`(`name`, `email`, `mobile`, `password`, `reg_date`) VALUES ('$name','$email','$mobile','$password','$reg_date')");
        print_r("<script>alert('User Added'); window.location.replace('add_user.php');</script>");
    }elseif($emp_type == "verify_user"){
        $rcid = "RC".date('ymdHis');
    $reg_date = date('Y-m-d H:i:s');
    $a = towquery("INSERT INTO `verify_user`(`name`, `email`, `mobile`, `password`, `reg_date`) VALUES ('$name','$email','$mobile','$password','$reg_date')");
        print_r("<script>alert('User Added'); window.location.replace('add_user.php');</script>");
    }else{
        $rcid = "RC".date('ymdHis');
        $a = towquery("INSERT INTO `user`(`rcid`, `name`, `email`, `mobile` `password`, `active`, `verify`, `otp`, `validation`, `reg_date`, `status`, `document_password`, `loan_limit`, `assign_account_manager`, `assign_recovery_officer`, `star_member`) VALUES ('$rcid','$name','$email','$mobile','$password',1,0,1111,'','$reg_date','waiting','pan no password pan#aadhar no password aadhar#aadha2 no password aadha2#salary no password salary#bank no password bank#address no password address#bank2 no password bank2#bank3 no password bank3',10000,1,1,2)");
    }
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
                                            <li><span class="bread-blod">Change password</span>
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
                                <li class="active"><a href="#description">Create Employee</a></li>
                                
                            </ul>
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade active in" id="description">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <div id="dropzone1" class="pro-ad">
                                                    <form method="post" class="dropzone dropzone-custom needsclick add-professors" id="demo1-upload" enctype="multipart/form-data">
                                            
                                                            <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                                                <div>
                                                                    <div class="table-responsive">
                                                                        <form action="" method="post">
                                                                            <select name="emp_type" class="form-control">
                                                                                <option value="account_manager">account manager</option>
                                                                                <option value="recovery_officer">recovery officer</option>
                                                                                <option value="verify_user">Verify User</option>
                                                                                <option value="user">User</option>
                                                                            </select>
                                                                            <input name="name" type="text" class="form-control" placeholder="Name" required>
                                                                            <input name="email" type="email" class="form-control" placeholder="Email" required>
                                                                            <input name="number" type="mobile" class="form-control" placeholder="Mobile" required>
                                                                            <input name="password" type="text" class="form-control" placeholder="Password" required>
                                                                            <input name="submit" type="submit" class="btn btn-success">
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <table class="table table-bordered" style="margin-top:25px;">
                                                <tr>
                                                    <th>emp_type</th>
                                                    <th>name</th>
                                                    <th>email</th>
                                                    <th>number</th>
                                                    <th>password</th>
                                                </tr>
                                                <?php
                                                $a = towquery("SELECT * FROM `verify_user`");
                                                $b = towquery("SELECT * FROM `account_manager`");
                                                $c = towquery("SELECT * FROM `recovery_officer`");
                                                while($aa = towfetch($a)){ ?>
                                                <tr>
                                                    <th><?php if($aa['type'] == 1){echo "Verify user";}else{echo "NBFC";}?></th>
                                                    <th><?=$aa['name'];?></th>
                                                    <th><?=$aa['email'];?></th>
                                                    <th><?=$aa['number'];?></th>
                                                    <th><?=$aa['password'];?></th>
                                                    <th><a href="deleteuser.php?id=<?=$aa['id'];?>&type=verify_user">Delete</a></th>
                                                </tr>
                                                <?php } while($bb = towfetch($b)){ ?>
                                                <tr>
                                                    <th>Account manager</th>
                                                    <th><?=$bb['name'];?></th>
                                                    <th><?=$bb['email'];?></th>
                                                    <th><?=$bb['number'];?></th>
                                                    <th><?=$bb['password'];?></th>
                                                    <th><a href="deleteuser.php?id=<?=$bb['id'];?>&type=account_manager">Delete</a></th>
                                                </tr>
                                                <?php } while($cc = towfetch($c)){ ?>
                                                <tr>
                                                    <th>Recovery officer</th>
                                                    <th><?=$cc['name'];?></th>
                                                    <th><?=$cc['email'];?></th>
                                                    <th><?=$cc['number'];?></th>
                                                    <th><?=$cc['password'];?></th>
                                                    <th><a href="deleteuser.php?id=<?=$cc['id'];?>&type=recovery_officer">Delete</a></th>
                                                </tr>
                                                <?php } ?>
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
<?php
if(isset($_POST['pan'])){
    $extract = towrealarray($_POST);
    extract($extract);
    if($user_mobile == $altmobile){
        print_r("<script>alert('please enter mobile number of any family person if you don’t have alternate number'); window.location.replace('index.php');</script>");
    }else{
    (towquery("UPDATE `user` SET `name`='$name',`altmobile`=$altmobile,`altemail`='$altemail',`dob`='$dob',`pan`='".strtoupper($pan)."',`company`='$company',`designation`='$designation',`department`='$department',`state`='$state',`marital_status`='$marital_status',`experience`='$experience' WHERE email='$user'") and
    print_r("<script> window.location.replace('index.php');</script>")) or print_r("<script>alert('Phone No. already register'); window.location.replace('index.php');</script>");
}}
    ?>
    <div style="background-image: url(img/loan.jpg);
  background-color: #cccccc;
    height:100%;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  position: relative;">
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
                                            
                                            <li>Your creditlab.in ID: <span class="bread-blod"><?=$user_rcid;?></span>
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
        
        <div class="container chat" style="background:#fff;">
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in</h4>
        </div>
        <div class="row">
            <div class="breadcome-list">
            <center><h2>Your loan is pre approved with creditlab.in;<br> Our executive will get back to you soon
            </h2></center>
            </div>
        </div><br><br><br><br><br><br>
<?php
include_once 'foot.php';
?>

</body>
</html>
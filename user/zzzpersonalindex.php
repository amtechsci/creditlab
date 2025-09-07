<?php
if(isset($_POST['salary'])){
    $extract = towrealarray($_POST);
    extract($extract);
    if(($salary >= 20000) and ($salarystatus == "Salaried") and ($get_salary == "bank transfer")){
    (towquery("UPDATE `user` SET `mobile`=$mobile,`salary`='$salary',`salarystatus`='$salarystatus',`verify`=1,`status`='Approve',`get_salary`='$get_salary' WHERE email='$user'") and
    print_r("<script>alert('congratulations! your membership has been approved by creditlab.in'); window.location.replace('index.php');</script>")) or print_r("<script>alert(''); window.location.replace('index.php');</script>");
    }elseif(($salary < 20000) and ($salarystatus == "Salaried")){
        towquery("UPDATE `user` SET `verify`=3,`status`='Hold' WHERE email='$user'") and
    print_r("<script>alert('Your membership is on hold. You can reapply after 90 days'); window.location.replace('index.php');</script>");
    }else{
        towquery("UPDATE `user` SET `verify`=4,`status`='Hold' WHERE email='$user'") and
    print_r("<script>alert('sorry you are not eligible for loan in creditlab.in'); window.location.replace('index.php');</script>");
    }
}
    ?>
            <!-- Mobile Menu end -->
            
            <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <ul >
                                            <li><h4><?=$user_name?></h4>
                                            </li>
                                            <li><span class="bread-blod"><?=$user_mobile;?></span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
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
        <div style="background-image: url(img/loan.jpg);
  background-color: #cccccc;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  position: relative;">
        <div class="container chat" style="background:#fff;">
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in</h4>
        </div>
        <br>
        <div class="analytics-sparkle-area" >
            <div class="container-fluid">
                <center><h4>Get started by Creating your profile</h4></center>
                 <div class="product-tab-list tab-pane fade active in" id="Personal">
                                    <div class="row">

                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <form id="add-department" action="" method="post" class="add-department">
                                                    <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            
                                                            
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                                                <select name="salary"  class="form-control" required>
<option value="" selected>Monthly Net Salary</option>
<option value="10000" <?php #if($user_salary == "10000"){echo "selected";}?>>Less than Rs 20,000</option>
<option value="21000" <?php #if($user_salary == "21000"){echo "selected";}?>>More than Rs 20,000</option>
<option value="20000" <?php #if($user_salary == "20000"){echo "selected";}?>>Exactly Rs 20,000</option>
                    </select>
                                                            </div>
                                                            <div class="form-group">
                    <select name="salarystatus"  class="form-control" required>
<option value="">Type of emplyment</option>
<option value="Salaried" <?php #if($user_salarystatus == "Salaried"){echo "selected";}?>>Salaried</option>
<option value="Self-Employed" <?php #if($user_salarystatus == "Self-Employed"){echo "selected";}?>>Self-Employed</option>
<option value="Student" <?php #if($user_salarystatus == "Student"){echo "selected";}?>>Student</option>
<option value="Retired" <?php #if($user_salarystatus == "Retired"){echo "selected";}?>>Retired</option>
<option value="Home maker" <?php #if($user_salarystatus == "Home maker"){echo "selected";}?>>Home maker</option>
                    </select>
                                </div>
                                <div class="form-group">
                    <select name="get_salary"  class="form-control" required>
<option value="">Salary payment mode?</option>
<option value="bank transfer" <?php #if($user_get_salary == "bank transfer"){echo "selected";}?>>bank transfer</option>
<option value="cash" <?php #if($user_get_salary == "cash"){echo "selected";}?>>cash</option>
<option value="cheque" <?php #if($user_get_salary == "cheque"){echo "selected";}?>>cheque</option>
                    </select>
                                </div>
                                <div class="form-group">
                   <input type="number" name="mobile" class="form-control" placeholder="Mobile">
                                </div>
                                                            
                                                        </div>
                                                        <div class="col-lg-12">
                                                            <div class="payment-adress">
                                                                <p id="mess"></p>
                                                                <button type="submit" class="btn btn-primary waves-effect waves-light" id="presub" style="margin-bottom:20px;">Submit</button><br>
                                                                
                                                            </div>
                                                        </div>
                                                        
                                                        </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                                
            </div>
            
        <br>
                                                        <br>
                                                        <br>
        </div>
        
        </div>
<?php
include_once 'foot.php';
?>
<script>
   /*     $( "#panNumber" ).keyup(function() {
            var panVal = $('#panNumber').val();
var regpan = /^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/;

if(regpan.test(panVal)){
   $('#panNumber').css("border-color", "green");
} else {
   $('#panNumber').css("border-color", "red");
}
});*/
        </script>
        
<script>
    /*    var email,altemail;
        $( "#altemail" ).keyup(function() {
        email = $('#email').val();
        altemail = $('#altemail').val();
        $("#presub").removeAttr("disabled");
if(email != altemail){
   $('#altemail').css("border-color", "green");
   $("#messs").html("");
} else {
   $('#altemail').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#messs").html("Email and alternate Email should not be same");
}
});*/
</script><script>
        /*var email,altemail;
        $( "#email" ).keyup(function() {
        email = $('#email').val();
        altemail = $('#altemail').val();
        $("#presub").removeAttr("disabled");
if(email != altemail){
   $('#altemail').css("border-color", "green");
   $("#messs").html("");
} else {
   $('#altemail').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#messs").html("Email and alternate Email should not be same");
}
});*/
</script>

<script>
   /*     var mobile,altmobile;
        $( "#altmobile" ).keyup(function() {
        mobile = $('#mobile').val();
        altmobile = $('#altmobile').val();
        $("#presub").removeAttr("disabled");
if(mobile != altmobile){
   $('#altmobile').css("border-color", "green");
   $("#mess").html("");
} else {
   $('#altmobile').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#mess").html("please enter mobile number of any family person if you don’t have alternate number");
}
});*/
</script><script>
        /*var mobile,altmobile;
        $( "#mobile" ).keyup(function() {
        mobile = $('#mobile').val();
        altmobile = $('#altmobile').val();
        $("#presub").removeAttr("disabled");
if(mobile != altmobile){
   $('#altmobile').css("border-color", "green");
   $("#mess").html("");
} else {
   $('#altmobile').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#mess").html("please enter mobile number of any family person if you don’t have alternate number");
}
});*/
</script>
</body>

</html>
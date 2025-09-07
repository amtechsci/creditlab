<?php
if(isset($_POST['mobile'])){
    $extract = towrealarray($_POST);
    extract($extract);
    (towquery("") and
    print_r("<script>alert('Updated'); window.location.replace('index.php');</script>")) or print_r("<script>alert('Not Updated'); window.location.replace('index.php');</script>");
}
    ?>
            <!-- Mobile Menu end -->
            <div style="background-image: url(img/loan.jpg);
  background-color: #cccccc;
    height:500px;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  position: relative;">
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
        
        <div class="container chat" style="background:#fff;">
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in</h4>
        </div>
        <br>
        <div class="analytics-sparkle-area" >
            <div class="container-fluid">
                <center><h4>Get started by Creating your profile</h4></center>
                <?php include 'profile/personal.php'; ?>
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
        $( "#panNumber" ).keyup(function() {
            var panVal = $('#panNumber').val();
var regpan = /^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/;

if(regpan.test(panVal)){
   $('#panNumber').css("border-color", "green");
} else {
   $('#panNumber').css("border-color", "red");
}
});
        </script>
        
<script>
        var email,altemail;
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
});
</script><script>
        var email,altemail;
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
});
</script>

<script>
        var mobile,altmobile;
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
});
</script><script>
        var mobile,altmobile;
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
});
</script>
</body>

</html>
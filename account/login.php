<?php
include '../db.php';
if(isset($_POST['email'])){
    extract(towrealarray2($_POST));
function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
$userip = get_client_ip();

if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
   $userbrowser = 'Internet explorer';
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) //For Supporting IE 11
    $userbrowser = 'Internet explorer';
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE)
   $userbrowser = 'Mozilla Firefox';
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE)
   $userbrowser = 'Google Chrome';
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE)
   $userbrowser = "Opera Mini";
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE)
   $userbrowser = "Opera";
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE)
   $userbrowser = "Safari";
 else
   $userbrowser = 'Unknown';
   $login_time = date('Y-m-d H:i:s');
    $result=towquery("SELECT * FROM user WHERE email ='$email' AND password='$password'");
    if (townum($result)==1) {
   $aa = towfetch($result);
        $id = $aa['id'];
        if($aa['active'] == 1){
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES ($id,'$userbrowser','$userip','$login_time')");
        $_SESSION['user'] = $email;
        setcookie('user', $email, time() + (86400 * 30), "/");
        header("location:../../user/");
    }elseif($aa['active'] == 2){
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES ($id,'$userbrowser','$userip','$login_time')");
        $_SESSION['admin'] = $email;
        setcookie('admin', $email, time() + (86400 * 30), "/");
        header("location:../../admin/");
    }
    }else{
        $sqll="SELECT * FROM verify_user WHERE email ='$email' AND password='$password'";
        $result=towquery($sqll);
    if(townum($result)==1){
        // print_r(11);exit;
        $aa = towfetch($result);
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES (700$id,'$userbrowser','$userip','$login_time')");
        $_SESSION['verify_user'] = $email;
        header("location:/verify_user/");
        exit;
    }else{
        // print_r(22);exit;
        $sqll="SELECT * FROM account_manager WHERE email ='$email' AND password='$password'";
    $result=towquery($sqll);
    if(townum($result)==1){
        // print_r($_POST);exit;
        $aa = towfetch($result);
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES (100$id,'$userbrowser','$userip','$login_time')");
        $_SESSION['account_manager'] = $email;
        header("location:/account_manager/");
        exit;
    }else{
    $sqll="SELECT * FROM recovery_officer WHERE email ='$email' AND password='$password'";
    // print_r($sqll);exit;
    $result=towquery($sqll);
    if(townum($result)==1){
        $aa = towfetch($result);
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES (200$id,'$userbrowser','$userip','$login_time')");
        $_SESSION['recovery_officer'] = $email;
        header("location:/recovery_officer/");
        exit;
    }}}
}
}
include_once 'head.php';
include_once '../head2.php';

if (isset($user)) {
    print_r("<script>window.location.replace('../user');</script>");
}else{ ?>
<style>
.footer-style-area.pt-100, .top-btn, .navbar-toggler{
    display:none;
}
</style>
<body class="dashboard-page">
		<div class="main-grid">
			<div class="agile-grids">	
				<div class="grids">
					
					<div class="forms-grids" style="margin-top:10%;">
						<div class="forms3">
						<div class="kagile-validation kls-validation">
						    <div class="panel panel-widget agile-validation register-form">
								<div class="validation-grids widget-shadow" data-example-id="basic-forms" style="background:#fff;"> 
									<div class="input-info">
										<h1 class="ath1">Sign in to start your session</h1>
									</div>
								</div>
							</div>
							<div class="panel panel-widget agile-validation">
								<div class="validation-grids validation-grids-right login-form">
									<div class="widget-shadow login-form-shadow" data-example-id="basic-forms">
										<div class="form-body form-body-info mb-5">
											<form data-toggle="validator" action="" method="post">
												<div class="form-group has-feedback">
													<input type="email" class="form-control" name="email" placeholder="Enter Your Email" style="height: 40px;font-size: 2rem;" required title="Please enter valid email">
												</div>
												<div class="form-group has-feedback">
													<input type="password" class="form-control" name="password" placeholder="Enter Your Password" style="height: 40px;font-size: 2rem;" required>
												</div>
												<!--<div class="row">-->
												<!--<div class="col-md-8">-->
												<!--<div class="form-group has-feedback">-->
												<!--	<input type="number" class="form-control" name="email" placeholder="Enter Your Moblie Number" style="height: 40px;font-size: 2rem;" required>-->
												<!--</div>-->
												<!--</div>-->
												<!--<div class="col-md-4">-->
												<!--<div class="form-group has-feedback">-->
												<!--	<button class="btn btn-success" style="font-size:2rem">Send OTP</button>-->
												<!--</div>-->
												<!--</div>-->
												<!--</div>-->
												<!--<div class="form-group has-feedback">-->
												<!--	<input type="text" class="form-control" name="otp" placeholder="Enter OTP" style="height: 40px;font-size: 2rem;" required>-->
												<!--</div>-->
												
											
												<div class="bottom">
													<div class="form-group">
														<button type="submit" name="submit" class="btn btn-primary ">Submit</button>
														<br>
													</div>
													<div class="clearfix"> </div>
												</div>
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="clear"> </div>
						</div>
					</div>
				</div>
				<!-- //validation -->
			</div>
		</div>
		<!-- footer -->
		<div class="footer">
			<!--<p>Copyright ©2020 rush4cash.in All rights reserved | Designed by Digital supporter</p>-->
		</div>
		<!-- //footer -->
		<!-- input-forms -->
		
		
		<script type="text/javascript" src="js/valida.2.1.6.min.js"></script>
		<script type="text/javascript" >

			$(document).ready(function() {

				// show Valida's version.
				$('#version').valida( 'version' );

				// Exemple 1
				$('.valida').valida();

				

        // setup the partial validation
				$('#partial-1').on('click', function( ev ) {
					ev.preventDefault();
					$('#res-1').click(); // clear form error msgs
					$('form').valida('partial', '#field-1'); // validate only field-1
					$('form').valida('partial', '#field-1-3'); // validate only field-1-3
				});

			});

		</script>
		<!-- //input-forms -->
		<!--validator js-->
		<script src="js/validator.min.js"></script>
		<!--//validator js-->
		<script>
function myFunction() {
  var x = document.getElementById("myTopnav");
  if (x.className === "topnav") {
    x.className += " responsive";
  } else {
    x.className = "topnav";
  }
}
</script>

</body>
</html>
<?php 
include '../foot.php';
} ?>
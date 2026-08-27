<?php
include '../db.php';
require_once __DIR__ . '/../lib/auth.php';
$login_next = creditlab_safe_internal_redirect((string) ($_GET['next'] ?? ''), '');
if(isset($_POST['email'])){
    extract(towrealarray2($_POST));
    $posted_next = creditlab_safe_internal_redirect((string) ($_POST['next'] ?? ''), '');
    if ($posted_next !== '') {
        $login_next = $posted_next;
    }
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
    $result=towquery("SELECT * FROM user WHERE email ='$email' LIMIT 1");
    if ($result && townum($result)==1) {
   $aa = towfetch($result);
        if (creditlab_verify_password($password, $aa['password'])) {
        $id = $aa['id'];
        if($aa['active'] == 1){
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES ($id,'$userbrowser','$userip','$login_time')");
        creditlab_establish_login('user', $email);
        header("location:../../user/");
        exit;
    }elseif($aa['active'] == 2){
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES ($id,'$userbrowser','$userip','$login_time')");
        creditlab_establish_login('admin', $email);
        header('Location: ' . ($login_next !== '' ? $login_next : '../../admin/'));
        exit;
    }
        }
    }
        $sqll="SELECT * FROM verify_user WHERE email ='$email' LIMIT 1";
        $result=towquery($sqll);
    if($result && townum($result)==1){
        // print_r(11);exit;
        $aa = towfetch($result);
        if (creditlab_verify_password($password, $aa['password'])) {
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES (700$id,'$userbrowser','$userip','$login_time')");
        creditlab_establish_login('verify_user', $email);
        header("location:/verify_user/");
        exit;
        }
    }
        $sqll="SELECT * FROM account_manager WHERE email ='$email' LIMIT 1";
    $result=towquery($sqll);
    if($result && townum($result)==1){
        $aa = towfetch($result);
        if (creditlab_verify_password($password, $aa['password'])) {
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES (100$id,'$userbrowser','$userip','$login_time')");
        creditlab_establish_login('account_manager', $email);
        header("location:/account_manager/");
        exit;
        }
    }
    $sqll="SELECT * FROM recovery_officer WHERE email ='$email' LIMIT 1";
    $result=towquery($sqll);
    if($result && townum($result)==1){
        $aa = towfetch($result);
        if (creditlab_verify_password($password, $aa['password'])) {
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES (200$id,'$userbrowser','$userip','$login_time')");
        creditlab_establish_login('recovery_officer', $email);
        header("location:/recovery_officer/");
        exit;
        }
    }
    $sqll="SELECT agency_admin.*, agency.name AS agency_name FROM agency_admin INNER JOIN agency ON agency.id = agency_admin.agency_id WHERE agency_admin.email ='$email' AND agency_admin.active=1 AND agency.active=1 LIMIT 1";
    $result=towquery($sqll);
    if($result && townum($result)==1){
        $aa = towfetch($result);
        if (creditlab_verify_password($password, $aa['password'])) {
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES (300$id,'$userbrowser','$userip','$login_time')");
        creditlab_establish_login('agency_admin', $email);
        header("location:/agency_admin/");
        exit;
        }
    }
}
include_once 'head.php';
include_once '../head2.php';

if (creditlab_is_staff_logged_in() && $login_next !== '') {
    header('Location: ' . $login_next);
    exit;
}

if (isset($user) && !creditlab_is_staff_logged_in()) {
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
												<?php if ($login_next !== ''): ?>
												<input type="hidden" name="next" value="<?= htmlspecialchars($login_next, ENT_QUOTES) ?>">
												<?php endif; ?>
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
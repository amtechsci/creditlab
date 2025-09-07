<?php
include_once '../db.php';
if (isset($_SESSION['user'])) {
    header('Location:index.php');
}elseif(isset($_COOKIE['user'])){
    header('Location:index.php');
}else{
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
   $mobile_handset_uid = substr(hash('sha256', (isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'').'|'.$userip.'|'.session_id()), 0, 32);

if (isset($_POST['submit'])) {
    towreal(extract($_POST));
    $sqll="SELECT * FROM user WHERE mobile ='$mobile' AND otp='$otp'";
    $result=towquery($sqll);
    if (townum($result)==1) {
        towquery("UPDATE `user` SET `active`=1 WHERE `mobile`='$mobile'");
        $aa = towfetch($result);
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`,`latitude`,`longitude`, `mobile_handset_uid`) VALUES ($id,'$userbrowser','$userip','$login_time','$lat','$long','$mobile_handset_uid')");
        $_SESSION['user'] = $mobile;
        setcookie('user', $mobile, time() + (86400 * 30), "/");
        header("location:../user/");
    }
    else{
        echo "<script>alert('not match')</script>";
    }
}}
if(isset($_GET['id'])){
    $mobile = towreal($_GET['id']);
}
?>
<?php
include_once 'head.php';
include_once '../head2.php';
?>
<body class="dashboard-page">
		<div class="main-grid">
			<div class="agile-grids">	
				<!-- validation -->
				<div class="grids">
					
					<div class="forms-grids" style="margin-top:10%;">
						<div class="forms3">
						<div class="kagile-validation kls-validation">
							<div class="panel panel-widget agile-validation">
								<div class="validation-grids validation-grids-right" style="padding: 2em 2em;">
									<div class="widget-shadow login-form-shadow" data-example-id="basic-forms"> 
										<div class="input-info">
											<h3>Verify Mobile number</h3>
											<p>
											    Enter the 4 digit OTP received on your mobile number (<?=$mobile?>)  [<a href="../account">Change ?</a>]
											</p>
										</div>
										<div class="form-body form-body-info">
											<form data-toggle="validator" action="" method="post">
												<input type="hidden" class="form-control" name="mobile" placeholder="Enter same mail ID used for registration." data-error="Bruh, that email address is invalid" required="" value="<?=$mobile?>">
												<input type="hidden" name="lat" id="lat" value="">
                                                <input type="hidden" name="long" id="long" value="">
												<div class="form-group">
												<input type="number" class="form-control" name="otp" placeholder="Enter the OTP here" required="" style="height: 40px;font-size: 2rem;">
												</div>
												<div class="form-group"><span id="demo"></span>
												</div>
												<div class="form-group">
<p class="atp3">(!) Creditlab agents will never ask you to transfer money (or) never ask you OTPs for any purposes.<br>
*    No hidden charges<br>
*   “ 0 “ zero prepayment charges<br>
*   100% Transparent to our Customers</p>
												</div>
												
												<div class="bottom">
													<div class="form-group">
														<button type="submit" name="submit" class="btn btn-primary disabled">Enter</button>
													</div>
													<div class="form-group">
													</div>
													<div class="clearfix"> </div>
												</div>
											</form>
											<br>
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
		</div>
		<span id="demo2"></span>
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
<?php include '../foot.php';?>
<script>
var countDownDate = new Date("<?=date('M d, Y H:i:s', strtotime("+30 sec"));?>").getTime();
var x = setInterval(function() {
var now = new Date().getTime();
var distance = countDownDate - now;
  var days = Math.floor(distance / (1000 * 60 * 60 * 24));
  var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  var seconds = Math.floor((distance % (1000 * 60)) / 1000);
  document.getElementById("demo").innerHTML = minutes + "m " + seconds + "s ";
  if (distance < 0) {
    clearInterval(x);
    document.getElementById("demo2").innerHTML = '<form action="/user/register.php" method="post" id="resendaa"><input type="hidden" name="mobile" value="<?=$mobile?>" id="mobile"></form>';
    document.getElementById("demo").innerHTML = '<a href="#" onClick="resend()">Resend ?</a>';
  }
}, 1000);
</script>
<script>
function resend(){
    document.getElementById("resendaa").submit();
}
if ("geolocation" in navigator) {
  navigator.geolocation.getCurrentPosition(function(position) {
    $('#lat').val(position.coords.latitude);
    $('#long').val(position.coords.longitude);
    // $('#al').attr('disabled',false);
  }, function(error) {
    if (error.code == error.PERMISSION_DENIED) {
    //   $('#locationModal').modal('show');
    //   $('#al').attr('disabled',true);
    }
  });
} else {
  console.log("Geolocation is not supported by this browser.");
}
</script>
</body>
</html>
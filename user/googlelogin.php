<?php
require '../account/home/google-api/vendor/autoload.php';
$client = new Google_Client();
$client->setClientId('733391350624-89eftho24hba33a4g2jpraepek7ee43j.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://creditlab.in/user/index.php');
$client->addScope("email");
$client->addScope("profile");
#$client->addScope("https://www.googleapis.com/auth/user.gender.read");
#$client->addScope("https://www.googleapis.com/auth/user.phonenumbers.read");
?>
    <style>#salabox{display:none;}#selfbox{display:none;}</style>
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
            <?php $wa_num = towfetch(towquery("SELECT * FROM `whatsapp_no` WHERE `page_id`=1"))['wa_phone'];?>
            <h4 style="padding:10px;">Contact us on whatsapp<a href="https://api.whatsapp.com/send?phone=91<?=$wa_num?> &text=CLID : <?=$user_rcid?> I need Help in ..." class="btn btn-default" style="background:#dfdfdf;">
                <img src="/ws.svg" style="width:30px;"> Whatsapp</a>
            </h4>
        </div>
        <div class="container">
          <div class="progress">
            <div class="progress-bar" role="progressbar" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100" style="width:85%">
              85%
            </div>
          </div>
        </div>
        <br>
        <div class="analytics-sparkle-area">
            <div class="container-fluid">
                <div style="background :white; padding:15px;"><h4>
                    <?php
                    if(isset($_GET['code'])){
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    // print_r($token);
    $client->setAccessToken($token['access_token']);
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    $name = $google_account_info['name'];
    $email= $google_account_info['email'];
    towquery("UPDATE `user` SET `email`='$email',`name`='$name' WHERE mobile='$user'"); ?>
    <script>
$(function () {
  setTimeout(function() {
    window.location.replace("../user");
  }, 2000);
});
</script>
<?php exit;} ?>
*Please click the Google Sign in button for one time registration.<br><br>
                    <a href="<?php echo $client->createAuthUrl(); ?>"><img src="../loginx.png" style="width:150px;"></a>
                    <br><br>*This would help you to get alerts/NOC/loan agreements.</h4></div>
</div>
</div>

        <br>
        <br>
        <br>
        </div>
<?php
include_once 'foot.php';
?>
<script>
// var x = document.getElementById("demo");
// 	function getLocation() {
// 	  	if (navigator.geolocation) {
// 	    	navigator.geolocation.getCurrentPosition(showPosition);
// 	  	} else { 
// 	    	x.innerHTML = "Geolocation is not supported by this browser.";
// 	  	}
// 	}
// 	function showPosition(position) {
// 		console.log(position);
// 	  	x.innerHTML = "Latitude: " + position.coords.latitude + "<br>Longitude: " + position.coords.longitude;
// 	} getLocation();
</script>

</body>

</html>
<?php
if(isset($_POST['name'])){
    $extract = towrealarray($_POST);
    extract($extract);
    // 1965-01-01
    // echo strtotime('1965-01-01');echo '<br>';
    // echo strtotime($dob);exit;
    if(strtotime('1965-01-01') <= strtotime($dob)){
    $pan = strtoupper($pan);
    (towquery("UPDATE `user` SET `pan_name`='$name',`dob`='$dob',`pan`='$pan',`pincode`='$pincode',`latlong`='$latlong' WHERE mobile='$user'") and
    print_r("<script>window.location.replace('index.php');</script>")) or print_r("<script>alert(''); window.location.replace('index.php');</script>");
    }else{
        towquery("UPDATE `user` SET `verify`=4,`status`='Hold' WHERE mobile='$user'") and
    print_r("<script>alert('sorry you are not eligible for loan in creditlab.in'); window.location.replace('index.php');</script>");
    }
}
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
            <div class="progress-bar" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width:20%">
              20%
            </div>
          </div>
        </div>
        <br>
        <div class="analytics-sparkle-area">
            <div class="container-fluid">
                <div style="background :white; padding:15px;"><h4>"CreditLab" operates as a platform that connects borrowers to RBI-registered NBFCs for loan transactions, with all applications being thoroughly verified, approved and sanctioned by these financial institutions.</h4></div><br>
                <div class="product-tab-list tab-pane fade active in" id="ADDITIONAL">
                      <form action="" method="post">
                        <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">  </div>
<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
<center><h4>Your profile is approved for instant loan</h4></center>
<div class="form-group">
    <lable><b>Full name as per PAN Card</b></lable>
<input type="text" name="name" placeholder="First and Last name" class="form-control col-xs-12 col-sm-12 pull-left">
</div>
<div class="form-group">
<input type="hidden" name="latlong" value="no" id="latlong">
<input type="text" name="dob" id="dob" onclick="document.getElementById('dob').type = 'date';" placeholder="Date of Birth" class="form-control col-xs-12 col-sm-12 pull-left" required>
</div>
<div class="form-group">
<input type="text" name="pan" placeholder="Pan Number" id="panNumber" class="form-control col-xs-12 col-sm-12 pull-left" required>
<span id="errormsgPan" style="color:red;display:block"></span>
<b>(subject to verification)</b>
</div>
<div class="form-group">
<input type="number" name="pincode" placeholder="Pin code of residence" class="form-control col-xs-12 col-sm-12 pull-left" min="100000" max="999999" required>
</div>
<div class="form-group" style="background:#fff;padding:15px;">
<input type="checkbox" name="che" class="col-xs-12 col-sm-12 pull-left" required><span style="font-size:11px;">I hereby appoint the lenders associated with Creditlab.in as my authorised representative to receive my credit information from CIBIL / EXPERIAN / EQUIFAX & CRIF HIGH MARK (bureau).<details>
  <summary>View</summary>
  <p>I hereby unconditionally consent to and instruct bureau to provide my credit information to the lenders associated with Creditlab.in on a month to month basis as per their requirement. I understand that I shall have the option to opt out/unsubscribe from the service.
By submitting this form, you hereby authorize Creditlab.in/lender to do all of the following in connection with providing you the Services:
1.Verify your identity and share with our Credit bureaus required personal identifiable information about you;
2.Request and receive your Credit report, and credit score from our Credit bureaus, including but not limited to a copy of your consumer credit report and score, at any time for so long as you have not opted out or unsubscribed from this service;
3.Share your details with NBFC partners in order to assist you to rectify and remove negative observations from your credit information report and increase your chances of loan approval in future;
4.To provide you with customized recommendations and personalized offers of the products and services of Creditlab.in and/or its business partners/ affiliates;
5.To send you information / personalized offers via email, text, call or online display or other means of delivery in Creditlab.in's reasonable sole discretion.
6.Retain a copy of your credit information, along with the other information you have given us access to under this Authorization, for use in accordance with Credit Score Terms of Use, Terms of Use and Privacy Policy.

Your Personal Information is 100% secured with us. We do not share your data with any third party.</p>
</details>
</span>
</div>
</div>
</div>
<center><p style="color:red" id="check"></p><button class="btn btn-success" id="presub">Submit</button></center>
</form>
</div>
</div>
</div>

        <br>
        <br>
        <br>
        </div>
<?php
include_once 'foot.php';
?>
<script>var x = document.getElementById("latlong");
	function getLocation() {
	  	if (navigator.geolocation) {
	    	navigator.geolocation.getCurrentPosition(showPosition);
	  	} else { 
	    	x.value = "Geolocation is not supported by this browser.";
	  	}
	}
	function showPosition(position) {
	  	x.value = position.coords.latitude + "," + position.coords.longitude;
	} getLocation(); </script>
<script>
        $( "#panNumber" ).keyup(function() {
            var panVal = $('#panNumber').val();
var regpan = /^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/;

if(regpan.test(panVal)){
   $('#panNumber').css("border-color", "green");
   $("#presub").removeAttr("disabled");
   
document.getElementById("errormsgPan").innerHTML = " ";
} else {
   $('#panNumber').css("border-color", "red");
   $("#presub").attr("disabled",true);
   
document.getElementById("errormsgPan").innerHTML = "enter a valid pan number";
}
});
        </script>
</body>

</html>
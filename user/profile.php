<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/s3_aws_sdk.php';
if(isset($_POST['mobile'])){
    $extract = towrealarray($_POST);     extract($extract);
    towquery("UPDATE `user` SET `mobile`=$mobile,`altmobile`=$altmobile,`state`='$state',`altemail`='$altemail',`dob`='$dob',`pan`='$pan',`salary`='$salary',`salarystatus`='$salarystatus',`present_address`='$present_address',`permanent_address`='$permanent_address' WHERE email='$user'") and print_r("<script>alert('Your data is successfully updated'); window.location.replace('profile.php');</script>");
}
if(isset($_POST['graduation_year'])){
    $extract = towrealarray($_POST);     extract($extract);
    if($freq_aoo){
    $freq_app = implode(" ",$freq_app);
    }else{ $freq_app = "none";}
    towquery("UPDATE `user` SET `graduation_year`='$graduation_year',`marital_status`='$marital_status',`college_name`='$college_name',`freq_app`='$freq_app',`experience`='$experience',`residence_type`='$residence_type',`credit_card`='$credit_card' WHERE email='$user'");
    print_r("<script>alert('Your data is successfully updated'); window.location.replace('profile.php');</script>");
}
if(isset($_POST['company'])){
    $extract = towrealarray($_POST);     extract($extract);
    towquery("UPDATE `user` SET `company`='$company',`designation`='$designation',`office_number`=$office_number,`department`='$department',`annual_income`='$annual_income',`office_pincode`='$office_pincode',`office_address_line1`='$office_address_line1', `office_address_line2`='$office_address_line2' WHERE email='$user'");
    print_r("<script>alert('Your data is successfully updated'); window.location.replace('profile.php');</script>");
}
if(isset($_POST['document'])){
    print_r(1);
    if(!empty($_FILES["conpanydocument"]["name"])){
    $file_type = strtolower(end(explode('.',$_FILES['conpanydocument']['name'])));
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
    $conpanydocument = $_FILES["conpanydocument"]["name"];
    $conpanydocument = explode(".",$conpanydocument);
    $conpanydocument = end($conpanydocument);
    $conpanydocument = $user.'conpany'.date('YmdHis').'.'.$conpanydocument;
    list($success, $result) = s3_upload_file($_FILES["conpanydocument"]["tmp_name"], $conpanydocument, 'application/octet-stream');
    if (!$success) $conpanydocument = "no";
    }$conpanydocument = "no";}else{$conpanydocument = "no";}
    print_r(2);
    if(!empty($_FILES['personaldocument']['name'])){
    $personaldocument = array();
    $i = 0;
    while($i < 2){
    $file_type = strtolower(end(explode('.',$_FILES['personaldocument']['name'][$i])));
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
        
        $a = $_FILES['personaldocument']['name'][$i];
$ext = explode(".",$a);
$ext = end($ext);
$new = date('ymdhis');
$personaldocument[$i] = $user.'personal'.$new.$i.'.'.$ext;
list($success, $result) = s3_upload_file($_FILES['personaldocument']['tmp_name'][$i], $personaldocument[$i], 'application/octet-stream');
if (!$success) $personaldocument[$i] = "no";
        $i++;
        }else{$personaldocument[$i] = "no"; $i++;}
    }
    $personaldocument = implode('#',$personaldocument);
    }else{$personaldocument = "no";}
    print_r(3);
    
    if(!empty($_FILES["salarydocument"]["name"])){
    $file_type = strtolower(end(explode('.',$_FILES['salarydocument']['name'])));
    $allowed = array("pdf" , "PDF");
    if(in_array($file_type, $allowed)) {
    $salarydocument = $_FILES["salarydocument"]["name"];
    $salarydocument = explode(".",$salarydocument);
    $salarydocument = end($salarydocument);
    $salarydocument = $user.'salary'.date('YmdHis').'.'.$salarydocument;
    list($success, $result) = s3_upload_file($_FILES["salarydocument"]["tmp_name"], $salarydocument, 'application/octet-stream');
    if (!$success) $salarydocument = "no";
    }else{$salarydocument = "no";}
    }else{$salarydocument = "no";}
    print_r(4);
    if(!empty($_FILES['bankdocument']['name'])){
    $bankdocument = array();
    $i = 0;
    while($i < 3){
    $file_type = strtolower(end(explode('.',$_FILES['bankdocument']['type'][$i])));
    $allowed = array("PDF", "pdf");
    if(in_array($file_type, $allowed)) {
        
        $a = $_FILES['bankdocument']['name'][$i];
$ext = explode(".",$a);
$ext = end($ext);
$new = date('ymdhis');
$bankdocument[$i] = $user.'bank'.$new.$i.'.'.$ext;
list($success, $result) = s3_upload_file($_FILES['bankdocument']['tmp_name'][$i], $bankdocument[$i], 'application/octet-stream');
if (!$success) $bankdocument[$i] = "no";
        $i++;
    }else{$bankdocument[$i] = "no"; $i++;}}
    $bankdocument = implode('#',$bankdocument);
    }else{
        $bankdocument = "no";
    }print_r(1);
    if(!empty($_FILES["addressdocument"]["name"])){
    $file_type = strtolower(end(explode('.',$_FILES['bankdocument']['type'])));
    $allowed = array("PDF", "pdf");
    if(in_array($file_type, $allowed)) {
    $addressdocument = $_FILES["addressdocument"]["name"];
    $addressdocument = explode(".",$addressdocument);
    $addressdocument = end($addressdocument);
    $addressdocument = $user.'bank'.date('YmdHis').'.'.$addressdocument;
    list($success, $result) = s3_upload_file($_FILES["addressdocument"]["tmp_name"], $addressdocument, 'application/octet-stream');
    if (!$success) $addressdocument = "no";
    }else{
        $addressdocument = "no";
    }}else{
        $addressdocument = "no";
    }print_r(9);
    $document_password = array();
    $extract = towrealarray($_POST);     extract($extract);
    if(!empty($pan_pass)){$document_password[0] = "pan : ".$pan_pass;}
    if(!empty($aadhar_pass)){$document_password[1] = "aadhar : ".$aadhar_pass;}
    if(!empty($salary_pass)){$document_password[2] = "salary : ".$salary_pass;}
    if(!empty($bank_pass)){$document_password[3] = "bank : ".$bank_pass;}
    if(!empty($address_pass)){$document_password[4] = "address : ".$address_pass;}
    $document_password = implode('#',$document_password);

    
    towquery("UPDATE `user` SET `conpanydocument`='$conpanydocument',`personaldocument`='$personaldocument',`salarydocument`='$salarydocument',`bankdocument`='$bankdocument',`addressdocument`='$addressdocument',`document_password`='$document_password' WHERE email='$user'") and print_r("<script>alert('Your data is successfully updated'); window.location.replace('ref.php');</script>");
}
    
if(isset($_POST['bank_name'])){
    $extract = towrealarray($_POST);     extract($extract);
    towquery("UPDATE `user` SET `bank_name`='$bank_name',`ifsc`='$ifsc',`account_no`='$account_no',`account_type`='$account_type',`account_name`='$account_name' WHERE email='$user'");
    print_r("<script>alert('Your data is successfully updated'); window.location.replace('profile.php');</script>");
}
if(($user_name != "") and ($user_present_address != "") and ($user_pan != "") and ($user_state != "") and ($user_email != "") and ($user_mobile != "") and ($user_permanent_address != "")){
    $ADDITIONAL=1;
}if(!empty($user_graduation_year) and ($user_present_address != "") and ($user_pan != "") and ($user_state != "") and ($user_email != "") and ($user_mobile != "") and ($user_permanent_address != "")){
    $Professional = 1;
}if(($user_company != "") and ($user_designation != "") and ($user_pan != "") and ($user_state != "") and ($user_email != "") and ($user_mobile != "") and ($user_permanent_address != "")){
    $INFORMATION = 1;
}if(($user_conpanydocument != "") and ($user_designation != "") and ($user_pan != "") and ($user_state != "") and ($user_email != "") and ($user_mobile != "") and ($user_permanent_address != "")){
    $Bank = 1;
}
$Validation = 1;
?>
<body>
    <!-- Start Left menu area -->
    <?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>
            <!-- Mobile Menu end -->
            
          <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                        <ul>
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
        <div class="container chat" style="background:#fff;">
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in</h4>
        </div>
        <br>
        <div class="single-pro-review-area mt-t-30 mg-b-15"><div class="container-fluid">
                <div class="row"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                   <p <?php if(isset($_GET['active'])){?>style="color:red;" <?php } ?> ><?=$user_validation;?></p> 
                </div></div></div></div>
                <!-- Single pro tab review Start-->
        <div class="single-pro-review-area mt-t-30 mg-b-15">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="product-payment-inner-st">
                            <ul id="myTabedu1" class="tab-review-design">
                    <li id="tab1" class="active"><a href="#Personal">Personal</a></li>
                    <li id="tab2"><a href="#<?php if(isset($ADDITIONAL)){echo 'ADDITIONAL';}?>"> Additional details</a></li>
                    <!--<li id="tab3"><a href="#<?php #if(isset($Professional)){echo 'Professional';}?>"> Professional</a></li>-->
                    
                    <li id="tab4"><a href="#<?php if(isset($INFORMATION)){echo 'INFORMATION';}?>">Documents</a></li>
                    <li id="tab5"><a href="#<?php if(isset($Bank)){echo 'Bank';}?>">Bank Information</a></li>
                    <!--<li><a href="#">Validation</a></li>-->
                            </ul>
                            <div id="myTabContent" class="tab-content custom-product-edit">
                               <?php include 'profile/personal.php'; ?>
                               <?php include 'profile/additional.php'; ?>
                               <?php #include 'profile/professional.php'; ?>
                               <?php include 'profile/information.php'; ?>
                               <?php include 'profile/bank.php'; ?>
                               <?php include 'profile/ref.php'; ?>
                               <!-- <div class="product-tab-list tab-pane fade" id="Validation">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                            
                                        </div>
                                    </div>
                                </div>-->

                            </div>
                        </div>
                    </div>
                </div>
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
   $("#mess").html("");
} else {
   $('#altemail').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#mess").html("Email and alternate Email should not be same");
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
   $("#mess").html("Mobile and alternate Mobile should not be same");
}
});
</script>

<script>
        $( "#next" ).click(function() {
        $("#Personal").attr("class","product-tab-list tab-pane fade");
        $("#ADDITIONAL").attr("class","product-tab-list tab-pane fade active in");
        $("#tab1").attr("class","");
        $("#tab2").attr("class","active");
        
});
        $( "#next2" ).click(function() {
        $("#ADDITIONAL").attr("class","product-tab-list tab-pane fade");
        $("#Professional").attr("class","product-tab-list tab-pane fade active in");
        $("#tab2").attr("class","");
        $("#tab3").attr("class","active");
        
});
        $( "#next3" ).click(function() {
        $("#Professional").attr("class","product-tab-list tab-pane fade");
        $("#INFORMATION").attr("class","product-tab-list tab-pane fade active in");
        $("#tab3").attr("class","");
        $("#tab4").attr("class","active");
        
});
</script>
<script>
$(document).ready(function(){
    $("#form6").submit(function(){
 if ($('#a').val() != ""){
        $("#check").html("");
 }else if ($('#b').val() != ""){
        $("#check").html("");
 }else if ($('#c').val() != ""){
        $("#check").html("");
 }else if ($('#d').val() != ""){
        $("#check").html("");
 }else if ($('#e').val() != ""){
        $("#check").html("");
 }else if ($('#f').val() != ""){
        $("#check").html("");
 }else{
     $("#check").html("choose at least one file to be upload");
 return false;
 }
    });
});</script>
</body>

</html>
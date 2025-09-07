<?php
include_once 'head.php';
$refquery = towquery("SELECT * FROM user_referrals WHERE uid=$user_id");
?>
<body>
<?php
if(($user_verify == 0) or ($user_verify == 1)){
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    
    if($user_limit_inc == 0){
        include 'limit_inc.php';
    }elseif(empty($user_salary) or empty($user_salarystatus) or empty($user_get_salary)){ 
        include 'personalindex.php';
    }elseif(empty($user_pan_name)){
        include 'nameindex.php';
        towquery("update user set credit_score=650 where id=$user_id");
    }else{
        $a = towquery("SELECT SUM(`amount`) AS totalamt FROM `loan_apply` WHERE `uid`=$user_id AND status = 'account manager'");
        $c = towquery("SELECT * FROM `loan_apply` WHERE `uid`=$user_id AND (status = 'pending' OR status = 'disbursal' OR status = 'follow up' OR status = 'account manager')");
        $ubank = towquery("SELECT * FROM `user_bank` WHERE `uid`=$user_id");
        if(townum($c) == 0){
        if($user_approvenew == 1){
            include 'applynow2.php';  
        }else{
            include 'applynownotemi.php';
        }
        }elseif(townum($ubank) == 0){
            include 'bankdetail.php';
        }
        elseif((townum($refquery) == 0) or empty($user_altmobile) or empty($user_altemail) or empty($user_company)){
            include 'ref.php';
        }elseif(empty($user_email)){
            include 'googlelogin.php';
        }
        elseif(empty($user_conpanydocument) or empty($user_personaldocument) or empty($user_salarydocument) or empty($user_bankdocument) or empty($user_addressdocument) or empty($user_companyidcard)){
            include 'document.php';
        }elseif(!(townum($c) == 0) or !(townum($a) == 0)){
            if($user_approvenew == 1){
                include('dashboard.php');
            }else{
                include('dashboardnotemi.php');
            }
        }else{ include 'breadcome.php';?>
        </div>
         <div class="container chat" style="background:#fff;">
             <?php $wa_num = towfetch(towquery("SELECT * FROM `whatsapp_no` WHERE `page_id`=1"))['wa_phone'];?>
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in or <a href="https://api.whatsapp.com/send?phone=91<?=$wa_num?>&text=Hello"><i class="fa fa-whatsapp" style="font-size:36px"></i></a></h4>
        </div>
        <br>
        <div class="analytics-sparkle-area">
            <div class="container-fluid">
                <?php $a = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND (status='pending' OR status='follow up') ORDER BY id DESC");
        if(townum($a) != 0){?>
                <div id="alert" style="padding:15px;background:#fff; position:relative; background:#00a77b;">
                <h3>Your loan is applied in creditlab.in and we will get back to you soon through mail or call.<br> Thank you 😊.</h3>
            </div><br>
            <?php }
            ?>
            <?php $a = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND status='disbursal' ORDER BY id DESC");
        if(townum($a) != 0){?>
                <div id="alert" style="padding:15px;background:#fff; position:relative; background:#00a77b;">
                <h3>your loan is approved and you will get the loan shortly.<br> Thank you 😊.</h3>
            </div><br>
            <?php } ?>
            </div>
        </div>
        <br>
        <br>
        <br>
<?php
include_once 'foot.php';
?>
<script>
    function alertcut(){
        $("#alert").hide();
    }
</script>
</body>

</html>
<?php }}}elseif(($user_verify == 4)){
echo "<body>"; include_once 'welcome.php'; ?>
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
                                        <ul class="breadcome-menu">
                                            <li>Your creditlab.in ID: 
                                            </li>
                                            <li><span class="bread-blod"><?=$user_rcid;?></span>
                                            </li>
                                        </ul>
                                        <ul class="breadcome-menu">
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
        <div class="analytics-sparkle-area">
            <div class="container-fluid">
                <h1>sorry you are not eligible for loan in creditlab.in</h1>
                <br>
                <b>Please mail us at support@creditlab.in  if you have chosen any wrong option in the previous page.</b>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
            </div>
        </div>
<?php include_once 'foot.php'; }elseif(($user_verify == 3)){
towquery("UPDATE `user` SET reg_date='".date('Y-m-d H:i:s')."' WHERE id=$user_id");
// towquery("UPDATE `user` SET `state`='',`dob`='',`pan`='',`salary`='',`salarystatus`='',`present_address`='',`permanent_address`='',`graduation_year`='',`marital_status`='',`college_name`='',`freq_app`='',`experience`='',`residence_type`='',`credit_card`='',`company`='',`designation`='',`office_number`='',`department`='',`annual_income`='',`office_pincode`='',`office_address_line1`='',`office_address_line2`='',`conpanydocument`='',`personaldocument`='',`salarydocument`='',`bankdocument`='',`bankdocument2`='',`bankdocument3`='',`addressdocument`='',`bank_name`='',`branch_name`='',`ifsc`='',`account_no`='',`account_type`='',`account_name`='',reg_date='".date('Y-m-d H:i:s')."',`document_password`='pan no password pan#aadhar no password aadhar#aadha2 no password aadha2#salary no password salary#bank no password bank#address no password address#bank2 no password bank2#bank3 no password bank3',`get_salary`='',`loan`=0,`loan_limit`=10000,`sloan`=0 WHERE id=$user_id");
// towquery("DELETE FROM `user_ref` WHERE `uid`=$user_id");
echo "<body>"; include_once 'welcome.php'; ?>
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
        <div class="analytics-sparkle-area">
            <div class="container-fluid">
                <?php 
                $stop_date = date('Y-m-d H:i:s', strtotime($user_reg_date . ' +45 day'));
                if($stop_date > date('Y-m-d H:i:s')){
                $stop_date = date_create($stop_date);
                $sa = date_create(date('Y-m-d H:i:s'));
                $aa = date_diff($stop_date,$sa); ?>
                <h1>Your membership is on hold. You can reapply after <?=$aa->format("%a days");?></h1>
                <?php 
                }else{ 
                towquery("UPDATE `user` SET verify=1,status='waiting',reg_date='".date('Y-m-d H:i:s')."' WHERE id=$user_id");
                print_r("<script>window.location.replace('index.php');</script>");
                } ?>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
            </div>
        </div>
<?php include_once 'foot.php'; } ?>
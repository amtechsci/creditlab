<?php
include_once 'head.php';
$refquery = towquery("SELECT * FROM user_referrals WHERE uid=$user_id");
?>
<body>
<?php
// Main condition for verified or new users
if (($user_verify == 0) or ($user_verify == 1)) {
    
    // ## 1. DETERMINE PAGE STATE LOGIC ##
    // This block determines which page to show by setting a number to $page_state.
    $page_state = 0; // Default state

    if ($user_limit_inc == 0) {
        $page_state = 1; // Needs limit increase
    } elseif (empty($user_salary) or empty($user_salarystatus) or empty($user_get_salary)) {
        $page_state = 2; // Needs personal info
    } elseif (empty($user_pan_name)) {
        $page_state = 3; // Needs PAN name
        towquery("update user set credit_score=650 where id=$user_id"); // Kept original query here
    } else {
        // These queries are only needed for the more advanced checks below
        $a = towquery("SELECT SUM(`amount`) AS totalamt FROM `loan_apply` WHERE `uid`=$user_id AND status = 'account manager'");
        $c = towquery("SELECT * FROM `loan_apply` WHERE `uid`=$user_id AND (status = 'pending' OR status = 'disbursal' OR status = 'follow up' OR status = 'account manager')");
        $ubank = towquery("SELECT * FROM `user_bank` WHERE `uid`=$user_id");

        if (townum($c) == 0) { // No active loan applications
            $page_state = ($user_approvenew == 1) ? 4 : 5; // Show appropriate application page
        } elseif (townum($ubank) == 0) {
            $page_state = 6; // Needs bank details
        } elseif ((townum($refquery) == 0) or empty($user_altmobile) or empty($user_altemail) or empty($user_company)) {
            $page_state = 7; // Needs referral/contact info
        } elseif (empty($user_email)) {
            $page_state = 8; // Needs to log in with Google
        } elseif (empty($user_conpanydocument) or empty($user_personaldocument) or empty($user_salarydocument) or empty($user_bankdocument) or empty($user_addressdocument) or empty($user_companyidcard)) {
            $page_state = 9; // Needs to upload documents
        } elseif (!(townum($c) == 0) or !(townum($a) == 0)) { // Has an active loan application
            $page_state = ($user_approvenew == 1) ? 10 : 11; // Show appropriate dashboard
        } else {
            $page_state = 99; // Fallback/Welcome page
        }
    }

    // ## 2. INCLUDE FILES BASED ON PAGE STATE ##
    // This block includes common UI elements and then uses the $page_state
    // number to include the specific content page.
    include_once 'Left_menu.php';
    // echo $page_state;exit;
    if($page_state == 10 or $page_state == 11){
        $fpage = $page_state;
                // ## 1. DETERMINE PAGE STATE & PROGRESS LOGIC ##
                $per = 0;           // Default progress
                $loanfetch = null;  // Initialize loan fetch variable
                
                $disbursal_query = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND status='disbursal' ORDER BY id DESC");
                
                if (townum($disbursal_query) > 0) {
                    $loanfetch = towfetchassoc($disbursal_query);
                
                    if (empty($user_signature)) {
                        $page_state = 12; // Needs signature
                        $per = 92;
                    } elseif (empty($user_selfie)) {
                        $page_state = 13; // Needs video KYC
                        $per = 94;
                    } elseif ($user_easebuzz == 0 || $user_easebuzz == 2) {
                        $page_state = 14; // Needs e-mandate (Easebuzz)
                        $per = 97;
                    } else {
                        $per = 100; // From here on, progress is 100%
                        if ($loanfetch['agreement'] == 0) {
                            if ($loanfetch['keyid'] == 0) {
                                $page_state = 15; // Needs to agree to Key Fact Statement
                            } else {
                                $page_state = 16; // Needs to agree to Loan Agreement
                            }
                        } else {
                            $page_state = 17; // Loan approved and finalized
                        }
                    }
                }
    }else{
        $fpage = 0;
    }
    include_once 'welcome.php';
    include_once 'm_menu.php';
    
    
    // echo $fpage;exit;
    switch ($fpage) {
        case 10:
            include('dashboard.php');
            break;
        case 11:
            include('dashboardnotemi.php');
            break;
    }
    switch ($page_state) {
        case 1:
            include 'limit_inc.php';
            break;
        case 2:
            include 'personalindex.php';
            break;
        case 3:
            include 'nameindex.php';
            break;
        case 4:
            include 'applynow2.php';
            break;
        case 5:
            include 'applynownotemi.php';
            break;
        case 6:
            include 'bankdetail.php';
            break;
        case 7:
            include 'ref.php';
            break;
        case 8:
            include 'googlelogin.php';
            break;
        case 9:
            include 'document.php';
            break;
        case 99:
            // This is the special case that had extra HTML after the include
            include 'breadcome.php';
?>
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
                    <?php } ?>

                    <?php $a = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND status='disbursal' ORDER BY id DESC");
                    if(townum($a) != 0){?>
                        <div id="alert" style="padding:15px;background:#fff; position:relative; background:#00a77b;">
                            <h3>your loan is approved and you will get the loan shortly.<br> Thank you 😊.</h3>
                        </div><br>
                    <?php } ?>
                </div>
            </div>
            <br><br><br>
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
<?php
            break;
    }

// Condition for non-eligible users
} elseif (($user_verify == 4)) {
    echo "<body>"; 
    $page_state = 1;
    include_once 'welcome.php'; 
?>
    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="breadcome-list">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <ul>
                                    <li><h4><?=$user_name?></h4></li>
                                    <li><span class="bread-blod"><?=$user_mobile;?></span></li>
                                </ul>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <ul class="breadcome-menu">
                                    <li>Your creditlab.in ID: </li>
                                    <li><span class="bread-blod"><?=$user_rcid;?></span></li>
                                </ul>
                                <ul class="breadcome-menu">
                                    <li><span class="bread-blod"><?=$user_email;?></span></li>
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
            <b>Please mail us at support@creditlab.in if you have chosen any wrong option in the previous page.</b>
            <br><br><br><br><br><br><br><br><br>
        </div>
    </div>
<?php 
    include_once 'foot.php'; 

// Condition for users on hold
} elseif (($user_verify == 3)) {
    towquery("UPDATE `user` SET reg_date='".date('Y-m-d H:i:s')."' WHERE id=$user_id");
    echo "<body>"; 
    $page_state = 1;
    include_once 'welcome.php'; 
?>
    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="breadcome-list">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <ul>
                                    <li><h4><?=$user_name?></h4></li>
                                    <li><span class="bread-blod"><?=$user_mobile;?></span></li>
                                </ul>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <div class="rc">
                                    <ul class="breadcome-menua">
                                        <li>Your creditlab.in ID: <span class="bread-bloda"><?=$user_rcid;?></span></li>
                                    </ul>
                                    <ul class="breadcome-menua">
                                        <li><span class="bread-blod"><?=$user_email;?></span></li>
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
            } else { 
                towquery("UPDATE `user` SET verify=1,status='waiting',reg_date='".date('Y-m-d H:i:s')."' WHERE id=$user_id");
                print_r("<script>window.location.replace('index.php');</script>");
            } 
            ?>
            <br><br><br><br><br><br><br><br><br><br>
        </div>
    </div>
<?php 
    include_once 'foot.php'; 
} 
?>
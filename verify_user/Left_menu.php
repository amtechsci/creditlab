<?php
if($user_type == 1){
    function maskEmail($email) {
        return $email;
    }
    function maskPhone($phone) {
        return $phone;
    }
}else{
    function maskEmail($email) {
        $email_parts = explode('@', $email);
        $local_part = $email_parts[0];
        $domain = $email_parts[1];
        $masked_local = substr($local_part, 0, 3) . str_repeat('*', strlen($local_part) - 3);
        return $masked_local . '@' . $domain;
    }
    function maskPhone($phone) {
        return substr($phone, 0, 2) . str_repeat('*', strlen($phone) - 4) . substr($phone, -2);
    }
}
?>
<div class="left-sidebar-pro" style="background-color: #ddd !important;">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="../index.php"><img src="https://creditlab.in/assets/img/logo.jpeg" class="black-logo" style="width:160px;" alt="Logo"></a>
                <strong><a href="index.php"><img src="img/logo/logosnaa.png" alt="" /></a></strong>
            </div>
            <div class="left-custom-menu-adp-wrap comment-scrollbar" >
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <?php if($user_type == 1){ ?>
                        <li>
                            <a href="dynamic_search.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/dynamic_search.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Dynamic Search</span></a>
                        </li>
                        <?php }else{ ?>
                        <li>
                            <a class="" href="apnlt.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/verify_user/apnlt.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Existing no loan taken</span></a>
                        </li>
                        <?php } $appicount = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='pending'");?>
                         <li>
                            <a class="" href="newloan.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/verify_user/newloan.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Applied Loans (<?=townum($appicount);?>)</span></a>
                        </li>
                        <?php $follow_up = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='follow up'");?>
                        <li>
                            <a class="" href="follow_up.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/verify_user/follow_up.php"){ ?> style="background: whitesmoke;" <?php }?>><i class="fas fa-landmark"></i> <span class="mini-click-non">Follow Up (<?=townum($follow_up);?>)</span></a>
                        </li>
                        <?php $disbursal = towquery("SELECT loan_apply.*, user.sloan FROM loan_apply Left JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND sloan=0 ORDER BY sloan DESC");?>
                         <li>
                            <a class="" href="disbursal.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/verify_user/disbursal.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Disbursal (<?=townum($disbursal);?>)</span></a>
                        </li>
                        <?php $rldcount = towquery("SELECT DISTINCT uid FROM loan_apply Left JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND user.sloan > 0");?>
                         <li>
                            <a class="" href="repeat_loan_d.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/verify_user/repeat_loan_d.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Repeat Loan D (<?=townum($rldcount);?>)</span></a>
                        </li>
                    </ul>
                </nav>
            </div>
        </nav>
    </div>
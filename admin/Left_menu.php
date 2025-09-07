<?php
// Combined query to get all counts
$counts = towquery("
    SELECT
        (SELECT COUNT(*) FROM `pay_ref`) AS loanclearance_count,
        (SELECT COUNT(*) FROM `user` WHERE `active`=1 AND `verify`=0) AS newusers_count,
        (SELECT COUNT(*) FROM `user` WHERE `verify`=0) AS approved_users_count,
        (SELECT COUNT(DISTINCT uid) FROM `loan_apply` WHERE `status`='pending') AS applied_loans_count,
        (SELECT COUNT(DISTINCT loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND user.sloan > 0) AS repeat_loan_d_count,
        (SELECT COUNT(DISTINCT loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='cancel') AS cancelled_loans_count,
        (SELECT COUNT(DISTINCT loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE user.status='HOLD') AS hold_loans_count,
        (SELECT COUNT(DISTINCT uid) FROM `loan_apply` WHERE `status`='follow up') AS follow_up_count,
        (SELECT COUNT(loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND sloan=0) AS disbursal_count,
        (SELECT COUNT(DISTINCT uid) FROM loan_apply WHERE `status`='account manager') AS account_manager_count,
        (SELECT COUNT(DISTINCT uid) FROM loan_apply WHERE `status`='recovery officer') AS recovery_officer_count
");

// Fetch the data into an associative array
$counts_data = towfetch($counts);
?>

<div class="left-sidebar-pro" style="background-color: #ddd !important;">
    <nav id="sidebar">
        <div class="sidebar-header">
            <a href="../index.php"><img src="https://creditlab.in/assets/img/logo.jpeg" class="black-logo" style="width:160px;" alt="Logo"></a>
            <strong><a href="index.php"><img src="img/logo/logosnaa.png" alt="" /></a></strong>
        </div>
        <div class="left-custom-menu-adp-wrap comment-scrollbar">
            <nav class="sidebar-nav left-sidebar-menu-pro">
                <ul class="metismenu" id="menu1">
                    <li>
                        <a href="index.php" <?php if($_SERVER['REQUEST_URI'] == "/admin/index.php"){ ?> style="background: whitesmoke;" <?php }?>>
                            <span> <i class="fas fa-tachometer-alt"></i></span>
                            <span class="mini-click-non">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="loanclearance.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/loanclearance.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Loan clearance (<?= $counts_data['loanclearance_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="newusers.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/newusers.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Just registered (<?= $counts_data['newusers_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="apnlt.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/apnlt.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Existing no loan taken</span></a>
                    </li>
                    <li>
                        <a href="users.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/users.php"){ ?> style="background: whitesmoke;" <?php }?>> <i class="fas fa-user-alt"></i> <span class="mini-click-non">Approved Users (<?= $counts_data['approved_users_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="newloan.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/newloan.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Applied Loans (<?= $counts_data['applied_loans_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="repeat_loan_d.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/repeat_loan_d.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Repeat Loan D (<?= $counts_data['repeat_loan_d_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="cancel_loan.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/cancel_loan.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Cancelled Loan (<?= $counts_data['cancelled_loans_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="hold_loan.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/hold_loan.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Hold Loan (<?= $counts_data['hold_loans_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="part_payment.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/part_payment.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Part Payment (<?= $counts_data['cancelled_loans_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="follow_up.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/follow_up.php"){ ?> style="background: whitesmoke;" <?php }?>><i class="fas fa-landmark"></i> <span class="mini-click-non">Follow Up (<?= $counts_data['follow_up_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="disbursal.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/disbursal.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Disbursal (<?= $counts_data['disbursal_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="loan_slider.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/loan_slider.php"){ ?> style="background: whitesmoke;" <?php }?>><i class="fas fa-landmark"></i> <span class="mini-click-non">Loan slider</span></a>
                    </li>
                    <li>
                        <a href="emi_calculator.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/emi_calculator.php"){ ?> style="background: whitesmoke;" <?php }?>><i class="fas fa-landmark"></i> <span class="mini-click-non">EMI Calculator</span></a>
                    </li>
                    <li>
                        <a href="dynamic_search.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/dynamic_search.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Dynamic Search</span></a>
                    </li>
                    <li>
                        <a href="account_manager.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/account_manager.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Account Manager (<?= $counts_data['account_manager_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="recovery_officer.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/recovery_officer.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Recovery Officer (<?= $counts_data['recovery_officer_count']; ?>)</span></a>
                    </li>
                    <li>
                        <a href="add_user.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/add_user.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Add User</span></a>
                    </li>
                    <li>
                        <a href="email.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/email.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Email</span></a>
                    </li>
                    <li>
                        <a href="bank_statistics.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/bank_statistics.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Bank Statistics</span></a>
                    </li>
                    <li>
                        <a href="overview.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/overview.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Overview</span></a>
                    </li>
                    <li>
                        <a href="downloader.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/downloader.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Downloader</span></a>
                    </li>
                </ul>
            </nav>
        </div>
    </nav>
</div>

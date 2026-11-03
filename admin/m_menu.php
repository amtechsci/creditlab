<?php
// Use $counts_data from Left_menu.php if present; fallback to avoid notice
if (!isset($counts_data) || !is_array($counts_data)) {
    $counts = towquery("
        SELECT
            (SELECT COUNT(*) FROM `pay_ref`) AS loanclearance_count,
            (SELECT COUNT(*) FROM `user` WHERE `active`=1 AND `verify`=0) AS newusers_count,
            (SELECT COUNT(*) FROM `user` WHERE `verify`=1 AND `status`='Approved') AS approved_users_count,
            (SELECT COUNT(DISTINCT uid) FROM `loan_apply` WHERE `status`='pending') AS applied_loans_count,
            (SELECT COUNT(DISTINCT loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND user.sloan > 0) AS repeat_loan_d_count,
            (SELECT COUNT(DISTINCT loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='cancel') AS cancelled_loans_count,
            (SELECT COUNT(DISTINCT loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE user.status='HOLD') AS hold_loans_count,
            (SELECT COUNT(DISTINCT uid) FROM `loan_apply` WHERE `status`='follow up') AS follow_up_count,
            (SELECT COUNT(loan_apply.uid) FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND sloan=0) AS disbursal_count,
            (SELECT COUNT(DISTINCT uid) FROM loan_apply WHERE `status`='account manager') AS account_manager_count,
            (SELECT COUNT(DISTINCT uid) FROM loan_apply WHERE `status`='recovery officer') AS recovery_officer_count,
            (SELECT COUNT(*) FROM `loan` WHERE `advance_amount` > 0 AND (`status_log` != 'cleared' OR `status_log` IS NULL)) AS part_payment_count
    ");
    $counts_data = towfetch($counts);
}
?>
<div class="mobile-menu-area">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="mobile-menu">
                    <nav id="dropdown">
                        <ul class="metismenu" id="menu-mobile">
                            <li>
                                <a href="index.php">
                                    <i class="fa-solid fa-gauge-high"></i>
                                    <span class="mini-click-non">Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="index.php?tab=performance">
                                    <i class="fa-solid fa-chart-pie"></i>
                                    <span class="mini-click-non">Performance</span>
                                </a>
                            </li>
                            <li>
                                <a href="overview.php">
                                    <i class="fa-solid fa-layer-group"></i>
                                    <span class="mini-click-non">Portfolio Overview</span>
                                </a>
                            </li>
                            <li>
                                <a href="newloan.php">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                    <span class="mini-click-non">Applied Loans (<?= (int)($counts_data['applied_loans_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="disbursal.php">
                                    <i class="fa-solid fa-paper-plane"></i>
                                    <span class="mini-click-non">Disbursal (<?= (int)($counts_data['disbursal_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="repeat_loan_d.php">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                    <span class="mini-click-non">Repeat Loan D (<?= (int)($counts_data['repeat_loan_d_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="loanclearance.php">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span class="mini-click-non">Loan Clearance (<?= (int)($counts_data['loanclearance_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="part_payment.php">
                                    <i class="fa-solid fa-receipt"></i>
                                    <span class="mini-click-non">Part Payment (<?= (int)($counts_data['part_payment_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="follow_up.php">
                                    <i class="fa-solid fa-phone-volume"></i>
                                    <span class="mini-click-non">Follow Up (<?= (int)($counts_data['follow_up_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="hold_loan.php">
                                    <i class="fa-solid fa-circle-pause"></i>
                                    <span class="mini-click-non">Hold Loans (<?= (int)($counts_data['hold_loans_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="cancel_loan.php">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                    <span class="mini-click-non">Cancelled Loans (<?= (int)($counts_data['cancelled_loans_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="account_manager.php">
                                    <i class="fa-solid fa-briefcase"></i>
                                    <span class="mini-click-non">Account Manager (<?= (int)($counts_data['account_manager_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="recovery_officer.php">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    <span class="mini-click-non">Recovery Officer (<?= (int)($counts_data['recovery_officer_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="users.php">
                                    <i class="fa-solid fa-user-check"></i>
                                    <span class="mini-click-non">Approved Users (<?= (int)($counts_data['approved_users_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="newusers.php">
                                    <i class="fa-solid fa-user-clock"></i>
                                    <span class="mini-click-non">Just Registered (<?= (int)($counts_data['newusers_count'] ?? 0); ?>)</span>
                                </a>
                            </li>
                            <li>
                                <a href="apnlt.php">
                                    <i class="fa-solid fa-user-slash"></i>
                                    <span class="mini-click-non">Existing (No Loan)</span>
                                </a>
                            </li>
                            <li>
                                <a href="add_user.php">
                                    <i class="fa-solid fa-users-gear"></i>
                                    <span class="mini-click-non">Manage Staff Users</span>
                                </a>
                            </li>
                            <li>
                                <a href="downloader.php">
                                    <i class="fa-solid fa-cloud-arrow-down"></i>
                                    <span class="mini-click-non">Downloader / Reports</span>
                                </a>
                            </li>
                            <li>
                                <a href="dynamic_search.php">
                                    <i class="fa-solid fa-magnifying-glass-chart"></i>
                                    <span class="mini-click-non">Dynamic Search</span>
                                </a>
                            </li>
                            <li>
                                <a href="bank_statistics.php">
                                    <i class="fa-solid fa-building-columns"></i>
                                    <span class="mini-click-non">Bank Statistics</span>
                                </a>
                            </li>
                            <li>
                                <a href="loan_slider.php">
                                    <i class="fa-solid fa-sliders"></i>
                                    <span class="mini-click-non">Loan Slider</span>
                                </a>
                            </li>
                            <li>
                                <a href="email.php">
                                    <i class="fa-solid fa-envelope"></i>
                                    <span class="mini-click-non">Email Center</span>
                                </a>
                            </li>
                            <li>
                                <a href="logs.php">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <span class="mini-click-non">System Logs</span>
                                </a>
                            </li>
                            <li>
                                <a href="sql.php">
                                    <i class="fa-solid fa-database"></i>
                                    <span class="mini-click-non">SQL Console</span>
                                </a>
                            </li>
                            <li>
                                <a href="settings.php">
                                    <i class="fa-solid fa-gear"></i>
                                    <span class="mini-click-non">Settings</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

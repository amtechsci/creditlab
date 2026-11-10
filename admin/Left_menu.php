<?php
// Combined query to get all counts
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

// Fetch the data into an associative array
$counts_data = towfetch($counts);

if (!function_exists('cl_nav_active')) {
    function cl_nav_active($file, $tab = null) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if ($tab !== null) {
            return (isset($_GET['tab']) && $_GET['tab'] === $tab) ? 'active' : '';
        }
        if ($file === 'index.php') {
            return ($uri === '/admin/' || $uri === '/admin/index.php' || (strpos($uri, 'index.php') !== false && !isset($_GET['tab']))) ? 'active' : '';
        }
        return (strpos($uri, $file) !== false) ? 'active' : '';
    }
}
?>

<div class="left-sidebar-pro">
    <nav id="sidebar">
        <div class="sidebar-header sidebar-header-minimal">
            <span class="brand-badge">ADMIN</span>
            <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close navigation menu" title="Close menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="left-custom-menu-adp-wrap sidebar-menu-scroll">
            <nav class="sidebar-nav left-sidebar-menu-pro">
                <ul class="metismenu" id="menu1">
                    <!-- SECTION: OVERVIEW -->
                    <li class="sidebar-section-title">Overview</li>
                    <li class="<?= cl_nav_active('index.php') ?>">
                        <a href="index.php">
                            <i class="fa-solid fa-gauge-high"></i>
                            <span class="mini-click-non">Dashboard</span>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('index.php', 'performance') ?>">
                        <a href="index.php?tab=performance">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span class="mini-click-non">Performance</span>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('overview.php') ?>">
                        <a href="overview.php">
                            <i class="fa-solid fa-layer-group"></i>
                            <span class="mini-click-non">Portfolio Overview</span>
                        </a>
                    </li>

                    <!-- SECTION: LOAN PIPELINE -->
                    <li class="sidebar-section-title">Loan Pipeline</li>
                    <li class="<?= cl_nav_active('newloan.php') ?>">
                        <a href="newloan.php">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                            <span class="mini-click-non">Applied Loans</span>
                            <?php if (!empty($counts_data['applied_loans_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['applied_loans_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('disbursal.php') ?>">
                        <a href="disbursal.php">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span class="mini-click-non">Disbursal</span>
                            <?php if (!empty($counts_data['disbursal_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['disbursal_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('repeat_loan_d.php') ?>">
                        <a href="repeat_loan_d.php">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            <span class="mini-click-non">Repeat Loan D</span>
                            <?php if (!empty($counts_data['repeat_loan_d_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['repeat_loan_d_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('loanclearance.php') ?>">
                        <a href="loanclearance.php">
                            <i class="fa-solid fa-circle-check"></i>
                            <span class="mini-click-non">Loan Clearance</span>
                            <?php if (!empty($counts_data['loanclearance_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['loanclearance_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('part_payment.php') ?>">
                        <a href="part_payment.php">
                            <i class="fa-solid fa-receipt"></i>
                            <span class="mini-click-non">Part Payment</span>
                            <?php if (!empty($counts_data['part_payment_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['part_payment_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('follow_up.php') ?>">
                        <a href="follow_up.php">
                            <i class="fa-solid fa-phone-volume"></i>
                            <span class="mini-click-non">Follow Up</span>
                            <?php if (!empty($counts_data['follow_up_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['follow_up_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('hold_loan.php') ?>">
                        <a href="hold_loan.php">
                            <i class="fa-solid fa-circle-pause"></i>
                            <span class="mini-click-non">Hold Loans</span>
                            <?php if (!empty($counts_data['hold_loans_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['hold_loans_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('cancel_loan.php') ?>">
                        <a href="cancel_loan.php">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span class="mini-click-non">Cancelled Loans</span>
                            <?php if (!empty($counts_data['cancelled_loans_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['cancelled_loans_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- SECTION: COLLECTIONS & RECOVERY -->
                    <li class="sidebar-section-title">Collections &amp; Recovery</li>
                    <li class="<?= cl_nav_active('account_manager.php') ?>">
                        <a href="account_manager.php">
                            <i class="fa-solid fa-briefcase"></i>
                            <span class="mini-click-non">Account Manager</span>
                            <?php if (!empty($counts_data['account_manager_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['account_manager_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('recovery_officer.php') ?>">
                        <a href="recovery_officer.php">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span class="mini-click-non">Recovery Officer</span>
                            <?php if (!empty($counts_data['recovery_officer_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['recovery_officer_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- SECTION: USERS & CUSTOMERS -->
                    <li class="sidebar-section-title">Users &amp; Customers</li>
                    <li class="<?= cl_nav_active('users.php') ?>">
                        <a href="users.php">
                            <i class="fa-solid fa-user-check"></i>
                            <span class="mini-click-non">Approved Users</span>
                            <?php if (!empty($counts_data['approved_users_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['approved_users_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('newusers.php') ?>">
                        <a href="newusers.php">
                            <i class="fa-solid fa-user-clock"></i>
                            <span class="mini-click-non">Just Registered</span>
                            <?php if (!empty($counts_data['newusers_count'])): ?>
                                <span class="nav-count-badge"><?= (int)$counts_data['newusers_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('apnlt.php') ?>">
                        <a href="apnlt.php">
                            <i class="fa-solid fa-user-slash"></i>
                            <span class="mini-click-non">Existing (No Loan)</span>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('add_user.php') ?>">
                        <a href="add_user.php">
                            <i class="fa-solid fa-users-gear"></i>
                            <span class="mini-click-non">Manage Staff Users</span>
                        </a>
                    </li>

                    <!-- SECTION: TOOLS & SYSTEM -->
                    <li class="sidebar-section-title">Tools &amp; Reports</li>
                    <li class="<?= cl_nav_active('downloader.php') ?>">
                        <a href="downloader.php">
                            <i class="fa-solid fa-cloud-arrow-down"></i>
                            <span class="mini-click-non">Downloader / Reports</span>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('dynamic_search.php') ?>">
                        <a href="dynamic_search.php">
                            <i class="fa-solid fa-magnifying-glass-chart"></i>
                            <span class="mini-click-non">Dynamic Search</span>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('bank_statistics.php') ?>">
                        <a href="bank_statistics.php">
                            <i class="fa-solid fa-building-columns"></i>
                            <span class="mini-click-non">Bank Statistics</span>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('loan_slider.php') ?>">
                        <a href="loan_slider.php">
                            <i class="fa-solid fa-sliders"></i>
                            <span class="mini-click-non">Loan Slider</span>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('email.php') ?>">
                        <a href="email.php">
                            <i class="fa-solid fa-envelope"></i>
                            <span class="mini-click-non">Email Center</span>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('logs.php') ?>">
                        <a href="logs.php">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span class="mini-click-non">System Logs</span>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('sql.php') ?>">
                        <a href="sql.php">
                            <i class="fa-solid fa-database"></i>
                            <span class="mini-click-non">SQL Console</span>
                        </a>
                    </li>
                    <li class="<?= cl_nav_active('settings.php') ?>">
                        <a href="settings.php">
                            <i class="fa-solid fa-gear"></i>
                            <span class="mini-click-non">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </nav>
</div>
<div class="cl-sidebar-backdrop" id="sidebarBackdrop"></div>

<script>
(function() {
    function closeMobileNav() {
        document.body.classList.remove('mobile-nav-open');
        var sidebarPro = document.querySelector('.left-sidebar-pro');
        if (sidebarPro) sidebarPro.classList.remove('active');
        var sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.remove('active');
    }

    function openMobileNav() {
        document.body.classList.remove('mini-navbar');
        document.body.classList.add('mobile-nav-open');
        var sidebarPro = document.querySelector('.left-sidebar-pro');
        if (sidebarPro) sidebarPro.classList.add('active');
        var sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.add('active');
    }

    // Use capture phase so we intercept before jQuery's main.js on mobile
    document.addEventListener('click', function(e) {
        var toggleBtn = e.target.closest('#sidebarCollapse, .cl-hamburger-btn');
        if (toggleBtn) {
            if (window.innerWidth <= 991) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                if (document.body.classList.contains('mobile-nav-open') || document.querySelector('.left-sidebar-pro.active')) {
                    closeMobileNav();
                } else {
                    openMobileNav();
                }
                return;
            }
        }

        var closeBtn = e.target.closest('#sidebarCloseBtn');
        if (closeBtn) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileNav();
            return;
        }

        var backdrop = e.target.closest('#sidebarBackdrop');
        if (backdrop) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileNav();
            return;
        }

        // Mobile search toggle
        var searchToggle = e.target.closest('#mobileSearchToggle');
        if (searchToggle) {
            e.preventDefault();
            e.stopPropagation();
            var searchBar = document.getElementById('mobileSearchBar');
            if (searchBar) {
                searchBar.classList.toggle('active');
                if (searchBar.classList.contains('active')) {
                    var input = searchBar.querySelector('input');
                    if (input) input.focus();
                }
            }
            return;
        }

        var searchClose = e.target.closest('#mobileSearchClose');
        if (searchClose) {
            e.preventDefault();
            e.stopPropagation();
            var searchBar2 = document.getElementById('mobileSearchBar');
            if (searchBar2) searchBar2.classList.remove('active');
            return;
        }

        // Clicking any link in the sidebar menu closes the mobile drawer
        if (window.innerWidth <= 991 && e.target.closest('.sidebar-nav a')) {
            closeMobileNav();
        }
    }, true);

    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            closeMobileNav();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileNav();
            var searchBar = document.getElementById('mobileSearchBar');
            if (searchBar) searchBar.classList.remove('active');
        }
    });
})();
</script>

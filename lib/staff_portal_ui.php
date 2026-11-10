<?php
/**
 * Shared modern staff-portal chrome (matches admin design; not used on user portal).
 */

require_once __DIR__ . '/admin_ui.php';

/** CSS prefix from portal dir to admin assets (e.g. ../admin/). */
function creditlab_staff_portal_admin_css_prefix(): string
{
    return '../admin/';
}

function creditlab_staff_nav_active(string $file): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if ($file === 'index.php') {
        if (preg_match('#/(account_manager|recovery_officer|agency_admin|verify_user)/?$#', $uri)) {
            return 'active';
        }
        return (strpos($uri, 'index.php') !== false && !isset($_GET['tab'])) ? 'active' : '';
    }
    return (strpos($uri, $file) !== false) ? 'active' : '';
}

function creditlab_render_staff_modern_styles(): void
{
    $p = htmlspecialchars(creditlab_staff_portal_admin_css_prefix(), ENT_QUOTES, 'UTF-8');
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">' . "\n";
    echo '<link rel="stylesheet" href="' . $p . 'css/admin-mobile.css">' . "\n";
    echo '<link rel="stylesheet" href="' . $p . 'css/modern_admin.css">' . "\n";
    echo '<link rel="stylesheet" href="' . $p . 'css/profile_tabs.css">' . "\n";
}

/**
 * @param array{
 *   portal_label?: string,
 *   show_help?: bool,
 *   show_settings?: bool,
 *   search_action?: string,
 *   logout_href?: string,
 *   settings_href?: string,
 *   password_href?: string,
 *   signed_in_as?: string
 * } $opts
 */
function creditlab_render_staff_welcome(array $opts = []): void
{
    $showHelp = !empty($opts['show_help']);
    $showSettings = !empty($opts['show_settings']);
    $searchAction = $opts['search_action'] ?? 'sea.php';
    $logoutHref = $opts['logout_href'] ?? 'logout.php';
    $settingsHref = $opts['settings_href'] ?? 'settings.php';
    $passwordHref = $opts['password_href'] ?? 'changepassword.php';
    $signedInAs = $opts['signed_in_as'] ?? ($GLOBALS['user_name'] ?? 'Staff');
    $displayName = $GLOBALS['user_name'] ?? 'Staff';
    ?>
<div class="all-content-wrapper">
    <div class="header-advance-area">
        <div class="header-top-area">
            <div class="cl-top-header-container">
                <div class="cl-header-left">
                    <button type="button" id="sidebarCollapse" class="cl-hamburger-btn" title="Toggle Navigation" aria-label="Toggle Navigation">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="cl-desktop-search">
                        <form class="header-search-form" action="<?= htmlspecialchars($searchAction) ?>" method="get" target="_blank">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input type="text" name="sea" class="form-control" placeholder="Search UID, name, mobile, PAN, loan ID..." autocomplete="off">
                        </form>
                    </div>
                </div>
                <div class="cl-header-right">
                    <button type="button" class="cl-mobile-search-toggle" id="mobileSearchToggle" title="Search" aria-label="Toggle Search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <?php if ($showHelp) { ?>
                    <button type="button" class="cl-help-btn" id="clHelpBtn" title="Color &amp; Symbol Guide" aria-label="Staff guide">
                        <i class="fa-solid fa-circle-question"></i>
                        <span class="hidden-xs">Guide</span>
                    </button>
                    <?php } ?>
                    <a href="<?= htmlspecialchars(getAppUrl()) ?>/" class="header-view-site" target="_blank" rel="noopener" title="View Public Website">
                        <span>View Site</span>
                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i>
                    </a>
                    <div class="dropdown header-user-dropdown">
                        <a href="#" data-toggle="dropdown" role="button" aria-expanded="false" class="header-user-btn nav-link dropdown-toggle">
                            <div class="header-user-avatar"><?= strtoupper(substr(trim($displayName), 0, 1)) ?></div>
                            <span class="header-user-name"><?= htmlspecialchars($displayName) ?></span>
                            <i class="fa-solid fa-chevron-down" style="font-size: 10px; color: var(--cl-slate-400);"></i>
                        </a>
                        <ul role="menu" class="dropdown-header-top author-log dropdown-menu dropdown-menu-right animated zoomIn">
                            <li class="user-dropdown-header">
                                <div class="user-dropdown-caption">Signed in as</div>
                                <div class="user-dropdown-user"><?= htmlspecialchars($signedInAs) ?></div>
                            </li>
                            <li>
                                <a href="<?= htmlspecialchars($passwordHref) ?>"><i class="fa-solid fa-key" style="width: 16px;"></i> Change password</a>
                            </li>
                            <?php if ($showSettings) { ?>
                            <li>
                                <a href="<?= htmlspecialchars($settingsHref) ?>"><i class="fa-solid fa-gear" style="width: 16px;"></i> System Settings</a>
                            </li>
                            <?php } ?>
                            <li class="divider" style="margin: 4px 0;"></li>
                            <li>
                                <a href="<?= htmlspecialchars($logoutHref) ?>" style="color: var(--cl-danger-text) !important;"><i class="fa-solid fa-right-from-bracket" style="width: 16px;"></i> Log Out</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="cl-mobile-search-bar" id="mobileSearchBar">
                <form class="cl-mobile-search-form" action="<?= htmlspecialchars($searchAction) ?>" method="get" target="_blank">
                    <i class="fa-solid fa-magnifying-glass cl-mobile-search-icon"></i>
                    <input type="text" name="sea" class="cl-mobile-search-input" placeholder="Search UID, name, phone, PAN, loan..." autocomplete="off">
                    <button type="button" class="cl-mobile-search-close" id="mobileSearchClose" aria-label="Close search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </form>
            </div>
        </div>
    <?php if ($showHelp) {
        echo creditlab_render_help_modal();
    } ?>
<?php
}

function creditlab_staff_sidebar_open(string $badgeLabel): void
{
    $badgeLabel = trim($badgeLabel) !== '' ? $badgeLabel : 'STAFF';
    ?>
<div class="left-sidebar-pro">
    <nav id="sidebar">
        <div class="sidebar-header sidebar-header-minimal">
            <span class="brand-badge"><?= htmlspecialchars($badgeLabel) ?></span>
            <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close navigation menu" title="Close menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="left-custom-menu-adp-wrap sidebar-menu-scroll">
            <nav class="sidebar-nav left-sidebar-menu-pro">
                <ul class="metismenu" id="menu1">
<?php
}

function creditlab_staff_sidebar_close(): void
{
    ?>
                </ul>
            </nav>
        </div>
    </nav>
</div>
<div class="cl-sidebar-backdrop" id="sidebarBackdrop"></div>
<?php
    creditlab_staff_sidebar_mobile_script();
}

function creditlab_staff_sidebar_mobile_script(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    ?>
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
        if (e.target.closest('#sidebarCloseBtn')) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileNav();
            return;
        }
        if (e.target.closest('#sidebarBackdrop')) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileNav();
            return;
        }
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
        if (e.target.closest('#mobileSearchClose')) {
            e.preventDefault();
            e.stopPropagation();
            var searchBar2 = document.getElementById('mobileSearchBar');
            if (searchBar2) searchBar2.classList.remove('active');
            return;
        }
        if (window.innerWidth <= 991 && e.target.closest('.sidebar-nav a')) {
            closeMobileNav();
        }
    }, true);
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) closeMobileNav();
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
<?php
}

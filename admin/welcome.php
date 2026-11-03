<div class="all-content-wrapper">
    <div class="header-advance-area">
        <div class="header-top-area">
            <div class="cl-top-header-container">
                <div class="cl-header-left">
                    <button type="button" id="sidebarCollapse" class="cl-hamburger-btn" title="Toggle Navigation" aria-label="Toggle Navigation">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <a href="index.php" class="cl-mobile-brand" title="CreditLab Admin">
                        <img src="/logo.svg" alt="CreditLab" class="cl-mobile-brand-logo">
                        <span class="cl-mobile-brand-title">CreditLab</span>
                    </a>
                    <div class="cl-desktop-search">
                        <form class="header-search-form" action="sea.php" method="get" target="_blank">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input type="text" name="sea" class="form-control" placeholder="Search UID, name, mobile, PAN, loan ID..." autocomplete="off">
                        </form>
                    </div>
                </div>

                <div class="cl-header-right">
                    <button type="button" class="cl-mobile-search-toggle" id="mobileSearchToggle" title="Search" aria-label="Toggle Search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <button type="button" class="cl-help-btn" id="clHelpBtn" title="Color & Symbol Guide (?)" aria-label="Admin Guide">
                        <i class="fa-solid fa-circle-question"></i>
                        <span class="hidden-xs">Guide</span>
                    </button>
                    <a href="<?=getAppUrl()?>/" class="header-view-site" target="_blank" title="View Public Website">
                        <span>View Site</span>
                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i>
                    </a>
                    <div class="dropdown header-user-dropdown">
                        <a href="#" data-toggle="dropdown" role="button" aria-expanded="false" class="header-user-btn nav-link dropdown-toggle">
                            <div class="header-user-avatar">
                                <?= strtoupper(substr(trim($user_name ?? 'A'), 0, 1)) ?>
                            </div>
                            <span class="header-user-name"><?=$user_name;?></span>
                            <i class="fa-solid fa-chevron-down" style="font-size: 10px; color: var(--cl-slate-400);"></i>
                        </a>
                        <ul role="menu" class="dropdown-header-top author-log dropdown-menu dropdown-menu-right animated zoomIn">
                            <li class="user-dropdown-header">
                                <div class="user-dropdown-caption">Signed in as</div>
                                <div class="user-dropdown-user"><?= htmlspecialchars($admin ?? $user_email ?? $user_name) ?></div>
                            </li>
                            <li>
                                <a href="changepassword.php"><i class="fa-solid fa-key" style="width: 16px;"></i> Change password</a>
                            </li>
                            <li>
                                <a href="settings.php"><i class="fa-solid fa-gear" style="width: 16px;"></i> System Settings</a>
                            </li>
                            <li class="divider" style="margin: 4px 0;"></li>
                            <li>
                                <a href="logout.php" style="color: var(--cl-danger-text) !important;"><i class="fa-solid fa-right-from-bracket" style="width: 16px;"></i> Log Out</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Expandable Mobile Search Dropdown Bar -->
            <div class="cl-mobile-search-bar" id="mobileSearchBar">
                <form class="cl-mobile-search-form" action="sea.php" method="get" target="_blank">
                    <i class="fa-solid fa-magnifying-glass cl-mobile-search-icon"></i>
                    <input type="text" name="sea" class="cl-mobile-search-input" placeholder="Search UID, name, phone, PAN, loan..." autocomplete="off">
                    <button type="button" class="cl-mobile-search-close" id="mobileSearchClose" aria-label="Close search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </form>
            </div>
        </div>
<?= creditlab_render_help_modal() ?>

<div class="all-content-wrapper">
    <div class="container-fluid hidden-xs hidden-sm" style="display:none;">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="logo-pro">
                    <h1>Creditlab</h1>
                </div>
            </div>
        </div>
    </div>
    <div class="header-advance-area">
        <div class="header-top-area">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="header-top-wraper">
                            <div class="row header-top-inner">
                                <div class="col-lg-7 col-md-7 hidden-xs hidden-sm" style="display:flex; align-items:center; gap:14px;">
                                    <div class="menu-switcher-pro">
                                        <button type="button" id="sidebarCollapse" class="btn bar-button-pro header-drl-controller-btn" title="Toggle Sidebar">
                                            <i class="fa-solid fa-bars"></i>
                                        </button>
                                    </div>
                                    <div class="header-top-menu tabl-d-n">
                                        <form class="header-search-form" action="sea.php" method="get" target="_blank">
                                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                                            <input type="text" name="sea" class="form-control" placeholder="Search UID, name, mobile, PAN, loan ID..." autocomplete="off">
                                        </form>
                                    </div>
                                </div>
                                <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12 header-right-col">
                                    <div class="header-right-info">
                                        <a href="<?=getAppUrl()?>/" class="header-view-site" target="_blank">
                                            <span>View Site</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i>
                                        </a>
                                        <ul class="nav navbar-nav mai-top-nav header-right-menu">
                                            <li class="nav-item">
                                                <a href="#" data-toggle="dropdown" role="button" aria-expanded="false" class="header-user-btn nav-link dropdown-toggle">
                                                    <div class="header-user-avatar">
                                                        <?= strtoupper(substr(trim($user_name ?? 'A'), 0, 1)) ?>
                                                    </div>
                                                    <span class="header-user-name"><?=$user_name;?></span>
                                                    <i class="fa-solid fa-chevron-down" style="font-size: 10px; color: var(--cl-slate-400);"></i>
                                                </a>
                                                <ul role="menu" class="dropdown-header-top author-log dropdown-menu animated zoomIn">
                                                    <li style="padding: 6px 12px; border-bottom: 1px solid var(--cl-slate-100); margin-bottom: 4px;">
                                                        <div style="font-size: 11px; text-transform: uppercase; color: var(--cl-slate-400); font-weight: 700; letter-spacing: 0.04em;">Signed in as</div>
                                                        <div style="font-size: 12.5px; font-weight: 600; color: var(--cl-slate-800); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;"><?= htmlspecialchars($admin ?? $user_email ?? $user_name) ?></div>
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
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <form class="header-mobile-search" action="sea.php" method="get" target="_blank">
                                <input type="text" name="sea" placeholder="Search users, loans, mobile..." autocomplete="off">
                                <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

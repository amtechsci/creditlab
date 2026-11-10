<?php
require_once __DIR__ . '/../lib/staff_portal_ui.php';

creditlab_staff_sidebar_open('AGENCY');
?>
                    <li class="sidebar-section-title">Agency</li>
                    <li class="<?= creditlab_staff_nav_active('index.php') ?>">
                        <a href="index.php">
                            <i class="fa-solid fa-building"></i>
                            <span class="mini-click-non"><?= htmlspecialchars($agency_admin_agency_name ?? 'Default list') ?></span>
                        </a>
                    </li>
                    <li class="<?= creditlab_staff_nav_active('sea.php') ?>">
                        <a href="sea.php">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span class="mini-click-non">Search</span>
                        </a>
                    </li>
<?php
creditlab_staff_sidebar_close();

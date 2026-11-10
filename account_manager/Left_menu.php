<?php
require_once __DIR__ . '/../lib/staff_portal_ui.php';

$am_count_q = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='account manager'");
$am_count = $am_count_q ? townum($am_count_q) : 0;
$part_count_q = towquery("SELECT DISTINCT loan.uid FROM loan LEFT JOIN user ON loan.uid = user.id WHERE loan.advance_amount > 0");
$part_count = $part_count_q ? townum($part_count_q) : 0;

creditlab_staff_sidebar_open('ACCOUNT MGR');
?>
                    <li class="sidebar-section-title">Collections</li>
                    <li class="<?= creditlab_staff_nav_active('index.php') ?>">
                        <a href="index.php">
                            <i class="fa-solid fa-briefcase"></i>
                            <span class="mini-click-non">Account Manager</span>
                            <?php if ($am_count > 0) { ?>
                            <span class="nav-count-badge"><?= (int) $am_count ?></span>
                            <?php } ?>
                        </a>
                    </li>
                    <li class="<?= creditlab_staff_nav_active('part_payment.php') ?>">
                        <a href="part_payment.php">
                            <i class="fa-solid fa-money-bill-wave"></i>
                            <span class="mini-click-non">Part Payment</span>
                            <?php if ($part_count > 0) { ?>
                            <span class="nav-count-badge"><?= (int) $part_count ?></span>
                            <?php } ?>
                        </a>
                    </li>
                    <li class="<?= creditlab_staff_nav_active('dynamic_search.php') ?>">
                        <a href="dynamic_search.php">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span class="mini-click-non">Dynamic Search</span>
                        </a>
                    </li>
<?php
creditlab_staff_sidebar_close();

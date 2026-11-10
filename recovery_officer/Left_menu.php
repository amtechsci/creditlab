<?php
require_once __DIR__ . '/../lib/staff_portal_ui.php';

$ro_count_q = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='recovery officer'");
$ro_count = $ro_count_q ? townum($ro_count_q) : 0;
$roStaffId = isset($user_id) ? (int) $user_id : 0;
$part_count = 0;
if ($roStaffId > 0) {
    $part_count_q = towquery(
        "SELECT COUNT(DISTINCT loan.uid) AS c FROM loan INNER JOIN user ON loan.uid = user.id
        WHERE CAST(loan.advance_amount AS DECIMAL(12,2)) > 0 AND user.assign_recovery_officer = $roStaffId"
    );
    if ($part_count_q && ($partRow = towfetch($part_count_q))) {
        $part_count = (int) ($partRow['c'] ?? 0);
    }
}

creditlab_staff_sidebar_open('RECOVERY');
?>
                    <li class="sidebar-section-title">Recovery</li>
                    <li class="<?= creditlab_staff_nav_active('index.php') ?>">
                        <a href="index.php">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span class="mini-click-non">Recovery Officer</span>
                            <?php if ($ro_count > 0) { ?>
                            <span class="nav-count-badge"><?= (int) $ro_count ?></span>
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

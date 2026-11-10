<?php
if ($user_type == 1) {
    function maskEmail($email)
    {
        return $email;
    }
    function maskPhone($phone)
    {
        return $phone;
    }
} else {
    function maskEmail($email)
    {
        $email_parts = explode('@', $email);
        $local_part = $email_parts[0];
        $domain = $email_parts[1] ?? '';
        $masked_local = substr($local_part, 0, 3) . str_repeat('*', max(0, strlen($local_part) - 3));
        return $masked_local . '@' . $domain;
    }
    function maskPhone($phone)
    {
        return substr($phone, 0, 2) . str_repeat('*', max(0, strlen($phone) - 4)) . substr($phone, -2);
    }
}

require_once __DIR__ . '/../lib/staff_portal_ui.php';

$appicount = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='pending'");
$appi_n = $appicount ? townum($appicount) : 0;
$follow_up = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='follow up'");
$follow_n = $follow_up ? townum($follow_up) : 0;
$disbursal = towquery("SELECT loan_apply.*, user.sloan FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND sloan=0");
$disb_n = $disbursal ? townum($disbursal) : 0;
$rldcount = towquery("SELECT DISTINCT uid FROM loan_apply LEFT JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='disbursal' AND user.sloan > 0");
$rld_n = $rldcount ? townum($rldcount) : 0;

creditlab_staff_sidebar_open('VERIFY');
?>
                    <li class="sidebar-section-title">Underwriting</li>
                    <?php if ($user_type == 1) { ?>
                    <li class="<?= creditlab_staff_nav_active('dynamic_search.php') ?>">
                        <a href="dynamic_search.php">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span class="mini-click-non">Dynamic Search</span>
                        </a>
                    </li>
                    <?php } else { ?>
                    <li class="<?= creditlab_staff_nav_active('apnlt.php') ?>">
                        <a href="apnlt.php">
                            <i class="fa-solid fa-user-minus"></i>
                            <span class="mini-click-non">Existing — no loan</span>
                        </a>
                    </li>
                    <?php } ?>
                    <li class="<?= creditlab_staff_nav_active('newloan.php') ?>">
                        <a href="newloan.php">
                            <i class="fa-solid fa-file-circle-plus"></i>
                            <span class="mini-click-non">Applied Loans</span>
                            <?php if ($appi_n > 0) { ?><span class="nav-count-badge"><?= (int) $appi_n ?></span><?php } ?>
                        </a>
                    </li>
                    <li class="<?= creditlab_staff_nav_active('follow_up.php') ?>">
                        <a href="follow_up.php">
                            <i class="fa-solid fa-phone-volume"></i>
                            <span class="mini-click-non">Follow Up</span>
                            <?php if ($follow_n > 0) { ?><span class="nav-count-badge"><?= (int) $follow_n ?></span><?php } ?>
                        </a>
                    </li>
                    <li class="<?= creditlab_staff_nav_active('disbursal.php') ?>">
                        <a href="disbursal.php">
                            <i class="fa-solid fa-circle-check"></i>
                            <span class="mini-click-non">Disbursal</span>
                            <?php if ($disb_n > 0) { ?><span class="nav-count-badge"><?= (int) $disb_n ?></span><?php } ?>
                        </a>
                    </li>
                    <li class="<?= creditlab_staff_nav_active('repeat_loan_d.php') ?>">
                        <a href="repeat_loan_d.php">
                            <i class="fa-solid fa-rotate"></i>
                            <span class="mini-click-non">Repeat Loan D</span>
                            <?php if ($rld_n > 0) { ?><span class="nav-count-badge"><?= (int) $rld_n ?></span><?php } ?>
                        </a>
                    </li>
<?php
creditlab_staff_sidebar_close();

<?php
include '../db.php';
require_once __DIR__ . '/../lib/admin_ui.php';
$search = towreal($_POST['search']);
$seausersquery = towquery("SELECT * FROM `user` WHERE `rcid` LIKE '%$search%' OR `name` LIKE '%$search%' OR `mobile` LIKE '%$search%' OR `altmobile` LIKE '%$search%' OR `email` LIKE '%$search%' OR `altemail` LIKE '%$search%' OR `pan` LIKE '%$search%' OR `account_no` LIKE '%$search%' OR `aadhar` LIKE '%$search%' LIMIT 100");
?>
<table class="table table-bordered" id="loan_history_datatable">
    <thead>
        <tr>
            <th>RCID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Mobile</th>
            <th>Status</th>
            <th style="text-align:right;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($seausersquery && townum($seausersquery) > 0): ?>
            <?php while($sealoanfetch = towfetch($seausersquery)): extract($sealoanfetch, EXTR_PREFIX_ALL, "users"); ?>
                <tr>
                    <td data-title="RCID">
                        <strong style="color:var(--cl-primary);"><?= creditlab_dash_text($users_rcid ?? '') ?></strong>
                    </td>
                    <td data-title="Name">
                        <span style="font-weight:600; color:var(--cl-slate-800);">
                            <?= creditlab_dash_text($users_name ?? ''); ?>
                        </span>
                        <?php
                            if (!empty($users_loan)) { echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; }
                            if (!empty($users_sloan)) { echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; }
                        ?>
                    </td>
                    <td data-title="Email" style="color:var(--cl-slate-600);"><?= creditlab_dash_text($users_email ?? '') ?></td>
                    <td data-title="Mobile" style="font-family:monospace; font-size:12.5px;"><?= creditlab_dash_text($users_mobile ?? '') ?></td>
                    <td data-title="Status"><?= creditlab_status_badge($users_status ?? '') ?></td>
                    <td data-title="Actions" style="text-align:right;">
                        <a class="btn btn-xs btn-primary" href="profile.php?id=<?= (int) $users_id ?>" target="_blank" style="padding:4px 10px;">
                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:30px; color:var(--cl-slate-400);">
                    No matching users found for "<?= htmlspecialchars($search) ?>"
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

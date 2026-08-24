<?php
include '../db.php';
require_once __DIR__ . '/../lib/admin_ui.php';
$search = towreal($_POST['search']);
$seausersquery = towquery("SELECT * FROM `user` WHERE `rcid` LIKE '%$search%' OR `name` LIKE '%$search%' OR `mobile` LIKE '%$search%' OR `altmobile` LIKE '%$search%' OR `email` LIKE '%$search%' OR `altemail` LIKE '%$search%' OR `pan` LIKE '%$search%' OR `account_no` LIKE '%$search%' OR `aadhar` LIKE '%$search%'");
?>
<table class="table table-bordered" id="loan_history_datatable">
        <thead>
            <tr>
                                        <th>RCID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
        </thead>
        <tbody>
                                   <?php while($sealoanfetch = towfetch($seausersquery)){ extract($sealoanfetch,EXTR_PREFIX_ALL,"users"); ?>
                                    <tr>
                                        <td data-title="CID"><?= creditlab_dash_text($users_rcid ?? '') ?></td>
                                        <td data-title="Name"><?php
                                            echo creditlab_dash_text($users_name ?? '');
                                            if (!empty($users_loan)) { echo "<span style='color:#dc2626'>#</span>"; }
                                            if (!empty($users_sloan)) { echo "<span style='color:#dc2626'>@</span>"; }
                                        ?></td>
                                        <td data-title="Email"><?= creditlab_dash_text($users_email ?? '') ?></td>
                                        <td data-title="Mobile"><?= creditlab_dash_text($users_mobile ?? '') ?></td>
                                        <td data-title="Status"><?= creditlab_status_badge($users_status ?? '') ?></td>
                                        <td data-title="Actions"><a class="btn btn-xs btn-primary" href="profile.php?id=<?= (int) $users_id ?>">View</a></td>
                                    </tr>
                                <?php } ?>
            </tbody>
    </table>

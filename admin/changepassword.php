<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/auth.php';

$alert_msg = '';
$alert_type = '';

if(isset($_POST['submit'])){
    $oldpass = towreal($_POST['password'] ?? '');
    $newpass = towreal($_POST['newpassword'] ?? '');
    if(creditlab_verify_password($oldpass, $user_password)){
        $hash = creditlab_hash_password($newpass);
        towquery("UPDATE user SET password='$hash' WHERE email='$user_email'");
        $alert_msg = 'Password successfully updated!';
        $alert_type = 'success';
    } else {
        $alert_msg = 'Old password does not match current password.';
        $alert_type = 'danger';
    }
}
?>
<body>
<?php
include_once 'Left_menu.php';
include_once 'welcome.php';
include_once 'm_menu.php';
?>
    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <ul class="breadcome-menu">
                        <li><a href="index.php"><i class="fa-solid fa-house" style="font-size:12px;"></i> Admin</a> <span class="bread-slash">/</span></li>
                        <li><span class="bread-blod">Change Password</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="single-pro-review-area mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-6 col-md-8 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
                        <div class="cl-card-header">
                            <div class="cl-card-header-left">
                                <h2 class="cl-card-header-title">
                                    <i class="fa-solid fa-key" style="color:var(--cl-brand); font-size:17px;"></i>
                                    Change Admin Password
                                </h2>
                                <p class="cl-card-header-desc">Update your staff account access credentials</p>
                            </div>
                        </div>

                        <?php if ($alert_msg): ?>
                            <div class="alert alert-<?= $alert_type ?>" style="margin-bottom:20px; font-weight:600;">
                                <i class="fa-solid <?= $alert_type == 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                                <?= htmlspecialchars($alert_msg) ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="post" style="max-width:440px;">
                            <div class="form-group" style="margin-bottom:16px;">
                                <label style="font-size:13px; font-weight:600; color:var(--cl-slate-700); margin-bottom:6px; display:block;">Current Password</label>
                                <input name="password" type="password" class="form-control" placeholder="Enter current password" required style="height:40px; border-radius:6px; border:1px solid var(--cl-slate-300);">
                            </div>
                            <div class="form-group" style="margin-bottom:20px;">
                                <label style="font-size:13px; font-weight:600; color:var(--cl-slate-700); margin-bottom:6px; display:block;">New Password</label>
                                <input name="newpassword" type="password" class="form-control" placeholder="Enter new password" required style="height:40px; border-radius:6px; border:1px solid var(--cl-slate-300);">
                            </div>
                            <div>
                                <button name="submit" type="submit" class="btn btn-primary" style="padding:9px 24px; font-weight:600;">
                                    <i class="fa-solid fa-lock" style="margin-right:6px;"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
include_once 'foot.php';
?>
</body>
</html>

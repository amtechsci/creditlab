<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/auth.php';

$allowed_tabs = ['users', 'agencies'];
$active_tab = isset($_GET['tab']) ? strtolower(trim((string) $_GET['tab'])) : 'users';
if (!in_array($active_tab, $allowed_tabs, true)) {
    $active_tab = 'users';
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_agency_admin'])) {
    $active_tab = 'agencies';
    $extract = towrealarray($_POST);
    extract($extract);
    $agency_id = (int) ($agency_id ?? 0);
    $name = trim($name ?? '');
    $email = trim($email ?? '');
    $password_plain = $password ?? '';
    if ($agency_id > 0 && $name !== '' && $email !== '' && $password_plain !== '') {
        $hashEsc = towreal(creditlab_hash_password($password_plain));
        $chk = towquery("SELECT id FROM agency_admin WHERE email='" . towreal($email) . "' LIMIT 1");
        if ($chk && townum($chk) > 0) {
            $message = 'Email already exists.';
        } else {
            towquery("INSERT INTO agency_admin (agency_id, name, email, password, active) VALUES ($agency_id, '" . towreal($name) . "', '" . towreal($email) . "', '$hashEsc', 1)");
            $message = 'Agency admin created.';
        }
    } else {
        $message = 'Fill all fields.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_agency'])) {
    $active_tab = 'agencies';
    $name = trim(towreal($_POST['agency_name'] ?? ''));
    if ($name !== '') {
        $exists = towquery("SELECT id FROM agency WHERE name='$name' LIMIT 1");
        if ($exists && townum($exists) > 0) {
            $message = 'Agency name already exists.';
        } else {
            towquery("INSERT INTO agency (name, active) VALUES ('$name', 1)");
            $message = 'Agency created.';
        }
    }
}

if (isset($_POST['submit'])) {
    $active_tab = 'users';
    $extract = towrealarray($_POST);
    extract($extract);
    if ($emp_type == "account_manager") {
        $reg_date = date('Y-m-d H:i:s');
        towquery("INSERT INTO `account_manager`(`name`, `email`, `mobile`, `password`, `reg_date`) VALUES ('$name','$email','$mobile','$password','$reg_date')");
        print_r("<script>alert('User Added'); window.location.replace('add_user.php?tab=users');</script>");
        exit;
    } elseif ($emp_type == "recovery_officer") {
        $reg_date = date('Y-m-d H:i:s');
        towquery("INSERT INTO `recovery_officer`(`name`, `email`, `mobile`, `password`, `reg_date`) VALUES ('$name','$email','$mobile','$password','$reg_date')");
        print_r("<script>alert('User Added'); window.location.replace('add_user.php?tab=users');</script>");
        exit;
    } elseif ($emp_type == "verify_user") {
        $reg_date = date('Y-m-d H:i:s');
        towquery("INSERT INTO `verify_user`(`name`, `email`, `mobile`, `password`, `reg_date`) VALUES ('$name','$email','$mobile','$password','$reg_date')");
        print_r("<script>alert('User Added'); window.location.replace('add_user.php?tab=users');</script>");
        exit;
    } elseif ($emp_type == "agency_admin") {
        $agency_id = (int) ($agency_id ?? 0);
        if ($agency_id <= 0 || trim($name ?? '') === '' || trim($email ?? '') === '' || ($password ?? '') === '') {
            print_r("<script>alert('Select agency and fill name, email, and password'); window.location.replace('add_user.php?tab=users');</script>");
            exit;
        }
        $chk = towquery("SELECT id FROM agency_admin WHERE email='" . towreal($email) . "' LIMIT 1");
        if ($chk && townum($chk) > 0) {
            print_r("<script>alert('Email already exists for a recovery admin'); window.location.replace('add_user.php?tab=users');</script>");
            exit;
        }
        $hashEsc = towreal(creditlab_hash_password($password));
        towquery("INSERT INTO agency_admin (agency_id, name, email, password, active) VALUES ($agency_id, '" . towreal($name) . "', '" . towreal($email) . "', '$hashEsc', 1)");
        print_r("<script>alert('Recovery admin created'); window.location.replace('add_user.php?tab=users');</script>");
        exit;
    } else {
        $rcid = "RC" . date('ymdHis');
        $reg_date = date('Y-m-d H:i:s');
        towquery("INSERT INTO `user`(`rcid`, `name`, `email`, `mobile` `password`, `active`, `verify`, `otp`, `validation`, `reg_date`, `status`, `document_password`, `loan_limit`, `assign_account_manager`, `assign_recovery_officer`, `star_member`) VALUES ('$rcid','$name','$email','$mobile','$password',1,0,1111,'','$reg_date','waiting','pan no password pan#aadhar no password aadhar#aadha2 no password aadha2#salary no password salary#bank no password bank#address no password address#bank2 no password bank2#bank3 no password bank3',10000,1,1,2)");
    }
}

$agencies = [];
$agencyQuery = towquery('SELECT id, name FROM agency WHERE active=1 ORDER BY name');
if ($agencyQuery) {
    while ($row = towfetch($agencyQuery)) {
        $agencies[] = $row;
    }
}

$all_agencies = [];
$aq = towquery('SELECT * FROM agency ORDER BY name');
if ($aq) {
    while ($r = towfetch($aq)) {
        $all_agencies[] = $r;
    }
}

$admins = [];
$dq = towquery('SELECT agency_admin.*, agency.name AS agency_name FROM agency_admin INNER JOIN agency ON agency.id = agency_admin.agency_id ORDER BY agency_admin.id DESC');
if ($dq) {
    while ($r = towfetch($dq)) {
        $admins[] = $r;
    }
}
?>
<body>
<?php include_once 'Left_menu.php'; include_once 'welcome.php'; include_once 'm_menu.php'; ?>

            <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <h2 style="margin:0 0 6px;"><i class="fa fa-users"></i> Users</h2>
                                <p style="margin:0;color:#666;">Staff accounts and agencies</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .settings-nav.nav-tabs {
                display: flex;
                flex-wrap: wrap;
                margin: 0 0 22px;
                padding: 0;
                list-style: none;
                border: 0;
                border-bottom: 2px solid #e8ecef;
                background: #fff;
            }
            .settings-nav > li { float: none; margin: 0; }
            .settings-nav > li > a {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 14px 22px;
                color: #6b7280;
                font-weight: 600;
                font-size: 15px;
                text-decoration: none;
                border: 0 !important;
                border-bottom: 3px solid transparent !important;
                margin-bottom: -2px;
                background: transparent !important;
                border-radius: 0;
            }
            .settings-nav > li > a:hover,
            .settings-nav > li > a:focus {
                background: transparent !important;
                color: #00a57d;
                text-decoration: none;
            }
            .settings-nav > li.active > a,
            .settings-nav > li.active > a:hover,
            .settings-nav > li.active > a:focus {
                color: #00a57d;
                background: transparent !important;
                border-bottom-color: #00c195 !important;
            }
            .users-panel {
                background: #fff;
                padding: 20px;
                margin-bottom: 20px;
                border: 1px solid #e7e7e7;
                border-radius: 8px;
            }
        </style>

        <div class="single-pro-review-area mt-t-30 mg-b-15">
            <div class="container-fluid">
                <ul class="nav nav-tabs settings-nav">
                    <li class="<?= $active_tab === 'users' ? 'active' : '' ?>">
                        <a href="add_user.php?tab=users"><i class="fa fa-user-plus"></i> Add User</a>
                    </li>
                    <li class="<?= $active_tab === 'agencies' ? 'active' : '' ?>">
                        <a href="add_user.php?tab=agencies"><i class="fa fa-building"></i> Agencies</a>
                    </li>
                </ul>

                <?php if ($active_tab === 'users'): ?>
                    <div class="users-panel">
                        <h4>Create employee</h4>
                        <form action="add_user.php?tab=users" method="post" id="createEmployeeForm">
                            <select name="emp_type" id="emp_type" class="form-control" style="margin-bottom:10px;">
                                <option value="account_manager">account manager</option>
                                <option value="recovery_officer">recovery officer</option>
                                <option value="agency_admin">recovery admin (agency)</option>
                                <option value="verify_user">Verify User</option>
                                <option value="user">User</option>
                            </select>
                            <div id="agencyFields" style="display:none; margin-bottom:10px;">
                                <select name="agency_id" id="agency_id" class="form-control">
                                    <option value="">Select agency</option>
                                    <?php foreach ($agencies as $ag) { ?>
                                    <option value="<?= (int) $ag['id'] ?>"><?= htmlspecialchars($ag['name']) ?></option>
                                    <?php } ?>
                                </select>
                                <?php if ($agencies === []) { ?>
                                <p class="text-muted" style="margin-top:8px;">No agencies yet. <a href="add_user.php?tab=agencies">Create an agency first</a>.</p>
                                <?php } ?>
                            </div>
                            <input name="name" type="text" class="form-control" placeholder="Name" required style="margin-bottom:10px;">
                            <input name="email" type="email" class="form-control" placeholder="Email" required style="margin-bottom:10px;">
                            <input name="mobile" id="mobileField" type="text" class="form-control" placeholder="Mobile" style="margin-bottom:10px;">
                            <input name="password" type="text" class="form-control" placeholder="Password" required style="margin-bottom:10px;">
                            <input name="submit" type="submit" class="btn btn-success" value="Create">
                        </form>
                    </div>

                    <div class="users-panel">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>emp_type</th>
                                    <th>name</th>
                                    <th>email</th>
                                    <th>number</th>
                                    <th>password</th>
                                    <th></th>
                                </tr>
                                <?php
                                $a = towquery("SELECT * FROM `verify_user`");
                                $b = towquery("SELECT * FROM `account_manager`");
                                $c = towquery("SELECT * FROM `recovery_officer`");
                                $d = towquery("SELECT agency_admin.*, agency.name AS agency_name FROM agency_admin INNER JOIN agency ON agency.id = agency_admin.agency_id ORDER BY agency_admin.id DESC");
                                while ($aa = towfetch($a)) { ?>
                                <tr>
                                    <td><?php if ($aa['type'] == 1) { echo "Verify user"; } else { echo "NBFC"; } ?></td>
                                    <td><?= $aa['name']; ?></td>
                                    <td><?= $aa['email']; ?></td>
                                    <td><?= htmlspecialchars((string) ($aa['number'] ?? $aa['mobile'] ?? '')) ?></td>
                                    <td><?= $aa['password']; ?></td>
                                    <td><a href="deleteuser.php?id=<?= $aa['id']; ?>&type=verify_user">Delete</a></td>
                                </tr>
                                <?php } while ($bb = towfetch($b)) { ?>
                                <tr>
                                    <td>Account manager</td>
                                    <td><?= $bb['name']; ?></td>
                                    <td><?= $bb['email']; ?></td>
                                    <td><?= htmlspecialchars((string) ($bb['number'] ?? $bb['mobile'] ?? '')) ?></td>
                                    <td><?= $bb['password']; ?></td>
                                    <td><a href="deleteuser.php?id=<?= $bb['id']; ?>&type=account_manager">Delete</a></td>
                                </tr>
                                <?php } while ($cc = towfetch($c)) { ?>
                                <tr>
                                    <td>Recovery officer</td>
                                    <td><?= $cc['name']; ?></td>
                                    <td><?= $cc['email']; ?></td>
                                    <td><?= htmlspecialchars((string) ($cc['number'] ?? $cc['mobile'] ?? '')) ?></td>
                                    <td><?= $cc['password']; ?></td>
                                    <td><a href="deleteuser.php?id=<?= $cc['id']; ?>&type=recovery_officer">Delete</a></td>
                                </tr>
                                <?php } while ($dd = towfetch($d)) { ?>
                                <tr>
                                    <td>Recovery admin<?php if (!empty($dd['agency_name'])) { echo ' (' . htmlspecialchars($dd['agency_name']) . ')'; } ?></td>
                                    <td><?= htmlspecialchars($dd['name']) ?></td>
                                    <td><?= htmlspecialchars($dd['email']) ?></td>
                                    <td>—</td>
                                    <td>(hashed)</td>
                                    <td><a href="deleteuser.php?id=<?= (int) $dd['id'] ?>&type=agency_admin">Delete</a></td>
                                </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </div>

                <?php else: ?>
                    <?php if ($message) { ?><div class="alert alert-info"><?= htmlspecialchars($message) ?></div><?php } ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="users-panel">
                                <h4>Create agency</h4>
                                <form method="post" class="form-inline">
                                    <input type="text" name="agency_name" class="form-control" placeholder="Agency name" required>
                                    <button type="submit" name="create_agency" class="btn btn-success">Add agency</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="users-panel">
                                <h4>Create agency admin login</h4>
                                <form method="post">
                                    <select name="agency_id" class="form-control" required>
                                        <option value="">Select agency</option>
                                        <?php foreach ($all_agencies as $ag) { ?>
                                        <option value="<?= (int) $ag['id'] ?>"><?= htmlspecialchars($ag['name']) ?></option>
                                        <?php } ?>
                                    </select>
                                    <input type="text" name="name" class="form-control" placeholder="Name" required style="margin-top:8px;">
                                    <input type="email" name="email" class="form-control" placeholder="Email" required style="margin-top:8px;">
                                    <input type="password" name="password" class="form-control" placeholder="Password" required style="margin-top:8px;">
                                    <button type="submit" name="create_agency_admin" class="btn btn-success" style="margin-top:8px;">Create login</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="users-panel">
                        <h4>Agency admins</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead><tr><th>ID</th><th>Agency</th><th>Name</th><th>Email</th><th>Active</th></tr></thead>
                                <tbody>
                                    <?php foreach ($admins as $a) { ?>
                                    <tr>
                                        <td><?= (int) $a['id'] ?></td>
                                        <td><?= htmlspecialchars($a['agency_name']) ?></td>
                                        <td><?= htmlspecialchars($a['name']) ?></td>
                                        <td><?= htmlspecialchars($a['email']) ?></td>
                                        <td><?= (int) $a['active'] ? 'Yes' : 'No' ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted">Agency users log in at <code>/account/login.php</code> and are redirected to <code>/agency_admin/</code>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

<?php include_once 'foot.php'; ?>
<script>
(function () {
    var empType = document.getElementById('emp_type');
    var agencyFields = document.getElementById('agencyFields');
    var agencySelect = document.getElementById('agency_id');
    var mobileField = document.getElementById('mobileField');
    if (!empType) return;

    function syncEmployeeForm() {
        var isAgencyAdmin = empType.value === 'agency_admin';
        if (agencyFields) {
            agencyFields.style.display = isAgencyAdmin ? 'block' : 'none';
        }
        if (agencySelect) {
            agencySelect.required = isAgencyAdmin;
        }
        if (mobileField) {
            mobileField.required = !isAgencyAdmin;
            mobileField.style.display = isAgencyAdmin ? 'none' : 'block';
        }
    }

    empType.addEventListener('change', syncEmployeeForm);
    syncEmployeeForm();
})();
</script>
</body>
</html>

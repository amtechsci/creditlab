<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/auth.php';

if (empty($admin)) {
    header('Location: /account/login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_agency_admin'])) {
    $extract = towrealarray($_POST);
    extract($extract);
    $agency_id = (int) ($agency_id ?? 0);
    $name = trim($name ?? '');
    $email = trim($email ?? '');
    $password_plain = $password ?? '';
    if ($agency_id > 0 && $name !== '' && $email !== '' && $password_plain !== '') {
        $hash = creditlab_hash_password($password_plain);
        $hashEsc = towreal($hash);
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

$agencies = [];
$aq = towquery('SELECT * FROM agency ORDER BY name');
if ($aq) {
    while ($r = towfetch($aq)) {
        $agencies[] = $r;
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
<div class="container-fluid" style="padding:30px;">
    <h2>Agency &amp; agency admin users</h2>
    <?php if ($message) { ?><div class="alert alert-info"><?= htmlspecialchars($message) ?></div><?php } ?>

    <div class="row">
        <div class="col-md-6">
            <h4>Create agency</h4>
            <form method="post" class="form-inline">
                <input type="text" name="agency_name" class="form-control" placeholder="Agency name" required>
                <button type="submit" name="create_agency" class="btn btn-primary">Add agency</button>
            </form>
        </div>
        <div class="col-md-6">
            <h4>Create agency admin login</h4>
            <form method="post">
                <select name="agency_id" class="form-control" required>
                    <option value="">Select agency</option>
                    <?php foreach ($agencies as $ag) { ?>
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

    <h4 style="margin-top:40px;">Agency admins</h4>
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
    <p class="text-muted">Agency users log in at <code>/account/login.php</code> and are redirected to <code>/agency_admin/</code>.</p>
</div>
<?php include_once 'foot.php'; ?>
</body>
</html>

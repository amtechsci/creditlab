<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/auth.php';

$agencies = [];
$agencyQuery = towquery('SELECT id, name FROM agency WHERE active=1 ORDER BY name');
if ($agencyQuery) {
    while ($row = towfetch($agencyQuery)) {
        $agencies[] = $row;
    }
}

if(isset($_POST['submit'])){
    $extract = towrealarray($_POST);
    extract($extract);
    if($emp_type == "account_manager"){
        $rcid = "RC".date('ymdHis');
    $reg_date = date('Y-m-d H:i:s');
    $a = towquery("INSERT INTO `account_manager`(`name`, `email`, `mobile`, `password`, `reg_date`) VALUES ('$name','$email','$mobile','$password','$reg_date')");
        print_r("<script>alert('User Added'); window.location.replace('add_user.php');</script>");
    }elseif($emp_type == "recovery_officer"){
        $rcid = "RC".date('ymdHis');
    $reg_date = date('Y-m-d H:i:s');
    $a = towquery("INSERT INTO `recovery_officer`(`name`, `email`, `mobile`, `password`, `reg_date`) VALUES ('$name','$email','$mobile','$password','$reg_date')");
        print_r("<script>alert('User Added'); window.location.replace('add_user.php');</script>");
    }elseif($emp_type == "verify_user"){
        $rcid = "RC".date('ymdHis');
    $reg_date = date('Y-m-d H:i:s');
    $a = towquery("INSERT INTO `verify_user`(`name`, `email`, `mobile`, `password`, `reg_date`) VALUES ('$name','$email','$mobile','$password','$reg_date')");
        print_r("<script>alert('User Added'); window.location.replace('add_user.php');</script>");
    }elseif($emp_type == "agency_admin"){
        $agency_id = (int) ($agency_id ?? 0);
        if ($agency_id <= 0 || trim($name ?? '') === '' || trim($email ?? '') === '' || ($password ?? '') === '') {
            print_r("<script>alert('Select agency and fill name, email, and password'); window.location.replace('add_user.php');</script>");
        } else {
            $chk = towquery("SELECT id FROM agency_admin WHERE email='" . towreal($email) . "' LIMIT 1");
            if ($chk && townum($chk) > 0) {
                print_r("<script>alert('Email already exists for a recovery admin'); window.location.replace('add_user.php');</script>");
            } else {
                $hashEsc = towreal(creditlab_hash_password($password));
                towquery("INSERT INTO agency_admin (agency_id, name, email, password, active) VALUES ($agency_id, '" . towreal($name) . "', '" . towreal($email) . "', '$hashEsc', 1)");
                print_r("<script>alert('Recovery admin created'); window.location.replace('add_user.php');</script>");
            }
        }
    }else{
        $rcid = "RC".date('ymdHis');
        $a = towquery("INSERT INTO `user`(`rcid`, `name`, `email`, `mobile` `password`, `active`, `verify`, `otp`, `validation`, `reg_date`, `status`, `document_password`, `loan_limit`, `assign_account_manager`, `assign_recovery_officer`, `star_member`) VALUES ('$rcid','$name','$email','$mobile','$password',1,0,1111,'','$reg_date','waiting','pan no password pan#aadhar no password aadhar#aadha2 no password aadha2#salary no password salary#bank no password bank#address no password address#bank2 no password bank2#bank3 no password bank3',10000,1,1,2)");
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
                            <div class="breadcome-list">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <ul class="breadcome-menu">
                                            <li><a href="../user">Home</a> <span class="bread-slash">/</span>
                                            </li>
                                            <li><span class="bread-blod">Change password</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Single pro tab review Start-->
        <div class="single-pro-review-area mt-t-30 mg-b-15">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="product-payment-inner-st">
                            <ul id="myTabedu1" class="tab-review-design">
                                <li class="active"><a href="#description">Create Employee</a></li>
                                
                            </ul>
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade active in" id="description">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <div id="dropzone1" class="pro-ad">
                                                    <form action="" method="post" class="add-professors" id="createEmployeeForm">
                                                        <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                                            <div class="table-responsive">
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
                                                                    <p class="text-muted" style="margin-top:8px;">No agencies yet. <a href="agency_admins.php">Create an agency first</a>.</p>
                                                                    <?php } ?>
                                                                </div>
                                                                <input name="name" type="text" class="form-control" placeholder="Name" required style="margin-bottom:10px;">
                                                                <input name="email" type="email" class="form-control" placeholder="Email" required style="margin-bottom:10px;">
                                                                <input name="mobile" id="mobileField" type="text" class="form-control" placeholder="Mobile" style="margin-bottom:10px;">
                                                                <input name="password" type="text" class="form-control" placeholder="Password" required style="margin-bottom:10px;">
                                                                <input name="submit" type="submit" class="btn btn-success" value="Create">
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <table class="table table-bordered" style="margin-top:25px;">
                                                <tr>
                                                    <th>emp_type</th>
                                                    <th>name</th>
                                                    <th>email</th>
                                                    <th>number</th>
                                                    <th>password</th>
                                                </tr>
                                                <?php
                                                $a = towquery("SELECT * FROM `verify_user`");
                                                $b = towquery("SELECT * FROM `account_manager`");
                                                $c = towquery("SELECT * FROM `recovery_officer`");
                                                $d = towquery("SELECT agency_admin.*, agency.name AS agency_name FROM agency_admin INNER JOIN agency ON agency.id = agency_admin.agency_id ORDER BY agency_admin.id DESC");
                                                while($aa = towfetch($a)){ ?>
                                                <tr>
                                                    <th><?php if($aa['type'] == 1){echo "Verify user";}else{echo "NBFC";}?></th>
                                                    <th><?=$aa['name'];?></th>
                                                    <th><?=$aa['email'];?></th>
                                                    <th><?=$aa['number'];?></th>
                                                    <th><?=$aa['password'];?></th>
                                                    <th><a href="deleteuser.php?id=<?=$aa['id'];?>&type=verify_user">Delete</a></th>
                                                </tr>
                                                <?php } while($bb = towfetch($b)){ ?>
                                                <tr>
                                                    <th>Account manager</th>
                                                    <th><?=$bb['name'];?></th>
                                                    <th><?=$bb['email'];?></th>
                                                    <th><?=$bb['number'];?></th>
                                                    <th><?=$bb['password'];?></th>
                                                    <th><a href="deleteuser.php?id=<?=$bb['id'];?>&type=account_manager">Delete</a></th>
                                                </tr>
                                                <?php } while($cc = towfetch($c)){ ?>
                                                <tr>
                                                    <th>Recovery officer</th>
                                                    <th><?=$cc['name'];?></th>
                                                    <th><?=$cc['email'];?></th>
                                                    <th><?=$cc['number'];?></th>
                                                    <th><?=$cc['password'];?></th>
                                                    <th><a href="deleteuser.php?id=<?=$cc['id'];?>&type=recovery_officer">Delete</a></th>
                                                </tr>
                                                <?php } while($dd = towfetch($d)){ ?>
                                                <tr>
                                                    <th>Recovery admin<?php if (!empty($dd['agency_name'])) { echo ' (' . htmlspecialchars($dd['agency_name']) . ')'; } ?></th>
                                                    <th><?= htmlspecialchars($dd['name']) ?></th>
                                                    <th><?= htmlspecialchars($dd['email']) ?></th>
                                                    <th>—</th>
                                                    <th>(hashed)</th>
                                                    <th><a href="deleteuser.php?id=<?= (int) $dd['id'] ?>&type=agency_admin">Delete</a></th>
                                                </tr>
                                                <?php } ?>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                            
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       <?php
       include_once 'foot.php';
       ?>
<script>
(function () {
    var empType = document.getElementById('emp_type');
    var agencyFields = document.getElementById('agencyFields');
    var agencySelect = document.getElementById('agency_id');
    var mobileField = document.getElementById('mobileField');

    function syncEmployeeForm() {
        var isAgencyAdmin = empType.value === 'agency_admin';
        agencyFields.style.display = isAgencyAdmin ? 'block' : 'none';
        if (agencySelect) {
            agencySelect.required = isAgencyAdmin;
        }
        if (mobileField) {
            mobileField.required = !isAgencyAdmin;
            mobileField.style.display = isAgencyAdmin ? 'none' : 'block';
        }
    }

    if (empType) {
        empType.addEventListener('change', syncEmployeeForm);
        syncEmployeeForm();
    }
})();
</script>
</body>
</html>
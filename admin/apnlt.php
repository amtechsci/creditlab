<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/sms_limit_increase.php';
require_once __DIR__ . '/../lib/app_url.php';

$apnlt_flash = '';
$apnlt_flash_type = 'success';

function creditlab_apnlt_last_apply_date(int $uid): ?string
{
    $row = towfetch(towquery('SELECT apply_date FROM `loan_apply` WHERE uid=' . intval($uid) . ' AND apply_date IS NOT NULL AND apply_date != "" ORDER BY id DESC LIMIT 1'));
    if (!$row || empty($row['apply_date'])) {
        return null;
    }
    return (string) $row['apply_date'];
}

function creditlab_apnlt_eligible(?string $applyDate): bool
{
    if ($applyDate === null || $applyDate === '') {
        return false;
    }
    $ts = strtotime($applyDate);
    if ($ts === false) {
        return false;
    }
    $cutoff = strtotime(date('Y-m-d') . ' -30 days');
    return $ts >= $cutoff;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apnlt_action'])) {
    $action = (string) $_POST['apnlt_action'];
    $ids = [];
    // Single-row action takes priority over bulk checkboxes.
    if (!empty($_POST['user_id'])) {
        $ids[] = (int) $_POST['user_id'];
    } elseif (!empty($_POST['check']) && is_array($_POST['check'])) {
        foreach ($_POST['check'] as $id) {
            $ids[] = (int) $id;
        }
    }
    $ids = array_values(array_unique(array_filter($ids)));

    $okCount = 0;
    $skipCount = 0;
    $errCount = 0;

    foreach ($ids as $uid) {
        $user = towfetch(towquery("SELECT id, mobile, loan_limit, status, sloan FROM `user` WHERE id=$uid AND sloan > 0 AND status='cleared' LIMIT 1"));
        if (!$user) {
            $skipCount++;
            continue;
        }
        $lastApply = creditlab_apnlt_last_apply_date($uid);
        if (!creditlab_apnlt_eligible($lastApply)) {
            $skipCount++;
            continue;
        }

        if ($action === 'increase_limit') {
            global $db;
            $oldLimit = (float) ($user['loan_limit'] ?? 0);
            $newLimit = $oldLimit + 2000;
            $oldEsc = mysqli_real_escape_string($db, (string) $oldLimit);
            $newEsc = mysqli_real_escape_string($db, (string) $newLimit);
            $ok = towquery("UPDATE `user` SET `old_loan_limit`='$oldEsc', `loan_limit`='$newEsc', `limit_inc`=0 WHERE id=$uid");
            if ($ok) {
                $okCount++;
            } else {
                $errCount++;
            }
        } elseif ($action === 'send_limit_sms') {
            $limit = (float) ($user['loan_limit'] ?? 0);
            $sent = creditlab_send_limit_increase_sms((string) ($user['mobile'] ?? ''), $limit);
            if ($sent) {
                $okCount++;
            } else {
                $errCount++;
            }
        }
    }

    if ($action === 'increase_limit') {
        $apnlt_flash = "Limit +₹2000 applied to $okCount user(s). Skipped: $skipCount. Failed: $errCount.";
    } elseif ($action === 'send_limit_sms') {
        $apnlt_flash = "Limit SMS sent to $okCount user(s). Skipped: $skipCount. Failed: $errCount.";
    }
    if ($errCount > 0 || $skipCount > 0 && $okCount === 0) {
        $apnlt_flash_type = 'warning';
    }
}

if (isset($_GET['pageno'])) {
    $pageno = (int)$_GET['pageno'];
} else {
    $pageno = 1;
}
$no_of_records_per_page = 50;
$offset = ($pageno - 1) * $no_of_records_per_page;
$ress = mysqli_query($db, "SELECT * FROM user WHERE `sloan` > 0 AND status='cleared' ORDER BY user.id DESC");
$total_rows = mysqli_num_rows($ress);
$total_pages = ceil($total_rows / $no_of_records_per_page);
$usersquery = mysqli_query($db, "SELECT * FROM user WHERE `sloan` > 0 AND status='cleared' ORDER BY user.id DESC LIMIT $offset, $no_of_records_per_page");
?>
<body>
    <?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>
    <div class="breadcome-area">
        <div class="container-fluid">
            <ul class="breadcome-menu">
                <li><a href="index.php"><i class="fa-solid fa-house" style="font-size:12px;"></i> Admin</a> <span class="bread-slash">/</span></li>
                <li><a href="users.php">Users</a> <span class="bread-slash">/</span></li>
                <li><span class="bread-blod">Existing (No Loan)</span></li>
            </ul>
        </div>
    </div>
</div>

<div class="single-pro-review-area mg-b-15">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="product-payment-inner-st">
                    <div class="cl-card-header">
                        <div class="cl-card-header-left">
                            <h3 class="cl-card-header-title">
                                <i class="fa-solid fa-user-slash"></i> Existing Users (No Active Loan)
                                <span class="cl-badge-pill"><?= number_format($total_rows) ?></span>
                            </h3>
                            <p class="cl-card-header-desc">Cleared repeat borrowers currently eligible for instant repeat loans. Limit +₹2000 / SMS only when last loan applied date is within 30 days.</p>
                        </div>
                        <div class="cl-card-header-actions">
                            <a href="users.php" class="btn btn-primary" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                <i class="fa-solid fa-user-check"></i> Approved Users
                            </a>
                        </div>
                    </div>

                    <?php if ($apnlt_flash !== ''): ?>
                        <div class="alert alert-<?= $apnlt_flash_type === 'warning' ? 'warning' : 'success' ?>" style="margin:12px 16px;">
                            <?= htmlspecialchars($apnlt_flash) ?>
                        </div>
                    <?php endif; ?>

                    <?= creditlab_render_table_legend() ?>

                    <form method="post" id="apnltBulkForm" style="margin:0;">
                        <div style="padding:8px 16px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                            <button type="submit" name="apnlt_action" value="increase_limit" class="btn btn-sm btn-success" onclick="document.getElementById('apnlt_user_id').value=''; return confirm('Increase loan limit by ₹2000 for all selected eligible users?');">
                                <i class="fa-solid fa-plus"></i> + ₹2000 limit (selected)
                            </button>
                            <button type="submit" name="apnlt_action" value="send_limit_sms" class="btn btn-sm btn-primary" onclick="document.getElementById('apnlt_user_id').value=''; return confirm('Send limit-update SMS to all selected eligible users?');">
                                <i class="fa-solid fa-comment-sms"></i> Send limit SMS (selected)
                            </button>
                        </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="loan_history_datatable">
                            <thead>
                                <tr>                
                                    <th style="width:34px; text-align:center;"><input type="checkbox" id="selectAll"></th>   
                                    <th style="width:48px;">SL</th>
                                    <th>Borrower / CID</th>        
                                    <th>Mobile</th>    
                                    <th style="text-align:right;">Loan Limit</th>
                                    <th style="text-align:center;">Last loan applied</th>
                                    <th>Account Manager</th> 
                                    <th style="text-align:center;">Status</th>    
                                    <th style="text-align:right; min-width:220px;">Actions</th>     
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($usersquery && townum($usersquery) > 0): ?>
                                    <?php $ii = $offset + 1; while($loanfetch = towfetch($usersquery)): extract($loanfetch, EXTR_PREFIX_ALL, "users");
                                        $lastApply = creditlab_apnlt_last_apply_date((int) $users_id);
                                        $eligible = creditlab_apnlt_eligible($lastApply);
                                        $lastApplyLabel = '—';
                                        if ($lastApply) {
                                            $ts = strtotime($lastApply);
                                            $lastApplyLabel = $ts ? date('d M Y', $ts) : htmlspecialchars($lastApply);
                                        }
                                    ?>
                                        <tr>
                                            <td style="text-align:center;"><input type="checkbox" class="apnlt-row-check" name="check[]" value="<?=$users_id?>" <?= $eligible ? '' : 'disabled' ?>></td>   
                                            <td style="color:var(--cl-slate-500); font-size:12px;"><?=$ii?></td>
                                            <td data-title="Borrower">
                                                <div style="font-weight:600; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                    <?=creditlab_dash_text($users_name ?? '')?>
                                                    <?php if(!empty($users_loan)){ echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; } ?>
                                                    <?php if(!empty($users_sloan)){ echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; } ?>
                                                </div>
                                                <div style="margin-top:3px; font-size:11.5px; font-family:monospace; color:var(--cl-primary); font-weight:600;">
                                                    <?=creditlab_dash_text($users_rcid ?? '')?>
                                                </div>
                                            </td>
                                            <td data-title="Mobile" style="white-space:nowrap;">
                                                <div style="font-family:monospace; font-size:12.5px; font-weight:500; color:var(--cl-slate-800);">
                                                    <?=creditlab_dash_text($users_mobile ?? '')?>
                                                </div>
                                            </td>
                                            <td data-title="Loan Limit" style="text-align:right; font-weight:700; color:var(--cl-slate-900); white-space:nowrap;">
                                                <?=creditlab_currency($users_loan_limit ?? 0)?>
                                            </td>
                                            <td data-title="Last loan applied" style="text-align:center; white-space:nowrap; font-size:12px;">
                                                <?= $lastApplyLabel ?>
                                                <?php if ($eligible): ?>
                                                    <div style="font-size:10px; color:#15803d; font-weight:600;">Within 30 days</div>
                                                <?php elseif ($lastApply): ?>
                                                    <div style="font-size:10px; color:var(--cl-slate-400);">Older than 30 days</div>
                                                <?php endif; ?>
                                            </td>
                                            <td data-title="Account Manager" style="white-space:nowrap;">
                                                <?php 
                                                $account_manager = !empty($users_assign_account_manager) ? towfetch(towquery("SELECT `name` FROM `account_manager` WHERE `id`='$users_assign_account_manager'")) : null;
                                                echo !empty($account_manager['name']) ? htmlspecialchars($account_manager['name']) : '<span style="color:var(--cl-slate-400);">Not Assigned</span>';
                                                ?>
                                            </td>
                                            <td data-title="Status" style="text-align:center; white-space:nowrap;">
                                                <?= creditlab_status_badge($users_status ?? '') ?>
                                            </td>
                                            <td data-title="Actions" style="text-align:right;">
                                                <div style="display:flex; flex-wrap:wrap; gap:4px; justify-content:flex-end;">
                                                    <?php if ($eligible): ?>
                                                    <button type="submit" name="apnlt_action" value="increase_limit" class="btn btn-xs btn-success"
                                                            onclick="document.getElementById('apnlt_user_id').value='<?= (int)$users_id ?>'; return confirm('Increase loan limit by ₹2000 for this user?');"
                                                            style="padding:3px 8px;">
                                                        + ₹2000
                                                    </button>
                                                    <button type="submit" name="apnlt_action" value="send_limit_sms" class="btn btn-xs btn-primary"
                                                            onclick="document.getElementById('apnlt_user_id').value='<?= (int)$users_id ?>'; return confirm('Send limit-update SMS to this user?');"
                                                            style="padding:3px 8px;">
                                                        Limit SMS
                                                    </button>
                                                    <?php endif; ?>
                                                    <a class="btn btn-xs btn-default" href="profile.php?id=<?=$users_id?>" target="_blank" style="padding:3px 8px;">
                                                        View
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php $ii++; endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" style="text-align:center; padding:30px; color:var(--cl-slate-400);">No existing cleared users found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="user_id" id="apnlt_user_id" value="">
                    </form>

                    <div style="padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; border-top:1px solid var(--cl-slate-100);">
                        <div style="font-size:12.5px; color:var(--cl-slate-500);">
                            Showing <strong><?= min($total_rows, $offset + 1) ?></strong> to <strong><?= min($total_rows, $offset + $no_of_records_per_page) ?></strong> of <strong><?= number_format($total_rows) ?></strong> users
                        </div>
                        <?= creditlab_render_pagination($pageno, $total_pages, '?pageno=') ?>
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
    var master = document.getElementById('selectAll');
    if (!master) return;
    master.addEventListener('change', function () {
        document.querySelectorAll('.apnlt-row-check:not(:disabled)').forEach(function (cb) {
            cb.checked = master.checked;
        });
    });
})();
</script>
</body>
</html>

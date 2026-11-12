<?php
/**
 * Full customer dossier header (admin + account_manager profile).
 * Expects $userpro_* from profile.php; optional $limit_percentage_* , $userpro_enach_label.
 */
require_once __DIR__ . '/../lib/admin_ui.php';

$account_manager_row = towfetch(towquery(
    'SELECT * FROM `account_manager` WHERE id=' . (int) ($userpro_assign_account_manager ?? 0)
));
$recovery_officer_row = towfetch(towquery(
    'SELECT * FROM `recovery_officer` WHERE id=' . (int) ($userpro_assign_recovery_officer ?? 0)
));

$totalfls = towfetch(towquery(
    "SELECT SUM(`transaction_amount`) AS total FROM `transaction_details` WHERE NOT `transaction_flow`='creditlab To Customer' AND uid=" . (int) $userpro_id
));
$totalflss = (is_array($totalfls) && !empty($totalfls['total'])) ? (float) $totalfls['total'] : 0.0;

$member_label = 'Silver';
$member_bg = '#f1f5f9';
$member_fg = '#475569';
$m = (int) ($userpro_member ?? 0);
if ($m === 1) {
    $member_label = 'Gold';
    $member_bg = '#fef3c7';
    $member_fg = '#b45309';
} elseif ($m === 2) {
    $member_label = 'Diamond';
    $member_bg = '#e0f2fe';
    $member_fg = '#0369a1';
} elseif ($m === 3) {
    $member_label = 'Platinum';
    $member_bg = '#f3e8ff';
    $member_fg = '#7e22ce';
} elseif ($m === 4) {
    $member_label = 'RISKY';
    $member_bg = '#fee2e2';
    $member_fg = '#b91c1c';
}

$limit_percentage_style_attr = $limit_percentage_style_attr ?? '';
$limit_percentage_display = $limit_percentage_display ?? 'N/A';
$userpro_enach_label = $userpro_enach_label ?? '—';

// Verification Status (user.verify: 0 = unverified, 1 = verified, 3 = re-process, 4 = issue/not process)
$v_code = (int) ($userpro_verify ?? 0);
if ($v_code === 1) {
    $verify_badge = '<span style="color:#15803d; font-weight:600;"><i class="fa-solid fa-circle-check"></i> Approved</span>';
} elseif ($v_code === 3) {
    $verify_badge = '<span style="color:#d97706; font-weight:600;"><i class="fa-solid fa-triangle-exclamation"></i> Re-Process</span>';
} elseif ($v_code === 4) {
    $verify_badge = '<span style="color:#dc2626; font-weight:600;"><i class="fa-solid fa-circle-xmark"></i> Issue / Hold</span>';
} else {
    $verify_badge = '<span style="color:#64748b; font-weight:600;"><i class="fa-solid fa-clock"></i> Unverified</span>';
}

// Bank Check
$user_bank_row = towfetch(towquery(
    'SELECT * FROM `user_bank` WHERE uid=' . (int) $userpro_id . ' ORDER BY id DESC LIMIT 1'
));
if ($user_bank_row) {
    if ((int) ($user_bank_row['verify'] ?? 0) === 1) {
        $bank_check_badge = '<span style="color:#15803d; font-weight:600;"><i class="fa-solid fa-circle-check"></i> Verified</span>';
    } else {
        $bank_check_badge = '<span style="color:#d97706; font-weight:600;"><i class="fa-solid fa-clock"></i> Pending</span>';
    }
} else {
    $bank_check_badge = '<span style="color:#94a3b8;"><i class="fa-solid fa-circle-minus"></i> Not Added</span>';
}

// PAN & Aadhaar Check
$has_pan = !empty(trim((string) ($userpro_pan ?? '')));
$has_aadhar = !empty(trim((string) ($userpro_aadhar ?? '')));
if ($v_code === 1) {
    $pan_aadhar_badge = '<span style="color:#15803d; font-weight:600;"><i class="fa-solid fa-circle-check"></i> Verified</span>';
} elseif ($v_code === 4) {
    $pan_aadhar_badge = '<span style="color:#dc2626; font-weight:600;"><i class="fa-solid fa-circle-xmark"></i> Issue / Hold</span>';
} elseif ($has_pan && $has_aadhar) {
    $pan_aadhar_badge = '<span style="color:#0284c7; font-weight:600;"><i class="fa-solid fa-file-circle-check"></i> Complete (Review)</span>';
} elseif ($has_pan || $has_aadhar) {
    $pan_aadhar_badge = '<span style="color:#d97706; font-weight:600;"><i class="fa-solid fa-triangle-exclamation"></i> Partial (' . ($has_pan ? 'PAN' : 'Aadhaar') . ')</span>';
} else {
    $pan_aadhar_badge = '<span style="color:#94a3b8;"><i class="fa-solid fa-circle-minus"></i> Missing</span>';
}
?>
<div class="profile-dossier-card">
    <div class="profile-dossier-header">
        <div class="profile-user-main">
            <div class="profile-big-avatar">
                <?= strtoupper(substr(trim(($userpro_name ?? '') !== '' ? $userpro_name : 'U'), 0, 1)) ?>
            </div>
            <div class="profile-user-titles">
                <h2>
                    <?= htmlspecialchars((string) ($userpro_name ?? '')) ?>
                    <?php
                    if (!empty($userpro_loan)) {
                        echo '<span class="badge-modern badge-rejected" style="font-size:11px; vertical-align:middle; margin-left:6px;" title="Loan Active"><i class="fa-solid fa-bolt"></i> Active Loan</span>';
                    }
                    if (!empty($userpro_sloan)) {
                        echo '<span class="badge-modern badge-hold" style="font-size:11px; vertical-align:middle; margin-left:6px;" title="Special Loan"><i class="fa-solid fa-star"></i> Special Loan</span>';
                    }
                    ?>
                </h2>
                <div class="profile-tag-badges">
                    <span class="badge-modern" style="background:#e0f2fe; color:#0369a1; font-family:monospace; font-size:12px;">
                        <i class="fa-solid fa-id-card-clip" style="margin-right:4px;"></i>
                        <?= htmlspecialchars((string) ($userpro_rcid ?? '')) ?>
                    </span>
                    <?= creditlab_status_badge($userpro_status ?? '') ?>
                    <span class="badge-modern" style="background:<?= $member_bg ?>; color:<?= $member_fg ?>; font-weight:700;">
                        <i class="fa-solid fa-gem" style="margin-right:3px;"></i> <?= $member_label ?>
                    </span>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <?php if (!empty($userpro_mobile)) { ?>
            <a href="http://wa.me/91<?= urlencode((string) $userpro_mobile) ?>" target="_blank" rel="noopener" class="btn btn-success" style="padding:6px 14px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <i class="fab fa-whatsapp" style="font-size:14px;"></i> WhatsApp
            </a>
            <a href="tel:<?= htmlspecialchars((string) $userpro_mobile) ?>" class="btn btn-default" style="border:1px solid var(--cl-slate-200); padding:6px 14px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <i class="fa-solid fa-phone" style="font-size:12px;"></i> Call
            </a>
            <?php } ?>
        </div>
    </div>

    <div class="profile-dossier-grid">
        <div class="profile-tile">
            <div class="profile-tile-label"><i class="fa-solid fa-address-card" style="color:var(--cl-primary);"></i> Contact &amp; Onboarding</div>
            <div class="profile-tile-row"><span class="k">Mobile:</span><span class="v" style="font-family:monospace;"><?= htmlspecialchars((string) ($userpro_mobile ?? '')) ?></span></div>
            <div class="profile-tile-row"><span class="k">Email:</span><span class="v" style="max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars((string) ($userpro_email ?? '')) ?></span></div>
            <div class="profile-tile-row"><span class="k">Registered:</span><span class="v"><?= getDateTimeDiff($userpro_reg_date ?? '') ?></span></div>
        </div>

        <div class="profile-tile">
            <div class="profile-tile-label"><i class="fa-solid fa-chart-line" style="color:#16a34a;"></i> Credit &amp; Eligibility</div>
            <div class="profile-tile-row"><span class="k">CreditLab Score:</span><span class="v"><strong style="color:var(--cl-brand); font-size:13.5px;"><i class="fa-solid fa-gauge-high" style="font-size:11px; margin-right:2px;"></i> <?= htmlspecialchars((string) ($userpro_credit_score ?? '')) ?></strong></span></div>
            <div class="profile-tile-row"><span class="k">Limit vs Salary:</span><span class="v"<?= $limit_percentage_style_attr ?>><?= htmlspecialchars((string) $limit_percentage_display) ?></span></div>
            <div class="profile-tile-row"><span class="k">Total Repaid:</span><span class="v" style="color:#15803d;"><?= creditlab_currency($totalflss) ?></span></div>
        </div>

        <div class="profile-tile">
            <div class="profile-tile-label"><i class="fa-solid fa-shield-halved" style="color:#0284c7;"></i> Verification Checks</div>
            <div class="profile-tile-row"><span class="k">Profile Verify:</span><span class="v"><?= $verify_badge ?></span></div>
            <div class="profile-tile-row"><span class="k">PAN &amp; Aadhaar:</span><span class="v"><?= $pan_aadhar_badge ?></span></div>
            <div class="profile-tile-row"><span class="k">Bank Check:</span><span class="v"><?= $bank_check_badge ?></span></div>
            <div class="profile-tile-row"><span class="k">eNACH:</span><span class="v"><span class="badge-modern" style="background:#f1f5f9; color:#334155; font-size:11px;"><?= htmlspecialchars((string) $userpro_enach_label) ?></span></span></div>
        </div>

        <div class="profile-tile">
            <div class="profile-tile-label"><i class="fa-solid fa-user-tie" style="color:#7c3aed;"></i> Portfolio Assignment</div>
            <div class="profile-tile-row"><span class="k">Account Mgr:</span><span class="v"><?= isset($account_manager_row['name']) ? htmlspecialchars($account_manager_row['name']) : '<span style="color:#94a3b8;">Not Assigned</span>' ?></span></div>
            <div class="profile-tile-row"><span class="k">Recovery Officer:</span><span class="v"><?= isset($recovery_officer_row['name']) ? htmlspecialchars($recovery_officer_row['name']) : '<span style="color:#94a3b8;">Not Assigned</span>' ?></span></div>
        </div>
    </div>
</div>

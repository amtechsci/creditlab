<?php
/**
 * Compact profile header for agency_admin (collections contact only).
 */
$alt = isset($userpro_altmobile) && $userpro_altmobile !== '' && $userpro_altmobile !== '0'
    ? (string) $userpro_altmobile
    : '—';
$initial = strtoupper(substr(trim($userpro_name !== '' ? $userpro_name : 'U'), 0, 1));
?>
<div class="profile-dossier-card">
    <div class="profile-dossier-header">
        <div class="profile-user-main">
            <div class="profile-big-avatar"><?= htmlspecialchars($initial) ?></div>
            <div class="profile-user-titles">
                <h2><?= htmlspecialchars($userpro_name ?? '') ?></h2>
                <div class="profile-tag-badges">
                    <span class="badge-modern" style="background:#e0f2fe; color:#0369a1; font-family:monospace; font-size:12px;">
                        <i class="fa-solid fa-id-card-clip" style="margin-right:4px;"></i>
                        <?= htmlspecialchars($userpro_rcid ?? '') ?>
                    </span>
                    <?php if ($agency_customer_dpd !== null) { ?>
                    <span class="badge-modern" style="background:#fee2e2; color:#b91c1c; font-weight:700;">
                        DPD <?= (int) $agency_customer_dpd ?>
                    </span>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <a href="index.php" class="btn btn-default" style="border:1px solid var(--cl-slate-200); padding:6px 14px; font-weight:600;">
                <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i> Back to list
            </a>
            <?php if (!empty($userpro_mobile)) { ?>
            <a href="http://wa.me/91<?= urlencode((string) $userpro_mobile) ?>" target="_blank" rel="noopener" class="btn btn-success" style="padding:6px 14px; font-weight:600;">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
            <a href="tel:<?= htmlspecialchars((string) $userpro_mobile) ?>" class="btn btn-default" style="border:1px solid var(--cl-slate-200); padding:6px 14px; font-weight:600;">
                <i class="fa-solid fa-phone"></i> Call
            </a>
            <?php } ?>
        </div>
    </div>
    <div class="profile-dossier-grid">
        <div class="profile-tile">
            <div class="profile-tile-label"><i class="fa-solid fa-address-card" style="color:var(--cl-primary);"></i> Contact</div>
            <div class="profile-tile-row"><span class="k">Mobile</span><span class="v" style="font-family:monospace;"><?= htmlspecialchars((string) ($userpro_mobile ?? '')) ?></span></div>
            <div class="profile-tile-row"><span class="k">Alt mobile</span><span class="v"><?= htmlspecialchars($alt) ?></span></div>
        </div>
        <div class="profile-tile">
            <div class="profile-tile-label"><i class="fa-solid fa-briefcase" style="color:#7c3aed;"></i> Work</div>
            <div class="profile-tile-row"><span class="k">Company</span><span class="v"><?= htmlspecialchars((string) ($userpro_company ?? '—')) ?></span></div>
            <div class="profile-tile-row"><span class="k">City</span><span class="v"><?= htmlspecialchars((string) ($userpro_state ?? '—')) ?></span></div>
        </div>
    </div>
</div>

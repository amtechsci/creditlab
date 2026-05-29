<?php
/**
 * Compact profile header for agency_admin (no PAN/Aadhar/score block).
 */
$alt = isset($userpro_altmobile) && $userpro_altmobile !== '' && $userpro_altmobile !== '0'
    ? $userpro_altmobile
    : '—';
?>
<div class="row">
    <div class="col-lg-12">
        <p><strong>NAME:</strong> <?= htmlspecialchars($userpro_name ?? '') ?></p>
        <p><strong>CLID:</strong> <?= htmlspecialchars($userpro_rcid ?? '') ?></p>
        <p><strong>Status:</strong> <?php
        if (($userpro_status ?? '') === 'waiting') {
            echo 'Just Registered';
        } else {
            echo htmlspecialchars($userpro_status ?? '');
        }
        ?></p>
        <p><strong>Mobile:</strong> <?= htmlspecialchars($userpro_mobile ?? '') ?></p>
        <p><strong>Alternate Mobile:</strong> <?= htmlspecialchars($alt) ?></p>
    </div>
</div>

<?php
/**
 * Hide profile tab panes not allowed for agency_admin (nav already limited).
 */
if (($creditlab_profile_role ?? '') !== 'agency_admin') {
    return;
}
$allowedPanes = creditlab_profile_tabs_for_role('agency_admin');
$allPanes = array_keys(creditlab_profile_tab_definitions());
$hide = array_diff($allPanes, $allowedPanes);
?>
<style>
<?php foreach ($hide as $paneId) { ?>
#myTabContent #<?= htmlspecialchars($paneId) ?> { display: none !important; }
<?php } ?>
</style>

<?php
require_once __DIR__ . '/../lib/profile_tabs.php';
$role = $creditlab_profile_role ?? creditlab_staff_role() ?? 'account_manager';
$defs = creditlab_profile_tab_definitions();
$allowed = creditlab_profile_tabs_for_role($role);
$defaultTab = ($role === 'agency_admin') ? 'pg_link' : 'Personal';
$activeTab = isset($tab) && in_array($tab, $allowed, true) ? $tab : $defaultTab;
echo '<ul id="myTabedu1" class="tab-review-design">';
foreach ($allowed as $i => $key) {
    if (!isset($defs[$key])) {
        continue;
    }
    $d = $defs[$key];
    $isActive = ($activeTab === $key);
    $liClass = $isActive ? ' class="active"' : '';
    $style = ($i === 0) ? ' style="margin-bottom:30px;"' : '';
    echo '<li' . $liClass . $style . '><a href="' . htmlspecialchars($d['href']) . '" data-toggle="tab">' . htmlspecialchars($d['label']) . '</a></li>';
}
echo '</ul>';

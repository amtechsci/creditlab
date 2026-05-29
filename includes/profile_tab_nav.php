<?php
require_once __DIR__ . '/../lib/profile_tabs.php';
$role = $creditlab_profile_role ?? creditlab_staff_role() ?? 'account_manager';
$defs = creditlab_profile_tab_definitions();
$allowed = creditlab_profile_tabs_for_role($role);
$activeTab = isset($tab) ? $tab : 'Personal';
$first = true;
echo '<ul id="myTabedu1" class="tab-review-design">';
foreach ($allowed as $key) {
    if (!isset($defs[$key])) {
        continue;
    }
    $d = $defs[$key];
    $isActive = ($activeTab === $key) || ($first && $activeTab === 'Personal' && $key === 'Personal');
    $liClass = $isActive ? ' class="active"' : '';
    $style = ($first && $key === 'Personal') ? ' style="margin-bottom:30px;"' : '';
    echo '<li' . $liClass . $style . '><a href="' . htmlspecialchars($d['href']) . '" data-toggle="tab">' . htmlspecialchars($d['label']) . '</a></li>';
    if ($key === 'Personal') {
        $first = false;
    }
}
echo '</ul>';

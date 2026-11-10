<?php
require_once __DIR__ . '/../lib/profile_tabs.php';
$role = creditlab_profile_page_role();
$defs = creditlab_profile_tab_definitions();
$allowed = creditlab_profile_tabs_for_role($role);
$defaultTab = 'Personal';
$activeTab = isset($tab) && in_array($tab, $allowed, true) ? $tab : $defaultTab;
echo '<div class="profile-tab-scroll">';
echo '<ul id="myTabedu1" class="tab-review-design">';
foreach ($allowed as $key) {
    if (!isset($defs[$key])) {
        continue;
    }
    $d = $defs[$key];
    $isActive = ($activeTab === $key);
    $liClass = $isActive ? ' class="active"' : '';
    echo '<li' . $liClass . '><a href="' . htmlspecialchars($d['href']) . '" data-toggle="tab">' . htmlspecialchars($d['label']) . '</a></li>';
}
echo '</ul>';
echo '</div>';

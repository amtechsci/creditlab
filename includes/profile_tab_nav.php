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
    $href = $d['href'];
    $icon = isset($d['icon']) ? (string) $d['icon'] : '';
    $iconHtml = $icon !== '' ? '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i> ' : '';
    echo '<li' . $liClass . '><a href="' . htmlspecialchars($href) . '" class="cl-profile-tab-link" data-tab="' . htmlspecialchars($key) . '">' . $iconHtml . htmlspecialchars($d['label']) . '</a></li>';
}
echo '</ul>';
echo '</div>';

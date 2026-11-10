<?php
require_once __DIR__ . '/../lib/staff_portal_ui.php';
creditlab_render_staff_welcome([
    'show_help' => true,
    'show_settings' => false,
    'search_action' => 'sea.php',
]);

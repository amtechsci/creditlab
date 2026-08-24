<?php
$edit = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$location = 'settings.php?tab=lsp';
if ($edit > 0) {
    $location .= '&edit=' . $edit;
}
header('Location: ' . $location);
exit;

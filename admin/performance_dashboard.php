<?php
$query = $_GET;
header('Location: index.php' . ($query ? ('?' . http_build_query($query)) : '') . '#performance-dashboard');
exit;

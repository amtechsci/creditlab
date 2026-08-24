<?php
$params = $_GET;
$params['tab'] = 'reports';
header('Location: downloader.php?' . http_build_query($params));
exit;

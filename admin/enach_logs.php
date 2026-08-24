<?php
$params = $_GET;
$params['tab'] = 'enach';
header('Location: logs.php?' . http_build_query($params));
exit;

<?php
$params = $_GET;
$params['tab'] = 'files';
header('Location: logs.php?' . http_build_query($params));
exit;

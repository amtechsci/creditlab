<?php
include_once 'head.php';
$query = $_GET;
$query['tab'] = 'performance';
if (!isset($query['view']) || ($query['view'] !== 'userwise' && $query['view'] !== 'updates')) {
    $query['view'] = 'userwise';
}
header('Location: index.php?' . http_build_query($query));
exit;

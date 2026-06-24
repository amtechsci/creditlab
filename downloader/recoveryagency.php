<?php

include '../db.php';
require_once __DIR__ . '/../lib/recovery_agency_export.php';

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

creditlab_send_recovery_agency_csv('recovery_agency_data.csv', null, $from_date, $to_date);

<?php

include '../db.php';
require_once __DIR__ . '/../lib/agency_payments_export.php';

if (empty($admin)) {
    http_response_code(403);
    exit('Forbidden');
}

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

creditlab_send_agency_payments_csv('agency_wise_payments.csv', $from_date, $to_date);

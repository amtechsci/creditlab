<?php
include '../db.php';

if (isset($agency_admin)) {
    $authQuery = towquery(
        "SELECT agency_admin.id FROM agency_admin INNER JOIN agency ON agency.id = agency_admin.agency_id
         WHERE agency_admin.email='" . towreal($agency_admin) . "' AND agency_admin.active=1 LIMIT 1"
    );
    if (!$authQuery || townum($authQuery) <= 0) {
        header('location:/agency_admin/logout.php');
        exit;
    }
} else {
    header('location:/account/login.php');
    exit;
}

require_once __DIR__ . '/../lib/recovery_agency_export.php';

creditlab_send_recovery_agency_csv('recovery_agency_dpd35plus.csv', 35);

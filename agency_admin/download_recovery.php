<?php
include '../db.php';

$downloadAgencyId = 0;
if (isset($agency_admin)) {
    $authQuery = towquery(
        "SELECT agency_admin.agency_id FROM agency_admin INNER JOIN agency ON agency.id = agency_admin.agency_id
         WHERE agency_admin.email='" . towreal($agency_admin) . "' AND agency_admin.active=1 LIMIT 1"
    );
    $authRow = ($authQuery && townum($authQuery) > 0) ? towfetch($authQuery) : null;
    if (!$authRow) {
        header('location:/agency_admin/logout.php');
        exit;
    }
    $downloadAgencyId = (int) ($authRow['agency_id'] ?? 0);
} else {
    header('location:/account/login.php');
    exit;
}

require_once __DIR__ . '/../lib/recovery_agency_export.php';

creditlab_send_recovery_agency_csv('recovery_agency_dpd35plus.csv', 35, null, null, $downloadAgencyId);

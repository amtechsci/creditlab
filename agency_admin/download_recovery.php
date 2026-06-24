<?php
include 'head.php';
require_once __DIR__ . '/../lib/recovery_agency_export.php';

creditlab_send_recovery_agency_csv('recovery_agency_dpd35plus.csv', 35);

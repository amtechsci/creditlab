<?php
include '../db.php';

if (isset($agency_admin)) {
    $userquery = towquery("SELECT agency_admin.*, agency.name AS agency_name FROM agency_admin INNER JOIN agency ON agency.id = agency_admin.agency_id WHERE agency_admin.email='" . towreal($agency_admin) . "' AND agency_admin.active=1 LIMIT 1");
    if ($userquery && townum($userquery) > 0) {
        $userfetch = towfetch($userquery);
        extract($userfetch, EXTR_PREFIX_ALL, 'user');
        $user_name = $userfetch['name'] ?? '';
        $user_id = (int) ($userfetch['id'] ?? 0);
        $agency_admin_agency_id = (int) ($userfetch['agency_id'] ?? 0);
        $agency_admin_agency_name = $userfetch['agency_name'] ?? '';
    } else {
        header('location:/agency_admin/logout.php');
        exit;
    }
} else {
    header('location:/account/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Agency — creditlab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,700,900" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/metisMenu/metisMenu.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
</head>

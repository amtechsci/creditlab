<?php
// utils/helper.php

function generateHash($data, $salt) {
    $hash_string = implode('|', array(
        $data['key'],
        $data['txnid'],
        $data['amount'],
        $data['productinfo'],
        $data['firstname'],
        $data['email'],
        '', '', '', '', '', '', '', '',
        $salt
    ));
    return strtolower(hash('sha512', $hash_string));
}

function sendApiRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../db.php';

$userQuery = "
    SELECT u.pan_name, u.dob, u.marital_status AS gender, u.pan, 
           u.mobile, u.email, u.present_address, u.state_code, 
           u.pincode, u.permanent_address, u.rcid, la.amount, la.processing_fees, u.id AS user_id
    FROM user u
    LEFT JOIN loan_apply la ON u.id = la.uid
    WHERE la.status IN ('disbursal') AND agreement=1
";
$userResult = towquery($userQuery);

// Set headers for file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="9151'.date('dm').'.0'.rand(10,99).'"');

// Open output stream
$output = fopen('php://output', 'w');

// Process user data
while ($user = towfetchassoc($userResult)) {
    // Generate Account ID: First 4 characters of name without spaces + "123"
    $accountID = strtoupper(substr(str_replace(' ', '', $user['pan_name']), 0, 4)) . "123";
    $amount = $user['amount'];
    
    // Fetch latest verified bank details for the user
    $bankQuery = "
        SELECT * FROM user_bank 
        WHERE verify = 1 AND uid = '{$user['user_id']}' 
        ORDER BY date DESC LIMIT 1
    ";
    $bankResult = towquery($bankQuery);
    $bank = towfetchassoc($bankResult);

    // Determine the "Code" value
    $code = strpos($bank['bank_name'], 'HDFC') !== false ? 'I' : 'N';

    // Format the amount to avoid unnecessary trailing zeros
    $amount = rtrim(rtrim(number_format($amount, 10, '.', ''), '0'), '.');

    // Manually format the CSV row to prevent quotes
    $row = [
        $code,
        $accountID,
        $bank['ac_no'],
        $amount ?? "",
        $user['pan_name'] ?? "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        $accountID,
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        date('d/m/Y'),
        "",
        $bank['ifsc_code'] ?? "",
        $bank['bank_name'] ?? "",
        "",
        $user['email'] ?? ""
    ];

    // Convert the array to a comma-separated string and write it to the output
    fwrite($output, implode(',', $row) . "\n");
}

// Close the output stream
fclose($output);

exit;

<?php
// Include database connection
include '../db.php';

// Assuming you have a $user object or variable set up (as in your example)
if(isset($user)){
    $userquery = towquery("SELECT * FROM user WHERE mobile='$user'");
    $userfetch = towfetch($userquery);
    extract($userfetch, EXTR_PREFIX_ALL, "user");
} else {
    header('location:../account/');
}

// Retrieve loan details
$userloan = towquery("SELECT * FROM `loan` WHERE uid=$user_id AND (status_log ='account manager' OR status_log ='recovery officer') ORDER BY id DESC");
$userloanfetch = towfetch($userloan);

// Calculate the loan amount
$dis_date = date('Y-m-d', strtotime(date_create($userloanfetch['processed_date'])->format("Y-m-d") . " -1 day"));
$di = strtotime($dis_date);
$sa = strtotime(date('Y-m-d'));
$datediff = $sa - $di;
$day_gap = round($datediff / (60 * 60 * 24));
(int)$amount = ceil((float)$userloanfetch['processed_amount'] + (float)$userloanfetch['p_fee'] + (float)$userloanfetch['service_charge'] + ((float)$userloanfetch['p_fee'] * 0.18) + (float)$userloanfetch['penality_charge']);

// Prepare POST data
$data = [
    'txnid' => uniqid('TXN_').'CL' . $userloanfetch['id'],
    'amount' => $amount . '.0',
    'firstname' => $user_name,
    'email' => $user_email,
    'phone' => $user_mobile,
    'productinfo' => 'Loan Payment',
    'surl' => 'https://creditlab.in/payeasebuzz/response.php',
    'furl' => 'https://creditlab.in/payeasebuzz/response.php'
];

// Insert transaction into pg_transaction table with status 'initiated'
$insert_transaction = towquery(
    "INSERT INTO pg_transaction (txnid, loan_id, amount, firstname, email, phone, productinfo, status) 
    VALUES ('" . $data['txnid'] . "', '" . $userloanfetch['id'] . "', '" . $data['amount'] . "', '" . $data['firstname'] . "', '" . $data['email'] . "', '" . $data['phone'] . "', '" . $data['productinfo'] . "', 'initiated')"
);

// Automatically submit the payment form
echo '<html><body>';
echo '<form id="postForm" action="https://creditlab.in/payeasebuzz/easebuzz.php?api_name=initiate_payment" method="POST">';
foreach ($data as $key => $value) {
    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
}
echo '<script>';
echo 'document.getElementById("postForm").submit();';
echo '</script>';
echo '</body></html>';
?>

<?php
$db = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");
function towquery($query)
 {
 	$db = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");
  	mysqli_set_charset($db,'utf8');
 	$re = mysqli_query($db,$query);
 	return $re;
 }
 function towquery2($query)
 {
 	$db = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");
  	mysqli_set_charset($db,'utf8');
 	$re = mysqli_query($db,$query);
 	$re2 = mysqli_insert_id($db);
 	return $re2;
 }
 function townum($query)
 {
 	$re = mysqli_num_rows($query);
 	return $re;
 }
 function towfetch($query)
 {
 	$re = mysqli_fetch_array($query);
 	return $re;
 }
 function towfetchassoc($query)
 {
 	$re = mysqli_fetch_assoc($query);
 	return $re;
 }
 function towreal($query)
 {
 	$db = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");
 	$re = str_replace("<","&lt;",$query);
 	$re = str_replace(">","&gt;",$re);
 	$re = mysqli_real_escape_string($db,$re);
 	return $re;
 }
 function towrealarray($query)
 {
 	$co = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");
 	$re = array();
 	foreach ($query as $key => $value) {
 	    if(!is_array($value)){
 	$$key = str_replace("<","&lt;",$value);
 	$$key = str_replace(">","&gt;",$$key);
 	$$key = mysqli_real_escape_string($co,$$key);

 	$re[$key] = $$key;
 	    }else{
 	        $re[$key] = towrealarray($value);
 	    }
    }
 	return $re;
 }
 function towrealarray2($query)
 {
 	$co = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");
 	$re = array();
 	foreach ($query as $key => $value) {
 	    if(!is_array($value)){
 	$$key = str_replace("<","&lt;",$value);
 	$$key = str_replace(">","&gt;",$$key);
 	$$key = mysqli_real_escape_string($co,$$key);

 	$re[$key] = $$key;
 	    }else{
 	        $re[$key] = towrealarray2($value);
 	    }
    }
 	return $re;
 }
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture the payment response sent by Easebuzz
    $response = $_POST;

    // Extract necessary fields from the response
    $customer_authentication_id = $response['customer_authentication_id'];

    // New fields to be updated
    $net_amount_debit = $response['net_amount_debit'];
    $bank_ref_num = $response['bank_ref_num'];
    $authorization_status = $response['authorization_status'];
    $easepayid = $response['easepayid'];
    $payment_source = $response['payment_source'];
    $error_message = $response['error_Message'];
    $status = $response['status'];
    $addedon = $response['addedon'];
    $cash_back_percentage = $response['cash_back_percentage'];

    $ge = towquery("SELECT uid FROM easebuzz_adtd WHERE `customer_authentication_id` = '$customer_authentication_id'");
    if(townum($ge) > 0){
        towquery("UPDATE `easebuzz_adtd` SET 
            `net_amount_debit` = '$net_amount_debit',
            `bank_ref_num` = '$bank_ref_num',
            `authorization_status` = '$authorization_status',
            `easepayid` = '$easepayid',
            `payment_source` = '$payment_source',
            `error_message` = '$error_message',
            `status` = '$status',
            `addedon` = '$addedon',
            `cash_back_percentage` = '$cash_back_percentage'
        WHERE `customer_authentication_id` = '$customer_authentication_id'");
        $gef = towfetch($ge);
        if ($status === 'success') {
            $message = "Transaction Successful!";
            towquery("UPDATE `user` SET easebuzz=1 WHERE id=".$gef['uid']);
        } else {
            $message = "Transaction Failed: " . $error_message;
        }
    }
    $redirect_url = "https://creditlab.in/user/index.php";

    // Display success or failure message and then redirect after 2 seconds
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Transaction Status</title>
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                font-family: Arial, sans-serif;
            }
            .message {
                text-align: center;
                padding: 20px;
                border: 1px solid #ccc;
                border-radius: 10px;
                background-color: #f9f9f9;
            }
        </style>
    </head>
    <body>
        <div class='message'>
            <h2>$message</h2>
            <p>You will be redirected shortly...</p>
        </div>
        <script>
            setTimeout(function() {
                window.location.href = '$redirect_url';
            }, 2000);
        </script>
    </body>
    </html>";
}

<?php
// --- DATABASE CONNECTION ---
$db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");

if (mysqli_connect_errno()) {
    error_log("Database connection failed: " . mysqli_connect_error());
    http_response_code(500);
    die("Database connection failed.");
}
mysqli_set_charset($db, 'utf8');

// --- DATABASE FUNCTIONS ---
function towquery($db, $query) {
    $result = mysqli_query($db, $query);
    if (!$result) {
        error_log("SQL Error: " . mysqli_error($db) . " - Query: " . $query);
        return false;
    }
    return $result;
}
function towquery2($db, $query) {
    $result = mysqli_query($db, $query);
    if (!$result) {
        error_log("SQL Error: " . mysqli_error($db) . " - Query: " . $query);
        return false;
    }
    return mysqli_insert_id($db);
}
function townum($query_result) {
    return mysqli_num_rows($query_result);
}
function towfetch($query_result) {
    return mysqli_fetch_array($query_result);
}
function towfetchassoc($query_result) {
    return mysqli_fetch_assoc($query_result);
}
function towreal($db, $query) {
    $re = str_replace("<","&lt;",$query);
    $re = str_replace(">","&gt;",$re);
    $re = mysqli_real_escape_string($db, $re);
    return $re;
}
function towrealarray($db, $query) {
    $re = array();
    if (!is_array($query) || $query === null) {
        return $re;
    }
    foreach ($query as $key => $value) {
        if(!is_array($value)){
            $$key = str_replace("<","&lt;",$value);
            $$key = str_replace(">","&gt;",$$key);
            $$key = mysqli_real_escape_string($db, $$key);
            $re[$key] = $$key;
        }else{
            $re[$key] = towrealarray($db, $value);
        }
    }
    return $re;
}
function towrealarray2($db, $query) {
    $re = array();
    if (!is_array($query) || $query === null) {
        return $re;
    }
    foreach ($query as $key => $value) {
        if(!is_array($value)){
            $$key = str_replace("<","&lt;",$value);
            $$key = str_replace(">","&gt;",$$key);
            $$key = mysqli_real_escape_string($db, $$key);
            $re[$key] = $$key;
        }else{
            $re[$key] = towrealarray2($db, $value);
        }
    }
    return $re;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture the payment response sent by Easebuzz
    $response = $_POST;

    // --- FIELD VALIDATION ---
    $required_fields = [
        'customer_authentication_id',
        'net_amount_debit',
        'bank_ref_num',
        'authorization_status',
        'easepayid',
        'payment_source',
        'error_Message',
        'status',
        'addedon',
        'cash_back_percentage'
    ];

    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($response[$field]) || empty($response[$field])) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        error_log("Missing required fields in easebuzz_callback: " . implode(', ', $missing_fields));
        http_response_code(400);
        die("Missing required fields");
    }

    // Extract and sanitize fields
    $customer_authentication_id = towreal($db, $response['customer_authentication_id']);
    $net_amount_debit = towreal($db, $response['net_amount_debit']);
    $bank_ref_num = towreal($db, $response['bank_ref_num']);
    $authorization_status = towreal($db, $response['authorization_status']);
    $easepayid = towreal($db, $response['easepayid']);
    $payment_source = towreal($db, $response['payment_source']);
    $error_message = towreal($db, $response['error_Message']);
    $status = towreal($db, $response['status']);
    $addedon = towreal($db, $response['addedon']);
    $cash_back_percentage = towreal($db, $response['cash_back_percentage']);

    // Check if customer exists
    $ge = towquery($db, "SELECT uid FROM easebuzz_adtd WHERE `customer_authentication_id` = '$customer_authentication_id'");
    if(townum($ge) > 0){
        // Update easebuzz_adtd table
        $update_query = "UPDATE `easebuzz_adtd` SET 
            `net_amount_debit` = '$net_amount_debit',
            `bank_ref_num` = '$bank_ref_num',
            `authorization_status` = '$authorization_status',
            `easepayid` = '$easepayid',
            `payment_source` = '$payment_source',
            `error_message` = '$error_message',
            `status` = '$status',
            `addedon` = '$addedon',
            `cash_back_percentage` = '$cash_back_percentage'
        WHERE `customer_authentication_id` = '$customer_authentication_id'";
        
        if (towquery($db, $update_query)) {
            $gef = towfetch($ge);
            $uid = $gef['uid'];
            
            if ($status === 'success') {
                $message = "Transaction Successful!";
                // Update user easebuzz status
                if (!towquery($db, "UPDATE `user` SET easebuzz=1 WHERE id=".$uid)) {
                    error_log("Failed to update user easebuzz status for uid: $uid");
                }
            } else {
                $message = "Transaction Failed: " . $error_message;
                // Update user easebuzz status to failed
                if (!towquery($db, "UPDATE `user` SET easebuzz=0 WHERE id=".$uid)) {
                    error_log("Failed to update user easebuzz status for uid: $uid");
                }
            }
        } else {
            error_log("Failed to update easebuzz_adtd for customer_authentication_id: $customer_authentication_id");
            $message = "Database update failed";
        }
    } else {
        error_log("Customer not found for customer_authentication_id: $customer_authentication_id");
        $message = "Customer not found";
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

// Close database connection
mysqli_close($db);

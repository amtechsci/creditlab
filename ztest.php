<?php

// Database connection function
function towquery($query) {
    $db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");
    mysqli_set_charset($db, 'utf8');
    $re = mysqli_query($db, $query);
    return $re;
}

// File containing webhook data
$filename = 'webhook_data.txt';

// Read the file content
$file_content = file_get_contents($filename);

// Split content into individual requests by the "=== New Request ===" separator
$requests = explode("=== New Request ===", $file_content);

foreach ($requests as $request) {
    // Skip empty entries
    if (trim($request) == "") continue;

    // Extract POST Data block
    preg_match("/POST Data:\nArray\s*\((.*?)\)\n/s", $request, $post_matches);
    if (!isset($post_matches[1])) continue;

    // Convert POST data from text format to associative array
    $post_data_raw = $post_matches[1];
    $post_data = [];
    preg_match_all("/\[(.*?)\] => (.*?)\n/", $post_data_raw, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $key = trim($match[1]);
        $value = trim($match[2]);
        $post_data[$key] = $value;
    }

    // Process and update based on extracted POST data
    if (isset($post_data['txnid'], $post_data['authorization_status'])) {
        $txnid = $post_data['txnid'];
        $authorization_status = strtolower($post_data['authorization_status']);
        $net_amount_debit = $post_data['net_amount_debit'] ?? '0.0';
        $bank_ref_num = $post_data['bank_ref_num'] ?? 'NA';
        $easepayid = $post_data['easepayid'] ?? 'NA';
        $addedon = $post_data['addedon'] ?? date('Y-m-d H:i:s');
        $cash_back_percentage = $post_data['cash_back_percentage'] ?? '0.0';
        $status = $post_data['status'] ?? 'unknown';
        $error_message = $post_data['error_Message'] ?? 'NA';
        $auto_debit_access_key = $post_data['auto_debit_access_key'] ?? 'NA';

        // Determine status and update user easebuzz status accordingly
        $update_status = $authorization_status;
        $user_easebuzz_status = ($authorization_status === 'rejected') ? 2 : (($status === 'failure') ? 0 : 1);

        // Update fields in `easebuzz_adtd`
        $sql1 = "UPDATE easebuzz_adtd 
                 SET authorization_status = '$update_status',
                     net_amount_debit = '$net_amount_debit',
                     bank_ref_num = '$bank_ref_num',
                     easepayid = '$easepayid',
                     addedon = '$addedon',
                     cash_back_percentage = '$cash_back_percentage',
                     status = '$status',
                     error_message = '$error_message',
                     auto_debit_access_key = '$auto_debit_access_key'
                 WHERE txnid = '$txnid'";
        if (towquery($sql1)) {
            echo "Authorization status and other fields updated in easebuzz_adtd for txnid $txnid.\n";
        } else {
            echo "Error updating easebuzz_adtd for txnid $txnid.\n";
        }

        // Get the `uid` for the txnid to update the corresponding user table
        $result = towquery("SELECT uid FROM easebuzz_adtd WHERE txnid = '$txnid'");
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $uid = $row['uid'];

            $sql2 = "UPDATE user 
                     SET easebuzz = '$user_easebuzz_status' 
                     WHERE id = '$uid'";

            if (towquery($sql2)) {
                echo "User table updated successfully for uid $uid.\n";
            } else {
                echo "Error updating user table for uid $uid.\n";
            }
        } else {
            echo "User ID not found for txnid: $txnid.\n";
        }
    } else {
        echo "Missing txnid or authorization_status in parsed data.\n";
    }
}

echo "All requests processed.";

?>

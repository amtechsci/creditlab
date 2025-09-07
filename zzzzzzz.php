<?php
/**
 * process_logs.php
 *
 * This script reads the webhook_data.txt log file, parses the missed 
 * webhook requests, and securely updates the database.
 * VERSION 2: Includes check for already-cleared loans.
 */

echo "<pre>"; // For readable output in a browser
date_default_timezone_set('Asia/Kolkata');

// --- 1. DATABASE CONNECTION ---
$db = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");

if (mysqli_connect_errno()) {
    die("FATAL ERROR: Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($db, 'utf8');
echo "✅ Database connection successful.\n\n";


// --- 2. READ AND PARSE THE LOG FILE ---
$logFilename = 'webhook_data.txt';
$logContent = file_get_contents($logFilename);

if ($logContent === false) {
    die("FATAL ERROR: Could not read log file '$logFilename'.");
}

$requests = explode('=== New Request ===', $logContent);
$requestCount = 0;

// --- 3. LOOP THROUGH EACH LOGGED REQUEST ---
foreach ($requests as $requestBlock) {
    if (trim($requestBlock) === '') continue;

    $rawBodyPos = strpos($requestBlock, 'Raw Body:');
    if ($rawBodyPos === false) continue;
    
    $rawBodyString = trim(substr($requestBlock, $rawBodyPos + strlen('Raw Body:')));
    parse_str($rawBodyString, $data);

    if (empty($data) || !isset($data['txnid'])) {
        echo "⚠️ Skipping a log entry with no transaction ID.\n";
        continue;
    }
    
    $requestCount++;
    $txnid = $data['txnid'];
    echo "--------------------------------------------------\n";
    echo "⚙️ Processing Transaction ID: " . htmlspecialchars($txnid) . "\n";


    // --- 4. EXECUTE DATABASE LOGIC BASED ON THE PARSED DATA ---

    // ----- LOGIC FOR AUTO-DEBIT AUTHORIZATION CALLBACKS -----
    if (isset($data['authorization_status'])) {
        $status = $data['status'];
        $update_status = strtolower($data['authorization_status']);
        $user_easebuzz_status = ($update_status === 'accepted') ? 1 : 2;
        if ($status === 'failure') $user_easebuzz_status = 0;

        $stmt_check = mysqli_prepare($db, "SELECT uid FROM easebuzz_adtd WHERE txnid = ? AND authorization_status IS NOT NULL AND authorization_status != ''");
        mysqli_stmt_bind_param($stmt_check, "s", $txnid);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            echo "↪️ Skipping: Authorization status for this transaction is already set.\n";
            continue;
        }
        
        $stmt1 = mysqli_prepare($db, "UPDATE easebuzz_adtd SET authorization_status = ?, net_amount_debit = ?, bank_ref_num = ?, easepayid = ?, status = ?, error_message = ?, auto_debit_access_key = ? WHERE txnid = ?");
        mysqli_stmt_bind_param($stmt1, "ssssssss", 
            $update_status, $data['net_amount_debit'], $data['bank_ref_num'], $data['easepayid'], 
            $data['status'], $data['error_Message'], $data['auto_debit_access_key'], $txnid
        );

        if (mysqli_stmt_execute($stmt1)) {
            echo "✅ Updated authorization status in easebuzz_adtd.\n";
        } else {
            echo "❌ Error updating easebuzz_adtd: " . mysqli_error($db) . "\n";
        }
    }
    // ----- LOGIC FOR STANDARD PAYMENT CALLBACKS -----
    elseif (isset($data['furl']) && strpos($data['furl'], 'payeasebuzz/response.php') !== false) {
        $status = $data['status'];
        
        if ($status == "success") {
            $stmt_check = mysqli_prepare($db, "SELECT loan_id FROM pg_transaction WHERE txnid = ? AND status != 'success'");
            mysqli_stmt_bind_param($stmt_check, "s", $txnid);
            mysqli_stmt_execute($stmt_check);
            $pg_transaction = mysqli_stmt_get_result($stmt_check);

            if(mysqli_num_rows($pg_transaction) > 0) {
                $pg_data = mysqli_fetch_assoc($pg_transaction);
                $cllid = $pg_data['loan_id'];

                $loan_data = mysqli_query($db, "SELECT * FROM loan WHERE id='$cllid'");
                $loan_details = mysqli_fetch_assoc($loan_data);
                $uid = $loan_details['uid'];
                
                // ================================================================
                // ## NEW CHECK ADDED HERE ##
                // Check if the loan's action is already 'cleared'.
                // ================================================================
                if (isset($loan_details['action']) && $loan_details['action'] === 'cleared') {
                    
                    echo "ℹ️ Loan (ID: $cllid) was already marked 'cleared'. Skipping loan/user updates.\n";

                } else {
                    // Loan is not cleared, so proceed with all the updates.
                    echo "✅ Loan (ID: $cllid) is not cleared. Proceeding with updates.\n";

                    $user_data_result = mysqli_query($db, "SELECT * FROM user WHERE id='$uid'");
                    $user_details = mysqli_fetch_assoc($user_data_result);

                    $dpd = $loan_details['exhausted_period'] - 30;
                    $point = ($dpd > 0) ? (($dpd > 30) ? -50 : (($dpd > 10) ? -8 : 2)) : 8;

                    mysqli_query($db, "UPDATE `user` SET `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point WHERE id=".$uid);
                    mysqli_query($db, "UPDATE `loan` SET `action`='cleared', `status_log`='cleared', `cleard_date`='".date('Y-m-d')."' WHERE id=".$loan_details['id']);
                }
                
                // This part now runs for both cases (already cleared or just cleared now)
                // to ensure the transaction itself is always recorded correctly.
                $payment_method = isset($data['mode']) ? $data['mode'] : 'N/A';
                $amount = $data['amount'];
                $bank_ref_num = $data['bank_ref_num'];

                mysqli_query($db, "UPDATE `pg_transaction` SET `status`='success', `amount`='$amount', `payment_method`='$payment_method', `bank_reference_number`='$bank_ref_num' WHERE txnid='$txnid'");
                
                echo "✅ Updated pg_transaction record for " . htmlspecialchars($txnid) . ".\n";

            } else {
                echo "↪️ Skipping: Transaction already marked as successful in the database.\n";
            }
        } else {
            // Handle failed payment
            $error_msg = mysqli_real_escape_string($db, $data['error_Message']);
            mysqli_query($db, "UPDATE `pg_transaction` SET `status`='failure', `error_message`='$error_msg' WHERE txnid='$txnid'");
            echo "ℹ️ Marked transaction as failed in the database.\n";
        }
    } else {
        echo "❓ Skipping: Log entry is not a recognized callback type.\n";
    }
}

echo "--------------------------------------------------\n";
echo "\n🎉 Processing complete. Processed $requestCount log entries.\n";

// --- 5. CLEANUP ---
mysqli_close($db);
echo "</pre>";
?>
<?php
// Include your database connection and functions. Adjust the path as necessary.
include '../db.php'; 

// --- Display the Form ---
// This part is new. It shows the form to the user.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Nach Cron Processor</title>
    <style>
        body { font-family: sans-serif; margin: 2em; background-color: #f4f4f9; color: #333; }
        .container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 800px; margin: auto; }
        h1, h2 { color: #5a5a5a; }
        form { margin-bottom: 20px; }
        label { font-weight: bold; margin-right: 10px; }
        select, button { padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem; }
        button { background-color: #007bff; color: white; cursor: pointer; border-color: #007bff; }
        button:hover { background-color: #0056b3; }
        .results { margin-top: 20px; padding: 15px; background-color: #e9ecef; border-left: 4px solid #007bff; white-space: pre-wrap; word-wrap: break-word; font-family: monospace; }
        hr { border: 0; height: 1px; background: #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Process Loan Repayments</h1>
        <p>Select the loan group to process for E-Nach auto-debit.</p>
        <form action="" method="POST">
            <label for="exhausted_period_option">Exhausted Period:</label>
            <select name="exhausted_period_option" id="exhausted_period_option" required>
                <option value="">-- Select an Option --</option>
                <option value="31">Exactly 31 Days</option>
                <option value="30">More than 30 Days</option>
            </select>
            <button type="submit">Process Loans</button>
        </form>
        <hr>

<?php
// --- Processing Logic ---
// This entire block will only run after the form has been submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exhausted_period_option'])) {

    echo '<div class="results">'; // Start a container for the output
    
    // Set a longer execution time limit, essential for processing many records.
    set_time_limit(0);

    // --- Dynamic SQL Query ---
    $option = $_POST['exhausted_period_option'];
    $sql = ""; // Initialize SQL variable

    if ($option == '31') {
        echo "Selected option: <b>Exactly 31 Days</b>. Building query...\n";
        $sql = "SELECT * FROM `loan` WHERE `exhausted_period` = 31 AND `status_log` = 'account manager' AND `enach_request` = 0";
    } elseif ($option == '30') {
        echo "Selected option: <b>More than 30 Days</b>. Building query...\n";
        $sql = "SELECT * FROM `loan` WHERE `exhausted_period` > 30 AND `status_log` = 'account manager' AND `enach_request` = 0";
    } else {
        die("Invalid option selected."); // Exit if the form value is manipulated
    }

    // Add the same calculation functions as auto_enach.php
    function calculateTotalAmount($loan, $loan_apply) {
        // Get current date and calculate days since processed_date
        $stop_date = date_create($loan['processed_date']);
        $sa = date_create(date('Y-m-d 23:59:59'));
        $aa = date_diff($stop_date, $sa);
        $days = $aa->format("%a");
        
        // Add +1 day as requested (if exhausted_period is 30, calculate for 31)
        $days++;
        
        // Calculate base amount with GST on processing fee (18% GST)
        $t = $loan['processed_amount'] + $loan['p_fee'] + ($loan['p_fee'] * 0.18);
        
        $service_charge = 0;
        $penality = 0;
        
        // Calculate service charge based on interest percentage
        if ($loan_apply['interest_percentage'] == 1) {
            // Special case for 1% interest - tiered calculation
            $remaining_days = $days;
            if ($remaining_days >= 3) {
                $fee = $t * 3 / 100 * 0;
                $remaining_days = $remaining_days - 3;
                $service_charge += $fee;
            } else {
                $fee = $t * $remaining_days / 100 * 0;
                $remaining_days = 0;
                $service_charge += $fee;
            }
            if ($remaining_days >= 7) {
                $fee = $t * 7 / 100 * 0.1;
                $remaining_days = $remaining_days - 7;
                $service_charge += $fee;
            } else {
                $fee = $t * $remaining_days / 100 * 0.1;
                $remaining_days = 0;
                $service_charge += $fee;
            }
            if ($remaining_days >= 20) {
                $fee = $t * 20 / 100 * 0.115;
                $remaining_days = $remaining_days - 20;
                $service_charge += $fee;
            } else {
                $fee = $t * $remaining_days / 100 * 0.115;
                $remaining_days = 0;
                $service_charge += $fee;
            }
            if ($remaining_days >= 1) {
                $fee = $t * $remaining_days / 100 * 0.1;
                $remaining_days = 0;
                $service_charge += $fee;
            }
        } else {
            // Standard interest calculation
            $fee = $t * $days / 100 * $loan_apply['interest_percentage'];
            $service_charge += $fee;
        }
        
        // Calculate penalty for days > 30
        if ($days > 30) {
            $penalitydays = $days - 30;
            $penalitydays--;
            $penality = (($t) / 100) * 4;
            $atnp = ((($t) / 100) * 0.2) * $penalitydays;
            $penality = $penality + $atnp;
        } else {
            $penality = 0;
        }
        
        // Add 18% GST to penalty
        $penality = ($penality + ($penality * 0.18));
        
        // Calculate total amount (including GST on processing fee)
        $p_fee_gst = $loan['p_fee'] * 0.18;
        $totalamount = (float)$loan['processed_amount'] + (float)$loan['p_fee'] + $p_fee_gst + (float)$service_charge + (float)$penality;
        
        return $totalamount;
    }

    function calculateAmountBreakdown($loan, $loan_apply) {
        // Get current date and calculate days since processed_date
        $stop_date = date_create($loan['processed_date']);
        $sa = date_create(date('Y-m-d 23:59:59'));
        $aa = date_diff($stop_date, $sa);
        $days = $aa->format("%a");
        
        // Add +1 day as requested (if exhausted_period is 30, calculate for 31)
        $days++;
        
        // Calculate base amount with GST on processing fee (18% GST)
        $p_fee_gst = $loan['p_fee'] * 0.18;
        $t = $loan['processed_amount'] + $loan['p_fee'] + $p_fee_gst;
        
        $service_charge = 0;
        $penality = 0;
        
        // Calculate service charge based on interest percentage
        if ($loan_apply['interest_percentage'] == 1) {
            // Special case for 1% interest - tiered calculation
            $remaining_days = $days;
            if ($remaining_days >= 3) {
                $fee = $t * 3 / 100 * 0;
                $remaining_days = $remaining_days - 3;
                $service_charge += $fee;
            } else {
                $fee = $t * $remaining_days / 100 * 0;
                $remaining_days = 0;
                $service_charge += $fee;
            }
            if ($remaining_days >= 7) {
                $fee = $t * 7 / 100 * 0.1;
                $remaining_days = $remaining_days - 7;
                $service_charge += $fee;
            } else {
                $fee = $t * $remaining_days / 100 * 0.1;
                $remaining_days = 0;
                $service_charge += $fee;
            }
            if ($remaining_days >= 20) {
                $fee = $t * 20 / 100 * 0.115;
                $remaining_days = $remaining_days - 20;
                $service_charge += $fee;
            } else {
                $fee = $t * $remaining_days / 100 * 0.115;
                $remaining_days = 0;
                $service_charge += $fee;
            }
            if ($remaining_days >= 1) {
                $fee = $t * $remaining_days / 100 * 0.1;
                $remaining_days = 0;
                $service_charge += $fee;
            }
        } else {
            // Standard interest calculation
            $fee = $t * $days / 100 * $loan_apply['interest_percentage'];
            $service_charge += $fee;
        }
        
        // Calculate penalty for days > 30
        if ($days > 30) {
            $penalitydays = $days - 30;
            $penalitydays--;
            $penality = (($t) / 100) * 4;
            $atnp = ((($t) / 100) * 0.2) * $penalitydays;
            $penality = $penality + $atnp;
        } else {
            $penality = 0;
        }
        
        // Add 18% GST to penalty
        $penality = ($penality + ($penality * 0.18));
        
        return [
            'processed_amount' => (float)$loan['processed_amount'],
            'p_fee' => (float)$loan['p_fee'],
            'p_fee_gst' => $p_fee_gst,
            'service_charge' => $service_charge,
            'penalty_charge' => $penality
        ];
    }

    // This function remains unchanged.
    function initiateEasebuzzDirectDebit(array $postParams): string {
        $key = '9BIB9D914T';
        $salt = 'GGW1QF6ONH';
        $txnid = uniqid("txn_");
        $surl = "https://creditlab.in/payment/cb_auto.php";
        $furl = "https://creditlab.in/payment/cb_auto.php";

        $requiredKeys = [
            "amount" => "", "productinfo" => "", "firstname" => "", "email" => "", "phone" => "",
            "customer_authentication_id" => "", "merchant_debit_id" => "", "auto_debit_access_key" => "",
            "sub_merchant_id" => ""
        ];
        for ($i = 1; $i <= 10; $i++) { $requiredKeys["udf{$i}"] = ""; }
        $data = array_merge($requiredKeys, $postParams);

        $hash_string = $key . '|' . $txnid . '|' . $data['amount'] . '|' . $data['productinfo'] . '|' . $data['firstname'] . '|' . $data['email'] . '|' .
                       $data['udf1'] . '|' . $data['udf2'] . '|' . $data['udf3'] . '|' . $data['udf4'] . '|' . $data['udf5'] . '|' .
                       $data['udf6'] . '|' . $data['udf7'] . '|' . $data['udf8'] . '|' . $data['udf9'] . '|' . $data['udf10'] . '|' . $salt;
        $hash = hash("sha512", $hash_string);

        $postData = [
            "key" => $key, "txnid" => $txnid, "hash" => $hash, "amount" => $data['amount'],
            "productinfo" => $data['productinfo'], "firstname" => $data['firstname'], "email" => $data['email'],
            "phone" => $data['phone'], "surl" => $surl, "furl" => $furl,
            "customer_authentication_id" => $data['customer_authentication_id'],
            "merchant_debit_id" => $data['merchant_debit_id'], "auto_debit_access_key" => $data['auto_debit_access_key'],
            "sub_merchant_id" => $data['sub_merchant_id']
        ];
        for ($i = 1; $i <= 10; $i++) { $postData["udf{$i}"] = $data["udf{$i}"]; }

        $ch = curl_init("https://pay.easebuzz.in/payment/initiateDirectDebitRequest/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Content-Type: application/x-www-form-urlencoded"]);
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = "cURL error: " . curl_error($ch);
            curl_close($ch);
            return json_encode(['status' => 0, 'error' => $error_msg]);
        }
        curl_close($ch);
        return $response;
    }

    echo "Job Started: " . date('Y-m-d H:i:s') . "\n\n";

    // 1. Select loans based on the dynamically built query.
    $eligible_loans = towquery($sql);

    if (townum($eligible_loans) == 0) {
        echo "No eligible loans found for E-Nach processing.\n";
    } else {
        echo "Found " . townum($eligible_loans) . " loans to process.\n";

        // 2. Loop through each eligible loan.
        while ($loan = towfetch($eligible_loans)) {
            $lid = $loan['lid'];
            $uid = $loan['uid'];

            echo "---------------------------------\n";
            echo "Processing Loan ID (lid): $lid for User ID (uid): $uid\n";

            // 3. Fetch associated user and E-Nach data.
            $userdata = towquery("SELECT * FROM `user` WHERE id='$uid'");
            $userdataff = towfetch($userdata);

            // Check for authorized E-Nach authorizations (case-insensitive)
            $easebuzz_adtd = towquery("SELECT * FROM `easebuzz_adtd` WHERE uid='$uid' AND LOWER(authorization_status) IN ('authorized', 'accepted')");

            if (townum($easebuzz_adtd) > 0) {
                $easebuzz_adtdff = towfetch($easebuzz_adtd);
                
                // Get loan application details for proper calculation
                $loan_apply_data = towquery("SELECT * FROM `loan_apply` WHERE id='$lid'");
                $loan_apply = towfetch($loan_apply_data);
                
                // Use the same calculation logic as auto_enach.php
                $totalamount = calculateTotalAmount($loan, $loan_apply);
                $totalamount = number_format($totalamount, 2, '.', '');

                $paymentDetails = [
                    "amount" => $totalamount,
                    "productinfo" => "Loan Repayment Manual",
                    "firstname" => $userdataff['name'],
                    "email" => $userdataff['email'],
                    "phone" => $userdataff['mobile'],
                    "customer_authentication_id" => $easebuzz_adtdff['customer_authentication_id'],
                    "merchant_debit_id" => "CLL_AUTO_" . $lid . "_" . time(),
                    "auto_debit_access_key" => $easebuzz_adtdff['auto_debit_access_key']
                ];

                $apiResponse = initiateEasebuzzDirectDebit($paymentDetails);
                $res = json_decode($apiResponse, true);

                // Show calculation breakdown
                $breakdown = calculateAmountBreakdown($loan, $loan_apply);
                echo "Calculation Breakdown for CLL$lid:\n";
                echo "  Processed Amount: ₹" . number_format($loan['processed_amount'], 2) . "\n";
                echo "  Processing Fee: ₹" . number_format($loan['p_fee'], 2) . "\n";
                echo "  Processing Fee GST (18%): ₹" . number_format($breakdown['p_fee_gst'], 2) . "\n";
                echo "  Service Charge: ₹" . number_format($breakdown['service_charge'], 2) . "\n";
                echo "  Penalty Charge: ₹" . number_format($breakdown['penalty_charge'], 2) . "\n";
                echo "  Total Amount: ₹$totalamount\n\n";

                if ($res && isset($res['status']) && $res['status']) {
                    towquery("UPDATE `loan` SET `enach_request` = 1, `enach_request_date` = '" . date('Y-m-d') . "' WHERE lid = $lid");
                    echo "SUCCESS: E-Nach request initiated for CLL$lid | Amount: ₹$totalamount | Customer Auth ID: {$easebuzz_adtdff['customer_authentication_id']}\n";
                } else {
                    $errorMessage = isset($res['error_desc']) ? $res['error_desc'] : 'Unknown API error.';
                    echo "FAILED: E-Nach request for CLL$lid | Error: $errorMessage\n";
                }
            } else {
                echo "SKIPPED: No E-Nach details found for user uid: $uid.\n";
            }
        }
    }

    echo "---------------------------------\n";
    echo "Job Finished: " . date('Y-m-d H:i:s') . "\n";
    echo '</div>'; // End the results container
}
?>
    </div> </body>
</html>
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

    // This function remains unchanged.
    function initiateEasebuzzDirectDebit(array $postParams): string {
        $key = '9BIB9D914T';
        $salt = 'GGW1QF6ONH';
        $txnid = uniqid("txn_");
        $surl = "https://creditlab.in/payment/cb.php";
        $furl = "https://creditlab.in/payment/cb.php";

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

            $easebuzz_adtd = towquery("SELECT * FROM `easebuzz_adtd` WHERE uid='$uid'");

            if (townum($easebuzz_adtd) > 0) {
                $easebuzz_adtdff = towfetch($easebuzz_adtd);
                
                // IMPORTANT: Fixed a potential undefined variable error for $gst. Set it to 0 or your actual GST value.
                $gst = 0; 
                
                $totalamount = (float)$loan['processed_amount'] + (float)$loan['p_fee'] + (float)$loan['service_charge'] + $gst + (float)$loan['penality_charge'];
                $totalamount = number_format($totalamount, 2, '.', '');

                $paymentDetails = [
                    "amount" => $totalamount,
                    "productinfo" => "Loan Repayment Cron",
                    "firstname" => $userdataff['name'],
                    "email" => $userdataff['email'],
                    "phone" => $userdataff['mobile'],
                    "customer_authentication_id" => $easebuzz_adtdff['customer_authentication_id'],
                    "merchant_debit_id" => "CLL_AUTO_" . $lid,
                    "auto_debit_access_key" => $easebuzz_adtdff['auto_debit_access_key']
                ];

                $apiResponse = initiateEasebuzzDirectDebit($paymentDetails);
                $res = json_decode($apiResponse, true);

                if ($res && isset($res['status']) && $res['status']) {
                    towquery("UPDATE `loan` SET `enach_request` = 1 WHERE lid = $lid");
                    echo "SUCCESS: E-Nach request initiated for lid: $lid. Response: $apiResponse\n";
                } else {
                    $errorMessage = isset($res['error_desc']) ? $res['error_desc'] : 'Unknown API error.';
                    echo "FAILED: E-Nach request for lid: $lid. Error: $errorMessage\n";
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
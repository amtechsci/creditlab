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
        <p>Select loans based on DPD (Days Past Due) relative to repayment date for E-Nach auto-debit.</p>
        <form action="" method="POST">
            <label for="dpd_option">DPD (Days Past Due):</label>
            <select name="dpd_option" id="dpd_option" required>
                <option value="">-- Select an Option --</option>
                <option value="1">DPD = 1 (Repayment Day + 1)</option>
                <option value="custom">Custom DPD Range</option>
            </select>
            <div id="custom_dpd_range" style="display: none; margin-top: 10px;">
                <label for="min_dpd">Min DPD:</label>
                <input type="number" name="min_dpd" id="min_dpd" min="0" style="width: 80px; padding: 5px;">
                <label for="max_dpd" style="margin-left: 10px;">Max DPD:</label>
                <input type="number" name="max_dpd" id="max_dpd" min="0" style="width: 80px; padding: 5px;">
            </div>
            <button type="submit" style="margin-top: 10px;">Process Loans</button>
        </form>
        <script>
            document.getElementById('dpd_option').addEventListener('change', function() {
                var customRange = document.getElementById('custom_dpd_range');
                if (this.value === 'custom') {
                    customRange.style.display = 'block';
                } else {
                    customRange.style.display = 'none';
                }
            });
        </script>
        <hr>

<?php
// --- Processing Logic ---
// This entire block will only run after the form has been submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dpd_option'])) {

    echo '<div class="results">'; // Start a container for the output
    
    // Set a longer execution time limit, essential for processing many records.
    set_time_limit(0);

    // Get current date for DPD calculation
    $current_date = date('Y-m-d');
    
    // --- Select all eligible loans (we'll filter by DPD in PHP) ---
    // Select loans with loan_apply.days to calculate DPD dynamically
    $base_sql = "SELECT l.*, la.days, la.apply_date 
                 FROM `loan` l 
                 INNER JOIN `loan_apply` la ON l.lid = la.id 
                 WHERE l.`status_log` = 'account manager' 
                 AND l.`action` != 'cleared' 
                 AND l.`enach_request` = 0 
                 AND la.`status` = 'account manager'";
    
    $option = $_POST['dpd_option'];
    
    if ($option == '1') {
        echo "Selected option: <b>DPD = 1 (Repayment Day + 1)</b>. Building query...\n";
        // We'll filter by DPD = 1 in the loop
    } elseif ($option == 'custom') {
        $min_dpd = isset($_POST['min_dpd']) ? (int)$_POST['min_dpd'] : 0;
        $max_dpd = isset($_POST['max_dpd']) ? (int)$_POST['max_dpd'] : 999;
        echo "Selected option: <b>Custom DPD Range: $min_dpd to $max_dpd</b>. Building query...\n";
        // We'll filter by DPD range in the loop
    } else {
        die("Invalid option selected."); // Exit if the form value is manipulated
    }

    // Add the same calculation functions as auto_enach.php
    function calculateTotalAmount($loan, $loan_apply) {
        // Get current date and calculate tday (days since processed_date)
        $stop_date = date_create($loan['processed_date']);
        $sa = date_create(date('Y-m-d 23:59:59'));
        $aa = date_diff($stop_date, $sa);
        $tday = (int)$aa->format("%a");
        
        // Get days from loan_apply
        $loan_days_raw = isset($loan_apply['days']) ? (int)$loan_apply['days'] : 30;
        $loan_is_emi = isset($loan['is_emi']) ? (int)$loan['is_emi'] : (($loan_days_raw <= 30) ? 1 : 0);
        $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
        
        // E-Nach triggers on tday = days + 1 (DPD = 1), so use tday for calculations
        $days = $tday; // Use tday for service charge calculation
        
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
        
        // Calculate penalty based on DPD (Days Past Due) = tday - loan_days
        // E-Nach triggers when DPD = 1, so penalty starts from DPD = 1
        $dpd = $tday - $loan_days; // DPD = Days Past Due
        if ($dpd > 0) {
            $penalitydays = $dpd - 1; // Penalty starts from DPD = 1, so subtract 1
            $penality = (($t) / 100) * 4; // First day penalty (4%)
            if ($penalitydays > 0) {
                $atnp = ((($t) / 100) * 0.2) * $penalitydays; // Additional penalty for remaining days (0.2%)
                $penality = $penality + $atnp;
            }
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
        // Get current date and calculate tday (days since processed_date)
        $stop_date = date_create($loan['processed_date']);
        $sa = date_create(date('Y-m-d 23:59:59'));
        $aa = date_diff($stop_date, $sa);
        $tday = (int)$aa->format("%a");
        
        // Get days from loan_apply
        $loan_days_raw = isset($loan_apply['days']) ? (int)$loan_apply['days'] : 30;
        $loan_is_emi = isset($loan['is_emi']) ? (int)$loan['is_emi'] : (($loan_days_raw <= 30) ? 1 : 0);
        $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
        
        // E-Nach triggers on tday = days + 1 (DPD = 1), so use tday for calculations
        $days = $tday; // Use tday for service charge calculation
        
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
        
        // Calculate penalty based on DPD (Days Past Due) = tday - loan_days
        // E-Nach triggers when DPD = 1, so penalty starts from DPD = 1
        $dpd = $tday - $loan_days; // DPD = Days Past Due
        if ($dpd > 0) {
            $penalitydays = $dpd - 1; // Penalty starts from DPD = 1, so subtract 1
            $penality = (($t) / 100) * 4; // First day penalty (4%)
            if ($penalitydays > 0) {
                $atnp = ((($t) / 100) * 0.2) * $penalitydays; // Additional penalty for remaining days (0.2%)
                $penality = $penality + $atnp;
            }
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

    function initiateEasebuzzDirectDebit(array $postParams, array $easebuzz_row = []): string {
        require_once __DIR__ . '/../lib/easebuzz_enach.php';
        return creditlab_easebuzz_initiate_direct_debit_json($postParams, $easebuzz_row);
    }

    echo "Job Started: " . date('Y-m-d H:i:s') . "\n\n";

    // 1. Select all eligible loans (we'll filter by DPD dynamically)
    $all_loans = towquery($base_sql);
    $eligible_loans = [];

    if (townum($all_loans) == 0) {
        echo "No eligible loans found for E-Nach processing.\n";
    } else {
        echo "Found " . townum($all_loans) . " total eligible loans. Filtering by DPD...\n\n";
        
        // Filter loans by DPD-based logic
        while ($loan = towfetch($all_loans)) {
            // Calculate tday (days since processed_date)
            $processed_date_str = date('Y-m-d', strtotime($loan['processed_date'] . " -1 day"));
            $tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
            
            // Get days from loan_apply
            $loan_days_raw = isset($loan['days']) ? (int)$loan['days'] : 30;
            $loan_is_emi = isset($loan['is_emi']) ? (int)$loan['is_emi'] : (($loan_days_raw <= 30) ? 1 : 0);
            $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
            
            // Calculate DPD (Days Past Due) = tday - loan_days
            $dpd = $tday - $loan_days;
            
            // Filter by selected DPD option
            $is_eligible = false;
            if ($option == '1') {
                // DPD = 1 (Repayment Day + 1)
                $is_eligible = ($dpd == 1);
            } elseif ($option == 'custom') {
                // Custom DPD range
                $min_dpd = isset($_POST['min_dpd']) ? (int)$_POST['min_dpd'] : 0;
                $max_dpd = isset($_POST['max_dpd']) ? (int)$_POST['max_dpd'] : 999;
                $is_eligible = ($dpd >= $min_dpd && $dpd <= $max_dpd);
            }
            
            if ($is_eligible) {
                $loan['calculated_tday'] = $tday;
                $loan['calculated_dpd'] = $dpd;
                $loan['calculated_loan_days'] = $loan_days;
                $eligible_loans[] = $loan;
            }
        }
        
        echo "Found " . count($eligible_loans) . " loans matching DPD criteria.\n\n";

        // 2. Loop through each eligible loan.
        foreach ($eligible_loans as $loan) {
            $lid = $loan['lid'];
            $uid = $loan['uid'];

            echo "---------------------------------\n";
            echo "Processing Loan ID (lid): $lid for User ID (uid): $uid\n";
            echo "  Loan Days: {$loan['calculated_loan_days']}, tday: {$loan['calculated_tday']}, DPD: {$loan['calculated_dpd']}\n";

            // Check if loan is already cleared to prevent duplicate autopay deduction
            if ($loan['status_log'] == 'cleared' || $loan['action'] == 'cleared') {
                echo "SKIPPED: Loan CLL$lid is already cleared. No payment needed.\n";
                continue;
            }

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
                    "auto_debit_access_key" => $easebuzz_adtdff['auto_debit_access_key'],
                    "udf1" => "CREDITLAB_MANUAL_ENACH",
                ];

                $apiResponse = initiateEasebuzzDirectDebit($paymentDetails, $easebuzz_adtdff);
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
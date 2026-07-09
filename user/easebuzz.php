<?php
if (!function_exists('towquery')) {
    include_once '../db.php';
}

require_once __DIR__ . '/../config/easebuzz.php';
$MERCHANT_KEY = EASEBUZZ_MERCHANT_KEY;
$SALT = EASEBUZZ_SALT;
$ENV = EASEBUZZ_ENV;

$bankcode = towquery("SELECT * FROM `bank_name`");
$bankCodes = [];
while($nc = towfetch($bankcode)){$bankCodes[$nc['bank_code']] = $nc['bank_name'];}

function generateHash($data, $salt) {
    $hashSequence = $data['key'] . '|' . $data['txnid'] . '|' . $data['amount'] . '|' . $data['productinfo'] . '|' . $data['firstname'] . '|' . $data['email'] . '|' . $data['udf1'] . '|' . $data['udf2'] . '|' . $data['udf3'] . '|' . $data['udf4'] . '|' . $data['udf5'] . '|' . $data['udf6'] . '|' . $data['udf7'] . '|' . $data['udf8'] . '|' . $data['udf9'] . '|' . $data['udf10'] . '|' . $salt;
    return hash('sha512', $hashSequence);
}
$udf5 = creditlab_easebuzz_max_debit_amount(
    isset($user_salary) ? (float)$user_salary : 0,
    isset($user_loan_limit) ? (float)$user_loan_limit : 0
);

function creditlab_easebuzz_base_url() {
    return (strtolower(EASEBUZZ_ENV) === 'test') ? 'https://testpay.easebuzz.in' : 'https://pay.easebuzz.in';
}

function creditlab_easebuzz_max_debit_amount($salary, $loan_limit) {
    $amount = round($salary * 0.6);
    if ($loan_limit > $amount) {
        $amount = (int)round($loan_limit);
    }
    if ($amount < 10000) {
        $amount = 10000;
    }
    return $amount;
}

function creditlab_resolve_easebuzz_bank_code($ifsc, $db_code) {
    $ifsc = strtoupper(trim((string)$ifsc));
    $prefix = strlen($ifsc) >= 4 ? substr($ifsc, 0, 4) : '';

    // Easebuzz codes often differ from IFSC prefix / DB values (e.g. HDFC -> HDFCB).
    $overrides = [
        'HDFC' => 'HDFCB',
        'UTIB' => 'UTIB',
        'ICIC' => 'ICIC',
        'SBIN' => 'SBIN',
        'KKBK' => 'KKBK',
        'IDIB' => 'IDIB',
        'BARB' => 'BARB',
        'PUNB' => 'PUNB',
        'CBIN' => 'CBIN',
        'YESB' => 'YESB',
        'CNRB' => 'CNRB',
        'FDRL' => 'FDRL',
        'BKID' => 'BKID',
        'INDB' => 'INDB',
        'AUBL' => 'AUBL',
    ];

    if ($prefix !== '' && isset($overrides[$prefix])) {
        return $overrides[$prefix];
    }

    $db_code = strtoupper(trim((string)$db_code));
    if ($db_code !== '' && $db_code !== '0' && preg_match('/^[A-Z]{4,5}$/', $db_code)) {
        return $db_code;
    }

    return $prefix;
}

function creditlab_resolve_easebuzz_account_type($ac_type) {
    $normalized = strtolower(trim((string)$ac_type));
    if (strpos($normalized, 'curr') !== false) {
        return 'CURRENT';
    }
    return 'SAVINGS';
}

function creditlab_is_valid_ifsc($ifsc) {
    return (bool)preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper(trim((string)$ifsc)));
}

function sendCurlRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $header_size);

    curl_close($ch);

    return json_decode($body, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- FIELD VALIDATION ---
    $required_fields = [
        'firstname',
        'phone',
        'email',
        'account_no',
        'account_type',
        'ifsc'
    ];

    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        error_log("Missing required fields in user/easebuzz.php: " . implode(', ', $missing_fields));
        die("Missing required fields: " . implode(', ', $missing_fields));
    }

    // Extract and sanitize fields
    $firstname = trim(towreal($_POST['firstname']));
    $phone = preg_replace('/\D+/', '', towreal($_POST['phone']));
    if (strlen($phone) > 10) {
        $phone = substr($phone, -10);
    }
    $email = trim(towreal($_POST['email']));
    $bankCode = towreal($_POST['bank_code']);
    $accountNo = preg_replace('/\D+/', '', towreal($_POST['account_no']));
    $auth_mode = towreal($_POST['auth_mode']);
    $accountType = towreal($_POST['account_type']);
    $ifsc = strtoupper(trim(towreal($_POST['ifsc'])));
    $use_seamless = isset($_POST['use_seamless']) && $_POST['use_seamless'] === '1';

    if (strlen($phone) !== 10) {
        die('Invalid mobile number. Please update your profile and try again.');
    }

    if ($accountNo === '') {
        die('Invalid account number. Please contact support to update bank details.');
    }

    $bankCode = creditlab_resolve_easebuzz_bank_code($ifsc, $bankCode);
    $accountType = creditlab_resolve_easebuzz_account_type($accountType);

    if (!creditlab_is_valid_ifsc($ifsc)) {
        error_log("Invalid IFSC in user/easebuzz.php for uid $user_id: $ifsc");
        die('Invalid IFSC code. It must be 11 characters (e.g. SBIN0001234). Please update your bank details and try again.');
    }

    if ($bankCode === '' || $bankCode === '0') {
        error_log("Missing Easebuzz bank_code for uid $user_id, IFSC $ifsc");
        die('Unable to resolve bank code for e-NACH. Please verify bank name and IFSC in your bank details.');
    }

    if (!in_array($auth_mode, ['NetBanking', 'DebitCard'], true)) {
        $auth_mode = 'NetBanking';
    }

    // Validate user_id exists
    if (!isset($user_id) || empty($user_id)) {
        error_log("User ID not set in user/easebuzz.php");
        die("User session not found. Please login again.");
    }

    $uid = uniqid();
    $cai = uniqid();
    $authData = [
        "key" => $MERCHANT_KEY,
        "txnid" => $uid,
        "amount" => "1.0",
        "productinfo" => "Loan Payment",
        "firstname" => $firstname,
        "phone" => $phone,
        "email" => $email,
        "surl" => getAppUrl() . "/easebuzz_callback.php",
        "furl" => getAppUrl() . "/easebuzz_callback.php",
        "udf1" => "", "udf2" => "", "udf3" => "", "udf4" => "", "udf5" => "$udf5.0", // Max debit amount
        "udf6" => "", "udf7" => "", "udf8" => "", "udf9" => "", "udf10" => "",
        "customer_authentication_id" => $cai,
        "final_collection_date" => date('d/m/Y', strtotime('+3 year'))
    ];
    if ($use_seamless) {
        $authData["request_flow"] = "SEAMLESS";
    }

    $authData['hash'] = generateHash($authData, $SALT);
    $easebuzz_base = creditlab_easebuzz_base_url();
    $authUrl = $easebuzz_base . "/payment/initiateLink";
    $authResponse = sendCurlRequest($authUrl, $authData);

    error_log("Easebuzz ENACH initiate uid=$user_id ifsc=$ifsc bank_code=$bankCode auth_mode=$auth_mode seamless=" . ($use_seamless ? '1' : '0') . " response=" . json_encode($authResponse));

    if ($authResponse && isset($authResponse['status']) && $authResponse['status'] == 1) {
        $access_key = $authResponse['data'];
        
        // Delete existing records for this user
        if (!towquery("DELETE FROM `easebuzz_adtd` WHERE `easebuzz_adtd`.`uid` = $user_id")) {
            error_log("Failed to delete existing easebuzz_adtd records for uid: $user_id");
        }

        // Insert new record
        $request_flow = $use_seamless ? 'SEAMLESS' : 'HOSTED';
        $insert_query = "INSERT INTO `easebuzz_adtd` (`uid`, `txnid`, `firstname`, `phone`, `email`, `udf5`, `request_flow`, `customer_authentication_id`, `final_collection_date`, `hash`, `access_key`, `payment_mode`, `ifsc`, `account_type`, `account_no`, `auth_mode`, `bank_code`) VALUES ($user_id, '{$authData['txnid']}', '$firstname', '$phone', '$email', '{$authData['udf5']}', '$request_flow', '$cai', '{$authData['final_collection_date']}', '{$authData['hash']}', '$access_key', 'EN', '$ifsc', '$accountType', '$accountNo', '$auth_mode', '$bankCode')";
        
        if (towquery($insert_query)) {
            if ($use_seamless) {
                echo "
                    <form id='seamless_auto_submit_upi_form' method='POST' action='".$easebuzz_base."/initiate_seamless_payment/'>
                        <input type='hidden' name='access_key' value='".$access_key."'>
                        <input type='hidden' name='payment_mode' value='EN'>
                        <input type='hidden' name='ifsc' value='".$ifsc."'>
                        <input type='hidden' name='account_type' value='".$accountType."'>
                        <input type='hidden' name='account_no' value='".$accountNo."'>
                        <input type='hidden' name='auth_mode' value='$auth_mode'>
                        <input type='hidden' name='bank_code' value='".$bankCode."'>
                    </form>
                    <script type='text/javascript'>
                        document.getElementById('seamless_auto_submit_upi_form').submit();
                    </script>
            ";
            } else {
                $hosted_url = $easebuzz_base . '/pay/' . rawurlencode($access_key);
                echo "<script>window.location.replace(" . json_encode($hosted_url) . ");</script>";
                echo "<p>Redirecting to Easebuzz for e-NACH registration... <a href='" . htmlspecialchars($hosted_url, ENT_QUOTES) . "'>Click here</a> if not redirected.</p>";
                exit;
            }
        } else {
            error_log("Failed to insert easebuzz_adtd record for uid: $user_id");
            die('Database error occurred. Please try again.');
        }
    } else {
        $error_msg = isset($authResponse['error_desc']) ? $authResponse['error_desc'] : 'Unknown API error';
        error_log("Easebuzz API error: " . $error_msg);
        die('Error in Auto Debit Authorization: ' . $error_msg);
    }
} else {
    $ub = towquery("SELECT * FROM `user_bank` WHERE uid=$user_id AND verify=1 ORDER BY id DESC");
    if(townum($ub) > 0){
        $ubf = towfetch($ub);
        $ubank_name = $ubf['bank_name'];
        $ifsc_code = strtoupper(trim($ubf['ifsc_code']));
        $account_type = creditlab_resolve_easebuzz_account_type($ubf['ac_type']);
        $bankcode = towquery("SELECT * FROM `bank_name` WHERE bank_name='".mysqli_real_escape_string($db, $ubank_name)."' LIMIT 1");
        if(townum($bankcode) == 0){
            $bankcode = towquery("SELECT * FROM `bank_name` WHERE bank_name LIKE '%".mysqli_real_escape_string($db, $ubank_name)."%' LIMIT 1");
        }
        if(townum($bankcode) > 0){
            $bankcode = towfetch($bankcode);
            $bankcoden = $bankcode['bank_name'];
            $bankcodebc = creditlab_resolve_easebuzz_bank_code($ifsc_code, $bankcode['bank_code']);
        }else{
            $bankcoden = $ubf['bank_name'];
            $bankcodebc = creditlab_resolve_easebuzz_bank_code($ifsc_code, '');
        }
        $bank_setup_error = '';
        if (!creditlab_is_valid_ifsc($ifsc_code)) {
            $bank_setup_error = 'Your IFSC code is invalid or incomplete. Please contact support to update bank details before e-NACH registration.';
        } elseif ($bankcodebc === '') {
            $bank_setup_error = 'Unable to resolve bank code for e-NACH. Please verify bank name and IFSC code.';
        }
        ?>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <p style="text-align:center;">What is e-NACH / e-Mandate?</p>
                    <p>Electronic National Automated Clearing House (e-NACH) is a financial system that helps automate recurring payments for loans.</p>
                    <p>e-NACH is a service that allows you to pay your loan EMIs from your bank account. It's a service provided by the National Payments Corporation of India (NPCI)</p>
                    <p>e-mandate is an electronic version of a mandate, which is a standing instruction given to the bank where a customer holds their account to debit a fixed amount to another bank account automatically.</p>
                    <p>ENACH, allows the platform to set up payments with predefined frequency or Ad Hoc. By setting the frequency to Adhoc, the platform can present a mandate per the business requirements.</p>
                    <p>The "Period or Tenure of E-NACH" refers to the validity of your E-mandate or e-NACH registration. It does not imply that your account will be debited throughout this period. Instead, it signifies that the E-mandate remains valid for transactions during this time. If you wish to apply for a loan, you can do so directly without having to go through the E-NACH registration process again, as the existing mandate remains valid.</p>
                    <p>The maximum amount refers to the highest total sum that can be debited throughout the duration of the mandate. This includes the principal loan amount, as well as any accrued interest and charges specified in the loan agreement. We set up an e-mandate for an amount higher than your current loan balance to accommodate potential future increases in your loan limit. This way, you won't need to register again if your limit changes, as the one-time registration will cover the higher amount.</p>
                    <p>Benefits :
                        <ul style="margin: 15px;list-style: disc;">
                            <li>One-time authorization: No need to submit fresh mandates for each transaction.</li>
                            <li>Easy digital authentication: Using Netbanking or Debit Card credentials</li>
                        </ul>
                    </p>
                    <p style="color:red;">Note  :<br>
                        Make sure you are ready with your debit card or net banking details linked to the bank shown below to proceed with e-NACH registration.
                    </p>
                    <table class="table table-bordered">
                        <tr>
                            <td>Bank Name</td>
                            <td><?=$bankcoden?></td>
                        </tr>
                        <tr>
                            <td>Account number</td>
                            <td><?=$ubf['ac_no']?></td>
                        </tr>
                        <tr>
                            <td>IFSC</td>
                            <td><?=$ifsc_code?></td>
                        </tr>
                        <tr>
                            <td>Account Type</td>
                            <td><?=$account_type?></td>
                        </tr>
                        <tr>
                            <td>Easebuzz Bank Code</td>
                            <td><?=htmlspecialchars($bankcodebc)?></td>
                        </tr>
                        <tr>
                            <td>Max Debit Amount</td>
                            <td>₹<?=number_format($udf5)?></td>
                        </tr>
                    </table>
                    <?php if ($bank_setup_error !== ''): ?>
                    <p style="color:red;"><?=htmlspecialchars($bank_setup_error)?></p>
                    <?php else: ?>
<?php echo "
        <form method='POST' action=''>
            <input type='hidden' name='firstname' value='".htmlspecialchars($user_pan_name ? $user_pan_name : $user_name, ENT_QUOTES)."'>
            <input type='hidden' name='phone' value='".htmlspecialchars($user_mobile, ENT_QUOTES)."'>
            <input type='hidden' name='email' value='".htmlspecialchars($user_email, ENT_QUOTES)."'>
            <input type='hidden' name='bank_code' value='".htmlspecialchars($bankcodebc, ENT_QUOTES)."'>
            <input type='hidden' name='account_no' value='".htmlspecialchars($ubf['ac_no'], ENT_QUOTES)."'>
            <input type='hidden' name='account_type' value='".htmlspecialchars($account_type, ENT_QUOTES)."'>
            <input type='hidden' name='ifsc' value='".htmlspecialchars($ifsc_code, ENT_QUOTES)."'>
            <input type='hidden' name='use_seamless' value='1'>
            <p><strong>Authentication mode:</strong></p>
            <label style='margin-right:15px;'><input type='radio' name='auth_mode' value='NetBanking' checked> Net Banking (recommended for HDFC/SBI)</label>
            <label><input type='radio' name='auth_mode' value='DebitCard'> Debit Card</label>
            <br><br>
                <button class='btn btn-primary' style='text-align:center;' type='submit'>Continue to Easebuzz</button>
            </form>
            <form method='POST' action='' style='margin-top:10px;text-align:center;'>
            <input type='hidden' name='firstname' value='".htmlspecialchars($user_pan_name ? $user_pan_name : $user_name, ENT_QUOTES)."'>
            <input type='hidden' name='phone' value='".htmlspecialchars($user_mobile, ENT_QUOTES)."'>
            <input type='hidden' name='email' value='".htmlspecialchars($user_email, ENT_QUOTES)."'>
            <input type='hidden' name='bank_code' value='".htmlspecialchars($bankcodebc, ENT_QUOTES)."'>
            <input type='hidden' name='account_no' value='".htmlspecialchars($ubf['ac_no'], ENT_QUOTES)."'>
            <input type='hidden' name='account_type' value='".htmlspecialchars($account_type, ENT_QUOTES)."'>
            <input type='hidden' name='ifsc' value='".htmlspecialchars($ifsc_code, ENT_QUOTES)."'>
            <input type='hidden' name='auth_mode' value='NetBanking'>
            <input type='hidden' name='use_seamless' value='0'>
                <button class='btn btn-default' style='text-align:center;' type='submit'>Try alternate Easebuzz page</button>
            </form>
        ";
                    endif;
    } ?>
                </div>
            </div>
        </div>
<?php } 

// Don't close the global database connection - it's shared across the application
// mysqli_close($db);
?>

<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
$MERCHANT_KEY = "9BIB9D914T"; // Replace with actual key
$SALT = "GGW1QF6ONH";         // Replace with actual salt
$ENV = "prod";               // Set to "test" or "prod"

$bankcode = towquery("SELECT * FROM `bank_name`");
$bankCodes = [];
while($nc = towfetch($bankcode)){$bankCodes[$nc['bank_code']] = $nc['bank_name'];}

function generateHash($data, $salt) {
    $hashSequence = $data['key'] . '|' . $data['txnid'] . '|' . $data['amount'] . '|' . $data['productinfo'] . '|' . $data['firstname'] . '|' . $data['email'] . '|' . $data['udf1'] . '|' . $data['udf2'] . '|' . $data['udf3'] . '|' . $data['udf4'] . '|' . $data['udf5'] . '|' . $data['udf6'] . '|' . $data['udf7'] . '|' . $data['udf8'] . '|' . $data['udf9'] . '|' . $data['udf10'] . '|' . $salt;
    return hash('sha512', $hashSequence);
}
$udf5 = round($user_salary*0.6);
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
    $firstname = $_POST['firstname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $bankCode = $_POST['bank_code'];
    $accountNo = $_POST['account_no'];
    $auth_mode = $_POST['auth_mode'];
    $accountType = $_POST['account_type'];
    $ifsc = $_POST['ifsc'];

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
        "surl" => "https://creditlab.in/easebuzz_callback.php",
        "furl" => "https://creditlab.in/easebuzz_callback.php",
        "udf1" => "", "udf2" => "", "udf3" => "", "udf4" => "", "udf5" => "$udf5.0", // Max debit amount
        "udf6" => "", "udf7" => "", "udf8" => "", "udf9" => "", "udf10" => "",
        "request_flow" => "SEAMLESS",
        "customer_authentication_id" => $cai,
        "final_collection_date" => date('d/m/Y', strtotime('+3 year'))
    ];

    $authData['hash'] = generateHash($authData, $SALT);
    $authUrl = "https://pay.easebuzz.in/payment/initiateLink";
    $authResponse = sendCurlRequest($authUrl, $authData);

    if ($authResponse['status'] == 1) {
        $access_key = $authResponse['data'];
        
        towquery("DELETE FROM `easebuzz_adtd` WHERE `easebuzz_adtd`.`uid` = $user_id");

        $stmt = towquery("INSERT INTO `easebuzz_adtd` (`uid`, `txnid`, `firstname`, `phone`, `email`, `udf5`, `request_flow`, `customer_authentication_id`, `final_collection_date`, `hash`, `access_key`, `payment_mode`, `ifsc`, `account_type`, `account_no`, `auth_mode`, `bank_code`) VALUES ($user_id, '{$authData['txnid']}', '$firstname', '$phone', '$email', '{$authData['udf5']}', '{$authData['request_flow']}', '$cai', '{$authData['final_collection_date']}', '{$authData['hash']}', '$access_key', 'EN', '$ifsc', '$accountType', '$accountNo', '$auth_mode', '$bankCode')");
        echo "
                <form id='seamless_auto_submit_upi_form' method='POST' action='https://pay.easebuzz.in/initiate_seamless_payment/'>
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
        die('Error in Auto Debit Authorization: ' . $authResponse['error_desc']);
    }
} else {
    $ub = towquery("SELECT * FROM `user_bank` WHERE uid=$user_id AND verify=1 ORDER BY id DESC");
    if(townum($ub) > 0){
        $ubf = towfetch($ub);
        $ubank_name = $ubf['bank_name'];
        $bankcode = towquery("SELECT * FROM `bank_name` WHERE bank_name LIKE '%".$ubank_name."%'");
        if(townum($bankcode) > 0){
            $bankcode = towfetch($bankcode);
            $bankcoden = $bankcode['bank_name'];
            $bankcodebc = $bankcode['bank_code'];
        }else{
            $bankcoden = $ubf['bank_name'];
            $bankcodebc = 0;
        } ?>
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
                            <td><?=$ubf['ifsc_code']?></td>
                        </tr>
                    </table>
<?php echo "
        <form method='POST' action=''>
            <input type='hidden' name='firstname' value='".($user_pan_name ? $user_pan_name : $user_name)."'>
            <input type='hidden' name='phone' value='$user_mobile'>
            <input type='hidden' name='email' value='$user_email'>
            <input type='hidden' name='bank_code' value='".$bankcodebc."'>
            <input type='hidden' name='account_no' value='".$ubf['ac_no']."'>
            <input type='hidden' name='account_type' value='SAVINGS'>
            <input type='hidden' name='auth_mode' value='DebitCard'>
            <input type='hidden' name='ifsc' value='".$ubf['ifsc_code']."'>
                <button class='btn' style='text-align:center;' type='submit'>Continue</button>
            </form>
        ";
    } ?>
                </div>
            </div>
        </div>
<?php } ?>

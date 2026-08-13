<?php
if (!function_exists('towquery')) {
    include_once '../db.php';
}

require_once __DIR__ . '/../lib/easebuzz_enach.php';
require_once __DIR__ . '/../lib/easebuzz_autocollect.php';

$udf5 = creditlab_easebuzz_max_debit_amount(
    isset($user_salary) ? (float)$user_salary : 0,
    isset($user_loan_limit) ? (float)$user_loan_limit : 0
);

$ub = towquery("SELECT * FROM `user_bank` WHERE uid=$user_id AND verify=1 ORDER BY id DESC");
if (townum($ub) > 0) {
    $ubf = towfetch($ub);
    $ubank_name = $ubf['bank_name'];
    $ifsc_code = strtoupper(trim($ubf['ifsc_code']));
    $account_type = creditlab_resolve_easebuzz_account_type($ubf['ac_type']);
    $account_name = trim((string)($ubf['ac_name'] ?? ''));
    $bankcode = towquery("SELECT * FROM `bank_name` WHERE bank_name='" . mysqli_real_escape_string($db, $ubank_name) . "' LIMIT 1");
    if (townum($bankcode) == 0) {
        $bankcode = towquery("SELECT * FROM `bank_name` WHERE bank_name LIKE '%" . mysqli_real_escape_string($db, $ubank_name) . "%' LIMIT 1");
    }
    if (townum($bankcode) > 0) {
        $bankcode = towfetch($bankcode);
        $bankcoden = $bankcode['bank_name'];
        $bankcodebc = creditlab_autocollect_resolve_bank_code($ifsc_code, $bankcode['bank_code'] ?? '');
    } else {
        $bankcoden = $ubf['bank_name'];
        $bankcodebc = creditlab_autocollect_resolve_bank_code($ifsc_code, '');
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
                <p style="color:red;">Note :<br>
                    Make sure you are ready with your debit card or net banking details linked to the bank shown below to proceed with e-NACH registration.
                </p>
                <table class="table table-bordered">
                    <tr>
                        <td>Bank Name</td>
                        <td><?=htmlspecialchars($bankcoden, ENT_QUOTES)?></td>
                    </tr>
                    <tr>
                        <td>Account number</td>
                        <td><?=htmlspecialchars($ubf['ac_no'], ENT_QUOTES)?></td>
                    </tr>
                    <tr>
                        <td>Account Name</td>
                        <td><?=htmlspecialchars($account_name !== '' ? $account_name : ($user_pan_name ? $user_pan_name : $user_name), ENT_QUOTES)?></td>
                    </tr>
                    <tr>
                        <td>IFSC</td>
                        <td><?=htmlspecialchars($ifsc_code, ENT_QUOTES)?></td>
                    </tr>
                    <tr>
                        <td>Account Type</td>
                        <td><?=htmlspecialchars($account_type, ENT_QUOTES)?></td>
                    </tr>
                    <tr>
                        <td>Easebuzz Bank Code</td>
                        <td><?=htmlspecialchars($bankcodebc, ENT_QUOTES)?> <span style="color:#666;font-size:12px;">(Autocollect 4-letter NPCI code)</span></td>
                    </tr>
                    <tr>
                        <td>Max Debit Amount</td>
                        <td>₹<?=number_format($udf5)?></td>
                    </tr>
                </table>
                <?php if ($bank_setup_error !== ''): ?>
                    <p style="color:red;"><?=htmlspecialchars($bank_setup_error, ENT_QUOTES)?></p>
                <?php else: ?>
                    <form method="POST" action="easebuzz_start.php">
                        <input type="hidden" name="firstname" value="<?=htmlspecialchars($user_pan_name ? $user_pan_name : $user_name, ENT_QUOTES)?>">
                        <input type="hidden" name="phone" value="<?=htmlspecialchars($user_mobile, ENT_QUOTES)?>">
                        <input type="hidden" name="email" value="<?=htmlspecialchars($user_email, ENT_QUOTES)?>">
                        <input type="hidden" name="bank_code" value="<?=htmlspecialchars($bankcodebc, ENT_QUOTES)?>">
                        <input type="hidden" name="account_no" value="<?=htmlspecialchars($ubf['ac_no'], ENT_QUOTES)?>">
                        <input type="hidden" name="account_type" value="<?=htmlspecialchars($account_type, ENT_QUOTES)?>">
                        <input type="hidden" name="account_name" value="<?=htmlspecialchars($account_name !== '' ? $account_name : ($user_pan_name ? $user_pan_name : $user_name), ENT_QUOTES)?>">
                        <input type="hidden" name="ifsc" value="<?=htmlspecialchars($ifsc_code, ENT_QUOTES)?>">
                        <p><strong>Authentication mode:</strong></p>
                        <label style="margin-right:15px;"><input type="radio" name="auth_mode" value="NetBanking" checked> Net Banking (recommended for HDFC/SBI)</label>
                        <label><input type="radio" name="auth_mode" value="DebitCard"> Debit Card</label>
                        <br><br>
                        <button class="btn btn-primary" style="text-align:center;" type="submit">Continue to Easebuzz</button>
                    </form>
                    <p style="margin-top:15px;color:#666;font-size:13px;">Use the same bank account shown above. If this fails with error WC0E05, contact support with your CL ID.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php }

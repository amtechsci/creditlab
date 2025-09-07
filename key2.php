<?php
include 'db.php';
function calculateEMI($loan_amount,$pro_fee_per = 13,$interest_percentage = 1) {
    $t = $loan_amount;
    $day = $cday =  30;
    $service_charge = 0;
        if($interest_percentage == 1){
                if($cday >= 3 ){
                    $fee = $t * 3 / 100 * 0;
                    $interest = "0%";
                    $cday = $cday-3;
                    $service_charge +=$fee;
                }else{
                    $fee = $t * $cday / 100 * 0;
                    $interest = "0%";
                    $cday = 0;
                    $service_charge +=$fee;
                }
                if(($cday) >= 7){
                    $fee = $t * 7 / 100 * 0.1;
                    $interest = "0.1%";
                    $cday = $cday-7;
                    $service_charge +=$fee;
                }else{
                    $fee = $t * $cday / 100 * 0.1;
                    $interest = "0.1%"; 
                    $cday = 0;
                    $service_charge +=$fee;
                }
                if(($cday) > 1){
                    $fee = $t * $cday / 100 * 0.115;
                    $interest = "0.115%";
                    $service_charge +=$fee;
                }
        }else{
            $fee = $t * $cday / 100 * $interest_percentage;
            $interest = "$interest_percentage%";
            $service_charge +=$fee;
        }
    
    $processing_fee_rate = $pro_fee_per / 100; // 13%
    $interest_rate_per_month = 0.03; // 3%
    $days = 30;
    $processing_fee = $loan_amount * $processing_fee_rate;
    $total_interest = $service_charge;
    $disbursed = $loan_amount - $processing_fee;
    $total_amount_payable = $disbursed + $processing_fee + $total_interest;
    return [
        'loan_amount' => $loan_amount,
        'processing_fee' => $processing_fee,
        'upfront_charges' => $upfront_charges,
        'total_interest' => $total_interest,
        'total_amount_payable' => $total_amount_payable,
        'disbursed' => $disbursed
    ];
}
if(isset($_GET['id'])){
    $id = towreal($_GET['id']);
$loan  = towquery("SELECT loan_apply.*,user.name,user.father_name,user.permanent_address,user.mobile,user.aadhar,user.pan,user.altmobile,user.email,user.signature,user.personaldocument,user.conpanydocument,user.marital_status FROM `loan_apply` INNER JOIN user
      ON loan_apply.uid=user.id WHERE loan_apply.id='$id'");
$loanf = towfetch($loan);
$a = towquery("SELECT * FROM user_login_details WHERE uid='".$loanf['uid']."' ORDER BY id DESC");
$b = $loanf;
             $lo = towquery("SELECT * FROM loan WHERE lid=".$b['id']);
             $lof = towfetch($lo);
             $loan_amountc = $b['amount'] + $b['processing_fees'] + $b['origination_fee'] + ($b['processing_fees']*0.18);
$result = calculateEMI($loan_amountc,$b['pro_fee_per'],$b['interest_percentage']);
}
function convertImageToBase64($imageUrl, $altText = 'Image') {
    // Get the image content
    $imageData = file_get_contents($imageUrl);
    
    // Check if the image was successfully fetched
    if ($imageData === false) {
        return 'Error: Unable to fetch the image.';
    }

    // Encode the image content to Base64
    $base64 = base64_encode($imageData);

    // Get the MIME type of the image
    $imageInfo = getimagesize($imageUrl);
    $mimeType = $imageInfo['mime'];

    // Create the Base64 image string for HTML
    $base64Image = 'data:' . $mimeType . ';base64,' . $base64;

    // Return the HTML img tag with the Base64 encoded image
    return '<img src="' . $base64Image . '" alt="' . htmlspecialchars($altText) . '" class="logo">';
}
$imageUrl = 'https://creditlab.in/Sonuletterhead-pdf.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <title>Key Facts Statement - Page 1</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0px;
            font-size: 5pt; /* Adjusted for better resemblance to the PDF font size */}
.container2 {
                width: 700.30800px;
                /*margin: 0 auto;*/
                border: 1px solid #ccc;
                padding: 20px;
                box-sizing: border-box;
                height: 901.25200px;
                overflow: hidden;
                position: relative;
                margin-top: 0px;
}
.header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;}
.header .logo {
            width: 50px;
            height: 50px;
            border: 1px solid #000; /* Placeholder for logo */
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
            margin-right: 15px;
            background-color: #008080; /* Teal-like color */
            color: white;
            border-radius: 5px;}
.header .company-info {
            flex-grow: 1;}
.header .company-info h1 {
            margin: 0;
            font-size: 14pt;
            color: #000;}
.header .company-info p {
            margin: 2px 0;
            font-size: 9pt;}
.part-title {
            font-weight: bold;
            text-align: center;
            margin: 0;
            font-size: 5pt;}
table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 5pt;
}
table th, table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            vertical-align: top;}
table th {
            background-color: #f2f2f2;
            font-weight: normal;}
.page-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 30px;
            font-size: 5pt;
            position: absolute;
            bottom: 10px;
            width: 95%;
}
.page-footer p {
            margin: 0;}
.digital-signature {
            text-align: right;
            line-height: 1.1;}
.penal-charges-list {
            list-style: none; /* Remove default bullet */
            padding: 0;
            margin: 0;}
.penal-charges-list li {
            position: relative;
            margin-bottom: 5px;
            padding-left: 15px; /* Space for custom bullet */}
.penal-charges-list li:before {
            content: "\2022"; /* Unicode for a bullet point */
            position: absolute;
            left: 0;
            color: black; /* Color of the bullet */}
/* Specific table column widths for precise alignment */
        .table-main-part1 td:nth-child(1) { width: 5%;}
.table-main-part1 td:nth-child(2) { width: 40%;}
.table-main-part1 td:nth-child(3) { width: 20%;}
.table-main-part1 td:nth-child(4) { width: 15%;}
.table-main-part1 td:nth-child(5) { width: 20%;}
.table-float-interest td:nth-child(1) { width: 5%;}
.table-float-interest td:nth-child(2) { width: 15%;}
.table-float-interest td:nth-child(3) { width: 10%;}
.table-float-interest td:nth-child(4) { width: 10%;}
.table-float-interest td:nth-child(5) { width: 10%;}
.table-float-interest td:nth-child(6) { width: 10%;}
.table-float-interest td:nth-child(7) { width: 10%;}
.table-float-interest td:nth-child(8) { width: 10%;}
.table-float-interest td:nth-child(9) { width: 20%;}
.table-fees-charges td:nth-child(1) { width: 5%;}
.table-fees-charges td:nth-child(2) { width: 25%;}
.table-fees-charges td:nth-child(3) { width: 15%;}
/* One-time/Recurring RE */
        .table-fees-charges td:nth-child(4) { width: 18%;}
/* Amount RE */
        .table-fees-charges td:nth-child(5) { width: 17%;}
/* One time/ Recurring 3rd party */
        .table-fees-charges td:nth-child(6) { width: 20%;}
/* Amount 3rd party */

        .table-contingent-charges td:nth-child(1) { width: 5%;}
.table-contingent-charges td:nth-child(2) { width: 30%;}
.table-contingent-charges td:nth-child(3) { width: 65%;}
/* Specific table column widths for precise alignment */
        .table-part2-main td:nth-child(1) { width: 5%;}
.table-part2-main td:nth-child(2) { width: 70%;}
.table-part2-main td:nth-child(3) { width: 25%;}
.table-part2-colending td:nth-child(1) { width: 5%;}
.table-part2-colending td:nth-child(2) { width: 31%;}
.table-part2-colending td:nth-child(3) { width: 32%;}
.table-part2-colending td:nth-child(4) { width: 32%;}
/* Specific table column widths for precise alignment */
        .table-apr-computation th:nth-child(1), .table-apr-computation td:nth-child(1) { width: 5%;}
.table-apr-computation th:nth-child(2), .table-apr-computation td:nth-child(2) { width: 60%;}
.table-apr-computation th:nth-child(3), .table-apr-computation td:nth-child(3) { width: 35%;}
.section-heading {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            font-size: 10.5pt;}
.recipient-info {
            margin-top: 20px;
            margin-bottom: 10px;}
.recipient-info p {
            margin: 2px 0;}
.content-block {
            margin-bottom: 15px;
            line-height: 1.5;}
.list-indent {
            margin-left: 20px;
            list-style-type: disc;
            padding-left: 0;}
.list-indent li {
            margin-bottom: 5px;}
/* Specific table column widths for precise alignment */
        .table-repayment-schedule th:nth-child(1), .table-repayment-schedule td:nth-child(1) { width: 20%;}
.table-repayment-schedule th:nth-child(2), .table-repayment-schedule td:nth-child(2) { width: 20%;}
.table-repayment-schedule th:nth-child(3), .table-repayment-schedule td:nth-child(3) { width: 20%;}
.table-repayment-schedule th:nth-child(4), .table-repayment-schedule td:nth-child(4) { width: 20%;}
.table-repayment-schedule th:nth-child(5), .table-repayment-schedule td:nth-child(5) { width: 20%;}

.header-container {
            display: flex;
            justify-content: center;
        }
        .logo {
            width: 100px;
            margin-right: 20px;
        }
        .company-details {
            line-height: 1.4;
            text-align: center;
        }
        .company-details h2 {
            margin: 0;
            font-size: 1.5em;
        }
        .company-details p {
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <div class="container2">
        <div class="header-container">
            <!--<img src="https://creditlab.in/logo.svg" alt="Company Logo" class="logo">-->
            <div class="company-details">
                <h2>Sonu Marketing Private Limited</h2>
                <p>CIN : U51909WB1995PTC068572 | RBI Registration no : : B.05.04069</p>
                <p>NO 30, 1st Main, 2nd floor, Bannerghatta Main Road, Opposite Gopalan Innovation Mall,<br>
                BHCS Layout, BTM Layout 2nd stage, Bangalore 560076</p>
            </div>
        </div>
        <hr style="margin-top:20px;margin-bottom:15px;">

        <div class="part-title">PART A - Key Facts Statement</div>
        <div class="part-title">Annex A</div>
        <div class="part-title">Part 1 (Interest rate and fees/charges)</div>

        <table class="table-main-part1" style="margin-top:10px;">
            <tr>
                <td>1</td>
                <td>Loan proposal/account No.</td>
                <td><?=$id?></td>
                <td>Type of Loan</td>
                <td>Personal Loan</td>
            </tr>
            <tr>
                <td>2</td>
                <td>Sanctioned Loan amount (in Rupees)</td>
                <td>Rs. <?=$result['loan_amount']?></td>
                <td colspan="2" rowspan="2"></td>
            </tr>
            <tr>
                <td>3</td>
                <td>Disbursal schedule</td>
                <td>100% upfront</td>
            </tr>
            <tr>
                <td></td>
                <td colspan="2">(i) Disbursement in stages or 100% upfront.</td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td>4</td>
                <td>Loan term (year/months/days)</td>
                <td colspan="3">30 days</td>
            </tr>
            <tr>
                <td>5</td>
                <td>Instalment details</td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td></td>
                <td>Type of instalments</td>
                <td>Number of EPIs</td>
                <td>EPI (₹)</td>
                <td>Commencement of repayment, post sanction</td>
            </tr>
            <tr>
                <td></td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
                <td><?= date('d/m/Y', strtotime('+29 days')); ?></td>
            </tr>
            <tr>
                <td>6</td>
                <td>Interest rate (%) and type (fixed or floating or hybrid)</td>
                <td colspan="3"><?=$b['interest_percentage']?>% per day (fixed)</td>
            </tr>
            <tr>
                <td>7</td>
                <td>Additional Information in case of Floating rate of interest</td>
                <td colspan="3"></td>
            </tr>
        </table>

        <table class="table-float-interest">
            <tr>
                <td></td>
                <td>Reference Benchmark</td>
                <td>Benchmark rate (%) (B)</td>
                <td>Spread (%) (S)</td>
                <td>Final rate (%) R = (B)+(S)</td>
                <td>Reset periodicity (Months)</td>
                <td colspan="3">Impact of change in the reference benchmark (for 25 bps change in 'R', change in ³)</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>B</td>
                <td>S</td>
                <td>EPI ()</td>
                <td>No. of EPIs</td>
            </tr>
            <tr>
                <td></td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
            </tr>
        </table>

        <table class="table-fees-charges">
            <tr>
                <td>8</td>
                <td colspan="5">Fee/Charges</td>
            </tr>
            <tr>
                <td></td>
                <td colspan="2">Payable to the RE (A)</td>
                <td colspan="3">Payable to a third party through RE (B)</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td>One-time/ Recurring</td>
                <td>Amount (in ₹) or Percentage (%) as applicable</td>
                <td>One time/ Recurring</td>
                <td>Amount (in ₹) or Percentage (%) as applicable</td>
            </tr>
            <tr>
                <td></td>
                <td>(i) Processing fees</td>
                <td>Onetime</td>
                <td>Rs. <?=$result['processing_fee']?></td>
                <td>N/A</td>
                <td>N/A</td>
            </tr>
            <tr>
                <td></td>
                <td>(ii) Insurance charges</td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
            </tr>
            <tr>
                <td></td>
                <td>(iii) Valuation fees</td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
            </tr>
            <tr>
                <td></td>
                <td>(iv) Gst</td>
                <td>Onetime</td>
                <td>Rs. <?=$b['processing_fees']*0.18?></td>
                <td>N/A</td>
                <td>N/A</td>
            </tr>
        </table>

        <table class="table-contingent-charges">
            <tr>
                <td style="width: 5%;">9</td>
                <td style="width: 30%;">Annual Percentage Rate (APR) (%)</td>
                <td style="width: 65%;"><?php echo round((((($b['processing_fees'] + $b['processing_fees']*0.18 + $result['total_interest']) / $result['loan_amount'])  / 30) * 36500),2) ?></td>
            </tr>
            <tr>
                <td>10</td>
                <td colspan="2">Details of Contingent Charges (in ₹ or %, as applicable)</td>
            </tr>
            <tr>
                <td></td>
                <td>(i) Penal charges, if any, in case of delayed payment</td>
                <td>
                    a) Late Payment Fees / Penal charges:<br><br>
                    If you miss a loan repayment:
                    <ul class="penal-charges-list">
                        <li>On the first day after the due date: You'll be charged a one-time penalty of 4% of the overdue principal amount.</li>
                        <li>From the second day until the loan is fully repaid: You'll be charged a daily penalty of 0.2% of the overdue principal each day.</li>
                    </ul>
                    <p style="padding-left: 15px;">Clarification : For the avoidance of doubt, it is hereby clarified that the Penal Charges will be calculated on the principal overdue amount only and shall be levied distinctly and separately from the components of the principal overdue amount and the loan interest. These charges are not added to the rate of interest against which the loan has been advanced and are also not subject to any further interest. Please note that these charges are calculated in a manner so as to be commensurate to the default and are levied in a non-discriminatory manner for this loan product.</p>
                    b) Annualized Rate of Interest post-due date :<br><br>
                     In case of loan repayment overdue, basic interest charges shall continue to accrue at the same rate at <?=$b['interest_percentage']?>% per day on the Principal overdue amount from the First Overdue Day to Till the Loan is closed.
                </td>
            </tr>
            <tr>
                <td></td>
                <td>(ii) Other penal charges, if any</td>
                <td>N/A</td>
            </tr>
            <tr>
                <td></td>
                <td>(iii) Foreclosure charges, if applicable</td>
                <td>Zero Foreclosure charges</td>
            </tr>
            <tr>
                <td></td>
                <td>(iv) Charges for switching of loans from floating to fixed rate and vice versa</td>
                <td>N/A</td>
            </tr>
            <tr>
                <td></td>
                <td>(v) Any other charges (please specify)</td>
                <td>N/A</td>
            </tr>
        </table>

        <div class="page-footer">
            <p>Page-1</p>
            <div class="digital-signature">
                <?=convertImageToBase64('https://creditlab.in/zzesign.jpg')?>
            </div>
        </div>
    </div>
    <div class="container2">
        <div class="header-container">
            
            <div class="company-details">
                <h2>Sonu Marketing Private Limited</h2>
                <p>CIN : U51909WB1995PTC068572 | RBI Registration no : : B.05.04069</p>
                <p>NO 30, 1st Main, 2nd floor, Bannerghatta Main Road, Opposite Gopalan Innovation Mall,<br>
                BHCS Layout, BTM Layout 2nd stage, Bangalore 560076</p>
            </div>
        </div>
        <hr style="margin-top:20px;margin-bottom:15px;">

        <div class="part-title">Part 2 (Other qualitative information)</div>

        <table class="table-part2-main" style="margin-top:10px;">
            <tr>
                <td>1</td>
                <td>Clause of Loan agreement relating to engagement of recovery agents</td>
                <td>1 (X)</td>
            </tr>
            <tr>
                <td>2</td>
                <td>Clause of Loan agreement which details grievance redressal mechanism</td>
                <td>12</td>
            </tr>
            <tr>
                <td>3</td>
                <td>Phone number and email id of the nodal grievance redressal officer</td>
                <td>
                    Name: Mr. Abhishek M R<br>
                    Number: +91 7259333111<br>
                    Mail ID: abhi@creditlab.in
                </td>
            </tr>
            <tr>
                <td>4</td>
                <td>Whether the loan is, or in future maybe, subject to transfer to other REs or securitisation (Yes/No)</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>5</td>
                <td colspan="2">In case of lending under collaborative lending arrangements (e.g., co-lending/ outsourcing), following additional details may be furnished:</td>
            </tr>
        </table>

        <table class="table-part2-colending">
            <tr>
                <td></td>
                <th>Name of the originating RE, along with its funding proportion</th>
                <th>Name of the partner RE along with its proportion of funding</th>
                <th>Blended rate of interest</th>
            </tr>
            <tr>
                <td></td>
                <td>N/A</td>
                <td>N/A</td>
                <td>N/A</td>
            </tr>
        </table>

        <table class="table-part2-main">
            <tr>
                <td>6</td>
                <td colspan="2">In case of digital loans, following specific disclosures may be furnished:</td>
            </tr>
            <tr>
                <td></td>
                <td>(i) Cooling off/look-up period, in terms of RE's board approved policy, during which borrower shall not be charged any penalty on prepayment of loan</td>
                <td>3 days</td>
            </tr>
            <tr>
                <td></td>
                <td>(ii) Details of LSP acting as recovery agent and authorized to approach the borrower</td>
                <td>Refer to : <a href="https://creditlab.in/lsp.php" target="_blank">List of LSPs</a></td>
            </tr>
        </table>

        <div class="page-footer">
            <p>Page-2</p>
            <div class="digital-signature">
                <?=convertImageToBase64('https://creditlab.in/zzesign.jpg')?>
            </div>
        </div>
    </div>
    <div class="container2">
        <div class="header-container">
            
            <div class="company-details">
                <h2>Sonu Marketing Private Limited</h2>
                <p>CIN : U51909WB1995PTC068572 | RBI Registration no : : B.05.04069</p>
                <p>NO 30, 1st Main, 2nd floor, Bannerghatta Main Road, Opposite Gopalan Innovation Mall,<br>
                BHCS Layout, BTM Layout 2nd stage, Bangalore 560076</p>
            </div>
        </div>
        <hr style="margin-top:20px;margin-bottom:15px;">

        <div class="part-title">Annex B</div>
        <div class="part-title">computation of APR</div>

        <table class="table-apr-computation" style="margin-top:10px;">
            <tr>
                <th>Sr. No.</th>
                <th>Parameter</th>
                <th>Details</th>
            </tr>
            <tr>
                <td>1</td>
                <td>Sanctioned Loan amount (in Rupees)</td>
                <td><?=$result['loan_amount']?></td>
            </tr>
            <tr>
                <td>2</td>
                <td>Loan Term (in years/ months/ days)</td>
                <td>30 days</td>
            </tr>
            <tr>
                <td></td>
                <td>a) No. of instalments for payment of principal, in case of non- equated periodic loans</td>
                <td>1</td>
            </tr>
            <tr>
                <td></td>
                <td>b) Type of EPI<br>Amount of each EPI (in Rupees) and<br>nos. of EPIs (e.g., no. of EMIs in case of monthly instalments)</td>
                <td>N/A<br>N/A<br>N/A</td>
            </tr>
            <tr>
                <td></td>
                <td>c) No. of instalments for payment of capitalised interest, if any</td>
                <td>N/A</td>
            </tr>
            <tr>
                <td></td>
                <td>d) Commencement of repayment, post sanction</td>
                <td><?= date('Y-m-d', strtotime('+29 days')); ?></td>
            </tr>
            <tr>
                <td>3</td>
                <td>Interest rate type (fixed or floating or hybrid)</td>
                <td>Fixed</td>
            </tr>
            <tr>
                <td>4</td>
                <td>Rate of Interest</td>
                <td><?=$b['interest_percentage']?>% per day</td>
            </tr>
            <tr>
                <td>5</td>
                <td>Total Interest Amount to be charged during the entire tenor of the loan as per the rate prevailing on sanction date (in Rupees)</td>
                <td><?=$result['total_interest']?></td>
            </tr>
            <tr>
                <td>6</td>
                <td>Fee/ Charges payable (in Rupees)</td>
                <td><?=$result['processing_fee'] + ($result['processing_fee']*0.18)?></td>
            </tr>
            <tr>
                <td></td>
                <td>A Payable to the RE</td>
                <td><?=$result['processing_fee'] + ($result['processing_fee']*0.18)?></td>
            </tr>
            <tr>
                <td></td>
                <td>B Payable to third-party routed through RE</td>
                <td>N/A</td>
            </tr>
            <tr>
                <td>7</td>
                <td>Net disbursed amount (1-6) (in Rupees)</td>
                <td><?=$result['disbursed']-$b['processing_fees']*0.18?></td>
            </tr>
            <tr>
                <td>8</td>
                <td>Total amount to be paid by the borrower (sum of 1 and 5) (in Rupees)</td>
                <td><?=$result['total_amount_payable']?></td>
            </tr>
            <tr>
                <td>9</td>
                <td>Annual Percentage rate- Effective annualized interest rate (in percentage)</td>
                <td><?php echo round((((($b['processing_fees'] + $b['processing_fees']*0.18 + $result['total_interest']) / $result['loan_amount'])  / 30) * 36500),2) ?></td>
            </tr>
            <tr>
                <td>10</td>
                <td>Schedule of disbursement as per terms and conditions</td>
                <td>100% upfront</td>
            </tr>
            <tr>
                <td>11</td>
                <td>Due date of payment of instalment and interest</td>
                <td><?= date('d/m/Y', strtotime('+29 days')); ?></td>
            </tr>
        </table>

        <div class="page-footer">
            <p>Page-3</p>
            <div class="digital-signature">
                <?=convertImageToBase64('https://creditlab.in/zzesign.jpg')?>
            </div>
        </div>
    </div>
    <div class="container2">
        <div class="header-container">
            
            <div class="company-details">
                <h2>Sonu Marketing Private Limited</h2>
                <p>CIN : U51909WB1995PTC068572 | RBI Registration no : : B.05.04069</p>
                <p>NO 30, 1st Main, 2nd floor, Bannerghatta Main Road, Opposite Gopalan Innovation Mall,<br>
                BHCS Layout, BTM Layout 2nd stage, Bangalore 560076</p>
            </div>
        </div>
        <hr style="margin-top:20px;margin-bottom:15px;">

        <div class="part-title">Annex C</div>
        <div class="part-title">Repayment Schedule</div>

        <table class="table-repayment-schedule" style="margin-top:10px;">
            <tr>
                <th>Instalment No.</th>
                <th>Outstanding Principal (in Rupees)</th>
                <th>Principal (in Rupees)</th>
                <th>Interest (in Rupees)</th>
                <th>Instalment (in Rupees)</th>
            </tr>
            <tr>
                <td>1</td>
                <td><?=$result['loan_amount']?></td>
                <td><?=$result['loan_amount']?></td>
                <td><?=$result['total_interest']?></td>
                <td><?=$result['loan_amount']+$result['total_interest']?></td>
            </tr>
        </table>

        <div class="part-title">PART B- SANCTION LETTER</div>

        <div class="recipient-info">
            <p>Dear <?=$loanf['name']?>,</p>
            <p>Date: <?=date('d/m/Y');?></p>
            <p>Sub: SANCTION LETTER</p>
        </div>

        <div class="content-block">
            <p>With reference to your application for availing a loan we are pleased to sanction the same subject to the terms and conditions as mentioned above in Key Facts Statement in PART A and in the loan agreement to be executed. Payable in the manner as mentioned in the Key Facts Statement (KFS) above & in the loan agreement to be executed.</p>
        </div>

        <div class="content-block">
            <p>The Borrower understands that the Lender has adopted risk-based pricing which is arrived by considering broad parameters like the borrower's financial and credit risk profile. Hence the rates of Interest will be different for different categories of borrowers based on the internal credit risk algorithms.</p>
            <p>Please note that this communication should not be construed as giving rise to any obligation on the part of LSP/DLA/RE unless the loan agreement and the other documents relating to the above assistance are executed by you in such form and manner as may be required by LSP/DLA/RE.</p>
        </div>

        <div class="content-block">
            <p>We look forward to your availing of the sanctioned loan and assure you our best service always.</p>
        </div>

        <div class="section-heading">TERMS & CONDITIONS OF RECOVERY MECHANISM</div>

        <div class="content-block">
            <p>The lender undertakes the recovery practices considering the following terms:</p>
            <ul class="list-indent">
                <li>In-house/Outsource Recovery</li>
                <li>Digital Recovery</li>
                <li>Reminder Communication</li>
                <li>Field Collection (if required)</li>
            </ul>
            <p>Where the Lender has failed to recover the money from the borrower it will rely upon the following legal recovery:</p>
            <ul class="list-indent">
                <li>Legal Notice</li>
                <li>Arbitration & Conciliation</li>
            </ul>
            <p>For the purpose of undertaking collection and recovery the Lender may either on its own or through the Lending service provider (including its debt recovery agents etc.) undertake collection or recovery from the Borrower. For details of Lending Service Providers please refer to <a href="https://creditlab.in/lsp.php" target="_blank">List of LSPs</a></p>
            <p>All loans are to be paid to the lender only through the digital lending app or payment link generated and shared with the borrowers by the Lender.</p>
        </div>

        <div class="section-heading">Other Disclosures:</div>

        <div class="content-block">
            <ul class="list-indent">
                <li>The lender will not be responsible for any payments made to any individual or entity in their bank accounts.</li>
                <li>As per the RBI regulations information related to all borrowings and payments against those borrowings are reported to Credit Information Companies on a regular basis with in the stipulated timelines.</li>
                <li>Payment of Loans after the due date may impact your credit scores maintained by the Credit Information Companies.</li>
            </ul>
        </div>

        <div class="page-footer">
            <p>Page-4</p>
            <div class="digital-signature">
                <?=convertImageToBase64('https://creditlab.in/zzesign.jpg')?>
            </div>
        </div>
    </div>
</body>
</html>
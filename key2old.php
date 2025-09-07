<?php
include 'db.php';
function calculateEMI($loan_amount,$pro_fee_per = 13) {
    $processing_fee_rate = $pro_fee_per / 100; // 13%
    $interest_rate_per_month = 0.03; // 3%
    $days = 30;
    $processing_fee = $loan_amount * $processing_fee_rate;
    $upfront_charges = $loan_amount * $processing_fee_rate;
    $daily_interest_rate = ($interest_rate_per_month / 30);
    $total_interest = $loan_amount * $daily_interest_rate * $days;
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
$loan  = towquery("SELECT loan_apply.*,user.name,user.father_name,user.permanent_address,user.mobile,user.altmobile,user.email,user.signature,user.personaldocument,user.conpanydocument,user.marital_status FROM `loan_apply` INNER JOIN user
      ON loan_apply.uid=user.id WHERE loan_apply.id='$id'");
$loanf = towfetch($loan);
$a = towquery("SELECT * FROM user_login_details WHERE uid='".$loanf['uid']."' ORDER BY id DESC");
$b = $loanf;
             $lo = towquery("SELECT * FROM loan WHERE lid=".$b['id']);
             $lof = towfetch($lo);
             $loan_amountc = $b['amount'] + $b['processing_fees'] + $b['origination_fee'] + ($b['processing_fees']*0.18);
$result = calculateEMI($loan_amountc,$b['pro_fee_per']);
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
    return '<img src="' . $base64Image . '" alt="' . htmlspecialchars($altText) . '">';
}
$imageUrl = 'https://creditlab.in/Sonuletterhead-pdf.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sanction Letter & Key Fact Statement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1, h2 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
        }
        .header img {
            width: 100%;
        }
        .content {
            margin-top: 100px; /* Adjust this value based on header height */
        }
    </style>
</head>
<body>
    <div class="header">
        <?=convertImageToBase64($imageUrl, 'Header Image')?>
    </div>
    <div class="content">
    <h1>SANCTION LETTER & KEY FACT STATEMENT / FACT SHEET</h1>
    <p><strong>Name of the Regulated Entity (RE):</strong> SONU MARKETING PRIVATE LIMITED</p>
    <p><strong>Name of LSP:</strong> FINWINGS TECHNOLOGIES PVT LTD</p>
    <p><strong>Name of DLA:</strong> Creditlab.in</p>

    <h2>Schedule - A</h2>
    <h3>SANCTION LETTER</h3>
    <p><strong>Date:</strong> <?=date('d/m/Y');?></p>
    <p>Dear <?=$loanf['name']?>,</p>
    <p>Sub: SANCTION LETTER</p>
    <p>With reference to your application for availing a loan we are pleased to sanction the same subject to the terms and conditions as mentioned below in KFS in Schedule-B and in the loan agreement to be executed.</p>
    <p>Payable in the manner as mentioned in the Key Fact Statement (KFS) below & in the loan agreement to be executed.</p>
    <p>The Borrower understands that the Lender has adopted risk-based pricing which is arrived by considering broad parameters like the borrower's financial and credit risk profile. Hence the rates of Interest will be different for different categories of borrowers based on the internal credit risk algorithms.</p>
    <p>Please note that this communication should not be construed as giving rise to any obligation on the part of LSP/DLA/RE unless the loan agreement and the other documents relating to the above assistance are executed by you in such form and manner as may be required by LSP/DLA/RE.</p>
    <p>We look forward to your availing of the sanctioned loan and assure you our best service always.</p>

    <h3>TERMS & CONDITIONS OF RECOVERY MECHANISM</h3>
    <p>The lender undertakes the recovery practices considering the following terms:</p>
    <ul>
        <li>In-house/Outsource Recovery</li>
        <li>Digital Recovery</li>
        <li>Reminder Communication</li>
        <li>Field Collection (if required)</li>
    </ul>
    <p>Where the Lender has failed to recover the money from the borrower it will rely upon the following legal recovery:</p>
    <ul>
        <li>Legal Notice</li>
        <li>Arbitration & Conciliation</li>
    </ul>
    <p>For the purpose of undertaking collection and recovery the Lender may either on its own or through the Lending service provider (including its debt recovery agents etc.) undertake collection or recovery from the Borrower.</p>
    <p>For details of Lending Service Providers please refer to <a href="https://www.brahmafinance.in/our-polices/lending-service-providers-digital-lending-apps">brahmafinance.in</a></p>
    <p>All loans are to be paid to the lender only through the digital lending app or payment link generated and shared with the borrowers by the Lender or LSP.</p>

    <h3>Other Disclosures:</h3>
    <ul>
        <li>The lender will not be responsible for any payments made to any individual or entity in their bank accounts.</li>
        <li>As per the RBI regulations information related to all borrowings and payments against those borrowings are reported to Credit Information Companies on a regular basis with in the stipulated timelines.</li>
        <li>Payment of Loans after the due date may impact your credit scores maintained by the Credit Information Companies.</li>
    </ul>

    <h2>Schedule - B</h2>
    <div style="width:100%;">
       <h3 style="width:50%;float:left;">Key Fact Statement / Fact Sheet</h3>
       <h3 style="width:50%;float:right;"><p style="text-align:end">UNIQUE PROPOSE NUMBER : KFS<?=$id.$result['loan_amount']?></p></h3>
    </div>
    <p><strong>Date:</strong> <?=date('d/m/Y');?></p>
    <p><strong>Loan ID:</strong> CLL<?=$id?></p>
    <p><strong>Applicant Name:</strong> <?=$loanf['name']?></p>

    <table>
        <tr>
            <th>Sr.No</th>
            <th>Details of Fees & Charges</th>
            <th>Details</th>
        </tr>
        <tr>
            <td>(i)</td>
            <td>Loan amount (In Rupees)</td>
            <td><?=$result['loan_amount']?></td>
        </tr>
        <tr>
            <td></td>
            <td>product (loan type)</td>
            <td>Advance_Salary</td>
        </tr>
        <tr>
            <td>(ii)</td>
            <td>Total interest charge during the entire tenor of the loan (in Rupees)</td>
            <td><?=$result['total_interest']?></td>
        </tr>
        <tr>
            <td>(iii)</td>
            <td>Other up-front charges (break-up of each component given below) (in Rupees)</td>
            <td><?=$result['processing_fee']+$b['processing_fees']*0.18?></td>
        </tr>
        <tr>
            <td></td>
            <td>(a) Processing fees (in Rupees)</td>
            <td><?=$result['processing_fee']?></td>
        </tr>
        <tr>
            <td></td>
            <td>(b) GST (in Rupees)</td>
            <td><?=$b['processing_fees']*0.18?></td>
        </tr>
        <tr>
            <td>(iv)</td>
            <td>Total Account management charges (in Rupees)</td>
            <td>0</td>
        </tr>
        <tr>
            <td>(v)</td>
            <td>Net disbursed amount ((i)-(iii)) (in Rupees)</td>
            <td><?=$result['disbursed']-$b['processing_fees']*0.18?></td>
        </tr>
        <tr>
            <td>(vi)</td>
            <td>Total amount to be paid by the borrower (sum of (ii)(iii)(iv) and (v)) (in Rupees)</td>
            <td><?=$result['total_amount_payable']?></td>
        </tr>
        <tr>
            <td>(vii)</td>
            <td>Annual rate of Interest (R.O.I)<br> Annual Percentage Rate (A.P.R)</td>
            <td>36 %<br><?php echo round((((($b['processing_fees'] + $b['processing_fees']*0.18 + $result['total_interest']) / $result['loan_amount'])  / 30) * 36500),2) ?> %</td>
        </tr>
        <tr>
            <td>(viii)</td>
            <td>The tenor of the Loan (in days)</td>
            <td>30 days</td>
        </tr>
        <tr>
            <td>(ix)</td>
            <td>Repayment frequency by the borrower & repayment date</td>
            <td>One time & <?= date('d/m/Y', strtotime('+29 days')); ?></td>
        </tr>
        <tr>
            <td>(x)</td>
            <td>Number of installments of repayment</td>
            <td>1</td>
        </tr>
        <tr>
            <td>(xi)</td>
            <td>Amount of installment of repayment (in Rupees)</td>
            <td><?=$result['total_amount_payable']?></td>
        </tr>
    </table>

    <h3>Details about Contingent Charges</h3>
    <table>
        <tr>
            <td>(xii)</td>
            <td>
                <p><b>a) Late Payment Fees / Penal charges</b></p>
                <p>Penal charges applicable on repayment post-due date per day for each installment:</p>
                <p>First Overdue Day penalty fees: 3% of principal overdue;</p>
                <p>Second Overdue Day to Till the Loan is closed: 0.2% of principal overdue per day.</p>
                <!--<p>The above penalty charges are inclusive GST.</p>-->
                <p><b>b) Annualized Rate of Interest post-due date</b></p>
                <p>In case of loan repayment overdue basic interest charges shall continue to accrue at the same rate at 36% per annum; applicable per day on the Principal overdue amount from the First Overdue Day to Till the Loan is closed.</p>
            </td>
        </tr>
        <tr>
            <td>(xiii)</td>
            <td>
                <p>Cooling off/look-up period during which borrower shall not be charged any interest account management fee & penalty on prepayment of loan</p>
                <p><b>3 days</b></p>
                <p>Sanction Validity from date of loan sanctioned</p>
                <p><b>7 days</b></p>
            </td>
        </tr>
        <tr>
            <td>(xiv)</td>
            <td>
                <p>Lender’s Grievance Redressal Mechanism</p>
                <p>Grievance Redressal Officer (GRO):</p>
                <p>Name: Mrs. Sugandhi</p>
                <p>Mail: <a href="mailto:sugandhi@brahmafinance.com">sugandhi@brahmafinance.com</a></p>
                <p>The GRO may be reached through the e-mail address above.</p>
                <p>The Grievance Redressal Officer shall endeavour to resolve the grievance within a period of 14 (Fourteen) business days from the date of receipt of a grievance.</p>
                <p>Grievance Redressal Policy link: <a href="https://drive.google.com/file/d/1MneFsNW-I1VQYN8CawYnfZ0cxGZ6Z9ek/view?pli=1">Click here</a></p>
            </td>
        </tr>
        <tr>
            <td>(xv)</td>
            <td>
                <p>Lending Service Provider / DLA Grievance Redressal Mechanism</p>
                <p>Grievance Redressal Officer (GRO):</p>
                <p>Name: Mr. Charan</p>
                <p>Mail: <a href="mailto:grievance@creditlab.in">grievance@creditlab.in</a></p>
                <p>The GRO may be reached through the e-mail address above.</p>
                <p>The Grievance Redressal Officer shall endeavour to resolve the grievance within a period of 14 (Fourteen) business days from the date of receipt of a grievance.</p>
                <p>Grievance Redressal Policy link: <a href="https://creditlab.in/grievanceredressal.pdf">Click here</a></p>
            </td>
        </tr>
    </table>

    <h3>Instalment Details</h3>
    <table>
        <tr>
            <th>Instalment No.</th>
            <th>Outstanding Principal (in Rupees)</th>
            <th>a) Principal (in Rupees)</th>
            <th>b) Interest + account management fee (in Rupees)</th>
            <th>a + b = Instalment (in Rupees)</th>
        </tr>
        <tr>
            <td>1</td>
            <td><?=$result['loan_amount']?></td>
            <td><?=$result['loan_amount']?></td>
            <td><?=$result['total_interest']?></td>
            <td><?=$result['total_amount_payable']?></td>
        </tr>
        <tr>
            <td>2</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
        </tr>
    </table>

    <h3>Pre-closure interest calculation:</h3>
    <p><strong>Days</strong> - Interest Percentage on principal loan amount</p>
    <ul>
        <li>1-3 days - “0” interest</li>
        <li>4-10 days - 0.1% Per day</li>
        <li>11-30 days - 0.115% Per day</li>
    </ul>
    <p>Sample calculation :</p>
    <p>Principal loan amount : Rs.10000<br>
    D is the Date of disbursal considered 1st day</p>
    <p>If the borrower wants to close the loan on (D+8days) 9th day the borrower need not pay full interest. The loan can be closed with only 9 days of interest i.e</p>
    <p>= Principal loan amount + interest accumulated till 9th day</p>
    <p>=10000+(10000*6*0.001) = 10000+60<br>
    = 10060 rupees only.</p>
    <p>Note: For the first 3 days (look up period) there is no interest</p>
    </div>
    <h3>Other charges : </h3>
    <div style="padding-left:15px">
        <h4>Enach registration & Enach bounce charges :</h4>
        <p>ENach registration charges are determined by the respective banks and may vary across different banks. Similarly, ENach bounce charges are applied by banks if a payment fails, with the amount also varying from bank to bank.</p>
    </div>
</body>
</html>

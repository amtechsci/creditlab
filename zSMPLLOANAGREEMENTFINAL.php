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
$loan  = towquery("SELECT loan_apply.*,user.name,user.father_name,user.permanent_address,user.mobile,user.altmobile,user.email,user.signature,user.personaldocument,user.conpanydocument,user.marital_status FROM `loan_apply` INNER JOIN user
      ON loan_apply.uid=user.id WHERE loan_apply.id='$id'");
$loanf = towfetch($loan);
$a = towquery("SELECT * FROM user_login_details WHERE uid='".$loanf['uid']."' ORDER BY id DESC");
$b = $loanf;
             $lo = towquery("SELECT * FROM loan WHERE lid=".$b['id']);
             $lof = towfetch($lo);
             $loan_amountc = $b['amount'] + $b['processing_fees'] + $b['origination_fee'] + ($b['processing_fees']*0.18);
$result = calculateEMI($loan_amountc,$b['pro_fee_per'],$b['interest_percentage']);
}
$us_bankq = towquery("SELECT * FROM user_bank WHERE uid=".$loanf['uid']);
$us_bank = towfetch($us_bankq);
// print_r($us_bank);exit;
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
    <!--<meta name="viewport" content="width=device-width, initial-scale=1.0">-->
    <meta name="viewport" content="width=1024">
    <title>Sonu Marketing Private Limited - Loan Agreement</title>
    <style>
        body {
            background-color: #525659;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-family: 'Times New Roman', Times, serif;
        }
        .page {
            width: 794px;
            height: 1124px;
            background: white;
            margin: 20px;
            padding: 60px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            box-sizing: border-box;
            position: relative;
        }
        h1, h2, h3 {
            color: #000;
            font-weight: bold;
        }
        h1 {
            text-align: center;
            font-size: 16pt;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 13pt;
            padding-bottom: 3px;
            margin-top: 20px;
        }
        h3 {
             font-size: 12pt;
             font-weight: normal;
             font-style: italic;
        }
        p, li {
            font-size: 12pt;
            line-height: 1.5;
            text-align: justify;
            margin-bottom: 10px;
        }
        .header-info {
            text-align: center;
            margin-bottom: 25px;
            font-size: 12pt;
            line-height: 1.6;
        }
        ol {
            padding-left: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12pt;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .signature-block {
            margin-top: 40px;
        }
        .signature-block p {
             text-align: left;
             margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <div class="page">
        <div class="header-info">
            <strong>Sonu Marketing Private Limited</strong><br>
            CIN: U51909WB1995PTC068572| RBI Registration no:: B.05.04069<br>
            NO 30, 1st Main, 2nd floor, Bannerghatta Main Road, Opposite Gopalan Innovation Mall, BHCS Layout, BTM Layout 2nd stage, Bangalore 560076
        </div>
        <hr>
        <h1>Sonu Marketing Private Limited - Loan Agreement ("hereinafter referred to as "Agreement")</h1>
        <p>This Loan Agreement ("Agreement") has been executed on "<?= date('d/m/Y'); ?>" by and between,</p>
        <p><strong>Sonu Marketing Private Limited</strong> incorporated under the Companies Act 1956 having CIN- U51909WB1995PTC068572, having its registered office at SHREE KRISHNA CHAMBERS, BLOCK - B, 4TH FLLOR, ROOM NO. 4D, 78, BENTINCK STREET,, KOLKATA, West Bengal, India 700001 and corporate office at NO 30, 1st Main, 2nd floor, Bannerghatta Main Road, Opposite Gopalan Innovation Mall, BHCS Layout, BTM Layout 2nd stage, Bangalore 560076 (hereinafter referred as "<strong>Lender</strong>" unless it be repugnant to the context or meaning thereof, be deemed to mean and include authorized representatives, heirs, successors, executors, administrators, nominees, and permitted assignees, as the case may be).</p>
        <p><strong>AND</strong></p>
        <p><?=$loanf['name']?> with PAN Number: <?=$loanf['pan']?> and whose additional details are mentioned in Annexure -1 Borrower Details (hereinafter referred to as the "<strong>Borrower</strong>", unless repugnant to the context or meaning thereof, be deemed to mean and include the Borrower's authorized representatives, heirs, successors, executors, administrators, and nominees, as the case may be).</p>
        <h2>BACKGROUND</h2>
        <ol type="i">
            <li>The Lender is a Non-Banking Financial Company registered with the Reserve Bank of India engaged in the business of extending financial assistance.</li>
            <li>The Borrower has voluntarily approached the Lender to request and avail the loan facility and has agreed to be bound by all terms and conditions as prescribed by the Lender and set forth in this Agreement.</li>
            <li>The Borrower confirms that they have read, understood, and accepted the terms of this Agreement, along with the Lender's Privacy Policy and Terms and Conditions, and agrees to comply with all provisions therein in consideration of availing the loan facility.</li>
            <li>Relying on the representations and information provided by the Borrower, the Lender has agreed to sanction the Loan for the purpose specified herein, subject to the terms and conditions contained in this Agreement.</li>
        </ol>
    </div>

    <div class="page">
        <p><strong>NOW THIS LOAN AGREEMENT WITNESSETH AS FOLLOWS:</strong></p>
        <h2>1. AMOUNT OF LOAN, DISBURSEMENT AND INTEREST</h2>
        <ol type="i">
            <li>The Lender agrees to extend, and the Borrower agrees to accept, a loan facility for an amount not exceeding <?=$result['loan_amount']?>/-, for the purpose specified in this Agreement, and subject to the terms and conditions set forth herein. The said amount shall hereinafter be referred to as the "<strong>Loan</strong>."</li>
            <li>The disbursement of the Loan by the Lender shall take place after execution of this Agreement. The Lender shall disburse the Loan from its designated bank account to the Borrower's bank account, as provided in the digital lending application of the Lender. In the event that the Borrower requests to change the designated bank account, the Borrower shall submit a formal written application via registered mail to the Lender. Upon receipt of such application, the Lender shall issue a response regarding its decision to the Borrower.</li>
            <li>Post acceptance of the Loan application by the Lender, the Borrower shall have the right to request the cancellation of the loan within 12 hours of such acceptance, before the Loan is disbursed to the Borrower.</li>
            <li>Borrower shall pay Interest at the rate specified which may be changed prospectively by the Lender by providing prior notice to the Borrower. The Interest shall be calculated as on the principal amount as mentioned in the Key Facts Statement ("KFS"). The Interest shall begin to accrue from the date of disbursement of the Loan to the Borrower till the repayment due dates mentioned in the Loan agreement and KFS. The Lender may, at its sole discretion, and, subject to applicable laws, alter such due dates.</li>
            <li>Each payment made by the Borrower under the terms of this Agreement shall be made on or before the respective due date, as mentioned in the Loan Agreement and KFS.</li>
            <li>Borrower shall make the Loan repayment along with the interest and other applicable charges to the Lender in accordance with the terms mentioned in this Agreement.</li>
            <li>Without prejudice to any other rights under this Agreement, the Lender shall be entitled to levy Penal Charges from the Borrower on the occurrence of an Event of Default under these Terms. The Borrower hereby acknowledges that all sums payable under these Terms of this agreement by way of Penal Charges are reasonable and that they represent genuine pre-estimates of the loss incurred by the Lender in the event of occurrence of any default.</li>
            <li>Lender may withhold or cancel the disbursement of the Loan or any installment thereof in the event of a breach of this Agreement or any applicable law by the Borrower.</li>
            <li>The Lender will decide on the grant of the Loan based on the merits of the application, at its sole discretion.</li>
        </ol>
    </div>

    <div class="page">
        <ol type="i" start="10">
            <li>Lender may engage agencies to recover the loan amount from the Borrower. Details of these agencies shall be mentioned in the Key Fact Statement provided to the Borrower.</li>
            <li>Lender reserves the right to share the Borrower's loan information, including credit history and defaults, with its affiliates, the Reserve Bank of India, banks, financial institutions, credit bureaus, statutory bodies, tax authorities, the Central Information Bureau, research merchants, third party service providers and other organizations the Lender deems appropriate in pursuance to the Loan facility.</li>
            <li>The Loan details for this loan facility are mentioned in Annexure-II Loan Facility.</li>
        </ol>
        
        <h2>2. DEFINITIONS AND INTERPRETATION</h2>
        <h3>2.1 Definitions</h3>
        <p>In this Agreement, the following capitalized words shall have the following meanings:</p>
        <ol type="i">
            <li>"<strong>Agreement</strong>" means this Loan Agreement together with the Schedules and Annexures attached hereto as may be amended from time to time in accordance with its terms.</li>
            <li>"<strong>Annexure</strong>" means the Annexure(s) or Schedule(s) to this Agreement.</li>
            <li>"<strong>Borrower</strong>" means a person who has approached the Lender for credit facility as the context may require.</li>
            <li>"<strong>Business Day</strong>" means:
                <ol type="a" style="padding-left: 20px;">
                    <li>for determining when a notice, consent or other communication is given, a day that is not a Saturday, Sunday or public holiday in the place to which the notice, consent or other communication is sent; and</li>
                    <li>for any other purpose, a day (other than a Saturday, Sunday or public holiday) on which banks are open for general banking business in Bengaluru.</li>
                </ol>
            </li>
            <li>"<strong>Cooling Off Period</strong>" means period as determined by the Lender wherein the Borrower can exercise option for foreclosure of loan without penalty and payment of only proportionate charges.</li>
            <li>"<strong>Loan</strong>" means the principal amount sanctioned and disbursed to the Borrower by the Lender under this Agreement.</li>
            <li>"<strong>Outstanding Amount</strong>" means the amount outstanding to be repaid under the Loan which amount shall include the principal, interest and such other expenses as are agreed to be borne by the Borrower under this Agreement.</li>
        </ol>
    </div>

    <div class="page">
        <ol type="i" start="8">
            <li>"<strong>Parties</strong>" means the Lender and the Borrower.</li>
            <li>"<strong>Foreclosure</strong>" means premature repayment as per the terms and conditions laid down by the Lender in that behalf and in force at the time of prepayment.</li>
        </ol>
        
        <h3>2.2 Other terms may be defined elsewhere in the text of this Agreement and, unless otherwise indicated, shall have such meaning throughout this Agreement.</h3>
        
        <h3>2.3 Interpretation</h3>
        <ol type="i">
            <li>Wherever the context so requires, any reference to the singular includes the plural and any reference to the plural includes the singular respectively;</li>
            <li>Words of any gender are deemed to include the other gender;</li>
            <li>The arrangement of clauses shall have no bearing on their interpretation;</li>
            <li>Words denoting a person shall include an individual, corporation, company, partnership trust or other entity; provided however that clause specifically applicable to a company or body corporate shall not apply to any other entity;</li>
            <li>Heading and bold typeface are only for convenience and shall be ignored for the purposes of interpretation;</li>
            <li>Reference to the word "include" or "including" shall be construed without limitation;</li>
            <li>Schedules, sub-schedules and Annexure to this Agreement shall form an integral part hereof.</li>
            <li>The terms and expressions not herein defined shall where the interpretation and meaning have been assigned to them in terms of the General Clauses Act, 1897, have that interpretation and meaning.</li>
        </ol>

        <h2>3. REPAYMENT</h2>
        <p>It is mutually agreed between the parties, that 'time shall be the essence of this agreement' and the Borrower shall repay the amounts availed under Loan by following repayment methods, as specified by the Lender in the Sanction Letter ("Repayment Method"):</p>
        <ol type="i">
            <li>The Repayment Method and corresponding due dates as detailed under the Annexure -II shall be specified in the Loan agreement and KFS and the Borrower undertakes to make regular repayments in accordance with the Annexure -1.</li>
        </ol>
    </div>

    <div class="page">
        <ol type="i" start="2">
            <li>Without prejudice to any other rights that the Lender may have under law, in the case of Event of Default, the Borrower shall pay additional Penal Charges at such rate as provided in this agreement and the Key Fact Statement.</li>
            <li>The Lender may, without prejudice to any other rights that the Lender may have under law, with assigning any reason, cancel in full or in part the Loan and demand repayment thereof. Upon such notice, the said dues shall become forthwith due and payable by the Borrower.</li>
            <li>In the event of any suspension/ withdrawal of the facility / recall of the Loan due to any kind of improper repayment behaviour, the Borrower agrees that the Lender shall not be obligated to refund any fee paid by the Borrower.</li>
        </ol>

        <h2>4. PRE-PAYMENT OF THE LOAN</h2>
        <ol type="i">
            <li>Notwithstanding the applicable Annexure-ll and repayment method, the Borrower may prepay the full Loan ("Foreclosure"). The Lender, at its sole discretion, will grant such foreclosure subject to such terms and conditions as it deems appropriate, including without limitation, the payment of foreclosure charges if any or part thereof (except in the event of foreclosure during cooling-off period), as may be stipulated by the Lender.</li>
            <li>In pursuance of request by the Borrower to foreclose the Loan during the cooling-off period, the Borrower shall be liable to pay proportionate charges for the Loan. For foreclosures made after the cooling-off period, the Borrower shall be liable to pay the entire applicable loan charges, as determined by the Lender.</li>
        </ol>

        <h2>5. BORROWER COVENANTS</h2>
        <ol type="i">
            <li>The Borrower shall utilize the entire Loan for the purposes specified in this Loan Agreement and unless otherwise agreed to by the Lender in writing for no other purpose whatsoever.</li>
            <li>The Borrower shall duly and punctually comply with all the terms and conditions this Agreement. The Borrower affirms that they are legally competent and possess the necessary legal authority to enter into, execute, and fulfill the obligations outlined in this Agreement. Borrower warrants that obtaining the Loan, complying with the terms and conditions of this Agreement, and executing this Agreement do not and will not violate any applicable laws or the Borrower's contractual obligations. Furthermore, the Borrower fully understands the terms of this Agreement and is both financially and legally competent of entering into this arrangement and performing all obligations stipulated herein.</li>
            <li>The Borrower shall be solely and unconditionally liable for the repayment of all amounts due and will make payments regardless of any reminders, demands, or notices issued. Borrower shall not withhold payment Lender under these terms and conditions, and agrees to receive updates, messages, or other communications with reference to the Loan on the designated mobile number or email address.</li>
        </ol>
    </div>

    <div class="page">
        <ol type="i" start="4">
            <li>The Borrower undertakes that the amount repaid by the Borrower shall be appropriated first towards principal amount, interest, penal charges and any other costs.</li>
            <li>The Borrower undertakes to keep the Lender updated immediately about any changes in the information provided to the Lender from time to time.</li>
            <li>Borrower shall not assign, sell, or transfer any rights or obligations under these terms and conditions to any other person without the prior approval of Lender;</li>
            <li>The Borrower undertakes to always act in good faith in all his / her dealings in relation to the Loan and the Lender.</li>
            <li>The Borrower agrees and authorises the Lender to use his / her Aadhaar Number to update all of his / her other loan facilities availed from the Lender (if any), for KYC purpose and/or for any other purpose and / or as may be required by the RBI Master Directions - Know Your Customer Directions (as amended from time to time) or any other applicable law.</li>
        </ol>
        
        <h2>6. BORROWER WARRANTIES</h2>
        <ol type="i">
            <li>The Borrower confirms the accuracy of the information given in his loan application made to the Lender and any prior or subsequent information or explanation given to the Lender in this behalf and such information shall be deemed to form part of the representations and warranties on the basis of which the lender has sanctioned the Loan.</li>
            <li>Borrower confirms that he has an annual household income exceeding Rs. 3,00,000 (Rupees Three Lakhs), where the term "household" refers to a single family unit, consisting of the Borrower, their spouse, and their unmarried children above the age of 18 years.</li>
            <li>The Borrower confirms that his/her name does not appear in the list of defaulters or wilful defaulters maintained by the Reserve Bank of India (RBI), the Credit Information Companies (CICs), or any caution/advisory list maintained by the Export Credit Guarantee Corporation (ECGC). The Borrower further declares that he/she is not listed on any sanctions or watchlists issued by competent authorities including the United Nations Security Council (UNSC), the Financial Action Task Force (FATF), or any government agency in connection with anti-money laundering (AML), combating financing of terrorism (CFT), or related regulatory frameworks. The Borrower neither has / had any insolvency proceedings against him / her, nor has ever been adjudicated insolvent by any court or other authority.</li>
            <li>The Borrower understands and acknowledges that the Lender shall have absolute discretion, without assigning any reason to reject his / her Application Form and that the Lender shall not be responsible/liable in any manner whatsoever for such rejection.</li>
            <li>The Borrower hereby consents to the verification of the Know Your Customer (KYC) details by the Lender or their authorized representatives or agents.</li>
        </ol>
    </div>

    <div class="page">
        <ol type="i" start="6">
            <li>The Borrower hereby consents to the recording of any telephonic conversations between the Borrower and the Lender or their authorized representatives, for the purposes of verification and record-keeping.</li>
            <li>The Borrower hereby agrees to mandatorily submit a copy of their required documents to the Lender, as required for the processing and verification of the loan.</li>
            <li>The Borrower hereby consents to the Lender sending communications via WhatsApp, email, SMS, telephone, or any other electronic medium for the purpose of informing the Borrower regarding the loan status, including but not limited to reminders for loan closure.</li>
            <li>The Borrower hereby agrees that he/she has understood the terms of this agreement and also agrees that they shall request the Lender for the agreement in vernacular language if needed.</li>
        </ol>
        <p>Any violation of the covenants and warranties set forth herein shall constitute a breach of material term of this Agreement and will result in any action deemed appropriate at the sole discretion of the Lender.</p>

        <h2>7. TERM OF THE LOAN AND TERMINATION</h2>
        <ol type="i">
            <li>The Agreement shall become binding on and from the date of execution hereof unless terminated earlier in pursuance to event of default. It shall be in force till all the monies due and payable to the Lender under this Agreement are fully paid.</li>
            <li>The Lender may at its sole discretion and with assigning any reason and upon written notice mailed or delivered to the Borrower terminate the Loan in full or part.</li>
            <li>Upon such termination, the Lender shall have the right to demand repayment of the total outstanding amount and upon such demand the total outstanding amount shall become forthwith due and payable by the Borrower to the Lender.</li>
        </ol>

        <h2>8. EVENT OF DEFAULT</h2>
        <h3>8.1 Each of the following events or circumstances would constitute events of default under the terms of the Facility Documents ("Event of Default")::</h3>
        <ol type="i">
            <li>Default shall have occurred in the performance of any of the covenants, conditions or agreements on the part of the Borrower under this Agreement in respect of the Loan and such default shall have continued over a period of 30 days after notice thereof shall have been given by the Lender to the Borrower, or if the Borrower fails to inform the Lender of the happening of event of default.</li>
        </ol>
    </div>
    
    <div class="page">
        <ol type="i" start="2">
            <li>Any information given by the Borrower in his loan application to the Lender for the Loan is found to be misleading or incorrect in any material respect or any covenant or warranty is found to be incorrect.</li>
            <li>The Borrower is in breach of any of the covenants provided in Covenants and Undertakings.</li>
            <li>The Borrower is found to be in breach of any of the representations made by the Borrower as provided in Representations and Warranties.</li>
            <li>The Borrower has or there is a reasonable apprehension (in the sole opinion of the Lender) that the Borrower would voluntarily become the subject of proceedings under any bankruptcy or insolvency law.</li>
            <li>The Borrower has failed to furnish information/ documents as required by the Lender.</li>
            <li>Failure to make repayment on the repayment due dates.</li>
        </ol>
        
        <h3>8.2 On the occurrence of any Event of Default, the Lender is entitled to undertake any or all of the following:</h3>
        <ol type="a" start="2" style="list-style-type: lower-alpha; padding-left: 40px;"> <li>terminate this Agreement with immediate effect and/or as the case may be;</li>
            <li>call upon the Borrower to pay forthwith all the outstanding balance in respect of Loan together with Interest, principal amount, penal charges, and all other sums payable as per the loan application Documents</li>
            <li>impose applicable penal charges and/or as the case may be;</li>
            <li>exercise any other right or remedy available under law or contractual agreements, including but not limited to initiating proceedings under Section 138 and/or Section 141 of the Negotiable Instruments Act, 1881, and Section 25 of the Payment and Settlement Systems Act, 2007</li>
            <li>In addition to the above, so long as there shall be an Event of Default, the Borrower shall pay the Penal Charges (as provided in the Loan Agreement and KFS) until such Event(s) of Default is/are rectified to the satisfaction of the Lender, without any prejudice to the remedies available to the Lender or the consequences of Events of Default.</li>
            <li>The Borrower acknowledges that the Lender may enforce payment of all outstanding amounts under this Agreement against the Borrower's estate and assets, and that this Agreement shall remain binding on the Borrower's heirs, executors, legal representatives, and administrators.</li>
            <li>Without prejudice to any other rights available to the Lender under this Agreement, the Lender shall have the right to initiate criminal proceedings or take any other appropriate legal action against the Borrower if, at its sole discretion, it has reasonable grounds to believe that the Borrower has provided any false information, misrepresented facts, or submitted forged documents or fabricated data. Further, if the Borrower becomes untraceable, the Lender reserves the right to contact the Borrower's family members, referees, or friends to ascertain the Borrower's whereabouts.</li>
        </ol>
    </div>

    <div class="page">
        <h2>9. WAIVER</h2>
        <p>No delay in exercising or omission to exercise, any right, power or remedy accruing to the Lender, shall impair any such right, power or remedy or shall be construed to be a waiver thereof or any acquiescence by it in any default; nor shall the action or inaction of the Lender in respect of any default or any acquiescence by it in any default affect or impair any right, power or remedy of the Lender in respect of any other default.</p>

        <h2>10. SEVERABILITY</h2>
        <p>If any provision of this Agreement is invalid, unenforceable or prohibited by law, this Agreement shall be considered divisible as to such provision and such provision, shall be inoperative and shall not be part of the consideration moving from either Party hereto to the other, and the remainder of this Agreement shall be valid, binding and of like effect as though such provision was not included herein.</p>
        
        <h2>11. INDEMNIFICATION</h2>
        <p>Borrower hereto indemnifies and agrees to defend and hold the Lender harmless from and against all liabilities, obligations, losses, expenses, costs, claims and damages (including all legal costs), whether direct or indirect, asserted against, imposed upon or incurred by such party by reason of or resulting from any breach or inaccuracy of any representation, warranty or covenant of either party set forth in this Agreement and/or any breach of any provisions of this Agreement by the Borrower. The indemnification rights of each party under this clause are independent of, and in addition to, such rights and remedies that Lender may have at law or in equity or otherwise, including the right to seek specific performance, rescission, restitution or other injunctive relief, none of which rights or remedies shall be affected or diminished thereby.</p>

        <h2>12. GRIEVANCE REDRESSAL</h2>
        <p>The Lender has established an adequate grievance redressal policy to address any complaints or grievances from the Borrower with relation to the credit facility, which the Borrower may refer to on the Lender's  <a href="https://creditlab.in/grievanceredressal.pdf" target="_blank">website.</a></p>
    </div>
    
    <div class="page">
        <h2>13. GOVERNING LAW</h2>
        <ol type="i">
            <li>Any dispute, difference, or claim arising out of or relating to this Agreement shall be referred to and resolved through arbitration by a sole arbitrator, appointed and nominated by the Lender. The arbitration proceedings shall be governed by the Arbitration and Conciliation Act, 1996, along with any amendments thereto. The venue for arbitration shall be Bangalore, and the language of the proceedings shall be English.</li>
            <li>The award rendered by the arbitrator shall be final and binding on both parties. In the event of the death or incapacity of the initially appointed arbitrator, the Lender shall appoint a replacement, who will be entitled to continue the arbitration from the point where the previous arbitrator left off.</li>
            <li>This Agreement shall be subject to the exclusive jurisdiction of the courts in Bangalore.</li>
        </ol>

        <h2>14. DATA AND PRIVACY</h2>
        <ol type="a" style="list-style-type: lower-alpha; padding-left: 40px;">
            <li>The Borrower's information will be collected and used only in accordance with the terms of this Agreement, the privacy policy on the Lender (for information usage), and applicable laws.</li>
            <li>All loan documents, agreements, sanction letters, and KFS statements shall be digitally stored and maintained by the Lender for record-keeping and future reference.</li>
            <li>The Lender may share the Borrower's loan information, including credit history and defaults, with its affiliates, the Reserve Bank of India (RBI), and other organizations deemed appropriate by the Lender, including for purposes such as fraud checks, performance data submission to bureaus, and self-regulatory organizations;</li>
            <li>The Lender may request credit reports, loan history, and other relevant information about the Borrower from credit bureaus, statutory bodies, tax authorities, the Central Information Bureau, research merchants, or any other organizations the Lender deems necessary;</li>
            <li>The Lender may share and disclose the Borrower's information with credit bureaus, lending service providers, and third-party service providers for purposes related to this Agreement and in accordance with applicable laws;</li>
            <li>The Borrower shall not hold the Lender liable for the use of this information or for conducting any background checks and verifications;</li>
            <li>The Borrower grants the Lender consent to collect, store, process and utilise information and data about the Borrower as outlined in the Privacy Policy and Terms and Conditions of the Lender.</li>
        </ol>
    </div>
    
    <div class="page">
        <h2>15. ASSIGNMENT</h2>
        <p>Lender may assign or delegate any or all of its rights, powers, and functions under this Agreement to one or more third parties. The Borrower hereby provides their unqualified consent to such assignment or delegation.</p>
        
        <h2>16. NOTICE</h2>
        <p>Any notice or other communication to be given by one Party to any other Party under, or in connection with, this Agreement shall be made in writing and signed by or on behalf of the Party giving it. It shall be served by letter or facsimile transmission (save as otherwise provided herein) and shall be deemed to be duly given or made when delivered (in the case of personal delivery), at the time of transmission (in the case of facsimile transmission, provided that the sender has received a receipt indicating proper transmission and a hard copy of such notice or communication is forthwith sent by prepaid post to the relevant address set out below) or five days after being dispatched in the post, postage prepaid, by the most efficient form of mail available and by registered mail if available (in the case of a letter) to such party at its address or facsimile number specified below, or at such other address or facsimile number as such Party may hereafter specify for such purpose to the other Parties hereto by notice in writing. The Parties understand that some confidential information may be transmitted over electronic mail and there are risks associated with the use of electronic mail, which can include the risk of interception, breach of confidentiality, alteration, loss or a delay in transmission, and that information sent by this means may be susceptible to forgery or distortion and agree to accept the risks of distribution by electronic mail.</p>
        <p><strong>Lender designated Mail id: support@creditlab.in</strong></p>
        
        <h2>17. VARIATION</h2>
        <p>No variation of this Agreement shall be binding on any Party unless, and to the extent that such variation is recorded in a written document executed by such Party, but where any such document exists and is so signed such Party shall not allege that such document is not binding by virtue of an absence of consideration.</p>
    </div>
    
    <div class="page">
        <h2>18. FORCE MAJEURE</h2>
        <p>Any circumstance beyond the reasonable control of a Party, such as natural disasters, acts of war, strikes, pandemics, or governmental actions, which impedes a Party's ability to fulfill its obligations. The affected Party must notify the other within ten business days and may suspend its performance for the duration of the event. Obligation deadlines will extend accordingly. If the disruption persists beyond thirty days, either Party may terminate the Agreement without liability. Notably, under no circumstances the Pledgor’s obligation to make payments shall be suspended during a Force Majeure Event.</p>
        
        <h2>19. ENTIRE AGREEMENT</h2>
        <p>Agreement, Privacy Policy and Terms and Conditions of Lender (including Borrower’s consent for use of information) shall constitute the entire Agreement between the parties.</p>
        
        <div class="signature-block">
            <p><strong>For and on behalf of Lender (Sonu Marketing Private Limited)</strong></p>
            <div class="digital-signature" style="width:200px;">
                <img src="https://creditlab.in/zzesign.jpg" alt="Company Logo" style="width:100%;">
            </div>
            <p>Reason: Loan Agreement</p>
            <br><br>
            <p><strong>I HAVE ACCEPTED THE LOAN AGREEMENT WITH THE TERMS AND CONDITIONS THEREIN</strong></p>
            <div class="digital-signature" style="width:200px;">
                <img src="https://creditlab.in/user/uploads/<?=$loanf['signature']?>" alt="signature" style="width:100%;">
            </div>
        </div>
    </div>

    <div class="page">
        <h2>ANNEXURE I (Borrower Details)</h2>
        <table>
            <thead>
                <tr>
                    <th>Sl. No.</th>
                    <th>Borrower Information</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1.</td>
                    <td>Name</td>
                    <td><?=$loanf['name']?></td>
                </tr>
                <tr>
                    <td>2.</td>
                    <td>Aadhar number</td>
                    <td><?=substr_replace($loanf['aadhar'], 'XXXXXXXX', 0, 8);?></td>
                </tr>
                <tr>
                    <td>3.</td>
                    <td>Address</td>
                    <td><?=$loanf['permanent_address']?></td>
                </tr>
                <tr>
                    <td>4.</td>
                    <td>Phone Number</td>
                    <td><?=$loanf['mobile']?></td>
                </tr>
                <tr>
                    <td>5.</td>
                    <td>Email ID</td>
                    <td><?=$loanf['email']?></td>
                </tr>
                <tr>
                    <td>6.</td>
                    <td>Bank Name & IFSC</td>
                    <td><?=$us_bank['bank_name']?> / <?=$us_bank['ifsc_code']?></td>
                </tr>
                <tr>
                    <td>7.</td>
                    <td>Bank Account Number</td>
                    <td><?=$us_bank['ac_no']?></td>
                </tr>
            </tbody>
        </table>

        <h2>ANNEXURE II – Loan Facility (or) KFS (or) KEY FACT STATEMENT</h2>
    </div>
    <div class="page">
        <div class="part-title">PART A - Key Facts Statement</div>
        <div class="part-title">Point A</div>
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
    </div>
    <div class="page">

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
        </table>
    </div>
    <div class="page">
        <table class="table-contingent-charges">
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
                        <li>On the first day after the due date: You'll be charged a one-time penalty of 3% of the overdue principal amount.</li>
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
    </div>
    <div class="page">
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
        
    </div>
    
    <div class="page">
        <div class="part-title">Point B</div>
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
        
        <div class="part-title">Point C</div>
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
    </div>
    
    
</body>
</html>
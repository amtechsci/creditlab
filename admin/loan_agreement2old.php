<?php
include '../db.php';
function base6($filePath){
if (file_exists($filePath)){
    $fileData = file_get_contents($filePath);
    $base64Data = base64_encode($fileData);
    $fileType = mime_content_type($filePath);
    $base64String = "data:$fileType;base64,$base64Data";
} else {
    $base64String = '';
}
return $base64String;
}
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
$loan  = towquery("SELECT loan_apply.*,user.name,user.father_name,user.aadhar,user.permanent_address,user.mobile,user.altmobile,user.email,user.signature,user.personaldocument,user.conpanydocument,user.marital_status FROM `loan_apply` INNER JOIN user
      ON loan_apply.uid=user.id WHERE loan_apply.id='$id'");
$loanf = towfetch($loan);
// $a = towquery("SELECT * FROM user_login_details WHERE uid='".$loanf['uid']."' ORDER BY id DESC");
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
$a = towquery("SELECT * FROM user_login_details WHERE uid='".$loanf['uid']."' AND ip_address IS NOT NULL ORDER BY id DESC");
$af = towfetch($a);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Agreement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
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
        <h1>LOAN AGREEMENT</h1>
        <p>This LOAN AGREEMENT (“Agreement”) is being entered into on <?=date('d/m/Y');?> (“Execution Date”).</p>
        <p>BETWEEN</p>
        <p><strong>SONU MARKETING PRIVATE LIMITED</strong>, a RBI registered Non-Banking Financial Company having Corporate Identification Number U51109WB1994PTC062753 having its registered office at 71 Metcalfe Street 3rd Floor Room No- 302  Kolkata West Bengal India – 700013, herein after referred to as the “Lender” (which expression shall unless repugnant to the subject and context hereof be deemed to include its successor(s), novates, transferees, nominees, and assigns) of the FIRST PART; AND</p>
        <p><strong><?=$loanf['name']?></strong>, son/daughter of <?=$loanf['father_name']?> having Adhaar Card No **** **** <?=substr($loanf['aadhar'], -4)?>, Indian citizen residing at <?=$loanf['permanent_address']?>, herein after referred to as the “Borrower” (which expression shall unless repugnant to the subject and context hereof be deemed to include the successors of the Borrower) of the SECOND PART.</p>
        <p>The Lender and the Borrower are hereinafter individually referred to as "Party" and collectively as the "Parties".</p>

        <h2>WHEREAS</h2>
        <p>The Borrower is the registered user of the ‘Creditlab.in’ (Digital Lending App “DLA”) managed and owned by Finwings Technologies Private Limited (“Lending Service Provider”) who has requested the Lender for financial assistance. The Lender has agreed to advance the Principal Amount (as defined hereunder) subject to the terms and conditions mentioned in this Agreement.</p>
        <p>The Parties have now agreed to execute this Agreement recording the provisions including the covenants and representations of the Borrower in relation to the debt requirement of the Borrower which is being funded by the Lender in terms of this Agreement.</p>
        <p>IN CONSIDERATION of the mutual covenants and agreements set forth herein and for other good and valuable consideration the receipt and sufficiency of which is acknowledged, the Parties intending to be legally bound by this Agreement hereby agree as follows:</p>

        <h2>NOW THIS AGREEMENT WITNESSETH AS BELOW:</h2>

        <h3>DEFINITIONS AND INTERPRETATION</h3>
        <p>In this Agreement, the following expressions shall unless the context otherwise requires have the following meanings:</p>
        <ul>
            <li><strong>“Agreement”</strong> means this Agreement as of the date hereof and as amended or supplemented in accordance with the provisions hereof together with schedule annexures attached and / or that may be attached in future thereto.</li>
            <li><strong>“Applicable Law”</strong> means any statute, law, enactment, regulation, ordinance, policy, treaty, rule, judgment, notification, directive, guideline, requirement, rule of common law, order, decree, bye-law, permits, licenses, approvals, consents, authorizations, government approvals or any restriction or condition or any similar form of decision of or determination application or execution by or interpretation or pronouncement having the force of law of any governmental authority having jurisdiction over the matter in question, whether in effect as of the Execution Date or thereafter.</li>
            <li><strong>“Annual Percentage Rate (APR)”</strong> shall have the meaning ascribed to it in the Schedule.</li>
            <li><strong>“Annual Fixed Rate of Interest”</strong> shall have the meaning ascribed to it in the REPAYMENT Schedule.</li>
            <li><strong>“Business Day”</strong> means any day other than a public holiday under Section 25 of the Negotiable Instruments Act 1881 or a Saturday/Sunday on which banks are open for general business in Place(s) of Business).</li>
            <li><strong>“Digital Lending App (DLA)”</strong> means mobile or/and web-based applications “Creditlab.in” with user interface that facilitates lending transactions between borrower and lender.</li>
            <li><strong>“Default Notice”</strong> shall have the meaning ascribed to it in Clause 4 of this Agreement.</li>
            <li><strong>“Disbursal Amount”</strong> shall have the meaning ascribed to it in the REPAYMENT Schedule.</li>
            <li><strong>“Event of Default” or “Default”</strong> shall have the meaning ascribed to it in Clause 4 of this Agreement.</li>
            <li><strong>“Final Repayment Date”</strong> shall have the meaning ascribed to it in the REPAYMENT Schedule.</li>
            <li><strong>“Insurance Premium”</strong> shall mean the insurance charges deducted from the facility amount as opted by the borrower at the time of availing of loan facility pursuant to this Agreement if applicable.</li>
            <li><strong>“Insurance Charges”</strong> shall have the meaning ascribed to it in the REPAYMENT Schedule.</li>
            <li><strong>Look-Up period</strong> shall mean a period of 3 days from the date of loan disbursal within which the borrower can exit or cancel his loan in case he decides not to continue with the loan.</li>
            <li><strong>“Lending Service Provider (LSP)”</strong> shall mean an agent appointed by Lender to carry out one or more of lender’s functions in relation to customer acquisition, underwriting support, pricing support, disbursement, servicing, monitoring, collection, liquidation of specific loan or loan portfolio.</li>
            <li><strong>“Account Management Fee”</strong> shall have the meaning ascribed to it in the REPAYMENT Schedule.</li>
            <li><strong>“Other Product/ Services”</strong> shall mean products/services opted by the borrower at the time of availing of loan facility pursuant to this Agreement.</li>
            <li><strong>“Outstanding Amount”</strong> shall mean the entire outstanding Principal Amount along with all Interest Amount (as applicable), Processing Fee, Account Management Fee, Default Interest, penalty charges if any, Insurance Charges if any, Other products or Services, other charges, taxes, any and all expenses for the enforcement and collection of any amounts due under the Agreement and any other charges, dues and monies payable, costs and expenses reimbursable as outstanding from time to time, and whether any of them are due under the Transaction Documents.</li>
            <li><strong>“Person”</strong> shall mean and include an individual, association of persons, corporation, trust, partnership, unincorporated body, or government or subdivision thereof, or any other entity.</li>
            <li><strong>“Principal Amount”</strong> shall have the meaning ascribed to it in the REPAYMENT Schedule.</li>
            <li><strong>“Processing Fee”</strong> shall have the meaning ascribed to it in the REPAYMENT Schedule.</li>
            <li><strong>“Penal Charges”</strong> shall have the meaning ascribed to it in the REPAYMENT Schedule.</li>
            <li><strong>“Repayment Date”</strong> shall mean each date on which a Repayment Installment shall be paid in the manner set out in the REPAYMENT Schedule.</li>
            <li><strong>“Repayment Installment”</strong> shall have the meaning ascribed to it in the REPAYMENT Schedule.</li>
            <li><strong>“Sanction Letter”</strong> shall mean the letter issued by the Lender to the Borrower sanctioning in-principle the Facility being granted hereby along with the terms and conditions.</li>
            <li><strong>“REPAYMENT Schedule”</strong> shall mean the schedule attached to this Agreement pertaining to the details, terms, and conditions of the transaction contemplated under this Agreement.</li>
            <li><strong>“Transaction Documents”</strong> shall mean this Agreement, any amendments or modifications made to the said agreements.</li>
        </ul>

        <h3>Interpretation</h3>
        <p>Unless the context of this Agreement otherwise requires:</p>
        <ul>
            <li>Words of any gender are deemed to include all genders.</li>
            <li>The terms “hereof”, “herein”, “hereby”, “hereto”, and derivative or similar words refer to this entire Agreement or specified Clauses of this Agreement as the case may be.</li>
            <li>The term “Clause” refers to the specified Clause of this Agreement.</li>
            <li>References to Recitals, Clauses, Annexures, or Schedules are unless the context otherwise requires to Recitals, Clauses, of Annexures, or Schedules to this Agreement.</li>
            <li>Heading and bold typeface are only for convenience and shall be ignored for the purpose of interpretation.</li>
            <li>Reference to any legislation or Applicable Law or to any provision thereof shall include references to any such Applicable Law as it may after the date hereof from time to time be amended, supplemented, or re-enacted and any reference to statutory provision shall include any subordinate legislation made from time to time under that provision.</li>
            <li>Time is of the essence in the performance of the Parties’ respective obligations and if any time period specified herein is extended, such extended time shall also be of the essence.</li>
            <li>Reference to the “include” shall be construed without limitation.</li>
            <li>All references to agreements (including this Agreement), documents, or other instruments include a reference to that agreement, document, or instrument as amended, supplemented, substituted, novated, or assigned from time to time.</li>
        </ul>

        <h3>FACILITY AND TERM</h3>
        <h4>Facility</h4>
        <p>The Lender at the request of the Borrower has agreed to lend to the Borrower and the Borrower agreed to borrow from the Lender the Facility as mentioned in Schedule I (“REPAYMENT Schedule”) annexed to this Agreement hereto on the basis of and subject to the conditions, terms, and conditions herein set forth.</p>

        <h4>Term</h4>
        <p>This Agreement shall become effective from the Execution Date and shall remain valid till Final Repayment Date as applicable or until the entire Outstanding Amount and dues are paid in full unless terminated before Repayment Date as applicable in accordance with Clause 7 of this Agreement.</p>

        <h3>TRANSACTION</h3>
        <h4>Disbursal</h4>
        <p>The Lender shall transfer the Principal Amount after deducting all such charges and fees as stated in Clause 3.2 below and REPAYMENT Schedule to the bank account of the Borrower, the details of which are captured in the REPAYMENT Schedule within 1 (one) Business Day from the Execution Date of this Agreement. The Lender shall assign a ‘Unique Loan ID’ in relation to the transaction as provided in the REPAYMENT Schedule on disbursement of Disbursal Amount.</p>

        <h4>Deductions</h4>
        <p>Lender shall advance the Principal Amount less the Processing Fee, Loan Management Fee, Insurance premium if any, and Charges for other products and services (if applicable), GST charges on any services provided to the Borrower, and such other additional fees, applicable taxes, and costs as applicable as detailed in REPAYMENT Schedule, which shall be deducted from the Principal Amount in accordance with this Agreement.</p>

        <p>The borrower agrees and acknowledges that the Lender is entitled to deduct Processing fee, Insurance, and charges for other products/ services (if applicable), GST charges on any services provided to the Borrower, and such other applicable taxes as applicable and detailed in REPAYMENT Schedule from the Principal Amount. The borrower agrees and acknowledges to pay Account management fee while repayment of loan along with the interest & penalty charges as applicable detailed in REPAYMENT schedule.</p>

        <p>Notwithstanding anything stated herein, the Lender shall have the right to deduct any such charges, fees, costs incurred for completion of the transaction and services provided to the Borrower from the Principal Amount as detailed hereunder in REPAYMENT schedule.</p>

        <h4>Interest and Penal Charges</h4>
        <p>The Borrower shall be liable to pay the Account management fee, Interest Amount along with the Principal Amount on or before the Repayment Date. The Borrower acknowledges that each Repayment Installment takes into account the relevant portion of the Interest Amount before Repayment Date and agrees that the part of the Interest Amount before Repayment Date which forms part of the last Repayment Installment shall be paid on or before the Repayment Date. The interest shall continue to be charged to the borrower till the actual date of payment at the agreed rate of interest.</p>

        <p>An amount of Rs.<?=$result['loan_amount']?> at an annual Rate of Interest of 36% & Account Management Fee of Rs.0 shall be payable by the Borrower along with the Principal Amount on or before the corresponding Repayment Date(s). The borrower can prepay the total outstanding amount without any charges & also the borrower has to pay the interest only till that repayment date as per the Foreclosure calculation shown below.</p>

        <p>In the event the Borrower fails to pay the Repayment Installment after the Repayment Due Date, the Borrower shall be liable to pay penal charges in the manner more specifically detailed in REPAYMENT Schedule, and the lender shall continue to charge the Annualised Rate of Interest 36% over the repayment installment due till the actual date of payment.</p>

        <h4>Repayment</h4>
        <p>The Borrower shall on each Repayment Date as stipulated in the REPAYMENT Schedule pay the corresponding Repayment Installment directly into the bank accounts of the Lender. Unless otherwise intimated by the Lender, the Borrower shall repay such amounts using the ‘Creditlab.in’ application/ Website operated by DLA.</p>

        <p>No notice, reminder, or intimation shall be required to be given to the Borrower regarding the Outstanding Amounts payable hereunder when the same are due and payable, rather it shall entirely be the sole responsibility of the Borrower to ensure prompt and regular payment of the amount payable by the Borrower to the Lender when due and in accordance with the Repayment Schedule.</p>

        <p>In the event of payment of any Repayment Installment (or any other Outstanding Amount if applicable) beyond the relevant Repayment Date by the Borrower, the Borrower shall be liable to pay the penal charges over the unpaid Outstanding Amount for each installment following the expiry of the relevant Repayment Date up to the date of actual payment. The Borrower shall continue to pay the Interest at an annualized Interest rate of 36% p.a. per day on the amount overdue for each Repayment Installment. It is hereby clarified that the Default Interest shall be due and payable for each delay in the payment of the Repayment Installment from its corresponding Repayment Date.</p>

        <p>It is further clarified that the Borrower shall be deemed to have committed a ‘Default’ in terms of this Agreement upon delay in payment of any or all Repayment Installment(s) on the corresponding Repayment Date(s). The payment of Interest Amount after Repayment Date shall not absolve the Borrower of the other obligations including payment of penal charges, timely payment of the next Repayment Installment, and/or in respect of such default or affect any of the other rights of the Lender under this Agreement.</p>

        <p>The Principal Amount along with the Interest Amount due and accrued, any applicable penal charges, or any other charges due as specified in this Agreement shall be immediately due and payable upon demand by the Lender at any time after Repayment Date in accordance with the terms and conditions contained herein.</p>

        <h4>Prepayment</h4>
        <p>The Borrower may at any time or from time to time prepay the total of the outstanding Repayment Installments prior to the Repayment Date. It is hereby acknowledged by the Parties that in case of prepayment during the look-up period, the borrower has to repay only the principal amount to exit the loan.</p>

        <p>In case of prepayment after the look-up period, the borrower shall be liable to pay proportionate interest up to the date of repayment.</p>

        <h4>Look-Up Period</h4>
        <p>A Look-up period of 3 days from the date of loan disbursal shall be provided to borrowers as an explicit option to exit from the loan by paying the principal amount without charging any penalty or interest during this period.</p>

        <h3>EVENT OF DEFAULT</h3>
        <p>The following events shall be construed as a default for the purpose of this Agreement:</p>
        <ul>
            <li>The Borrower is in violation of any Applicable Law which would have or likely to have an adverse effect on (i) the Borrower's ability to perform all or any of its obligations under this Agreement including repayment of the Principal Amount; or (ii) on the business or financial or other conditions or operations of the Lender.</li>
            <li>The Borrower does not pay any amount payable pursuant to this Agreement in accordance with the terms of this Agreement on the due date of such amount.</li>
            <li>Any representation or statement made or deemed to be made by the Borrower in this Agreement or any other document delivered by or on behalf of the Borrower under or in connection with the Agreement being incorrect or misleading when made or deemed to be made.</li>
            <li>The Borrower is in breach of or has omitted to observe or defaulted in any of his obligations, covenants, warranties, undertakings, and liabilities under (i) this Agreement; or (ii) the terms and conditions agreed with Lending Service Provider (including the privacy-policy and terms-and-conditions).</li>
            <li>The Borrower commits an act of fraud, gross negligence, or willful misconduct.</li>
        </ul>

        <h4>Consequences of an Event of Default</h4>
        <p>Upon the occurrence of a Default, the Lender shall have the following rights:</p>
        <ul>
            <li>To declare in terms of the Default Notice (or such other notice to the Borrower) all or a part of the Outstanding Amount to become immediately due and payable whereupon they shall become immediately due and payable for the purposes of this Agreement.</li>
            <li>Initiate appropriate proceedings, whether arbitral, civil, or criminal proceedings against the Borrower and the Borrower shall be liable for payment of all legal and other costs and expenses resulting from Default.</li>
            <li>Disclose the fact of such default by the Borrower to references provided by the Borrower who have explicitly provided their consent via any medium (online or offline) including by way of SMS to such persons (which information and right of access and dissemination the Borrower has consented to hereby and otherwise) for necessary remedial steps; without prejudice to the generality of the foregoing. Notwithstanding anything contained in this Agreement and subject to Applicable Law.</li>
            <li>The Borrower agrees that the Lender may either directly or through its LSPs or employees share information (other than any information that may be construed as sensitive personal data or information as defined under the Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules 2011) with third parties, the details of such third parties is disclosed to the borrower in the DLA`s Privacy Policy available at its website <a href="https://creditlab.in/privacy">privacy link</a> including without limitation to debt collection agencies, credit rating agencies, lending service providers, banks, non-banking financial institutions, credit information companies. Borrower confirms that he/she does not have and shall not raise at any time any objection or action in relation to the Lender sharing the Borrower’s information in the manner described herein. The Borrower explicitly consents to share his personal details/KYC information to the insurer/vendor of product/service as required for granting the insurance/service/product if applicable. The Lender shall not be obliged to take explicit consent of the borrower before sharing of his personal information in case the requirement of sharing such information is as per the statutory or regulatory requirement.</li>
            <li>Engage recovery agents as LSPs for the purpose of following-up with the Borrower for recovery of the amounts Payable by the Borrower. The borrower may refer to the active list of recovery agents on the website “Creditlab.in” if any.</li>
            <li>Take any action as permitted under Applicable Law against the Borrower with or without the intervention of the courts of law in India.</li>
        </ul>
        <p>The rights, powers, and remedies given to the Lender by this Agreement shall be in addition to all rights, powers, and remedies given to the Lender by virtue of any other security, statute, or rule of law. The Borrower hereby consents and acknowledges that the Lender is entitled to make claims in the manner set out in this clause at any time upon occurrence of a Default and the Borrower acknowledges that the time period to recover monies due to the Lender under this Agreement shall stand renewed every time the Borrower comes in possession of any monies whether by way of income, any gifts, or otherwise.</p>

        <p>The remedies available to the Lender under this Agreement at law, equity, custom, trade practice, or otherwise are cumulative and not alternative and may be enforced concurrently or successively at the discretion of the Lender.</p>

        <h3>REPRESENTATIONS, WARRANTIES, AND COVENANTS</h3>
        <p>The Borrower hereby represents and warrants that:</p>
        <ul>
            <li>No intellectual property rights, trade secret, or other proprietary rights or rights of publicity or privacy rights of any Person is being infringed by entering into or providing any information required by this Agreement or by LSPs.</li>
            <li>It has full power and authority to enter into, execute, and deliver this Agreement and to perform the obligations contemplated hereby.</li>
            <li>The execution and delivery of this Agreement and the performance of the obligations does not violate any prior agreement, covenant, or requirements of law or contract.</li>
            <li>The execution, delivery, and performance of this Agreement and the consummation of the obligations contemplated hereby shall not: (i) violate any order, judgment, or decree against or binding upon him/her or upon his/her respective assets, properties, or businesses; or (ii) violate any Applicable Law.</li>
            <li>All the information provided by the Borrower in connection with this Agreement and the Transaction is complete, true, accurate, current, and not misleading in any manner.</li>
            <li>The Borrower is at least 18 years of age and is competent to contract as per the Indian Contract Act 1872.</li>
            <li>The Borrower is a citizen of India and a person resident in India for the purpose of India’s taxation and foreign exchange laws.</li>
            <li>No litigation, claim, dispute, or proceeding is pending against the Borrower which would adversely affect this Agreement in any way.</li>
            <li>The Borrower has not entered into any agreement that would prevent it from fulfilling any of the obligations under this Agreement.</li>
            <li>No event has occurred which shall prejudicially affect the interest of Lender or affect the financial conditions of Borrower or affect his liability to perform all or any of the obligations under this Agreement.</li>
            <li>The Borrower is not in default of payment of any taxes or Government dues.</li>
            <li>The Borrower shall do all acts, deeds, and things as required by Lender to give effect to the terms of this Agreement and the transactions contemplated herein.</li>
        </ul>

        <p>The Borrower covenants that:</p>
        <ul>
            <li>The Borrower shall perform all its obligations under this Agreement.</li>
            <li>The Borrower shall immediately deliver to Lender all documents including bank account statements as may be required by Lender from time to time.</li>
            <li>The Borrower shall not close his/her bank account without prior intimation to the Lender as a condition precedent.</li>
            <li>The Borrower authorizes, in the event he/she is not available or is not reachable, the Lender to communicate independently with the reference of the borrower as mentioned above in clause 4.4(c) within working hours in accordance with the collection as Lender may deem necessary for the purpose of this Agreement and for the management of the creditworthiness of Borrower.</li>
            <li>The Borrower shall immediately notify Lender of any litigations or legal proceedings against the Borrower.</li>
            <li>The Borrower shall notify the Lender in writing no later than 7 days of all changes in the location/address of office/residence/place of studying/place of business and submit the documents at the satisfaction of the lender evidencing such change in the location/address of office/residence/place of studying/place of business of borrower.</li>
            <li>The Borrower shall not leave India for employment, long-term study program, business, or long-term stay abroad without fully repaying the Amounts Payable and fulfilling all obligations under this Agreement.</li>
            <li>The Borrower confirms that it has taken appropriate advice and waives any defenses available to him/her under money lending, usury, or other laws relating to the charging of interest.</li>
            <li>The Borrower has read all the terms and conditions, privacy policy of Lender, its DLA, and LSPs, and other material available at the website of the Lender, Its DLA, “Creditlab.in” website of its LSPs.</li>
            <li>The Borrower hereby unconditionally agrees to abide by the terms and conditions, privacy policy, and other material contained on the website of the Lender/LSPs and/or the “Creditlab.in” application and/or website, and such terms and conditions, privacy policy contained on the website of the Lender shall be incorporated herein by reference “Creditlab.in”.</li>
            <li>The information and financial details submitted by him/her on the “Creditlab.in” application and/or website are true and correct. They have not provided any information which is incorrect or materially impairs the decision of the lender to either register him/her or permit to lend him/her through the “Creditlab.in” application and/or website.</li>
            <li>The Borrower confirms that all types of communication and transactions between them (Borrower and Lender) will be/have been done online via an online platform provided by Lending Service Provider.</li>
            <li>The Borrower acknowledges that the “Creditlab.in” application and/or website operated by Lending Service Provider operates merely as a technology platform which brings the Lender and the Borrower together.</li>
            <li>The Borrower understands that Lending Service Provider only facilitates the meeting of lenders and borrowers and is not engaged in lending.</li>
            <li>The Borrower shall waive her/his right of granting authority to any third person to communicate or receive correspondence on her/his behalf until all Outstanding Amount under this Agreement are repaid.</li>
        </ul>

        <h4>Additional Covenants</h4>
        <p>Use of funds</p>
        <p>The Borrower hereby covenants and undertakes that during the entire term of the Agreement the Amount disbursed by the Lender to the Borrower in accordance with the terms of this Agreement shall be used by the Borrower for lawful purposes only.</p>

        <p>Other Covenants and Consents of the Borrower</p>
        <ul>
            <li>The Lender shall be entitled to outsource any and all of its functions to any third party as it may think fit and in line with RBI guidelines including the right and authority to collect the outstanding on behalf of the Lender, the dues and unpaid installments, and other amounts due under the Agreement and to perform and execute all lawful acts, deeds, and matters, and things connected therewith and incidental thereto including sending notices to the Borrower to the extent prescribed under Applicable Laws. The details of such Lending Service providers engaged by the lender shall be available on the website.</li>
            <li>The Lender shall at any time without the consent of the Borrower be entitled to securitize, sell, assign, novate or transfer all or any of the Lender’s rights and obligations under this Agreement.</li>
            <li>The Borrower agrees and acknowledges that he/she has read the terms and conditions as set out in this Agreement and fully understood his/her obligations including the fees, charges, and deductions that will be incurred by them at the time of disbursement including penalty charges for breach of the terms of this Agreement.</li>
            <li>The borrower hereby declares that he/she can read and understand the terms in English and agrees to receive all documents/ correspondence in English. In the event the Borrower does not understand English, the Borrower agrees and acknowledges that he/she has taken the assistance of a relative/ friend/ third party to explain the terms of this Agreement and related transaction documents in his/her vernacular language and is fully aware of all the terms and conditions under this Agreement.</li>
        </ul>

        <h3>INDEMNITY</h3>
        <p>The Borrower shall indemnify, defend, and hold harmless the Lender, its directors, officials, employees, affiliates, agents, contractors, advisors, partners, and every attorney, manager, agent, or other Person appointed by the Lender (each an "Indemnified Party") from and against all or any direct or indirect losses, liabilities, obligations, claims, demands, actions, suits, judgments, awards, fines, penalties, taxes, fees, settlements, and proceedings, expenses, deficiencies, damages (whether or not resulting from third party claims), charges, costs (including costs of investigation, recovery, or other response actions), interests, processing fee, Account management fee, penalties, reasonable out-of-pocket expenses, reasonable attorneys’ and accountants’ fees incurred by the Lender and/or his agents as a result of arising from or in connection with or relating to (a) a Default; or (b) any fraud, gross negligence, willful misconduct attributable to the Borrower.</p>

        <h3>TERMINATION</h3>
        <p>This Agreement may be terminated in the following manners:</p>
        <ul>
            <li>At the option of the Lender at any time and without providing any notice upon occurrence of an Event of Default under Clause 4 of this Agreement which shall also entail payment of applicable damages by the Borrower to the Lender.</li>
            <li>By mutual agreement of the Parties recorded in writing at any time prior to the Term.</li>
            <li>On the date the Borrower has repaid the Amounts Payable (as defined REPAYMENT Schedule) in full and has fulfilled all other obligations under the Agreement to the satisfaction of the Lender.</li>
            <li>On the date the Borrower has repaid the amount in full during the Look-up period.</li>
            <li>In case of termination of this Agreement, the Borrower shall repay the entire Outstanding Amount to the Lender within 2 (two) Business Days from such date of termination.</li>
        </ul>

        <h4>SURVIVAL</h4>
        <p>The provisions of Clauses 1 (Definitions and Interpretation), 4 (Event of Default), 5 (Representations, Warranties, and Covenants), 6 (Indemnities), 9 (Dispute Resolution), 10.11 (Information Rights), 7 (Termination) of the Agreement, and REPAYMENT Schedule shall survive termination of this Agreement.</p>

        <h3>DISPUTE RESOLUTION</h3>
        <h4>Arbitration</h4>
        <p>Any and all Dispute(s) arising under or pursuant to this Agreement shall be referred to binding arbitration before a sole independent arbitrator appointed solely by the Lender under the Arbitration and Conciliation Act 1996 and the rules made thereunder as amended and in force from time to time. It is expressly agreed between the Parties that:</p>
        <ul>
            <li>The venue and seat of such arbitration shall be Kolkata/Hyderabad.</li>
            <li>The arbitration proceedings shall be conducted in the English language.</li>
            <li>The arbitration award shall be final and binding on the Parties.</li>
        </ul>
        <p>The Parties shall continue to fulfill their obligations under this Agreement pending the final resolution of the Dispute and the Parties shall not have the right to suspend their obligations under this Agreement by virtue of any dispute being referred to arbitration.</p>
        <p>Nothing shall preclude a Party from seeking interim equitable or injunctive relief or both from any court having jurisdiction to grant the same. The pursuit of equitable or injunctive relief shall not be a waiver of the right of the Parties to pursue any remedy for monetary losses through the arbitration described in this Clause 9.2.</p>
        <p>The Parties hereby agree that all costs of the arbitration shall be borne by the losing Party in the arbitration.</p>

        <h3>MISCELLANEOUS</h3>
        <h4>Notice:</h4>
        <p>Unless otherwise stated herein (i) the Lender may issue notices and other communications to the Borrower pursuant to this Agreement by SMS, email, Whatsapp, personal delivery, or by prepaid registered mail addressed to the Borrower at the phone number, address, or other coordinates of the Borrower specified in the REPAYMENT Schedule; (ii) the Borrower may issue notices and other communications to the Lender pursuant to this Agreement by way of email to support@creditlab.in</p>
        <p>Notices shall be deemed to be effective (a) when delivered if personally delivered; (b) upon receipt in the recipient’s email account or upon receipt in the recipient’s Whatsapp inbox or SMS account/service when sent by email, Whatsapp, or SMS; (c) three days after posting if sent by registered mail.</p>

        <h4>Governing Law and Jurisdiction:</h4>
        <p>This Agreement is governed by and shall be construed in accordance with the laws of India and subject to the dispute resolution provisions of this Agreement, the courts and tribunals at Kolkata shall have exclusive jurisdiction with regard to any disputes arising in relation to this Agreement.</p>

        <h4>Waivers:</h4>
        <p>No forbearance, delay, or inaction by any Party at any time in exercising a right, power, or remedy shall impair any such right, power, or remedy or operate as a waiver or acquiescence to a breach under this Agreement by the other Party. No waiver of right or acquiescence to non-compliance shall be effective or deemed made unless made in writing and duly executed by the Lender. Any such waiver or acquiescence shall be effective only in the specific instance and for the specific purpose for which it is given and may be subject to such conditions as the waiving or acquiescing Party may impose at its sole discretion. No such waiver or acquiescence in respect of a breach shall be construed as a waiver, acquiescence, or consent to any continuing or succeeding breach.</p>

        <h4>Force Majeure:</h4>
        <p>Neither Party shall be responsible for a delay in its performance under this Agreement, other than a delay in repayment of amounts due under this Agreement if such delay is caused by natural catastrophes, war, riots, or acts of any governmental agencies. Performance of any obligations affected by the Force Majeure event must be resumed as soon as reasonably possible after the termination or abatement of such Force Majeure event and its effects.</p>

        <h4>No Assignment:</h4>
        <p>The obligations of the Borrower under this Agreement shall not be assigned or transferred to any third party without the prior written consent of the Lender. However, the Lender shall have the right to assign, transfer, or novate this Agreement to any third party without prior intimation to the Borrower.</p>

        <h4>Entire Agreement:</h4>
        <p>This Agreement shall constitute the entire Agreement and understanding between the Parties with respect to this transaction. Any previous and future Agreement(s) related to other transactions between the Lender and Borrower shall not be affected by the terms of this Agreement.</p>

        <h4>Partial Invalidity:</h4>
        <p>In the event of one or more of the provisions of this Agreement being invalid, illegal, or unenforceable in any respect, the validity, legality, and enforceability of the remaining provisions shall not in any way be affected or impaired thereby.</p>

        <h4>Amendment:</h4>
        <p>This Agreement shall not be modified except by an instrument or instruments in writing signed by each Party or an authorized representative of the Party as the case may be. Any amendment/modification to the terms and conditions of the borrowing shall be intimated to the Borrower by the Lender at the earliest.</p>

        <h4>Harmonious Interpretation:</h4>
        <p>This Agreement must be interpreted and construed in harmony with the General terms of Use and the Terms of Registration that have been provided by the Lender to the Borrower before the use of the Creditlab.in website or application.</p>

        <h4>Costs and stamp duty expenses:</h4>
        <ul>
            <li>The Borrower shall bear all costs and expenses incurred in relation to the completion of the Agreement including stamp duty costs incurred in relation to recovery of sums due, taxes incurred in relation to the enforcement under this Agreement, and all related agreements, writings, and documents executed by and between the Parties hereto in respect of the Amounts Payable.</li>
            <li>Any claim, demands, actions, costs, expenses, and liabilities incurred or suffered by the Lender by reason of non-payment or insufficient payment of stamp duty on this Agreement and the documents and any other writings or documents which may be executed by the Borrower pursuant to or in relation to this Agreement will be to the cost of the Borrower.</li>
            <li>The Borrower hereby acknowledges that he/she is aware of the possibility of the costs and expenses incurred by the Lender in connection with the enforcement of or the preservation of any rights under this Agreement exceeding the aggregate of Principal Amount, Interest, and/or Default Interest, or/and penalty if any payable to the Lender. The Borrower further acknowledges that such sums are reasonable given the risk involved in granting unsecured loans to borrowers with lower creditworthiness and the ticket size of the loans that are being provided.</li>
        </ul>

        <h4>Information rights:</h4>
        <p>The Borrower hereby agrees and consents (as a precondition relating to the grant of Facility given to the Borrower by the Lender) that the Lender and/or RBI shall have unqualified right to disclose and furnish to credit information companies and other agencies so authorized by RBI:</p>
        <ul>
            <li>Information and data relating to the Borrower, the Facility availed of/to be availed by the Borrower, obligations assured/to be assured by the Borrower in relation thereto.</li>
            <li>To disclose the name of the Borrower as defaulter in such manner and through such medium as the Lender or RBI in their absolute discretion may think fit.</li>
        </ul>
        <p>Accordingly, the Borrower hereby agrees and gives consent:</p>
        <ul>
            <li>For the disclosure by the Lender of all or any such information and data relating to the Borrower.</li>
            <li>For the disclosure by the Lender of all or any such information or data relating to any loan availed of/to be availed by the Borrower.</li>
            <li>For the disclosure by the Lender of all/any default committed by the Borrower in discharge of obligations of the Borrower under this Agreement as the Lender may deem appropriate and necessary to disclose and furnish to credit information companies and any other agency authorized in this behalf by RBI.</li>
            <li>The Borrower hereby declares that the information and data furnished by the Borrower to the Lender are true and correct.</li>
            <li>The Borrower hereby declares that the Credit Information Companies and any other agency so authorized in this regard may use, process the said information and data disclosed by the Lender in the manner as deemed fit by them; and may furnish for consideration the processed information and data or products thereof prepared by them to the Lender or banks/financial institutions and other credit grantors or registered users as may be specified by the RBI in this regard.</li>
            <li>The Lender may disclose to a potential assignee or to any person who may otherwise enter into contractual relations with the Lender in relation to this Agreement such information about the Borrower as the Lender may deem appropriate.</li>
            <li>The Borrower confirms that the Lender may for the purposes of credit reference checks, verification, etc. disclose any information/documents relating to the Borrower (pertaining to the Facility availed by the Borrower) to any third party appointed by it. The Borrower further authorizes the Lender to disclose said information/documents to RBI, income tax authorities, credit bureau, third parties, credit rating agencies, databanks, corporates, banks, financial institutions, or any other government or regulatory authorities, statutory authorities, quasi-judicial authorities.</li>
            <li>The Borrower on behalf of themselves and their respective heirs and assigns hereby irrevocably and unconditionally release and forever discharge the Lender, its affiliated companies, and each of their respective officers, directors, employees, shareholders, representatives, parent companies, subsidiaries, predecessors, successors, assigns, attorneys, and all persons acting by or through or in concert with them of and from any and all charges, claims, complaints, demands, liabilities, causes of action, losses, costs, and expenses of any kind whatsoever (including related attorney’s fees and costs), known or unknown, suspected or unsuspected, including those which do not know or suspect to exist as of the date of this Agreement the Borrower may now have or has ever had against the Lender in respect of any invasion of privacy, misrepresentation, fraud, stress, breach of any covenant of good faith and fair dealing on the part of the Lender. All such claims are forever barred by this Agreement whether they arise in contract or tort or under a statute or any other Law(s). The final release of all claims by the Borrower against the Lender constitutes a material part of the consideration flowing from the Lender to the Borrower under this Agreement. The term "claims" for the purpose of this Clause shall mean and include all actions, claims, and grievances whether actual or potential, known or unknown, and specifically but not exclusively including all claims against the Lender of the type referenced in this Clause.</li>
        </ul>

        <h4>Execution in counterparts:</h4>
        <p>This Agreement may be executed in any number of counterparts and all of which taken together shall constitute one and the same instrument. The Parties may enter into this Agreement by signing any such counterpart.</p>

        <h4>Further assurance:</h4>
        <p>Each of the Parties hereto shall cooperate with the other and execute and deliver to the other such instruments and documents and take such other actions as may be reasonably requested from time to time in order to carry out, evidence, and confirm their rights and the intended purpose of this Agreement.</p>

        <p>The Parties agree that this Agreement is being executed in an electronic form by way of a click wrap for execution of this Agreement. The Parties hereby agree and undertake that:</p>
        <ul>
            <li>The Parties understand and agree that they have the right to execute this Agreement through paper or through electronic signature technology or by clicking “I agree” button on the website which is in compliance with Information Technology Act 2000.</li>
            <li>Notwithstanding the absence of physical signatures of the parties on this Agreement or any of the notice/intimation/offer/acceptance in connection with this Agreement, the Agreement or any of the other documents executed by me/us in electronic form shall be legally valid, binding, and enforceable against me/us; This electronic signature is equivalent to their handwritten signature and shall have the same validity and meaning as their handwritten signature. The Parties shall not at any time in the future repudiate the meaning of my electronic signature or claim that their electronic signature is not legally binding.</li>
            <li>The Parties shall not raise any objection or claims in relation to validity or enforceability of this Agreement or any of the other documents solely on account of this Agreement or any of the other documents having been executed in an electronic form by way of a click wrap agreement; The Parties agree not to object to the admissibility of this Agreement as an electronic record or a paper copy of an electronic document or a paper copy of a document bearing an electronic signature on the grounds that it is an electronic record or electronic signature or that it is not in its original form or is not an original.</li>
            <li>The Parties shall not raise any objection or claim in relation to process, method, storage, or means of authentication of execution of this Agreement or any of the documents in connection with this Agreement.</li>
        </ul>

        <h3>SCHEDULE 1: REPAYMENT SCHEDULE</h3>
        <p>This “Repayment Schedule” is an integral part of the Agreement. The terms defined in this REPAYMENT Schedule shall have the meaning ascribed to them herein when used in this REPAYMENT Schedule unless the context otherwise so requires.</p>
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
    <h3>Other charges : </h3>
    <div style="padding-left:15px">
        <h4>Enach registration & Enach bounce charges :</h4>
        <p>ENach registration charges are determined by the respective banks and may vary across different banks. Similarly, ENach bounce charges are applied by banks if a payment fails, with the amount also varying from bank to bank.</p>
    </div>
        <h3>Borrower’s information :</h3>
        <?php
        $bank = towquery("SELECT * FROM `user_bank` WHERE uid='{$loanf['uid']}' ORDER BY id DESC");
        $bdf = towfetch($bank);
        ?>
        <p>Bank account details:</p>
        <ul>
            <li>Bank name: <?=$bdf['bank_name']?></li>
            <li>IFSC: <?=$bdf['ifsc_code']?></li>
            <li>Account Number: <?=$bdf['ac_no']?></li>
        </ul>
        <p>Contact details:</p>
        <ul>
            <li>Primary mobile number: <?=$loanf['mobile']?></li>
            <li>Alternate mobile number: <?=$loanf['altmobile']?></li>
            <li>Borrower’s address: <?=$loanf['permanent_address']?></li>
        </ul>
        <p>Borrower’s email: <?=$loanf['email']?></p>

        <p>Other Information:</p>
        <ul>
            <li>IP address: <?php echo $af['ip_address'] ? $af['ip_address'] : 'NA';?></li>
            <li>Approximate location at the time of signing: <?php echo $af['latitude'] ? $af['latitude'] : 'NA';?>, <?php echo $af['longitude'] ? $af['longitude'] : 'NA';?></li>
        </ul>
    </div>
    <div><?=convertImageToBase64('user/uploads/'.$loanf['signature'], 'Header Image')?><br><b>Digitally signed</b></div>
</body>
</html>

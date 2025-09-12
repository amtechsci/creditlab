<?php

// --- FIX 1: Added for debugging ---
// This will help show any PHP errors directly in the browser.
// You can remove these two lines once the script is working correctly.
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db.php';

// Set headers to download the CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=recovery_agency_data.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Define all column headings, now including up to 10 references
fputcsv($output, [
    'customer PAN NAME', 'LoanID', 'monthly net salary', 'monthly salary date',
    'LoanPrinciple', 'loanOutstanding', 'Loan exhausted days', 'DPD',
    'loanPenalty', 'totalInterest', 'Company', 'log in data ( address lat,long)',
    'permanent Address', 'PrimaryNo', 'AltMobile',
    'reference 1 name / relation /number /status', 'reference 2 name / relation /number /status',
    'reference 3 name / relation /number /status', 'reference 4 name / relation /number /status',
    'reference 5 name / relation /number /status', 'reference 6 name / relation /number /status',
    'reference 7 name / relation /number /status', 'reference 8 name / relation /number /status',
    'reference 9 name / relation /number /status', 'reference 10 name / relation /number /status',
    'customer response 1 & commitment date 1', 'customer response 2 & commitment date 2',
    'customer response 3 & commitment date 3', 'customer response 4 & commitment date 4',
    'customer response 5 & commitment date 5'
]);

// SQL query to fetch all loans with status 'account manager'
$sql = "SELECT 
            u.id AS user_id,
            u.pan_name,
            u.salary,
            u.salary_date,
            u.company,
            u.latlong,
            u.permanent_address,
            u.mobile,
            u.altmobile,
            l.lid AS loan_id,
            l.processed_amount,
            l.p_fee,
            l.service_charge AS total_interest,
            l.penality_charge,
            l.processed_date AS loan_start_date,
            la.amount,
            la.days AS loan_tenure
        FROM 
            loan_apply la
        INNER JOIN 
            user u ON la.uid = u.id
        INNER JOIN 
            loan l ON la.id = l.lid
        WHERE 
            la.status = 'account manager'
        ORDER BY 
            la.id DESC";

// Execute the main query
$result = towquery($sql);

// Get the current date to calculate DPD and exhausted days
$today = new DateTime();
$today->setTime(0, 0, 0); // Set to midnight for accurate day counting

// Helper function to format response and date
function formatResponse($response, $date) {
    $response_text = trim($response ?? '');
    $date_text = trim($date ?? '');
    if (empty($response_text) && empty($date_text)) {
        return 'NA';
    }
    return $response_text . ' & ' . $date_text;
}

// Loop through each loan
while ($row = towfetch($result)) {
    
    $exhausted_days = 0;
    $dpd = 0;

    // --- UPDATED CALCULATIONS based on your provided code ---

    // Calculate Loan Principle as per your logic
    $loan_principle = (float)$row['processed_amount'] + (float)$row['p_fee'] + ((float)$row['p_fee'] * 0.18);

    // Calculate Outstanding Amount as per your logic
    $outstanding_amount = (float)$row['processed_amount'] + (float)$row['p_fee'] + ((float)$row['p_fee'] * 0.18) + (float)$row['total_interest'] + (float)$row['penality_charge'];

    // Calculate Exhausted Days and DPD
    if (!empty($row['loan_start_date'])) {
        // Calculate Exhausted Days as per your logic
        $exhausted_days = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($row['loan_start_date'] . " -1 day")))) / (60 * 60 * 24));
        if($exhausted_days > 30){
            $dpd = $exhausted_days - 30;
        }
    }

    // Fetch the last 5 customer responses for the current loan
    $responses = [];
    $commit_dates = [];
    $response_query = towquery("SELECT customer_response, commitment_date FROM `loan_acc_man` WHERE lid=".$row['loan_id']." ORDER BY id DESC LIMIT 5");
    while($response_row = towfetch($response_query)){
        $responses[] = $response_row['customer_response'];
        $commit_dates[] = $response_row['commitment_date'];
    }

    // Fetch up to 10 user referrals
    $referrals = [];
    $referral_query = towquery("SELECT name, relation, phone, status FROM `user_referrals` WHERE uid=".$row['user_id']." ORDER BY id ASC LIMIT 10");
    while($referral_row = towfetch($referral_query)){
        $referrals[] = $referral_row['name'] . ' / ' . $referral_row['relation'] . ' / ' . $referral_row['phone'] . ' / ' . $referral_row['status'];
    }

    // Create the array for the CSV row
    $data = [
        $row['pan_name'],
        'CLL' . $row['loan_id'],
        $row['salary'],
        $row['salary_date'],
        number_format($loan_principle, 2, '.', ''), // Using the new calculation
        number_format($outstanding_amount, 2, '.', ''), // Using the new calculation
        $exhausted_days, // Using the new calculation
        $dpd,
        $row['penality_charge'],
        $row['total_interest'],
        $row['company'],
        $row['latlong'],
        $row['permanent_address'],
        $row['mobile'],
        $row['altmobile'],
        $referrals[0] ?? '', // reference 1
        $referrals[1] ?? '', // reference 2
        $referrals[2] ?? '', // reference 3
        $referrals[3] ?? '', // reference 4
        $referrals[4] ?? '', // reference 5
        $referrals[5] ?? '', // reference 6
        $referrals[6] ?? '', // reference 7
        $referrals[7] ?? '', // reference 8
        $referrals[8] ?? '', // reference 9
        $referrals[9] ?? '', // reference 10
        formatResponse($responses[0] ?? null, $commit_dates[0] ?? null),
        formatResponse($responses[1] ?? null, $commit_dates[1] ?? null),
        formatResponse($responses[2] ?? null, $commit_dates[2] ?? null),
        formatResponse($responses[3] ?? null, $commit_dates[3] ?? null),
        formatResponse($responses[4] ?? null, $commit_dates[4] ?? null),
    ];

    // Write the row to the CSV file
    fputcsv($output, $data);
}

// Close the output stream
fclose($output);

exit;
?>

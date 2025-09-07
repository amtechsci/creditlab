<?php

include '../db.php';

// Set headers to download the CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=loan_officer_report.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Output the column headings for the CSV file
fputcsv($output, [
    'CID', 
    'Name of user', 
    'Primary Number', 
    'Alt Number', 
    'Primary Mail ID', 
    'Loan Officer Name', 
    'Loan Applied Date'
]);

// SQL query to fetch the required data
// It joins user, loan_apply, and account_manager tables
$sql = "SELECT 
            u.rcid,
            u.name AS user_name,
            u.mobile,
            u.altmobile,
            u.email,
            la.apply_date,
            am.name AS loan_officer_name
        FROM 
            user u
        INNER JOIN 
            loan_apply la ON u.id = la.uid
        LEFT JOIN 
            account_manager am ON u.assign_account_manager = am.id
            WHERE la.status='pending'
        ORDER BY 
            la.apply_date DESC";

// Execute the query
$result = towquery($sql);

// Loop through the result and write each row to the CSV
while ($row = towfetch($result)) {
    // Create the array for the CSV row
    $data = [
        $row['rcid'],
        $row['user_name'],
        $row['mobile'],
        $row['altmobile'],
        $row['email'],
        $row['loan_officer_name'] ?? 'N/A', // Use 'N/A' if no loan officer is assigned
        date('d-m-Y', strtotime($row['apply_date'])) // Format the date
    ];

    // Write the row to the CSV file
    fputcsv($output, $data);
}

// Close the output stream
fclose($output);

exit;
?>

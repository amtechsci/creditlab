<?php
/**
 * Fix Old Loan Days Script
 * This script recalculates and updates loan_apply.days and loan.total_time
 * using the correct calculateLoanDays() function
 * 
 * Run this script from browser or CLI:
 * - Browser: http://localhost/creditlab/fix_old_loan_days.php?mode=preview
 * - CLI: php fix_old_loan_days.php preview
 * 
 * Modes:
 * - preview (default): Show what would be changed without making changes
 * - execute: Actually update the database
 */

include 'db.php';
require_once __DIR__ . '/lib/auth.php';
creditlab_require_staff();

// Get mode from URL parameter or CLI argument
$mode = 'preview';
if (isset($_GET['mode'])) {
    $mode = $_GET['mode'];
} elseif (isset($argv[1])) {
    $mode = $argv[1];
}

$isExecute = ($mode === 'execute');

echo "<pre>";
echo "=== Fix Old Loan Days Script ===\n";
echo "Mode: " . strtoupper($mode) . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

if (!$isExecute) {
    echo "*** PREVIEW MODE - No changes will be made ***\n";
    echo "*** Add ?mode=execute to URL to apply changes ***\n\n";
}

// Query: Find loans on "account manager" where due date crosses into March
$query = "
    SELECT 
        l.id as loan_id,
        l.lid as loan_apply_id,
        l.uid,
        u.name,
        u.salary_date,
        u.approvenew,
        DATE(l.processed_date) as processed_date,
        l.total_time,
        l.is_emi,
        la.days as loan_apply_days,
        DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY) as current_due_date
    FROM loan l
    INNER JOIN user u ON l.uid = u.id
    INNER JOIN loan_apply la ON l.lid = la.id
    WHERE l.action = 'account manager'
    AND (l.is_emi = 0 OR l.is_emi IS NULL)
    AND u.salary_date IS NOT NULL 
    AND u.salary_date != '' 
    AND u.salary_date != '0'
    AND u.salary_date REGEXP '^[0-9]+$'
    AND CAST(u.salary_date AS UNSIGNED) BETWEEN 1 AND 31
    AND DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY) BETWEEN '2026-03-01' AND '2026-03-31'
    ORDER BY DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY), l.id
";

$result = towquery($query);

$totalChecked = 0;
$needsFixing = 0;
$fixed = 0;

$changes = [];

echo "Finding loans on 'account manager' with due dates in March 2026...\n\n";

while ($row = towfetch($result)) {
    $totalChecked++;
    
    $loan_id = $row['loan_id'];
    $loan_apply_id = $row['loan_apply_id'];
    $uid = $row['uid'];
    $name = $row['name'];
    $salary_date = (int)$row['salary_date'];
    $processed_date = $row['processed_date'];
    $total_time = (int)$row['total_time'];
    $loan_apply_days = (int)$row['loan_apply_days'];
    $current_due_date = $row['current_due_date'];
    
    // Calculate correct days using the function
    $correct_days = calculateLoanDays($processed_date, $salary_date);
    $correct_due_date = calculateLoanDueDate($processed_date, $salary_date);
    
    // Check if needs fixing
    $loan_needs_fix = ($total_time != $correct_days);
    $loan_apply_needs_fix = ($loan_apply_days != $correct_days);
    
    if ($loan_needs_fix || $loan_apply_needs_fix) {
        $needsFixing++;
        
        $change = [
            'loan_id' => $loan_id,
            'loan_apply_id' => $loan_apply_id,
            'uid' => $uid,
            'name' => $name,
            'processed_date' => $processed_date,
            'salary_date' => $salary_date,
            'current_days' => $total_time,
            'correct_days' => $correct_days,
            'current_due_date' => $current_due_date,
            'correct_due_date' => $correct_due_date,
            'loan_apply_days' => $loan_apply_days,
            'loan_needs_fix' => $loan_needs_fix,
            'loan_apply_needs_fix' => $loan_apply_needs_fix
        ];
        
        $changes[] = $change;
        
        if ($isExecute) {
            // Update loan.total_time
            if ($loan_needs_fix) {
                $updateQuery1 = "UPDATE loan SET total_time = $correct_days WHERE id = $loan_id";
                towquery($updateQuery1);
            }
            
            // Update loan_apply.days
            if ($loan_apply_needs_fix) {
                $updateQuery2 = "UPDATE loan_apply SET days = $correct_days WHERE id = $loan_apply_id";
                towquery($updateQuery2);
            }
            
            $fixed++;
        }
    }
}

// Output results
echo "=== Summary ===\n";
echo "Total loans with March due dates: $totalChecked\n";
echo "Loans needing fix: $needsFixing\n";

if ($isExecute) {
    echo "Loans fixed: $fixed\n";
}

echo "\n=== Changes " . ($isExecute ? "Made" : "To Be Made") . " ===\n\n";

if (count($changes) > 0) {
    // Show all changes
    $showCount = count($changes);
    echo "Total: $showCount loans\n\n";
    
    echo str_pad("L_ID", 6) . " | " . str_pad("LA_ID", 6) . " | " . str_pad("User", 20) . " | " . str_pad("Proc Date", 12) . " | " . str_pad("Sal", 3) . " | " . str_pad("Old", 4) . " | " . str_pad("New", 4) . " | " . str_pad("Old Due", 12) . " | " . str_pad("New Due", 12) . "\n";
    echo str_repeat("-", 115) . "\n";
    
    for ($i = 0; $i < $showCount; $i++) {
        $c = $changes[$i];
        
        echo str_pad($c['loan_id'], 6) . " | " . 
             str_pad($c['loan_apply_id'], 6) . " | " . 
             str_pad(substr($c['name'], 0, 20), 20) . " | " . 
             str_pad($c['processed_date'], 12) . " | " . 
             str_pad($c['salary_date'], 3) . " | " . 
             str_pad($c['current_days'], 4) . " | " . 
             str_pad($c['correct_days'], 4) . " | " . 
             str_pad($c['current_due_date'], 12) . " | " . 
             str_pad($c['correct_due_date'], 12) . "\n";
    }
} else {
    echo "No changes needed! All loan days are correct.\n";
}

echo "\n=== End of Script ===\n";

if (!$isExecute && $needsFixing > 0) {
    echo "\nTo apply these changes, run with mode=execute\n";
    echo "URL: ?mode=execute\n";
}

echo "</pre>";

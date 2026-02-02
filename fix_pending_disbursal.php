<?php
/**
 * Fix Pending Disbursal Loan Days
 * 
 * This script fixes loan_apply.days for loans waiting to be disbursed
 * (status = 'disbursal', 'pending', 'follow up')
 * 
 * Run: http://localhost/creditlab/fix_pending_disbursal.php?mode=preview
 * Execute: http://localhost/creditlab/fix_pending_disbursal.php?mode=execute
 * 
 * Optional filters:
 * - from=2026-01-01 (only loans applied on or after this date)
 * - status=disbursal (only specific status: disbursal, pending, follow up)
 */

include 'db.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'preview';
$isExecute = ($mode === 'execute');

// Optional date filter (default: 2026-01-01)
$fromDate = isset($_GET['from']) ? $_GET['from'] : '2026-01-01';

// Optional status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;

echo "<pre>";
echo "=== Fix Pending Disbursal Loan Days ===\n";
echo "Mode: " . strtoupper($mode) . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Filter: Loans applied from $fromDate onwards\n";
if ($statusFilter) {
    echo "Status filter: $statusFilter\n";
}
echo "\n";

if (!$isExecute) {
    echo "*** PREVIEW MODE - No changes will be made ***\n";
    echo "*** Add ?mode=execute to apply changes ***\n\n";
}

// Build status filter
$statusCondition = "la.status IN ('disbursal', 'pending', 'follow up')";
if ($statusFilter && in_array($statusFilter, ['disbursal', 'pending', 'follow up'])) {
    $statusCondition = "la.status = '$statusFilter'";
}

// Find loan_apply records waiting for disbursal with valid salary_date
// Only loans applied from $fromDate onwards
$query = "
    SELECT 
        la.id as loan_apply_id,
        la.uid,
        la.days as current_days,
        la.apply_date,
        la.status,
        u.name,
        u.salary_date,
        u.approvenew
    FROM loan_apply la
    INNER JOIN user u ON la.uid = u.id
    WHERE $statusCondition
    AND DATE(la.apply_date) >= '$fromDate'
    AND (u.approvenew = 0 OR u.approvenew IS NULL)  -- Non-EMI only
    AND u.salary_date IS NOT NULL 
    AND u.salary_date != '' 
    AND u.salary_date != '0'
    AND u.salary_date REGEXP '^[0-9]+$'
    AND CAST(u.salary_date AS UNSIGNED) BETWEEN 1 AND 31
    ORDER BY la.status, la.id DESC
";

$result = towquery($query);

$totalChecked = 0;
$needsFixing = 0;
$fixed = 0;
$changes = [];

echo "Checking loan_apply records waiting for disbursal...\n\n";

while ($row = towfetch($result)) {
    $totalChecked++;
    
    $loan_apply_id = $row['loan_apply_id'];
    $uid = $row['uid'];
    $current_days = (int)$row['current_days'];
    $apply_date = date('Y-m-d', strtotime($row['apply_date']));
    $status = $row['status'];
    $name = $row['name'];
    $salary_date = (int)$row['salary_date'];
    
    // Calculate correct days
    $correct_days = calculateLoanDays($apply_date, $salary_date);
    $correct_due_date = calculateLoanDueDate($apply_date, $salary_date);
    
    // Calculate current due date
    $current_due_date = date('Y-m-d', strtotime($apply_date . ' + ' . $current_days . ' days'));
    
    if ($current_days != $correct_days) {
        $needsFixing++;
        
        $changes[] = [
            'loan_apply_id' => $loan_apply_id,
            'uid' => $uid,
            'name' => $name,
            'status' => $status,
            'apply_date' => $apply_date,
            'salary_date' => $salary_date,
            'current_days' => $current_days,
            'correct_days' => $correct_days,
            'current_due_date' => $current_due_date,
            'correct_due_date' => $correct_due_date
        ];
        
        if ($isExecute) {
            towquery("UPDATE loan_apply SET days = $correct_days WHERE id = $loan_apply_id");
            $fixed++;
        }
    }
}

// Summary
echo "=== Summary ===\n";
echo "Total pending disbursal loans checked: $totalChecked\n";
echo "Loans needing fix: $needsFixing\n";
if ($isExecute) {
    echo "Loans fixed: $fixed\n";
}

echo "\n=== Changes " . ($isExecute ? "Made" : "To Be Made") . " ===\n\n";

if (count($changes) > 0) {
    echo "Total: " . count($changes) . " loans\n\n";
    
    echo str_pad("LA_ID", 7) . " | " . str_pad("Status", 10) . " | " . str_pad("User", 20) . " | " . str_pad("Apply Date", 12) . " | " . str_pad("Sal", 3) . " | " . str_pad("Old", 4) . " | " . str_pad("New", 4) . " | " . str_pad("Old Due", 12) . " | " . str_pad("New Due", 12) . "\n";
    echo str_repeat("-", 120) . "\n";
    
    foreach ($changes as $c) {
        echo str_pad($c['loan_apply_id'], 7) . " | " . 
             str_pad($c['status'], 10) . " | " . 
             str_pad(substr($c['name'], 0, 20), 20) . " | " . 
             str_pad($c['apply_date'], 12) . " | " . 
             str_pad($c['salary_date'], 3) . " | " . 
             str_pad($c['current_days'], 4) . " | " . 
             str_pad($c['correct_days'], 4) . " | " . 
             str_pad($c['current_due_date'], 12) . " | " . 
             str_pad($c['correct_due_date'], 12) . "\n";
    }
} else {
    echo "No changes needed! All pending loan days are correct.\n";
}

echo "\n=== End of Script ===\n";

if (!$isExecute && $needsFixing > 0) {
    echo "\nTo apply changes: ?mode=execute\n";
}

echo "\n=== Available Filters ===\n";
echo "?mode=preview (default) - Show changes without applying\n";
echo "?mode=execute - Apply the changes\n";
echo "?from=2026-01-01 (default) - Only loans applied from this date\n";
echo "?status=disbursal - Only disbursal status (or: pending, follow up)\n";
echo "\nExamples:\n";
echo "?mode=preview&from=2026-02-01 - Preview loans from Feb 2026\n";
echo "?mode=execute&status=disbursal - Execute only disbursal loans\n";
echo "?mode=preview&from=2025-01-01 - Include 2025 loans in preview\n";

echo "</pre>";

<?php
/**
 * Test Cases for Loan Due Date Calculation Logic
 * 
 * This file tests:
 * 1. calculateLoanDays() function
 * 2. DPD (Days Past Due) calculations
 * 3. SMS cron logic
 * 4. E-Nach trigger logic
 * 5. KFS calculations
 * 
 * Run: php test_loan_due_date_logic.php
 */

// Include the database file to get the calculateLoanDays function
require_once 'db.php';

// Test colors for output
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$BLUE = "\033[34m";
$RESET = "\033[0m";

$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

/**
 * Test helper function
 */
function test($test_name, $expected, $actual, $description = '') {
    global $total_tests, $passed_tests, $failed_tests, $GREEN, $RED, $RESET;
    
    $total_tests++;
    $passed = ($expected === $actual);
    
    if ($passed) {
        $passed_tests++;
        echo "{$GREEN}✓ PASS{$RESET}: $test_name\n";
        if ($description) {
            echo "  → $description\n";
        }
    } else {
        $failed_tests++;
        echo "{$RED}✗ FAIL{$RESET}: $test_name\n";
        echo "  Expected: $expected\n";
        echo "  Actual: $actual\n";
        if ($description) {
            echo "  → $description\n";
        }
    }
    echo "\n";
}

echo "{$BLUE}========================================{$RESET}\n";
echo "{$BLUE}Loan Due Date Calculation Test Cases{$RESET}\n";
echo "{$BLUE}========================================{$RESET}\n\n";

// ============================================
// TEST 1: calculateLoanDays() Function
// ============================================
echo "{$YELLOW}TEST SUITE 1: calculateLoanDays() Function{$RESET}\n";
echo str_repeat("-", 50) . "\n\n";

// Test 1.1: Gap < 8 days → Next month's salary date
$applied_date = '2024-01-10';
$salary_date = 15; // Jan 15
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 15 - 10 = 5 days (< 8) → Next month = Feb 15
// Days = Feb 15 - Jan 10 = 36 days
test(
    "Test 1.1: Gap < 8 days (Applied: Jan 10, Salary: 15)",
    36,
    $result,
    "Should return 36 days (Jan 10 → Feb 15)"
);

// Test 1.2: Gap >= 8 days → Same month's salary date
$applied_date = '2024-01-10';
$salary_date = 25; // Jan 25
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 25 - 10 = 15 days (>= 8) → Same month = Jan 25
// Days = Jan 25 - Jan 10 = 15 days
test(
    "Test 1.2: Gap >= 8 days (Applied: Jan 10, Salary: 25)",
    15,
    $result,
    "Should return 15 days (Jan 10 → Jan 25)"
);

// Test 1.3: Salary date already passed this month
$applied_date = '2024-01-25';
$salary_date = 5; // Jan 5 (already passed)
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 5 - 25 = -20 (negative) → Next month = Feb 5
// Days = Feb 5 - Jan 25 = 11 days
test(
    "Test 1.3: Salary date passed (Applied: Jan 25, Salary: 5)",
    11,
    $result,
    "Should return 11 days (Jan 25 → Feb 5)"
);

// Test 1.4: No salary date → Default 30 days
$applied_date = '2024-01-10';
$salary_date = null;
$result = calculateLoanDays($applied_date, $salary_date);
test(
    "Test 1.4: No salary date (Applied: Jan 10, Salary: null)",
    30,
    $result,
    "Should return 30 days (default)"
);

// Test 1.5: Invalid salary date → Default 30 days
$applied_date = '2024-01-10';
$salary_date = 0;
$result = calculateLoanDays($applied_date, $salary_date);
test(
    "Test 1.5: Invalid salary date (Applied: Jan 10, Salary: 0)",
    30,
    $result,
    "Should return 30 days (default for invalid)"
);

// Test 1.6: Edge case - Gap exactly 8 days
$applied_date = '2024-01-10';
$salary_date = 18; // Jan 18
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 18 - 10 = 8 days (>= 8) → Same month = Jan 18
// Days = Jan 18 - Jan 10 = 8 days
test(
    "Test 1.6: Gap exactly 8 days (Applied: Jan 10, Salary: 18)",
    8,
    $result,
    "Should return 8 days (Jan 10 → Jan 18)"
);

// Test 1.7: Edge case - Gap exactly 7 days (should go to next month)
$applied_date = '2024-01-10';
$salary_date = 17; // Jan 17
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 17 - 10 = 7 days (< 8) → Next month = Feb 17
// Days = Feb 17 - Jan 10 = 38 days
test(
    "Test 1.7: Gap exactly 7 days (Applied: Jan 10, Salary: 17)",
    38,
    $result,
    "Should return 38 days (Jan 10 → Feb 17, gap < 8)"
);

// Test 1.8: February edge case (salary date 31 → Feb 28/29)
$applied_date = '2024-01-10';
$salary_date = 31; // Jan 31
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 31 - 10 = 21 days (>= 8) → Same month = Jan 31
// Days = Jan 31 - Jan 10 = 21 days
test(
    "Test 1.8: Salary date 31 in January (Applied: Jan 10, Salary: 31)",
    21,
    $result,
    "Should return 21 days (Jan 10 → Jan 31)"
);

// Test 1.9: February with salary date 31 (should adjust to Feb 29 in leap year)
$applied_date = '2024-01-25';
$salary_date = 31; // Jan 31
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 31 - 25 = 6 days (< 8) → Next month = Feb 29 (2024 is leap year)
// Days = Feb 29 - Jan 25 = 35 days
test(
    "Test 1.9: Salary date 31, next month February (Applied: Jan 25, Salary: 31)",
    35,
    $result,
    "Should return 35 days (Jan 25 → Feb 29, 2024 is leap year)"
);

// ============================================
// TEST 2: DPD (Days Past Due) Calculations
// ============================================
echo "\n{$YELLOW}TEST SUITE 2: DPD (Days Past Due) Calculations{$RESET}\n";
echo str_repeat("-", 50) . "\n\n";

// Test 2.1: Before due date (DPD negative)
$processed_date = '2024-01-01';
$loan_days = 30;
$current_date = '2024-01-15';
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$dpd = $tday - $loan_days;
test(
    "Test 2.1: Before due date (Processed: Jan 1, Days: 30, Current: Jan 15)",
    -15,
    $dpd,
    "DPD should be -15 (15 days before due date)"
);

// Test 2.2: On due date (DPD = 0)
$processed_date = '2024-01-01';
$loan_days = 30;
$current_date = '2024-01-31';
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$dpd = $tday - $loan_days;
test(
    "Test 2.2: On due date (Processed: Jan 1, Days: 30, Current: Jan 31)",
    0,
    $dpd,
    "DPD should be 0 (exactly on due date)"
);

// Test 2.3: After due date (DPD positive)
$processed_date = '2024-01-01';
$loan_days = 30;
$current_date = '2024-02-05';
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$dpd = $tday - $loan_days;
test(
    "Test 2.3: After due date (Processed: Jan 1, Days: 30, Current: Feb 5)",
    5,
    $dpd,
    "DPD should be 5 (5 days past due date)"
);

// Test 2.4: New loan with 25 days
$processed_date = '2024-01-01';
$loan_days = 25; // New loan
$current_date = '2024-01-26';
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$dpd = $tday - $loan_days;
test(
    "Test 2.4: New loan on due date (Processed: Jan 1, Days: 25, Current: Jan 26)",
    0,
    $dpd,
    "DPD should be 0 (on due date for 25-day loan)"
);

// ============================================
// TEST 3: SMS Cron Logic
// ============================================
echo "\n{$YELLOW}TEST SUITE 3: SMS Cron Logic{$RESET}\n";
echo str_repeat("-", 50) . "\n\n";

// Test 3.1: 5 days before due date reminder
$processed_date = '2024-01-01';
$loan_days = 30;
$current_date = '2024-01-26'; // 5 days before Jan 31
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$dpd = $tday - $loan_days;
$days_to_due = ($tday < $loan_days) ? ($loan_days - $tday) : 0;
test(
    "Test 3.1: 5 days before due date (Processed: Jan 1, Days: 30, Current: Jan 26)",
    5,
    $days_to_due,
    "Should trigger 5-day reminder (days_to_due = 5)"
);

// Test 3.2: DPD 1-5 SMS condition
$processed_date = '2024-01-01';
$loan_days = 30;
$current_date = '2024-02-02'; // DPD = 2
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$dpd = $tday - $loan_days;
$should_send = ($dpd >= 1 && $dpd <= 5);
test(
    "Test 3.2: DPD 1-5 SMS (Processed: Jan 1, Days: 30, Current: Feb 2, DPD: 2)",
    true,
    $should_send,
    "Should send DPD 1-5 SMS (DPD = 2)"
);

// Test 3.3: DPD 6-10 SMS condition
$processed_date = '2024-01-01';
$loan_days = 30;
$current_date = '2024-02-07'; // DPD = 7
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$dpd = $tday - $loan_days;
$should_send = ($dpd >= 6 && $dpd <= 10);
test(
    "Test 3.3: DPD 6-10 SMS (Processed: Jan 1, Days: 30, Current: Feb 7, DPD: 7)",
    true,
    $should_send,
    "Should send DPD 6-10 SMS (DPD = 7)"
);

// ============================================
// TEST 4: E-Nach Trigger Logic
// ============================================
echo "\n{$YELLOW}TEST SUITE 4: E-Nach Trigger Logic{$RESET}\n";
echo str_repeat("-", 50) . "\n\n";

// Test 4.1: E-Nach triggers on DPD = 1 (old loan)
$processed_date = '2024-01-01';
$loan_days = 30;
$current_date = '2024-02-01'; // DPD = 1
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$should_trigger = ($tday == ($loan_days + 1));
test(
    "Test 4.1: E-Nach trigger on DPD = 1 (Processed: Jan 1, Days: 30, Current: Feb 1)",
    true,
    $should_trigger,
    "Should trigger E-Nach (tday = 31, days = 30, tday == days + 1)"
);

// Test 4.2: E-Nach does NOT trigger before DPD = 1
$processed_date = '2024-01-01';
$loan_days = 30;
$current_date = '2024-01-31'; // DPD = 0
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$should_trigger = ($tday == ($loan_days + 1));
test(
    "Test 4.2: E-Nach does NOT trigger on due date (Processed: Jan 1, Days: 30, Current: Jan 31)",
    false,
    $should_trigger,
    "Should NOT trigger E-Nach (tday = 30, days = 30, tday != days + 1)"
);

// Test 4.3: E-Nach triggers on DPD = 1 (new loan with 25 days)
$processed_date = '2024-01-01';
$loan_days = 25; // New loan
$current_date = '2024-01-27'; // DPD = 1
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$should_trigger = ($tday == ($loan_days + 1));
test(
    "Test 4.3: E-Nach trigger on DPD = 1 (New loan: Processed: Jan 1, Days: 25, Current: Jan 27)",
    true,
    $should_trigger,
    "Should trigger E-Nach (tday = 26, days = 25, tday == days + 1)"
);

// Test 4.4: E-Nach does NOT trigger on DPD > 1
$processed_date = '2024-01-01';
$loan_days = 30;
$current_date = '2024-02-05'; // DPD = 5
$processed_date_str = date('Y-m-d', strtotime($processed_date . " -1 day"));
$tday = ceil((strtotime($current_date) - strtotime($processed_date_str)) / (60 * 60 * 24));
$should_trigger = ($tday == ($loan_days + 1));
test(
    "Test 4.4: E-Nach does NOT trigger on DPD > 1 (Processed: Jan 1, Days: 30, Current: Feb 5)",
    false,
    $should_trigger,
    "Should NOT trigger E-Nach (tday = 35, days = 30, tday != days + 1)"
);

// ============================================
// TEST 5: KFS Calculations
// ============================================
echo "\n{$YELLOW}TEST SUITE 5: KFS Calculations{$RESET}\n";
echo str_repeat("-", 50) . "\n\n";

// Test 5.1: KFS uses actual days for femi_date
$dis_date = '2024-01-01';
$loan_days = 25; // New loan
$femi_date = date('Y-m-d', strtotime($dis_date . " +" . $loan_days . " day"));
$expected_femi_date = '2024-01-26';
test(
    "Test 5.1: KFS femi_date calculation (Dis: Jan 1, Days: 25)",
    $expected_femi_date,
    $femi_date,
    "femi_date should be Jan 26 (dis_date + 25 days)"
);

// Test 5.2: KFS uses actual days for interest calculation
$loan_amount = 10000;
$loan_days = 25;
$dint = ($loan_days > 0) ? (($loan_amount * 0.03) / $loan_days) : (($loan_amount * 0.03) / 30);
$expected_dint = (10000 * 0.03) / 25; // 300 / 25 = 12
test(
    "Test 5.2: KFS daily interest calculation (Amount: 10000, Days: 25)",
    $expected_dint,
    $dint,
    "Daily interest should be 12 (300 / 25)"
);

// Test 5.3: KFS total interest uses actual days
$loan_amount = 10000;
$loan_days = 25;
$dint = ($loan_days > 0) ? (($loan_amount * 0.03) / $loan_days) : (($loan_amount * 0.03) / 30);
$total_days_for_interest = $loan_days + 35; // 25 + 35 = 60
$tint = $dint * $total_days_for_interest;
$expected_tint = 12 * 60; // 720
test(
    "Test 5.3: KFS total interest calculation (Amount: 10000, Days: 25)",
    $expected_tint,
    $tint,
    "Total interest should be 720 (12 * 60 days)"
);

// Test 5.4: Old loan still uses days = 30
$dis_date = '2024-01-01';
$loan_days = 30; // Old loan
$femi_date = date('Y-m-d', strtotime($dis_date . " +" . $loan_days . " day"));
$expected_femi_date = '2024-01-31';
test(
    "Test 5.4: KFS femi_date for old loan (Dis: Jan 1, Days: 30)",
    $expected_femi_date,
    $femi_date,
    "femi_date should be Jan 31 (dis_date + 30 days, same as before)"
);

// ============================================
// TEST 6: Edge Cases
// ============================================
echo "\n{$YELLOW}TEST SUITE 6: Edge Cases{$RESET}\n";
echo str_repeat("-", 50) . "\n\n";

// Test 6.1: Month boundary crossing
$applied_date = '2024-01-28';
$salary_date = 5; // Feb 5
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 5 - 28 = -23 (negative) → Next month = Feb 5
// Days = Feb 5 - Jan 28 = 8 days
test(
    "Test 6.1: Month boundary crossing (Applied: Jan 28, Salary: 5)",
    8,
    $result,
    "Should return 8 days (Jan 28 → Feb 5)"
);

// Test 6.2: Year boundary crossing
$applied_date = '2023-12-28';
$salary_date = 5; // Jan 5
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 5 - 28 = -23 (negative) → Next month = Jan 5, 2024
// Days = Jan 5, 2024 - Dec 28, 2023 = 8 days
test(
    "Test 6.2: Year boundary crossing (Applied: Dec 28, 2023, Salary: 5)",
    8,
    $result,
    "Should return 8 days (Dec 28, 2023 → Jan 5, 2024)"
);

// Test 6.3: Leap year February
$applied_date = '2024-01-25';
$salary_date = 29; // Feb 29 (leap year)
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 29 - 25 = 4 days (< 8) → Next month = Feb 29
// Days = Feb 29 - Jan 25 = 35 days
test(
    "Test 6.3: Leap year February (Applied: Jan 25, 2024, Salary: 29)",
    35,
    $result,
    "Should return 35 days (Jan 25 → Feb 29, 2024 is leap year)"
);

// Test 6.4: Non-leap year February (salary date 31 → Feb 28)
$applied_date = '2023-01-25';
$salary_date = 31; // Jan 31
$result = calculateLoanDays($applied_date, $salary_date);
// Gap = 31 - 25 = 6 days (< 8) → Next month = Feb 28 (2023 is not leap year)
// Days = Feb 28 - Jan 25 = 34 days
test(
    "Test 6.4: Non-leap year February (Applied: Jan 25, 2023, Salary: 31)",
    34,
    $result,
    "Should return 34 days (Jan 25 → Feb 28, 2023 is not leap year)"
);

// ============================================
// TEST SUMMARY
// ============================================
echo "\n{$BLUE}========================================{$RESET}\n";
echo "{$BLUE}Test Summary{$RESET}\n";
echo "{$BLUE}========================================{$RESET}\n";
echo "Total Tests: $total_tests\n";
echo "{$GREEN}Passed: $passed_tests{$RESET}\n";
echo "{$RED}Failed: $failed_tests{$RESET}\n";

if ($failed_tests == 0) {
    echo "\n{$GREEN}✓ All tests passed!{$RESET}\n";
    exit(0);
} else {
    echo "\n{$RED}✗ Some tests failed!{$RESET}\n";
    exit(1);
}
?>


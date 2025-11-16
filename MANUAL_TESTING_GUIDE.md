# Manual Testing Guide - Loan Due Date Calculation

This guide helps you manually test the loan due date calculation logic in your system.

## Prerequisites

1. Access to the database
2. Ability to create test loans
3. Access to SMS cron logs
4. Access to E-Nach cron logs

---

## Test Scenario 1: New Loan with Salary Date (Gap < 8 Days)

### Setup:
- **User**: Create a test user with `salary_date = 15`
- **Applied Date**: January 10, 2024
- **Expected**: Due date should be **February 15, 2024** (36 days)

### Steps:
1. Apply for a loan on January 10, 2024
2. Check `loan_apply` table:
   ```sql
   SELECT id, days, apply_date FROM loan_apply WHERE uid = [test_user_id] ORDER BY id DESC LIMIT 1;
   ```
   - **Expected**: `days = 36`

3. Check KFS (`key.php?id=[loan_id]`):
   - **Expected**: Repayment date shows **February 15, 2024**
   - **Expected**: Interest calculations use 36 days

4. Check Loan Agreement (`user/loan_agreement.php?id=[loan_id]`):
   - **Expected**: Repayment date shows **February 15, 2024**

5. Wait until February 10 (5 days before due date):
   - **Expected**: SMS reminder sent (5 days before reminder)

6. Wait until February 15 (due date):
   - **Expected**: SMS sent (due date reminder)

7. Wait until February 16 (DPD = 1):
   - **Expected**: E-Nach triggers
   - **Expected**: SMS sent (DPD 1-5)

---

## Test Scenario 2: New Loan with Salary Date (Gap >= 8 Days)

### Setup:
- **User**: Create a test user with `salary_date = 25`
- **Applied Date**: January 10, 2024
- **Expected**: Due date should be **January 25, 2024** (15 days)

### Steps:
1. Apply for a loan on January 10, 2024
2. Check `loan_apply` table:
   - **Expected**: `days = 15`

3. Check KFS:
   - **Expected**: Repayment date shows **January 25, 2024**
   - **Expected**: Interest calculations use 15 days

4. Wait until January 20 (5 days before due date):
   - **Expected**: SMS reminder sent

5. Wait until January 25 (due date):
   - **Expected**: SMS sent (due date reminder)

6. Wait until January 26 (DPD = 1):
   - **Expected**: E-Nach triggers
   - **Expected**: SMS sent (DPD 1-5)

---

## Test Scenario 3: New Loan without Salary Date

### Setup:
- **User**: Create a test user with `salary_date = NULL` or `0`
- **Applied Date**: January 10, 2024
- **Expected**: Due date should be **February 9, 2024** (30 days default)

### Steps:
1. Apply for a loan on January 10, 2024
2. Check `loan_apply` table:
   - **Expected**: `days = 30`

3. Check KFS:
   - **Expected**: Repayment date shows **February 9, 2024**
   - **Expected**: Interest calculations use 30 days (same as old loans)

4. Verify it works exactly like old loans

---

## Test Scenario 4: Old Loan (Backward Compatibility)

### Setup:
- **Loan**: Use an existing loan with `days = 30`
- **Expected**: Should work exactly as before

### Steps:
1. Find an existing loan with `days = 30`
2. Check KFS:
   - **Expected**: Repayment date = `processed_date + 30 days`
   - **Expected**: Interest calculations use 30 days

3. Check SMS cron:
   - **Expected**: SMS sent based on DPD = `tday - 30`

4. Check E-Nach cron:
   - **Expected**: E-Nach triggers when `tday = 31` (DPD = 1)

---

## Test Scenario 5: SMS Cron - 5 Days Before Reminder

### Setup:
- **Loan**: Create a loan with `days = 30`, processed on January 1
- **Expected**: 5-day reminder on January 26

### Steps:
1. Create loan on January 1, 2024
2. On January 26, 2024 (5 days before due date):
   - Check SMS cron logs (`zzautosms_complete.php`)
   - **Expected**: SMS sent with "5 day reminder" message
   - **Expected**: Time window: 10:00-10:04 AM or 5:00-5:04 PM

3. Verify SMS is sent only once per day

---

## Test Scenario 6: SMS Cron - DPD Based Logic

### Setup:
- **Loan**: Create a loan with `days = 25`, processed on January 1
- **Due Date**: January 26
- **Expected**: SMS based on DPD, not fixed days

### Steps:
1. Create loan on January 1, 2024
2. On January 21 (5 days before due date):
   - **Expected**: SMS sent (5-day reminder)

3. On January 26 (due date, DPD = 0):
   - **Expected**: SMS sent (due date reminder)

4. On January 27 (DPD = 1):
   - **Expected**: SMS sent (DPD 1-5)

5. On January 28 (DPD = 2):
   - **Expected**: SMS sent (DPD 1-5)

6. On February 1 (DPD = 6):
   - **Expected**: SMS sent (DPD 6-10)

---

## Test Scenario 7: E-Nach Trigger Logic

### Setup:
- **Loan**: Create a loan with `days = 25`, processed on January 1
- **Due Date**: January 26
- **Expected**: E-Nach triggers on January 27 (DPD = 1)

### Steps:
1. Create loan on January 1, 2024
2. On January 26 (due date):
   - Check E-Nach cron logs (`payment/auto_enach.php`)
   - **Expected**: E-Nach does NOT trigger (DPD = 0)

3. On January 27 (DPD = 1):
   - Check E-Nach cron logs
   - **Expected**: E-Nach triggers
   - **Expected**: Amount calculated with DPD = 1 penalty

4. Verify amount calculation:
   - Service charge: Based on `tday = 26`
   - Penalty: Based on `DPD = 1` (first day penalty: 4%)

---

## Test Scenario 8: KFS Interest Calculations

### Setup:
- **Loan**: Create a loan with `days = 25`, amount = 10,000
- **Expected**: Interest calculations use 25 days, not 30

### Steps:
1. Create loan with `days = 25`
2. Open KFS (`key.php?id=[loan_id]`)
3. Verify:
   - **Daily Interest**: `(loan_amount * 0.03) / 25` = `300 / 25` = `12`
   - **Total Interest**: `12 * (25 + 35)` = `12 * 60` = `720`
   - **Repayment Date**: `disbursal_date + 25 days`

---

## Test Scenario 9: Loan Agreement Calculations

### Setup:
- **Loan**: Create a loan with `days = 25`
- **Expected**: Loan agreement shows correct repayment date

### Steps:
1. Create loan with `days = 25`
2. Open Loan Agreement (`user/loan_agreement.php?id=[loan_id]`)
3. Verify:
   - **Repayment Date**: `disbursal_date + 25 days`
   - **Repayment Amount**: Calculated based on 25 days

---

## Test Scenario 10: Edge Cases

### 10.1: Salary Date 31 in February
- **Applied**: January 25, 2024
- **Salary**: 31
- **Expected**: Due date = February 29, 2024 (2024 is leap year)
- **Expected**: `days = 35`

### 10.2: Month Boundary
- **Applied**: January 28, 2024
- **Salary**: 5
- **Expected**: Due date = February 5, 2024
- **Expected**: `days = 8`

### 10.3: Year Boundary
- **Applied**: December 28, 2023
- **Salary**: 5
- **Expected**: Due date = January 5, 2024
- **Expected**: `days = 8`

---

## Verification Checklist

After running all tests, verify:

- [ ] New loans calculate `days` correctly based on salary_date
- [ ] Old loans keep `days = 30` and work as before
- [ ] KFS shows correct repayment date based on actual `days`
- [ ] KFS interest calculations use actual `days`
- [ ] Loan agreement shows correct repayment date
- [ ] SMS cron sends reminders 5 days before due date
- [ ] SMS cron uses DPD logic (not fixed days)
- [ ] E-Nach triggers on DPD = 1 (not fixed 31st day)
- [ ] E-Nach amount calculations use DPD for penalty
- [ ] All calculations work for both old and new loans

---

## SQL Queries for Verification

### Check loan days:
```sql
SELECT 
    la.id, 
    la.days, 
    la.apply_date,
    u.salary_date,
    l.processed_date,
    DATE_ADD(l.processed_date, INTERVAL la.days DAY) as calculated_due_date
FROM loan_apply la
INNER JOIN loan l ON la.id = l.lid
INNER JOIN user u ON la.uid = u.id
WHERE la.id = [loan_id];
```

### Check all new loans:
```sql
SELECT 
    la.id,
    la.days,
    la.apply_date,
    u.salary_date,
    CASE 
        WHEN la.days = 30 THEN 'Old Loan (Default)'
        ELSE 'New Loan (Calculated)'
    END as loan_type
FROM loan_apply la
INNER JOIN user u ON la.uid = u.id
WHERE la.apply_date >= '2024-01-01' -- Adjust date as needed
ORDER BY la.id DESC;
```

---

## Common Issues to Watch For

1. **Days calculation wrong**: Check `calculateLoanDays()` function logic
2. **KFS shows wrong date**: Check if `key.php` uses `$b['days']` correctly
3. **SMS not sending**: Check DPD calculation in `zzautosms_complete.php`
4. **E-Nach not triggering**: Check if `tday == days + 1` condition is met
5. **Old loans broken**: Ensure fallback to `days = 30` works

---

## Rollback Plan

If something goes wrong:

1. **Revert code changes**: Use git to revert to previous version
2. **Database**: No database changes were made, so no rollback needed
3. **Old loans**: Should continue working as `days = 30` is preserved

---

## Notes

- All new loans will have calculated `days` based on salary_date
- Old loans (10k+) keep `days = 30` and work exactly as before
- No database migration needed - uses existing `days` column
- All changes are backward compatible


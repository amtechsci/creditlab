# Loan Due Date Implementation Summary

## Overview
This implementation updates the loan system to calculate due dates based on salary date instead of a fixed 30-day period. All related calculations, displays, SMS cron jobs, and E-Nach cron jobs have been updated to use the new due_date logic.

## Changes Made

### 1. Database Changes
- **SQL Script**: `add_due_date_column.sql`
  - Adds `due_date` DATE column to `loan_apply` table
  - Adds index on `due_date` for better query performance
  - Backfill command provided (commented) for existing loans

### 2. Core Function (`db.php`)
- **New Function**: `calculateLoanDueDate($applied_date, $salary_date)`
  - Business Rules:
    - If gap between applied date and salary date < 8 days: due date = next month's salary date
    - If gap >= 8 days: due date = next salary date (same month if possible, else next month)
    - If salary date is missing: default to 30 days from applied date
  - Handles edge cases (e.g., February 31st -> February 28th/29th)

### 3. Loan Application Files Updated
- **`user/apply.php`**: 
  - Calculates due_date when creating loan applications
  - Stores due_date in loan_apply table
  - Updates days calculation based on due_date
  
- **`zzzzzapi`**: 
  - Calculates due_date for API loan applications
  - Stores due_date in loan_apply table
  
- **`user/secapply.php`**: 
  - Calculates due_date for secondary loan applications
  - Stores due_date in loan_apply table

### 4. Key Fact Statement (KFS) Updated (`key.php`)
- Uses `due_date` from loan_apply instead of hardcoded 30 days
- Calculates first EMI date (femi_date) based on due_date
- Updates interest calculations to use actual days instead of hardcoded 30/65 days
- Fallback logic for old loans without due_date

### 5. SMS Cron Updated (`zzautosms_complete.php`)
- **Changed from**: Fixed days (20th, 25th, 30th, 31st day since processed_date)
- **Changed to**: DPD (Days Past Due) based on due_date
- **Key Changes**:
  - Fetches `due_date` from loan_apply table
  - Calculates DPD (Days Past Due) = current_date - due_date
  - Calculates days_to_due for reminders before due date
  - Updated SMS conditions:
    - **CIBIL DROP ALERT**: 5-0 days before due date or on due date
    - **DPD 1-5**: 1-5 days past due date
    - **DPD 6-10**: 6-10 days past due date
    - **Initial Reminder**: 5-0 days before due date
    - **Due Date Missed**: 1-5 days past due date
    - **E-NACH Will Not Happen**: 0-1 days (on due date or day after)

### 6. E-Nach Cron Updated (`payment/auto_enach.php`)
- **Changed from**: `exhausted_period = 31` (fixed 31st day logic)
- **Changed to**: `due_date + 1 day` (DPD = 1)
- **Key Changes**:
  - Removed Condition 2 (fixed days: 3rd, 10th, last day of month)
  - Removed Condition 3 (salary date based on exhausted_period > 30)
  - New Condition 1: Triggers E-Nach when `current_date = due_date + 1 day`
  - Fallback logic for old loans without due_date (processed_date + 31 days)
  - Updated SQL queries to join with loan_apply table to access due_date

## Business Logic

### Due Date Calculation
1. **Gap < 8 days**: 
   - Example: Applied on Jan 15, Salary date Jan 20 (gap = 5 days)
   - Due date: February 20 (next month's salary date)

2. **Gap >= 8 days**:
   - Example: Applied on Jan 10, Salary date Jan 25 (gap = 15 days)
   - Due date: January 25 (same month's salary date)
   
   - Example: Applied on Jan 25, Salary date Jan 5 (salary date passed)
   - Due date: February 5 (next month's salary date)

3. **No Salary Date**:
   - Default: Applied date + 30 days

### SMS & E-Nach Timing
- **SMS Reminders**: Based on DPD (Days Past Due) or days to due date
- **E-Nach Trigger**: Due date + 1 day (DPD = 1)
- **Penalty Start**: After due date (DPD > 0)

## Deployment Steps

1. **Run SQL Script**:
   ```sql
   -- Run add_due_date_column.sql on your database
   ALTER TABLE `loan_apply` ADD COLUMN `due_date` DATE NULL AFTER `days`;
   ALTER TABLE `loan_apply` ADD INDEX `idx_due_date` (`due_date`);
   ```

2. **Backfill Existing Loans** (Optional):
   ```sql
   -- If you want to backfill due_date for existing loans
   UPDATE `loan_apply` SET `due_date` = DATE_ADD(`apply_date`, INTERVAL 30 DAY) WHERE `due_date` IS NULL;
   ```

3. **Deploy Code Changes**:
   - All PHP files have been updated
   - New loans will automatically calculate and store due_date
   - Old loans will use fallback logic where needed

## Testing Recommendations

1. **Test Due Date Calculation**:
   - Apply new loan with salary date < 8 days from applied date
   - Apply new loan with salary date >= 8 days from applied date
   - Apply new loan without salary date

2. **Test SMS Cron**:
   - Verify SMS are sent based on DPD, not fixed days
   - Check reminders before due date
   - Check alerts after due date

3. **Test E-Nach Cron**:
   - Verify E-Nach triggers on due_date + 1 day
   - Check that it doesn't trigger based on exhausted_period = 31 anymore

4. **Test KFS Generation**:
   - Verify KFS shows correct due date
   - Check calculations use actual days, not 30 days

## Notes

- **Backward Compatibility**: All changes include fallback logic for old loans without due_date
- **Database**: Ensure `due_date` column exists before deploying code changes
- **Cron Jobs**: SMS and E-Nach crons will work with both new and old loans
- **Existing Loans**: Old loans without due_date will use fallback calculations (apply_date + 30 days)

## Files Modified

1. `db.php` - Added calculateLoanDueDate() function
2. `user/apply.php` - Calculate and store due_date
3. `zzzzzapi` - Calculate and store due_date
4. `user/secapply.php` - Calculate and store due_date
5. `key.php` - Use due_date in KFS calculations
6. `zzautosms_complete.php` - Use DPD based on due_date
7. `payment/auto_enach.php` - Use due_date + 1 day instead of exhausted_period = 31

## Files Created

1. `add_due_date_column.sql` - Database migration script
2. `IMPLEMENTATION_SUMMARY.md` - This file


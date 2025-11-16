# Simplified Implementation - Using Days Column Only

## Overview
Instead of adding a new `due_date` column, we just calculate the number of **days** based on salary_date logic and store it in the existing `days` column in `loan_apply` table.

## What We Need

1. **Function to calculate days** based on salary_date and applied_date
2. **Update loan applications** to calculate and store days (not due_date)
3. **Use existing `days` column** - no new database column needed!
4. **Update calculations** to use `days` from database instead of hardcoded 30

## Benefits

- ✅ **No database changes needed** - use existing `days` column
- ✅ **Simpler code** - just calculate number of days
- ✅ **Backward compatible** - old loans keep their `days = 30`
- ✅ **Less risky** - no new column dependency

## How It Works

### For New Loans
- Calculate `days` based on salary_date logic
- Store in existing `days` column
- `femi_date = processed_date + days` (already works this way!)
- `DPD = tday - days` (for SMS cron)
- E-Nach triggers when: `tday = days + 1`

### For Old Loans
- Keep existing `days = 30`
- Everything works as before
- No changes needed


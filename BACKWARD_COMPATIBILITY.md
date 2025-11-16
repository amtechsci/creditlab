# Backward Compatibility Guide

## Overview
This document explains how the new `due_date` feature maintains full backward compatibility with 10k+ existing loans that don't have the `due_date` column populated.

## Key Principles

1. **New Column is NULL by Default**: The `due_date` column is added as `NULL` and is only populated for **new loans** created after deployment.

2. **Old Loans Use Original Logic**: All existing loans (10k+) will continue to use the original 30-day logic exactly as before. No changes to their behavior.

3. **Fallback Logic Everywhere**: Every place that uses `due_date` has explicit fallback logic to use the old 30-day calculation when `due_date` is NULL.

## How It Works

### For New Loans (with `due_date`)
- `due_date` is calculated based on salary date and applied date
- All calculations, SMS, E-Nach use this `due_date`
- Behavior: Dynamic based on salary date

### For Old Loans (without `due_date`)
- `due_date` is NULL
- System detects NULL and uses original 30-day logic
- Behavior: Exactly as before (processed_date + 30 days)

## Fallback Logic by File

### 1. `key.php` (KFS Generation)
```php
if (!empty($b['due_date'])) {
    // New loan: use due_date
} else {
    // Old loan: use original logic (sal_day + 30 days = femi_date)
    $femi_date = date('Y-m-d', strtotime($sal_day . " +30 day"));
    $actual_days = 30;
}
```

### 2. `zzautosms_complete.php` (SMS Cron)
```php
if (!empty($loan_data['due_date'])) {
    // New loan: calculate DPD based on due_date
    $dpd = (current_date - due_date);
} else {
    // Old loan: use original 30-day logic
    $dpd = $tday - 30;  // Since original due date was processed_date + 30 days
}
```

### 3. `payment/auto_enach.php` (E-Nach Cron)
```php
if (empty($loan_apply_data['due_date'])) {
    // Old loan: use original logic (processed_date + 31 days)
    $fallback_trigger_date = processed_date + 31 days;
} else {
    // New loan: use due_date + 1 day
    $due_date_plus_one = due_date + 1 day;
}
```

## Database Impact

### New Column
- **Column Name**: `due_date`
- **Type**: `DATE NULL`
- **Default**: `NULL` (for all existing loans)
- **Index**: Added for performance (doesn't affect existing queries)

### Existing Data
- **10k+ existing loans**: `due_date` remains `NULL`
- **No data migration required**
- **No downtime needed**

### New Loans
- Automatically calculate and store `due_date`
- No manual intervention needed

## Testing Recommendations

### Test Old Loans
1. Verify KFS generation works for old loans (should show 30 days)
2. Verify SMS cron works for old loans (should use original 30-day logic)
3. Verify E-Nach cron works for old loans (should trigger on day 31)

### Test New Loans
1. Create new loan with salary date
2. Verify `due_date` is calculated and stored
3. Verify KFS uses actual due_date (not 30 days)
4. Verify SMS cron uses DPD based on due_date
5. Verify E-Nach triggers on due_date + 1 day

### Test Mixed Scenario
1. Run cron jobs with both old and new loans
2. Verify old loans use fallback logic
3. Verify new loans use due_date logic
4. Verify no conflicts or errors

## Why NOT to Backfill

**Recommended**: Do NOT backfill `due_date` for existing loans because:

1. **Original Logic May Differ**: Old loans may have been processed with different rules
2. **Historical Accuracy**: Changing due dates for old loans could affect reporting
3. **Risk**: Backfilling could introduce inconsistencies
4. **Unnecessary**: Fallback logic handles old loans perfectly

## Migration Strategy (If Needed Later)

If you decide to backfill for reporting purposes:

```sql
-- ONLY for reporting/reference, not for changing behavior
-- Old loans will still use fallback logic even if due_date is populated
UPDATE `loan_apply` 
SET `due_date` = DATE_ADD(`apply_date`, INTERVAL 30 DAY) 
WHERE `due_date` IS NULL 
AND `apply_date` IS NOT NULL;
```

**Note**: Even after backfilling, the code will still check if `due_date` matches the old 30-day logic and use fallback if needed to ensure consistency.

## Safety Features

1. **NULL Checks**: All code checks `if (!empty($due_date))` before using it
2. **Explicit Fallbacks**: Fallback logic is explicit and matches original behavior
3. **No Breaking Changes**: Old loans continue to work exactly as before
4. **Gradual Migration**: Only new loans use new logic, allowing gradual migration

## Support

If you encounter any issues:
1. Check logs for "fallback" messages (indicates old loans using fallback logic)
2. Verify `due_date` is NULL for old loans (expected behavior)
3. Verify `due_date` is populated for new loans (expected behavior)


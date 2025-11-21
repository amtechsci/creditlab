# URL Migration Summary - Dynamic Base URL

## What Was Done

### 1. Created `getAppUrl()` Function
- **Location**: `db.php` (line ~393)
- **Functionality**: 
  - Fetches base URL from `site_config` table in database
  - Falls back to auto-detection from current request if not in database
  - Creates `site_config` table automatically if it doesn't exist
  - Caches the URL for performance

### 2. Updated Files (Payment & Webhook)
✅ **payment/auto_enach.php** - Added function, updated URLs
✅ **payment/zzenach.php** - Updated URLs  
✅ **payment/manual_enach.php** - Updated URLs
✅ **payment/cb.php** - Added function, updated URLs
✅ **payment/cb_auto.php** - Added function, updated URLs
✅ **easebuzz_callback.php** - Added function, updated URLs
✅ **easebuzz_webhook.php** - Added function, updated URLs

### 3. Database Table Created
- **Table**: `site_config`
- **Structure**:
  - `id` (primary key)
  - `config_key` (unique) - stores 'base_url'
  - `config_value` (text) - stores the actual URL
  - `updated_at` (timestamp)

## How to Set Your Base URL

### Option 1: Via SQL (Recommended)
Run this SQL command to set your base URL:

```sql
-- For testing environment
UPDATE `site_config` SET `config_value` = 'https://testing.creditlab.in' WHERE `config_key` = 'base_url';

-- Or insert if it doesn't exist
INSERT INTO `site_config` (`config_key`, `config_value`) 
VALUES ('base_url', 'https://testing.creditlab.in') 
ON DUPLICATE KEY UPDATE `config_value` = 'https://testing.creditlab.in';
```

### Option 2: Via PHP
You can also update it programmatically:
```php
include 'db.php';
towquery("UPDATE `site_config` SET `config_value` = 'https://testing.creditlab.in' WHERE `config_key` = 'base_url'");
```

## Remaining Files to Update

The following files still have hardcoded URLs that need to be updated:

### High Priority (User-Facing)
- `zzzzzapi` - API responses with URLs
- `zzautosms_complete.php` - SMS messages with URLs
- `key.php` - KFS document URLs
- `user/apply.php` - Email templates
- `user/secapply.php` - Email templates

### Medium Priority
- `user/loan_agreement.php` - Loan agreement document
- `user/dashboardnotemi.php` - User dashboard
- Various admin/account_manager files

### Low Priority (Documentation/Static)
- PDF generation files
- Documentation files (can be left as-is)

## Testing

After setting the base URL in database:
1. Clear any PHP opcode cache (if using OPcache)
2. Test payment callbacks
3. Test SMS links
4. Test email links
5. Verify all redirects work correctly

## Notes

- The function auto-detects the URL from `$_SERVER['HTTP_HOST']` if not in database
- URLs are cached per request for performance
- All URLs are stored without trailing slash (removed automatically)
- The system will work even if the database table doesn't exist (falls back to auto-detection)


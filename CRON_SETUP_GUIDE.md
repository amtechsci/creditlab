# 🕐 CreditLab SMS Cron Job Setup Guide

## 📋 Overview
This guide explains how to set up automated SMS cron jobs for your CreditLab system.

## 🔧 Files Ready for Cron
- `zzautosms_optimized.php` - Main automated SMS system (RECOMMENDED)
- `zzautosms.php` - Original automated SMS system
- `zzemiautosms.php` - EMI-specific automated SMS
- `sms_monitoring.php` - Dashboard to monitor SMS delivery

## 🖥️ Windows Setup (XAMPP)

### Method 1: Windows Task Scheduler (Recommended)

1. **Open Task Scheduler**
   - Press `Win + R`, type `taskschd.msc`, press Enter

2. **Create Basic Task**
   - Click "Create Basic Task" in the right panel
   - Name: `CreditLab SMS Automation`
   - Description: `Automated SMS for loan reminders and notifications`

3. **Set Trigger**
   - Choose "Daily"
   - Start: `Today`
   - Recur every: `1 day`
   - Click "Next"

4. **Set Action**
   - Choose "Start a program"
   - Program/script: `C:\xampp\php\php.exe`
   - Add arguments: `C:\xampp\htdocs\creditlab\zzautosms_optimized.php`
   - Start in: `C:\xampp\htdocs\creditlab`

5. **Advanced Settings**
   - Check "Run whether user is logged on or not"
   - Check "Run with highest privileges"
   - Click "Finish"

### Method 2: Multiple Cron Jobs for Different Frequencies

Create separate tasks for different SMS types:

#### Task 1: Hourly SMS Check
- **Name:** `CreditLab Hourly SMS`
- **Trigger:** Daily, Every 1 hour
- **Action:** `php.exe` with `zzautosms_optimized.php`

#### Task 2: Business Hours SMS (9 AM - 6 PM)
- **Name:** `CreditLab Business Hours SMS`
- **Trigger:** Daily, Every 2 hours, 9 AM to 6 PM
- **Action:** `php.exe` with `zzautosms_optimized.php`

#### Task 3: Morning Reminders
- **Name:** `CreditLab Morning Reminders`
- **Trigger:** Daily at 9:00 AM
- **Action:** `php.exe` with `zzautosms_optimized.php`

#### Task 4: Evening Reminders
- **Name:** `CreditLab Evening Reminders`
- **Trigger:** Daily at 6:00 PM
- **Action:** `php.exe` with `zzautosms_optimized.php`

## 🐧 Linux/Unix Setup

### Method 1: Crontab (Recommended)

1. **Open Crontab**
   ```bash
   crontab -e
   ```

2. **Add Cron Jobs**
   ```bash
   # Run every hour
   0 * * * * /usr/bin/php /path/to/creditlab/zzautosms_optimized.php

   # Run every 2 hours during business hours (9 AM - 6 PM)
   0 9-18/2 * * * /usr/bin/php /path/to/creditlab/zzautosms_optimized.php

   # Run daily at 9 AM for morning reminders
   0 9 * * * /usr/bin/php /path/to/creditlab/zzautosms_optimized.php

   # Run daily at 6 PM for evening reminders
   0 18 * * * /usr/bin/php /path/to/creditlab/zzautosms_optimized.php

   # Run every 30 minutes during business hours
   */30 9-18 * * * /usr/bin/php /path/to/creditlab/zzautosms_optimized.php
   ```

3. **Save and Exit**
   - Press `Ctrl + X`, then `Y`, then `Enter`

### Method 2: System Cron

1. **Create Cron File**
   ```bash
   sudo nano /etc/cron.d/creditlab-sms
   ```

2. **Add Content**
   ```bash
   # CreditLab SMS Automation
   0 * * * * www-data /usr/bin/php /var/www/html/creditlab/zzautosms_optimized.php
   ```

3. **Set Permissions**
   ```bash
   sudo chmod 644 /etc/cron.d/creditlab-sms
   ```

## 🔍 Monitoring & Testing

### 1. Check Cron Status
```bash
# Linux - Check if cron is running
sudo systemctl status cron

# Windows - Check Task Scheduler
# Open Task Scheduler and check "Active Tasks"
```

### 2. Monitor SMS Logs
- Visit: `https://yourdomain.com/creditlab/sms_monitoring.php`
- Check log file: `sms_cron_log.txt`
- Monitor delivery status

### 3. Manual Testing
```bash
# Test the SMS script manually
php zzautosms_optimized.php

# Check for errors
tail -f sms_cron_log.txt
```

## ⚙️ Configuration Options

### SMS Frequency Settings
Edit `zzautosms_optimized.php` to modify:
- **Day ranges** for different SMS types
- **Message templates**
- **User selection criteria**

### Log Settings
- **Log file:** `sms_cron_log.txt`
- **Log level:** All SMS attempts and errors
- **Rotation:** Manual (delete old logs periodically)

### Error Handling
- **Lock file:** Prevents multiple instances
- **Timeout:** 5 minutes maximum execution
- **Memory limit:** 256MB

## 🚨 Troubleshooting

### Common Issues

1. **SMS Not Sending**
   - Check database connection
   - Verify SMS portal credentials
   - Check log file for errors

2. **Cron Not Running**
   - Verify cron service is active
   - Check file permissions
   - Test manual execution

3. **Permission Errors**
   ```bash
   # Linux - Fix permissions
   chmod +x zzautosms_optimized.php
   chown www-data:www-data zzautosms_optimized.php
   ```

4. **Database Connection Issues**
   - Ensure database is running
   - Check connection credentials in `db.php`
   - Verify database server is accessible

### Debug Commands

```bash
# Test PHP execution
php -v

# Test script execution
php zzautosms_optimized.php

# Check cron logs (Linux)
grep CRON /var/log/syslog

# Check Windows Task Scheduler logs
# Event Viewer > Windows Logs > System
```

## 📊 Recommended Schedule

### For Production:
- **Every 2 hours** during business hours (9 AM - 6 PM)
- **Daily at 9 AM** for morning reminders
- **Daily at 6 PM** for evening reminders

### For Testing:
- **Every 30 minutes** during business hours
- **Manual execution** for immediate testing

## 🔐 Security Notes

1. **File Permissions**
   - Ensure only authorized users can modify cron files
   - Protect database credentials

2. **Log Security**
   - Monitor log files for sensitive information
   - Rotate logs regularly

3. **Network Security**
   - Ensure SMS portal is accessible
   - Use HTTPS for all communications

## 📞 Support

If you encounter issues:
1. Check the monitoring dashboard
2. Review log files
3. Test manual execution
4. Verify database connectivity
5. Contact SMS provider if needed

---

**Setup completed!** Your automated SMS system is ready to run. 🚀

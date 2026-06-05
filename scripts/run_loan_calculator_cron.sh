#!/bin/sh
# Loan penalty/service charge calculator — run from www-data crontab only.
cd /var/www/creditlab.in || exit 1
/usr/bin/php /var/www/creditlab.in/zzautoloanamountcalculator.php >> /var/www/creditlab.in/logs/autocalculator_cron.log 2>&1

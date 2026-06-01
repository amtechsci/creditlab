<?php
if (php_sapi_name() !== 'cli') {
	exit(1);
}
require_once dirname(__DIR__) . '/lib/sms_cron_schedule.php';
creditlab_print_sms_cron_schedule();

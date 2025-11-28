<?php
/**
 * Complete CreditLab Automated SMS System - DLT COMPLIANT
 * This is a self-contained file with the database connection included.
 * Run this via cron job for automated SMS.
 */

// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Set time limit and memory
set_time_limit(300); // 5 minutes
ini_set("memory_limit", "256M");

// --- LOGGING SETUP ---
$log_dir = "logs";
$sent_log_dir = "sent_logs"; // Directory to track sent messages
if (!is_dir($log_dir)) { @mkdir($log_dir, 0755, true); }
if (!is_dir($sent_log_dir)) { @mkdir($sent_log_dir, 0755, true); }

$current_log_date = date('Y-m-d');
$log_file = $log_dir . "/sms_cron_" . $current_log_date . ".log";
$sent_log_file = $sent_log_dir . "/sent_" . $current_log_date . ".log";

// Main log function
function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// =============================================================================
// == DATABASE CONNECTION AND HELPERS (INTEGRATED) ==
// =============================================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db = mysqli_connect("localhost", "root", "Atul@1012#", "testing_credit");
mysqli_set_charset($db,'utf8');
mysqli_query($db, "SET sql_mode = 'NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

function ensure_db_connection() {
    global $db;
    if (!isset($db) || !@mysqli_ping($db)) {
        logMessage("Database connection lost. Attempting to reconnect...");
        $db = @mysqli_connect("localhost", "root", "Atul@1012#", "testing_credit");
        if (!$db) {
            logMessage("FATAL: Database reconnection failed: " . mysqli_connect_error());
            return false;
        }
        @mysqli_set_charset($db,'utf8');
        @mysqli_query($db, "SET sql_mode = 'NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        logMessage("Database reconnected successfully.");
    }
    return true;
}

function towquery($query) {
    global $db;
    if (!ensure_db_connection()) { return false; }
    $re = mysqli_query($db, $query);
    if (!$re) {
        logMessage("SQL Error: " . mysqli_error($db) . " - Query: " . $query);
        return false;
    }
    return $re;
}

function townum($query) {
    if (!$query) return 0;
    return mysqli_num_rows($query);
}

function towfetch($query) {
    if (!$query) return null;
    return mysqli_fetch_assoc($query);
}

// --- DUPLICATE PREVENTION SYSTEM ---
$sent_today_cache = [];
function loadSentLog() {
    global $sent_log_file, $sent_today_cache;
    if (file_exists($sent_log_file)) {
        $lines = file($sent_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            $sent_today_cache = array_flip($lines);
        }
    }
}

function getSentLogKey($loan_id, $template_name) {
    return $loan_id . '_' . $template_name;
}

function hasBeenSentToday($loan_id, $template_name) {
    global $sent_today_cache;
    $key = getSentLogKey($loan_id, $template_name);
    return isset($sent_today_cache[$key]);
}

function markAsSent($loan_id, $template_name) {
    global $sent_log_file, $sent_today_cache;
    $key = getSentLogKey($loan_id, $template_name);
    if (!isset($sent_today_cache[$key])) {
        file_put_contents($sent_log_file, $key . PHP_EOL, FILE_APPEND | LOCK_EX);
        $sent_today_cache[$key] = true;
    }
}

// Check if script is already running
$lock_file = "sms_cron.lock";
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    if (time() - $lock_time < 240) { // Lock valid for 4 minutes
        logMessage("Script already running, exiting");
        exit;
    }
}
file_put_contents($lock_file, time()); // Create/update lock file

// =============================================================================
// == CENTRALIZED SMS TEMPLATES - DO NOT MODIFY UNLESS DLT TEMPLATE CHANGES ==
// =============================================================================
$sms_templates = [
    'cibil_drop_alert' => [
        'id' => '1407175283747333288',
        'template' => 'Act Now %s ! Your latest Creditlab loan is reported to 4 CIBIL bureaus. Pay on time to avoid CIBIL score drop : %s'
    ],
    'dpd_1_5' => [
        'id' => '1407175283203362638',
        'template' => 'URGENT %s ! Your Creditlab loan is OVERDUE. Pay immediately to avoid Penalty & severe CIBIL impact : %s'
    ],
    'dpd_6_10' => [
        'id' => '1407175283363256063',
        'template' => 'ATTENTION %s ! Your Creditlab loan is still OVERDUE. Recovery proceedings & CIBIL impact begin. Clear now to stop further action : %s'
    ],
    'dpd_11_15' => [
        'id' => '1407175283390827183',
        'template' => 'FINAL WARNING %s ! Your Creditlab.in loan remains OVERDUE. Legal, RECOVERY & CIBIL DAMAGE imminent. Settle dues TODAY to avoid escalation.'
    ],
    'initial_reminder' => [
        'id' => '1407175016269681511',
        'template' => "Dear Creditlab.in user, It's %s day reminder to repay your loan before due date. Doing so will grow Trust Score & increase your CIBIL, Experian & CRIF scores."
    ],
    'preclose' => [
        'id' => '1407175024263728707',
        'template' => "Dear creditlab.in user, It's been%sdays! Pre-close ur loan now, save %s interest & boost your CIBIL score. Act now : %s"
    ],
    'salary_day' => [
        'id' => '1407175007069974553',
        'template' => 'Dear Creditlab.in user, Clear the loan on salary day & reapply. It aligns ur repayment with ur salary day for smooth cycle from next loan %s'
    ],
    '45th_day_reminder' => [
        'id' => '1407175016251351187',
        'template' => 'Dear %s, you have a pending loan with Creditlab.in, Repay it immediately. Failure leads to DEFAULT/OVERDUE in CIBIL & to Debt collection agency.'
    ],
    'field_recovery' => [
        'id' => '1407175016192466512',
        'template' => 'Dear %s, your Creditlab.in loan %s, is now moved for Field Recovery. Our Field Recovery agent will visit your home & office addresses anytime in the next 6 to 10 days. Incase you choose to settle/close it before physical recovery visit, please contact %s'
    ],
    'legal_notice' => [
        'id' => '1407175016047912195',
        'template' => "LEGAL NOTICE !!! It's a follow-up reminder to close your Overdued Creditlab.in loan immediately to avoid further Legal consequences."
    ],
    'final_alert' => [
        'id' => '1407175016080435385',
        'template' => 'FINAL WARNING ! ! All Creditlab overdue loans will be reported as "Default" to CIBIL/CRIF/EXPERIAN/CRIF. Clear now %s'
    ],
    'cibil_dip' => [
        'id' => '1407175267110690531',
        'template' => 'Hello %s, your creditlab.in loan is OVERDUE & reported as DEFAULTER to CIBIL. Your score will drop 50-100 points. Avoid damage, pay now: %s'
    ],
    'legal_suit' => [
        'id' => '1407175267151421703',
        'template' => 'FINAL WARNING : %s Creditlab.in loan in DEFAULT. Legal suit being filed. This is your last chance to settle & close loan %s'
    ],
    'written_off' => [
        'id' => '1407175016041686176',
        'template' => 'Hey %s, your creditlab.in loan reported to CIBIL as written-off & default which affects all future loans. Repay the Principal to cancel this.'
    ],
    'waive_off' => [
        'id' => '1407175006859804198',
        'template' => 'Hey Creditlab.in user, 100%% penalty waived off for a limited period ! Close your pending loan & remove your CIBIL defaulter tag. Contact support@creditlab.in'
    ],
    'attention' => [
        'id' => '1407175016862547934',
        'template' => 'Attention! %s, your creditlab.in loan is unpaid despite reminders. LEGAL actions initiated. If incorrect, contact us at support@creditlab.in'
    ],
    'were_to_pay' => [
        'id' => '1407175024235958869',
        'template' => "Alert! Hey Creditlab.in user, You were to pay Rs %s for your loan. It's overdue! Pay now to avoid CIBIL & recovery complications : %s"
    ],
    'due_date_missed' => [
        'id' => '1407175108833441096',
        'template' => 'Alert ! ! Your Creditlab.in loan DUE DATE is Crossed. Close now to avoid Reminder calls, EXTRA PENALTY & Late Payment reporting to CIBIL : %s'
    ],
    'enach_reminder' => [
        'id' => '1407175015994490488',
        'template' => 'Hi ! Your Creditlab.in loan of Rs. %s will auto-debit on %s. Ensure sufficient balance to avoid chq bounce & legal action under Section 138 N.I. Act'
    ],
    'enach_will_not_happen' => [
        'id' => '14071750161538869',
        'template' => 'Repay your Creditlab.in loan directly through the dashboard %s .If you repay now before any further extension/default, auto-debit will not occur.'
    ],
    'autodebit_bounce' => [
        'id' => '1407175016580415506',
        'template' => 'Auto-debit of Creditlab.in loan of Rs. %s got bounced due to insufficient funds. Close it now %s to avoid further debits/bounce charges & legal action'
    ],
    'commitment_day_reminder' => [
        'id' => '1407175016237946657',
        'template' => 'As per the commitment given to your Relationship Manager, we urge you to repay today the due amount of Rs %s through this link: %s -Creditlab'
    ],
    'commit_to_pay_reminder' => [
        'id' => '1407175017002651513',
        'template' => 'Reminder: You had committed to pay Rs %s to your Creditlab Account Manager. Pay & Reapply today immediately: %s'
    ],
    'salary_date_reminder' => [
        'id' => '1407175016247659901',
        'template' => 'Dear %s, you must have received salary. Repay your Creditlab.in loan now. Failure leads to Penalty & reduce CIBIL/Experian/CRIF/EQUIFAX scores'
    ],
    'limit_increase' => [
        'id' => '1407175198059581991',
        'template' => 'Dear Creditlab.in customer, your limit has been updated to Rs%s. Please log in to your account to withdraw: %s'
    ]
];

// =============================================================================
// == CORE SCRIPT LOGIC ==
// =============================================================================
try {
    logMessage("Starting complete automated SMS process - IST: " . date('Y-m-d H:i:s'));
    loadSentLog(); // Load the sent log at the beginning
    
    // Array to track monitoring SMS sent during this specific script execution
    $monitoring_sms_sent_this_run = [];

    function sendSMS($mobile, $message, $template_id, $sender = "CREDLB"){
        $url = "https://sms.k7marketinghub.com/app/smsapi/index.php?key=2683C705E7CB39&campaign=16613&routeid=30&type=text&contacts=$mobile&senderid=$sender&msg=".urlencode($message)."&template_id=$template_id&pe_id=1401337620000065797";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            logMessage("SMS Error to $mobile with Template $template_id: $error");
            return false;
        } else {
            logMessage("SMS Sent to $mobile: HTTP $httpCode - Template: $template_id - Response: $response");
            return true;
        }
    }
    
    function sendSMSDual($primary_mobile, $alt_mobile, $message, $template_id, &$monitoring_sms_sent_this_run, $sender = "CREDLB"){
        $sent_count = 0;
        $error_count = 0;
    
        $numbers_to_send = [];
        if (!empty($primary_mobile) && strlen($primary_mobile) >= 10) {
            $numbers_to_send[$primary_mobile] = true; // Use keys for uniqueness
        }
        if (!empty($alt_mobile) && strlen($alt_mobile) >= 10) {
            $numbers_to_send[$alt_mobile] = true;
        }
        
        $unique_numbers = array_keys($numbers_to_send);
    
        foreach ($unique_numbers as $number) {
            if (sendSMS($number, $message, $template_id, $sender)) {
                $sent_count++;
            } else {
                $error_count++;
            }
        }
    
        // Check if a monitoring SMS for this template has NOT been sent during this script run
        if ($sent_count > 0 && !isset($monitoring_sms_sent_this_run[$template_id])) {
            $monitoring_number = '8328350247';
            if (!in_array($monitoring_number, $unique_numbers)) { // Don't send if already sent to this number
                logMessage("Sending FIRST monitoring copy for template $template_id to $monitoring_number.");
                sendSMS($monitoring_number, $message, $template_id, $sender);
            }
            // Mark this template as sent for this run to prevent duplicates
            $monitoring_sms_sent_this_run[$template_id] = true;
        }
    
        return ['sent' => $sent_count, 'errors' => $error_count];
    }
    
    // Check for a manual time override for testing purposes
    if (php_sapi_name() == "cli" && isset($argv[1])) {
        parse_str($argv[1], $_GET);
    }

    if (isset($_GET['time']) && preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $_GET['time'])) {
        $current_time = $_GET['time'];
        logMessage("MANUAL TIME OVERRIDE: Using specified time: $current_time");
    } else {
        $current_time = date('H:i');
        logMessage("Using current system time: $current_time");
    }

    $current_day_of_month = (int)date('j');
    
    // Daily data cleanup task
    if($current_time >= "00:00" && $current_time < "00:05"){
        logMessage("Running daily data cleanup for '0000-00-00' dates.");
        towquery("UPDATE `loan` SET `cleard_date`=NULL WHERE `cleard_date` = '0000-00-00'");
    }

    // Extended date limit to include more loans (180 days instead of 120)
    $date_limit = date('Y-m-d H:i:s', strtotime("-180 days"));
    
$loan_query_sql = "SELECT 
                          user.id as user_id, user.name as user_name, user.mobile as user_mobile, 
                           user.altmobile as user_altmobile, user.salary_date, user.loan_limit, user.limit_inc,
                          loan.lid, loan.is_emi, loan_apply.amount AS processed_amount, loan.processed_date, 
                           loan.total_amount, loan.service_charge, loan.penality_charge, loan.advance_amount,
                           loan_apply.days, loan_apply.apply_date
                       FROM loan
                       INNER JOIN user ON loan.uid = user.id
                       INNER JOIN loan_apply ON loan.lid = loan_apply.id
                       WHERE 
                           (loan.status_log = 'account manager' OR loan.status_log = 'recovery officer') AND 
                           (loan_apply.status = 'account manager' OR loan_apply.status = 'recovery officer') AND 
                           (loan.action != 'cleared' OR loan.action IS NULL) AND
                           loan.processed_date > '{$date_limit}'";
                           
    $loan_query = towquery($loan_query_sql);
    
    $sms_sent = 0;
    $errors = 0;
    $total_loans = $loan_query ? townum($loan_query) : 0;
    
    logMessage("Processing $total_loans loans for SMS");

    if ($total_loans > 0) {
        while($loan_data = towfetch($loan_query)){
            $user_lid = $loan_data['lid'];
            $first_name = explode(' ', $loan_data['user_name'])[0];
            $primary_mobile = $loan_data['user_mobile'];
            $alt_mobile = $loan_data['user_altmobile'];
            // --- FIX: Cast amount fields from TEXT/string to float/int ---
            $processed_amount = (float)$loan_data['processed_amount'];
            $total_amount = (float)$loan_data['total_amount'] + (float)$loan_data['service_charge'] + (float)$loan_data['penality_charge'];
            $outstanding_amount = $total_amount - (float)$loan_data['advance_amount'];
            $loan_limit = (int)$loan_data['loan_limit'];
            $limit_inc_status = (int)$loan_data['limit_inc'];
            // --- END FIX ---
            $salary_date = (int)$loan_data['salary_date'];
            
            // Calculate tday (days since processed_date)
            $tday = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',strtotime($loan_data['processed_date']." -1 day")))) / (60 * 60 * 24));
            
            // Get days from loan_apply
            $loan_days_raw = isset($loan_data['days']) ? (int)$loan_data['days'] : 30;
            $is_emi = isset($loan_data['is_emi']) ? (int)$loan_data['is_emi'] : (($loan_days_raw <= 30) ? 1 : 0);
            $loan_days = ($is_emi === 1) ? 30 : $loan_days_raw;
            
            // loan_days is now INCLUSIVE (applied date = day 1, due date = day N)
            // For DPD calculation, we need exclusive days (actual days between dates)
            // Example: 39 days inclusive = 38 days exclusive (Nov 27 to Jan 4)
            $loan_days_exclusive = $loan_days - 1;
            
            // Calculate DPD (Days Past Due) = tday - loan_days_exclusive
            // If tday < loan_days_exclusive: we're before due date (DPD is negative)
            // If tday >= loan_days_exclusive: we're past due date (DPD is positive)
            $dpd = $tday - $loan_days_exclusive;
            
            // Calculate days to due date (for reminders before due date)
            if ($tday < $loan_days_exclusive) {
                $days_to_due = $loan_days_exclusive - $tday; // Days remaining before due date
            } else {
                $days_to_due = 0; // Due date has passed
            }
            
            $url_link = 'creditlab.in/user';

            // --- SCHEDULED SMS CHECKS ---
            
            // 1. CIBIL DROP ALERT (Window: 11:45 - 11:49 AM) - Use DPD logic: 4-0 days before due date, or on due date (excludes 5 days before which has separate reminder)
            if ($current_time >= "11:45" && $current_time < "11:50") {
                logMessage("LID $user_lid: Checking CIBIL DROP ALERT. Time condition met. DPD: $dpd, Days to Due: $days_to_due");
                if (($days_to_due >= 0 && $days_to_due <= 4) || $dpd == 0) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd, Days to Due: $days_to_due).");
                    if (!hasBeenSentToday($user_lid, 'cibil_drop_alert')) {
                        logMessage("LID $user_lid: 'cibil_drop_alert' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['cibil_drop_alert'];
                        $message = sprintf($tpl['template'], $first_name, $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'cibil_drop_alert'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'cibil_drop_alert' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for CIBIL DROP ALERT not met (DPD: $dpd, Days to Due: $days_to_due)."); }
            }

            // 2. DPD 1-5 (Windows: 08:30-08:34 AM, 16:35-16:39 PM) - Use actual DPD: 1-5 days past due date
            if (($current_time >= "08:30" && $current_time < "08:35") || ($current_time >= "16:35" && $current_time < "16:40")) {
                logMessage("LID $user_lid: Checking DPD 1-5. Time condition met. DPD: $dpd");
                if ($dpd >= 1 && $dpd <= 5) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd).");
                    if (!hasBeenSentToday($user_lid, 'dpd_1_5')) {
                        logMessage("LID $user_lid: 'dpd_1_5' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['dpd_1_5'];
                        $message = sprintf($tpl['template'], $first_name, $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'dpd_1_5'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'dpd_1_5' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for DPD 1-5 not met (DPD: $dpd)."); }
            }
            
            // 3. DPD 6-10 (Windows: 08:30-08:34 AM, 18:00-18:04 PM) - Use actual DPD: 6-10 days past due date
            if (($current_time >= "08:30" && $current_time < "08:35") || ($current_time >= "18:00" && $current_time < "18:05")) {
                logMessage("LID $user_lid: Checking DPD 6-10. Time condition met. DPD: $dpd");
                if ($dpd >= 6 && $dpd <= 10) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd).");
                    if (!hasBeenSentToday($user_lid, 'dpd_6_10')) {
                        logMessage("LID $user_lid: 'dpd_6_10' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['dpd_6_10'];
                        $message = sprintf($tpl['template'], $first_name, $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'dpd_6_10'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'dpd_6_10' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for DPD 6-10 not met (DPD: $dpd)."); }
            }
            
            // 4. DPD 11-15 (Windows: 08:00-08:04 AM, 11:45-11:49 AM, 18:35-18:39 PM) - Use actual DPD: 11-15 days past due date
            if (($current_time >= "08:00" && $current_time < "08:05") || ($current_time >= "11:45" && $current_time < "11:50") || ($current_time >= "18:35" && $current_time < "18:40")) {
                logMessage("LID $user_lid: Checking DPD 11-15. Time condition met. DPD: $dpd");
                if ($dpd >= 10 && $dpd <= 15) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd).");
                    if (!hasBeenSentToday($user_lid, 'dpd_11_15')) {
                        logMessage("LID $user_lid: 'dpd_11_15' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['dpd_11_15'];
                        $message = sprintf($tpl['template'], $first_name);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'dpd_11_15'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'dpd_11_15' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for DPD 11-15 not met (DPD: $dpd)."); }
            }

            // 5. 5 Days Before Due Date Reminder (Windows: 10:00-10:04 AM, 17:00-17:04 PM) - Specific reminder 5 days before due date
            if (($current_time >= "10:00" && $current_time < "10:05") || ($current_time >= "17:00" && $current_time < "17:05")) {
                logMessage("LID $user_lid: Checking 5 Days Before Reminder. Time condition met. DPD: $dpd, Days to Due: $days_to_due");
                if ($days_to_due == 5) {
                    logMessage("LID $user_lid: Day condition met (Days to Due: $days_to_due = 5 days before).");
                    if (!hasBeenSentToday($user_lid, '5_days_before_reminder')) {
                        logMessage("LID $user_lid: '5_days_before_reminder' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['initial_reminder'];
                        $message = sprintf($tpl['template'], 5);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, '5_days_before_reminder'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent '5_days_before_reminder' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for 5 Days Before Reminder not met (Days to Due: $days_to_due)."); }
            }

            // 6. Initial Reminder (Windows: 09:00-09:04 AM, 16:30-16:34 PM) - Use days to due date: 4-0 days before due date
            if (($current_time >= "09:00" && $current_time < "09:05") || ($current_time >= "16:30" && $current_time < "16:35")) {
                logMessage("LID $user_lid: Checking Initial Reminder. Time condition met. DPD: $dpd, Days to Due: $days_to_due");
                if ($days_to_due >= 0 && $days_to_due <= 4) {
                    logMessage("LID $user_lid: Day condition met (Days to Due: $days_to_due).");
                    if (!hasBeenSentToday($user_lid, 'initial_reminder')) {
                        logMessage("LID $user_lid: 'initial_reminder' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['initial_reminder'];
                        $message = sprintf($tpl['template'], $days_to_due > 0 ? $days_to_due : 0);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'initial_reminder'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'initial_reminder' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Initial Reminder not met (Days to Due: $days_to_due)."); }
            }

            // 7. Pre-close Reminder (Windows: 08:00-08:04 AM, 16:00-16:04 PM)
            // Trigger based on days before due date (days_to_due), not days since processed
            if (($current_time >= "08:00" && $current_time < "08:05") || ($current_time >= "16:00" && $current_time < "16:05")) {
                logMessage("LID $user_lid: Checking Pre-close Reminder. Time condition met. Days to Due: $days_to_due, tday: $tday");
                // Use days_to_due if loan is not past due, otherwise use tday for past due loans
                $preclose_day = ($days_to_due > 0) ? $days_to_due : $tday;
                if (in_array($preclose_day, [10, 15, 20, 25])) {
                    logMessage("LID $user_lid: Day condition met (Pre-close Day: $preclose_day, Days to Due: $days_to_due, tday: $tday).");
                    if (!hasBeenSentToday($user_lid, 'preclose_day_'.$preclose_day)) { // Unique key per day
                        logMessage("LID $user_lid: 'preclose_day_$preclose_day' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['preclose'];
                        $interest_amount = 0;
                        switch ($preclose_day) {
                            case 10: $interest_amount = $processed_amount * 0.02; break;
                            case 15: $interest_amount = $processed_amount * 0.015; break;
                            case 20: $interest_amount = $processed_amount * 0.01; break;
                            case 25: $interest_amount = $processed_amount * 0.005; break;
                        }
                        $message = sprintf($tpl['template'], $preclose_day, number_format($interest_amount, 2), $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'preclose_day_'.$preclose_day); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'preclose_day_$preclose_day' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Pre-close Reminder not met (Pre-close Day: $preclose_day, Days to Due: $days_to_due, tday: $tday)."); }
            }

            // 8. Salary Day Reminder (Active Loans) (Windows: 14:00-14:04, 18:30-18:34, 20:00-20:04) - Use days_to_due: before due date
            if (($current_time >= "14:00" && $current_time < "14:05") || ($current_time >= "18:30" && $current_time < "18:35") || ($current_time >= "20:00" && $current_time < "20:05")) {
                logMessage("LID $user_lid: Checking Salary Day (Active). Time condition met. Days to Due: $days_to_due");
                if ($days_to_due > 0 && $salary_date == $current_day_of_month) {
                    logMessage("LID $user_lid: Day/Salary condition met (Days to Due: $days_to_due, Salary Day: $salary_date).");
                     if (!hasBeenSentToday($user_lid, 'salary_day_active')) {
                        logMessage("LID $user_lid: 'salary_day_active' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['salary_day'];
                        $message = sprintf($tpl['template'], $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'salary_day_active'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'salary_day_active' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day/Salary condition for Salary Day (Active) not met (Days to Due: $days_to_due, Salary Day: $salary_date)."); }
            }

            // 9. 15-30 Days Past Due Reminder (Window: 15:00-15:04 PM) - Use DPD: 15-30 days past due date (Repayment Day + 15 to + 30)
            if ($current_time >= "15:00" && $current_time < "15:05") {
                logMessage("LID $user_lid: Checking 15-30 Days Past Due Reminder. Time condition met. DPD: $dpd");
                if ($dpd >= 15 && $dpd <= 30) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd, Repayment Day + $dpd).");
                    if (!hasBeenSentToday($user_lid, '45th_day_reminder')) {
                        logMessage("LID $user_lid: '45th_day_reminder' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['45th_day_reminder'];
                        $message = sprintf($tpl['template'], $first_name);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, '45th_day_reminder'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent '45th_day_reminder' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for 15-30 Days Past Due Reminder not met (DPD: $dpd)."); }
            }

            // 10. Field Recovery (Window: 14:35-14:39 PM) - Use DPD: 35-40 days past due date (Repayment Day + 35 to + 40)
            if ($current_time >= "14:35" && $current_time < "14:40") {
                logMessage("LID $user_lid: Checking Field Recovery. Time condition met. DPD: $dpd");
                if ($dpd >= 35 && $dpd <= 40) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd, Repayment Day + $dpd).");
                    if (!hasBeenSentToday($user_lid, 'field_recovery')) {
                        logMessage("LID $user_lid: 'field_recovery' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['field_recovery'];
                        $message = sprintf($tpl['template'], $first_name, $user_lid, 'support@creditlab.in');
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'field_recovery'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'field_recovery' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Field Recovery not met (DPD: $dpd)."); }
            }
            
            // 11. Legal Notice (Window: 19:35-19:39 PM) - Use DPD: 16-30 days past due date (Repayment Day + 16 to + 30)
            if ($current_time >= "19:35" && $current_time < "19:40") {
                logMessage("LID $user_lid: Checking Legal Notice. Time condition met. DPD: $dpd");
                if ($dpd >= 16 && $dpd <= 30) {
                     logMessage("LID $user_lid: Day condition met (DPD: $dpd, Repayment Day + $dpd).");
                    if (!hasBeenSentToday($user_lid, 'legal_notice')) {
                        logMessage("LID $user_lid: 'legal_notice' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['legal_notice'];
                        $message = $tpl['template'];
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'legal_notice'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'legal_notice' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Legal Notice not met (DPD: $dpd)."); }
            }

            // 12. Final Alert (Window: 14:35-14:39 PM) - Use DPD: 16-30 days past due date (Repayment Day + 16 to + 30)
            if ($current_time >= "14:35" && $current_time < "14:40") {
                logMessage("LID $user_lid: Checking Final Alert. Time condition met. DPD: $dpd");
                if ($dpd >= 16 && $dpd <= 30) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd, Repayment Day + $dpd).");
                    if (!hasBeenSentToday($user_lid, 'final_alert')) {
                         logMessage("LID $user_lid: 'final_alert' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['final_alert'];
                        $message = sprintf($tpl['template'], $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'final_alert'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'final_alert' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Final Alert not met (DPD: $dpd)."); }
            }

            // 13. CIBIL Dip (Window: 15:00-15:04 PM) - Use DPD: 15-30 days past due date (Repayment Day + 15 to + 30)
            if ($current_time >= "15:00" && $current_time < "15:05") {
                logMessage("LID $user_lid: Checking CIBIL Dip. Time condition met. DPD: $dpd");
                if ($dpd >= 15 && $dpd <= 30) {
                     logMessage("LID $user_lid: Day condition met (DPD: $dpd, Repayment Day + $dpd).");
                    if (!hasBeenSentToday($user_lid, 'cibil_dip')) {
                        logMessage("LID $user_lid: 'cibil_dip' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['cibil_dip'];
                        $message = sprintf($tpl['template'], $first_name, $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'cibil_dip'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'cibil_dip' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for CIBIL Dip not met (DPD: $dpd)."); }
            }

            // 14. Legal Suit (Window: 16:00-16:04 PM) - Use DPD: 31-44 days past due date (Repayment Day + 31 to + 44)
            if ($current_time >= "16:00" && $current_time < "16:05") {
                logMessage("LID $user_lid: Checking Legal Suit. Time condition met. DPD: $dpd");
                if ($dpd >= 31 && $dpd <= 44) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd, Repayment Day + $dpd).");
                    if (!hasBeenSentToday($user_lid, 'legal_suit')) {
                        logMessage("LID $user_lid: 'legal_suit' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['legal_suit'];
                        $message = sprintf($tpl['template'], $first_name, $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'legal_suit'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'legal_suit' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Legal Suit not met (DPD: $dpd)."); }
            }
            
            // 15. Written Off (Window: 19:30-19:34 PM) - Use DPD: 46, 59, 69, 89 days past due date (Repayment Day + 46, + 59, + 69, + 89)
            if ($current_time >= "19:30" && $current_time < "19:35") {
                logMessage("LID $user_lid: Checking Written Off. Time condition met. DPD: $dpd");
                // Convert fixed days to DPD: 76, 89, 99, 119 → DPD 46, 59, 69, 89 (for 30-day loans)
                // But make it dynamic: Written Off at DPD 46, 59, 69, 89
                if (in_array($dpd, [46, 59, 69, 89])) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd, Repayment Day + $dpd).");
                    if (!hasBeenSentToday($user_lid, 'written_off')) {
                        logMessage("LID $user_lid: 'written_off' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['written_off'];
                        $message = sprintf($tpl['template'], $first_name);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'written_off'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'written_off' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Written Off not met (DPD: $dpd)."); }
            }

            // 16. Waive Off (Window: 14:10-14:14 PM) - Use DPD: 46, 50, 59 days past due date (Repayment Day + 46, + 50, + 59)
            if ($current_time >= "14:10" && $current_time < "14:15") {
                 logMessage("LID $user_lid: Checking Waive Off. Time condition met. DPD: $dpd");
                // Convert fixed days to DPD: 76, 80, 89 → DPD 46, 50, 59 (for 30-day loans)
                // But make it dynamic: Waive Off at DPD 46, 50, 59
                if (in_array($dpd, [46, 50, 59])) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd, Repayment Day + $dpd).");
                    if (!hasBeenSentToday($user_lid, 'waive_off')) {
                        logMessage("LID $user_lid: 'waive_off' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['waive_off'];
                        $message = $tpl['template'];
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'waive_off'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'waive_off' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Waive Off not met (DPD: $dpd)."); }
            }

            // 17. Attention (Window: 14:45-14:49 PM) - Use DPD: 15-30 days past due date (Repayment Day + 15 to + 30)
            if ($current_time >= "14:45" && $current_time < "14:50") {
                logMessage("LID $user_lid: Checking Attention. Time condition met. DPD: $dpd");
                if ($dpd >= 15 && $dpd <= 30) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd, Repayment Day + $dpd).");
                    if (!hasBeenSentToday($user_lid, 'attention')) {
                        logMessage("LID $user_lid: 'attention' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['attention'];
                        $message = sprintf($tpl['template'], $first_name);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'attention'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'attention' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Attention not met (DPD: $dpd)."); }
            }

            // 18. Were to Pay (Window: 13:30-13:34 PM) - Use DPD: 6-15 days past due date (Repayment Day + 6 to + 15)
            if ($current_time >= "13:30" && $current_time < "13:35") {
                logMessage("LID $user_lid: Checking Were to Pay. Time condition met. DPD: $dpd");
                if ($dpd >= 6 && $dpd <= 15) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd, Repayment Day + $dpd).");
                    if (!hasBeenSentToday($user_lid, 'were_to_pay')) {
                        logMessage("LID $user_lid: 'were_to_pay' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['were_to_pay'];
                        $message = sprintf($tpl['template'], number_format($outstanding_amount, 2), $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'were_to_pay'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'were_to_pay' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Were to Pay not met (DPD: $dpd)."); }
            }

            // 19. Due Date Missed (Window: 13:45-13:49 PM) - Use DPD: 1-5 days past due date
            if ($current_time >= "13:45" && $current_time < "13:50") {
                logMessage("LID $user_lid: Checking Due Date Missed. Time condition met. DPD: $dpd");
                if ($dpd >= 1 && $dpd <= 5) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd).");
                     if (!hasBeenSentToday($user_lid, 'due_date_missed')) {
                        logMessage("LID $user_lid: 'due_date_missed' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['due_date_missed'];
                        $message = sprintf($tpl['template'], $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'due_date_missed'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'due_date_missed' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for Due Date Missed not met (DPD: $dpd)."); }
            }
            
            // 20. E-NACH Will Not Happen (Window: 14:00-14:04 PM) - Use DPD: 0-1 days (on due date or day after)
            if ($current_time >= "14:00" && $current_time < "14:05") {
                logMessage("LID $user_lid: Checking E-NACH Will Not Happen. Time condition met. DPD: $dpd");
                if ($dpd == 0 || $dpd == 1) {
                    logMessage("LID $user_lid: Day condition met (DPD: $dpd).");
                    if (!hasBeenSentToday($user_lid, 'enach_will_not_happen')) {
                        logMessage("LID $user_lid: 'enach_will_not_happen' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['enach_will_not_happen'];
                        $message = sprintf($tpl['template'], $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'enach_will_not_happen'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'enach_will_not_happen' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day condition for E-NACH Will Not Happen not met (Day: $tday)."); }
            }

            // 21. Salary Date Reminder for OVERDUE loans (Window: 16:00-16:04 PM) - Use DPD: on or after due date (DPD >= 0)
            if ($current_time >= "16:00" && $current_time < "16:05") {
                logMessage("LID $user_lid: Checking Salary Day (Overdue). Time condition met. DPD: $dpd");
                if ($dpd >= 0 && $salary_date == $current_day_of_month) {
                    logMessage("LID $user_lid: Day/Salary condition met (DPD: $dpd, Salary Day: $salary_date).");
                    if (!hasBeenSentToday($user_lid, 'salary_date_overdue')) {
                        logMessage("LID $user_lid: 'salary_date_overdue' not sent today. Proceeding to send.");
                        $tpl = $sms_templates['salary_date_reminder'];
                        $message = sprintf($tpl['template'], $first_name);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, 'salary_date_overdue'); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'salary_date_overdue' today. Skipping."); }
                } else { logMessage("LID $user_lid: Day/Salary condition for Salary Day (Overdue) not met (DPD: $dpd, Salary Day: $salary_date)."); }
            }

            // 22. Limit Increase (Windows: 08:00-08:04, 12:50-12:54, 16:00-16:04)
            if (($current_time >= "08:00" && $current_time < "08:05") || ($current_time >= "12:50" && $current_time < "12:55") || ($current_time >= "16:00" && $current_time < "16:05")) {
                logMessage("LID $user_lid: Checking Limit Increase. Time condition met.");
                // Send SMS if user's loan limit is higher than their processed amount (regardless of limit_inc status)
                // The hasBeenSentToday check prevents duplicates
                if ($loan_limit > $processed_amount) {
                    logMessage("LID $user_lid: Loan limit condition met (limit: $loan_limit > processed: $processed_amount).");
                    // Determine which time window we're in for unique tracking
                    $time_slot = '';
                    if ($current_time >= "08:00" && $current_time < "08:05") {
                        $time_slot = '08am';
                    } elseif ($current_time >= "12:50" && $current_time < "12:55") {
                        $time_slot = '1250pm';
                    } elseif ($current_time >= "16:00" && $current_time < "16:05") {
                        $time_slot = '4pm';
                    }
                    $unique_key = 'limit_increase_' . $time_slot;
                    
                    if (!hasBeenSentToday($user_lid, $unique_key)) {
                        logMessage("LID $user_lid: 'limit_increase' not sent for time slot $time_slot. Proceeding to send.");
                        $tpl = $sms_templates['limit_increase'];
                        $message = sprintf($tpl['template'], number_format($loan_limit), $url_link);
                        $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id'], $monitoring_sms_sent_this_run);
                        if ($result['sent'] > 0) { markAsSent($user_lid, $unique_key); }
                        $sms_sent += $result['sent']; $errors += $result['errors'];
                    } else { logMessage("LID $user_lid: Already sent 'limit_increase' for time slot $time_slot today. Skipping."); }
                } else { logMessage("LID $user_lid: Loan limit condition not met (limit: $loan_limit <= processed: $processed_amount)."); }
            }

        }
    }
    
    logMessage("Complete SMS process finished. Total loans processed: $total_loans, SMS sent: $sms_sent, Errors: $errors");
    
} catch (Exception $e) {
    logMessage("Critical Error: " . $e->getMessage());
} finally {
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
    logMessage("Script execution finished");
}
?>


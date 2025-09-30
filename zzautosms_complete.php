<?php
/**
 * Complete CreditLab Automated SMS System - DLT COMPLIANT
 * Uses EXACT template text from the approved templates list.
 * Run this via cron job for automated SMS.
 */

// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Set time limit and memory
set_time_limit(300); // 5 minutes
ini_set("memory_limit", "256M");

// Log file (daily rotation under logs/)
$log_dir = "logs";
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}
$current_log_date = date('Y-m-d');
$log_file = $log_dir . "/sms_cron_" . $current_log_date . ".log";

// Log function
function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Include database
include_once 'db.php';

// Check if script is already running
$lock_file = "sms_cron.lock";
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    if (time() - $lock_time < 300) { // Lock valid for 5 minutes
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
        'id' => '1407175016153886869',
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
    
    function sendSMSDual($primary_mobile, $alt_mobile, $message, $template_id, $sender = "CREDLB"){
        $current_hour = (int)date('H');
        $sent_count = 0;
        $error_count = 0;

        $target_mobile = null;

        // Morning/Day (before 6 PM): Send to primary mobile
        if ($current_hour < 18) {
            if (!empty($primary_mobile) && strlen($primary_mobile) >= 10) {
                $target_mobile = $primary_mobile;
            }
        } 
        // Evening (6 PM onwards): Send to alternate mobile if available and different, otherwise fallback to primary
        else {
            if (!empty($alt_mobile) && strlen($alt_mobile) >= 10 && $alt_mobile != $primary_mobile) {
                $target_mobile = $alt_mobile;
            } elseif (!empty($primary_mobile) && strlen($primary_mobile) >= 10) {
                $target_mobile = $primary_mobile;
            }
        }

        if ($target_mobile) {
            if (sendSMS($target_mobile, $message, $template_id, $sender)) {
                $sent_count++;
            } else {
                $error_count++;
            }
        }

        return ['sent' => $sent_count, 'errors' => $error_count];
    }
    
    // Get current IST time details
    $current_time = date('H:i');
    $current_date = date('Y-m-d');
    
    logMessage("Current IST Time: $current_time");

    // Fetch all active loans from the last 120 days to cover all conditions
    $date_limit = date('Y-m-d H:i:s', strtotime("-120 days"));
    
    // --- SQL FIX: JOIN with loan_apply table and filter by loan_apply.status ---
    $loan_query_sql = "SELECT 
                           user.id as user_id, user.name as user_name, user.mobile as user_mobile, 
                           user.altmobile as user_altmobile, user.salary_date, user.loan_limit,
                           loan.lid, loan.processed_date, loan.processed_amount, 
                           loan.total_amount, loan.advance_amount
                       FROM loan
                       INNER JOIN user ON loan.uid = user.id
                       INNER JOIN loan_apply ON loan.lid = loan_apply.id
                       WHERE 
                           loan_apply.status = 'account manager' AND 
                           loan_apply.status_date > '{$date_limit}'";
                           
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
            $processed_amount = $loan_data['processed_amount'];
            $outstanding_amount = $loan_data['total_amount'] - $loan_data['advance_amount'];
            $salary_date = $loan_data['salary_date'];
            $loan_limit = $loan_data['loan_limit'];

            // Calculate days since loan was processed
            $tday = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',strtotime($loan_data['processed_date']." -1 day")))) / (60 * 60 * 24));
            
            $url_link = 'creditlab.in/user'; // Common URL variable

            // --- SCHEDULED SMS CHECKS ---

            // 1. CIBIL DROP ALERT (11:45 AM)
            if ($current_time == "11:45" && (($tday >= 25 && $tday <= 30) || $tday == 15 || $tday == 20)) {
                $tpl = $sms_templates['cibil_drop_alert'];
                $message = sprintf($tpl['template'], $first_name, $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 2. DPD 1-5 (8:30 AM, 4:35 PM)
            if (($current_time == "08:30" || $current_time == "16:35") && ($tday >= 31 && $tday <= 35)) {
                $tpl = $sms_templates['dpd_1_5'];
                $message = sprintf($tpl['template'], $first_name, $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 3. DPD 6-10 (8:30 AM, 6:00 PM)
            if (($current_time == "08:30" || $current_time == "18:00") && ($tday >= 36 && $tday <= 40)) {
                $tpl = $sms_templates['dpd_6_10'];
                $message = sprintf($tpl['template'], $first_name, $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 4. DPD 11-15 (8:00 AM, 11:45 AM, 6:35 PM)
            if (($current_time == "08:00" || $current_time == "11:45" || $current_time == "18:35") && ($tday >= 40 && $tday <= 45)) {
                $tpl = $sms_templates['dpd_11_15'];
                $message = sprintf($tpl['template'], $first_name);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 5. Initial Reminder (9:00 AM, 4:30 PM)
            if (($current_time == "09:00" || $current_time == "16:30") && ($tday >= 25 && $tday <= 30)) {
                $tpl = $sms_templates['initial_reminder'];
                $message = sprintf($tpl['template'], $tday);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 6. Pre-close Reminder (8:00 AM, 4:00 PM)
            if (($current_time == "08:00" || $current_time == "16:00") && in_array($tday, [10, 15, 20, 25])) {
                $tpl = $sms_templates['preclose'];
                $interest_amount = 0;
                switch ($tday) {
                    case 10: $interest_amount = $processed_amount * 0.02; break;
                    case 15: $interest_amount = $processed_amount * 0.015; break;
                    case 20: $interest_amount = $processed_amount * 0.01; break;
                    case 25: $interest_amount = $processed_amount * 0.005; break;
                }
                $message = sprintf($tpl['template'], $tday, number_format($interest_amount, 2), $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 7. Salary Day Reminder (2:00 PM, 6:30 PM, 8:00 PM) - This is for ACTIVE loans, not overdue ones
            if (($current_time == "14:00" || $current_time == "18:30" || $current_time == "20:00") && ($tday < 30) && ($salary_date == $current_date)) {
                $tpl = $sms_templates['salary_day'];
                $message = sprintf($tpl['template'], $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 8. 45th Day Reminder (3:00 PM)
            if ($current_time == "15:00" && ($tday >= 45 && $tday <= 60)) {
                $tpl = $sms_templates['45th_day_reminder'];
                $message = sprintf($tpl['template'], $first_name);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 9. Field Recovery (2:35 PM)
            if ($current_time == "14:35" && ($tday >= 65 && $tday <= 70)) {
                $tpl = $sms_templates['field_recovery'];
                $message = sprintf($tpl['template'], $first_name, $user_lid, 'support@creditlab.in');
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 10. Legal Notice (7:35 PM)
            if ($current_time == "19:35" && ($tday >= 46 && $tday <= 60)) {
                $tpl = $sms_templates['legal_notice'];
                $message = $tpl['template'];
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 11. Final Alert (2:35 PM)
            if ($current_time == "14:35" && ($tday >= 46 && $tday <= 60)) {
                $tpl = $sms_templates['final_alert'];
                $message = sprintf($tpl['template'], $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 12. CIBIL Dip (3:00 PM)
            if ($current_time == "15:00" && ($tday >= 45 && $tday <= 60)) {
                $tpl = $sms_templates['cibil_dip'];
                $message = sprintf($tpl['template'], $first_name, $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 13. Legal Suit (4:00 PM)
            if ($current_time == "16:00" && ($tday >= 61 && $tday <= 74)) {
                $tpl = $sms_templates['legal_suit'];
                $message = sprintf($tpl['template'], $first_name, $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 14. Written Off (7:30 PM)
            if ($current_time == "19:30" && in_array($tday, [76, 89, 99, 119])) {
                $tpl = $sms_templates['written_off'];
                $message = sprintf($tpl['template'], $first_name);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 15. Waive Off (2:10 PM)
            if ($current_time == "14:10" && in_array($tday, [76, 80, 89])) {
                $tpl = $sms_templates['waive_off'];
                $message = $tpl['template'];
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 16. Attention (2:45 PM)
            if ($current_time == "14:45" && ($tday >= 45 && $tday <= 60)) {
                $tpl = $sms_templates['attention'];
                $message = sprintf($tpl['template'], $first_name);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 17. Were to Pay (1:30 PM)
            if ($current_time == "13:30" && ($tday >= 36 && $tday <= 45)) {
                $tpl = $sms_templates['were_to_pay'];
                $message = sprintf($tpl['template'], number_format($outstanding_amount, 2), $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 18. Due Date Missed (1:45 PM)
            if ($current_time == "13:45" && ($tday >= 31 && $tday <= 35)) {
                $tpl = $sms_templates['due_date_missed'];
                $message = sprintf($tpl['template'], $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 19. E-NACH Will Not Happen (2:00 PM)
            if ($current_time == "14:00" && ($tday == 30 || $tday == 31)) {
                $tpl = $sms_templates['enach_will_not_happen'];
                $message = sprintf($tpl['template'], $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 20. Salary Date Reminder for OVERDUE loans (4:00 PM)
            if ($current_time == "16:00" && $tday >= 30 && $salary_date == $current_date) {
                $tpl = $sms_templates['salary_date_reminder'];
                $message = sprintf($tpl['template'], $first_name);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
            
            // 21. Limit Increase (8:00 AM, 12:50 PM, 4:00 PM)
            if (($current_time == "08:00" || $current_time == "12:50" || $current_time == "16:00") && $loan_limit > $processed_amount) {
                $tpl = $sms_templates['limit_increase'];
                $message = sprintf($tpl['template'], number_format($loan_limit, 2), $url_link);
                $result = sendSMSDual($primary_mobile, $alt_mobile, $message, $tpl['id']);
                $sms_sent += $result['sent']; $errors += $result['errors'];
            }
        }
    }
    
    logMessage("Complete SMS process finished. Total loans processed: $total_loans, SMS sent: $sms_sent, Errors: $errors");
    
} catch (Exception $e) {
    logMessage("Critical Error: " . $e->getMessage());
} finally {
    // Remove lock file
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
    logMessage("Script execution finished");
}
?>


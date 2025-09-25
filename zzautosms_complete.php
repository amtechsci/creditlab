<?php
/**
 * Complete CreditLab Automated SMS System - EXACT TEMPLATES
 * Uses EXACT template text from CSV file - no modifications
 * Run this via cron job for automated SMS
 */

// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Set time limit and memory
set_time_limit(300); // 5 minutes
ini_set("memory_limit", "256M");

// Log file
$log_file = "sms_cron_log.txt";
$log_date = date("Y-m-d H:i:s");

// Log function
function logMessage($message) {
    global $log_file, $log_date;
    $log_entry = "[$log_date] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Include database
include_once 'db.php';

// Check if script is already running
$lock_file = "sms_cron.lock";
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    if (time() - $lock_time < 300) { // 5 minutes
        logMessage("Script already running, exiting");
        exit;
    }
}

// Create lock file
file_put_contents($lock_file, time());

try {
    logMessage("Starting complete automated SMS process - IST: " . date('Y-m-d H:i:s'));
    
    $username="finwin";
    $password="kiran@100";
    $sender="CREDLB";

    function sendSMS($mobile, $message, $template_id, $sender = "CREDLB"){
        // Use the specific template ID for each SMS type
        $final_template_id = $template_id; // Use the actual template ID from CSV
        
        $url = "https://sms.k7marketinghub.com/app/smsapi/index.php?key=2683C705E7CB39&campaign=16613&routeid=30&type=text&contacts=$mobile&senderid=$sender&msg=".urlencode($message)."&template_id=$final_template_id&pe_id=1401337620000065797";
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        // Log SMS attempt
        if ($error) {
            logMessage("SMS Error to $mobile: $error");
            return false;
        } else {
            logMessage("SMS Sent to $mobile: HTTP $httpCode - Template: $final_template_id");
            return true;
        }
    }

    // Get current IST time
    $current_time = date('H:i');
    $current_date = date('Y-m-d');
    $current_hour = (int)date('H');
    $current_minute = (int)date('i');
    
    logMessage("Current IST Time: $current_time");

    // Get loans from last 90 days
    $today = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . " -90 day"));
    $newloanquery = towquery("SELECT uid,id FROM `loan_apply` WHERE `status`='account manager' AND status_date > '{$today}'");
    
    $seauserid = array();
    $i = 0;
    while($a = towfetch($newloanquery)){ 
        $seauserid[$i] = $a['id']; 
        $i++; 
    }
    $seauserid = array_unique($seauserid);
    
    $sms_sent = 0;
    $errors = 0;
    $total_loans = count($seauserid);
    
    logMessage("Processing $total_loans loans for SMS");

    foreach($seauserid as $value){
        $a = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi FROM user INNER JOIN loan ON loan.uid=user.id WHERE loan.lid=$value");
        
        if(townum($a) > 0){
            $b = towfetch($a);
            extract($b,EXTR_PREFIX_ALL,"user");
            $tday = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',strtotime($user_processed_date." -1 day")))) / (60 * 60 * 24));
            
            // Get first name from full name
            $first_name = explode(' ', $user_name)[0];
            
            // 1. CIBIL DROP ALERT (11:45 AM) - Days 25-30, 15th day, 20th day
            if(($current_time == "11:45" || $current_time == "11:46") && 
               (($tday >= 25 && $tday <= 30) || $tday == 15 || $tday == 20)){
                $mobile = $user_mobile;
                $template_id = "1407175283747333288";
                $message = "Act Now $first_name ! Your latest Creditlab loan is reported to 4 CIBIL bureaus. Pay on time to avoid CIBIL score drop : creditlab.in/user";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 2. DPD 1-5 (8:30 AM, 4:35 PM) - Days 31-35
            if(($current_time == "08:30" || $current_time == "16:35") && $tday >= 31 && $tday <= 35){
                $mobile = $user_mobile;
                $template_id = "1407175283203362638";
                $message = "URGENT $first_name ! Your Creditlab loan is OVERDUE. Pay immediately to avoid Penalty & severe CIBIL impact : creditlab.in/user";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 3. DPD 6-10 (8:30 AM, 6:00 PM) - Days 36-40
            if(($current_time == "08:30" || $current_time == "18:00") && $tday >= 36 && $tday <= 40){
                $mobile = $user_mobile;
                $template_id = "1407175283363256063";
                $message = "ATTENTION $first_name ! Your Creditlab loan is still OVERDUE. Recovery proceedings & CIBIL impact begin. Clear now to stop further action : creditlab.in/user";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 4. DPD 11-15 (8:00 AM, 11:45 AM, 6:35 PM) - Days 40-45
            if(($current_time == "08:00" || $current_time == "11:45" || $current_time == "18:35") && $tday >= 40 && $tday <= 45){
                $mobile = $user_mobile;
                $template_id = "1407175283390827183";
                $message = "FINAL WARNING $first_name ! Your Creditlab.in loan remains OVERDUE. Legal, RECOVERY & CIBIL DAMAGE imminent. Settle dues TODAY to avoid escalation.";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 5. Initial Reminder (9:00 AM, 4:30 PM) - Days 25-30
            if(($current_time == "09:00" || $current_time == "16:30") && $tday >= 25 && $tday <= 30){
                $mobile = $user_mobile;
                $template_id = "1407175016269681511";
                $message = "Dear Creditlab.in user, It's $tday day reminder to repay your loan before due date. Doing so will grow Trust Score & increase your CIBIL, Experian & CRIF scores.";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 6. Pre-close Reminder (8:00 AM, 4:00 PM) - Days 10, 15, 20, 25
            if(($current_time == "08:00" || $current_time == "16:00") && 
               ($tday == 10 || $tday == 15 || $tday == 20 || $tday == 25)){
                $mobile = $user_mobile;
                $template_id = "1407175024263728707";
                $interest_amount = $user_processed_amount * (0.02 - ($tday - 10) * 0.005);
                $message = "Dear creditlab.in user, It's been{$tday}days! Pre-close ur loan now, save $interest_amount interest & boost your CIBIL score. Act now : creditlab.in/user";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 7. Salary Day Reminder (2:00 PM, 6:30 PM, 8:00 PM) - On salary date
            if(($current_time == "14:00" || $current_time == "18:30" || $current_time == "20:00") && 
               $tday >= 30 && $user_salary_date == $current_date){
                $mobile = $user_mobile;
                $template_id = "1407175007069974553";
                $message = "Dear Creditlab.in user, Clear the loan on salary day & reapply. It aligns ur repayment with ur salary day for smooth cycle from next loan creditlab.in/user";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 8. 45th Day Reminder (3:00 PM) - Days 45-60
            if($current_time == "15:00" && $tday >= 45 && $tday <= 60){
                $mobile = $user_mobile;
                $template_id = "1407175016251351187";
                $message = "Dear $first_name, you have a pending loan with Creditlab.in, Repay it immediately. Failure leads to DEFAULT/OVERDUE in CIBIL & to Debt collection agency.";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 9. Field Recovery (2:35 PM) - Days 65-70
            if($current_time == "14:35" && $tday >= 65 && $tday <= 70){
                $mobile = $user_mobile;
                $template_id = "1407175016192466512";
                $message = "Dear $first_name, your Creditlab.in loan $user_lid, is now moved for Field Recovery. Our Field Recovery agent will visit your home & office addresses anytime in the next 6 to 10 days. Incase you choose to settle/close it before physical recovery visit, please contact support@creditlab.in";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 10. Legal Notice (7:35 PM) - Days 46-60
            if($current_time == "19:35" && $tday >= 46 && $tday <= 60){
                $mobile = $user_mobile;
                $template_id = "1407175016047912195";
                $message = "LEGAL NOTICE !!! It's a follow-up reminder to close your Overdued Creditlab.in loan immediately to avoid further Legal consequences.";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 11. Final Alert (2:35 PM) - Days 46-60
            if($current_time == "14:35" && $tday >= 46 && $tday <= 60){
                $mobile = $user_mobile;
                $template_id = "1407175016080435385";
                $message = "FINAL WARNING ! ! All Creditlab overdue loans will be reported as \"Default\" to CIBIL/CRIF/EXPERIAN/CRIF. Clear now creditlab.in/user";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 12. CIBIL Dip (3:00 PM) - Days 45-60
            if($current_time == "15:00" && $tday >= 45 && $tday <= 60){
                $mobile = $user_mobile;
                $template_id = "1407175267110690531";
                $message = "Hello $first_name, your creditlab.in loan is OVERDUE & reported as DEFAULTER to CIBIL. Your score will drop 50-100 points. Avoid damage, pay now: creditlab.in/user";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 13. Legal Suit (4:00 PM) - Days 61-74
            if($current_time == "16:00" && $tday >= 61 && $tday <= 74){
                $mobile = $user_mobile;
                $template_id = "1407175267151421703";
                $message = "FINAL WARNING : $first_name Creditlab.in loan in DEFAULT. Legal suit being filed. This is your last chance to settle & close loan creditlab.in/user";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 14. Written Off (7:30 PM) - Days 76, 89, 99, 119
            if($current_time == "19:30" && 
               ($tday == 76 || $tday == 89 || $tday == 99 || $tday == 119)){
                $mobile = $user_mobile;
                $template_id = "1407175016041686176";
                $message = "Hey $first_name, your creditlab.in loan reported to CIBIL as written-off & default which affects all future loans. Repay the Principal to cancel this.";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 15. Waive Off (2:10 PM) - Days 76, 80, 89
            if($current_time == "14:10" && 
               ($tday == 76 || $tday == 80 || $tday == 89)){
                $mobile = $user_mobile;
                $template_id = "1407175006859804198";
                $message = "Hey Creditlab.in user, 100% penalty waived off for a limited period ! Close your pending loan & remove your CIBIL defaulter tag. Contact support@creditlab.in";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 16. Attention (2:45 PM) - Days 45-60
            if($current_time == "14:45" && $tday >= 45 && $tday <= 60){
                $mobile = $user_mobile;
                $template_id = "1407175016862547934";
                $message = "Attention! $first_name, your creditlab.in loan is unpaid despite reminders. LEGAL actions initiated. If incorrect, contact us at support@creditlab.in";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 17. Were to Pay (1:30 PM) - Days 36-45
            if($current_time == "13:30" && $tday >= 36 && $tday <= 45){
                $mobile = $user_mobile;
                $template_id = "1407175024235958869";
                $outstanding = $user_total_amount - $user_advance_amount;
                $message = "Alert! Hey Creditlab.in user, You were to pay Rs $outstanding for your loan. It's overdue! Pay now to avoid CIBIL & recovery complications : creditlab.in/user";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
            
            // 18. Due Date Missed (1:45 PM) - Days 31-35
            if($current_time == "13:45" && $tday >= 31 && $tday <= 35){
                $mobile = $user_mobile;
                $template_id = "1407175108833441096";
                $message = "Alert ! ! Your Creditlab.in loan DUE DATE is Crossed. Close now to avoid Reminder calls, EXTRA PENALTY & Late Payment reporting to CIBIL : creditlab.in/user";
                if(sendSMS($mobile, $message, $template_id, $sender)) $sms_sent++; else $errors++;
            }
        }
    }
    
    logMessage("Complete SMS process finished. Total loans: $total_loans, SMS sent: $sms_sent, Errors: $errors");
    
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

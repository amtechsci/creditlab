<?php
/**
 * Optimized CreditLab Automated SMS Cron Script
 * Run this via cron job for automated SMS
 */

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
    logMessage("Starting automated SMS process");
    
    $username="finwin";
    $password="kiran@100";
    $sender="FNWINT";

    function send($sender,$mobile,$message,$template_id){
        $url="https://sms.k7marketinghub.com/app/smsapi/index.php?key=2683C705E7CB39&campaign=16613&routeid=30&type=text&contacts=$mobile&senderid=$sender&msg=".urlencode($message)."&template_id=$template_id&pe_id=1401337620000065797";
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
            logMessage("SMS Sent to $mobile: HTTP $httpCode");
            return true;
        }
    }

    // Get loans from last 64 days
    $today = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . " -64 day"));
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
            
            // Days 25-29: First EMI reminder
            if($tday >= 25 and $tday <= 29){
                if($user_femi == 1){
                    logMessage("Loan CLL{$user_lid}: First EMI already paid, skipping");
                } else {
                    $mobile = $user_mobile;
                    $template_id = '1107169460407304545';
                    $message = "Dear Creditlab.in user, It's {$tday} day reminder to repay your loan before CLL{$user_lid}. Doing so avoids Penalty & grow your CIBIL, Experian,Equifax & CRIF scores.";
                    
                    if(send($sender,$mobile,$message,$template_id)) {
                        $sms_sent++;
                    } else {
                        $errors++;
                    }
                }
            }
            
            // Days 31-39: EMI overdue warning
            if($tday >= 31 and $tday <= 39){
                if($user_femi == 1){
                    logMessage("Loan CLL{$user_lid}: First EMI already paid, skipping overdue warning");
                } else {
                    $mobile = $user_mobile;
                    $template_id = '1107169519485631845';
                    $message = "Your Creditlab.in loan EMI DATE is Crossed. Close now to avoid Reminder calls, EXTRA PENALTY & Late Payment reporting to CIBIL/EXPERIAN/CRIF & EQUIFAX";
                    
                    if(send($sender,$mobile,$message,$template_id)) {
                        $sms_sent++;
                    } else {
                        $errors++;
                    }
                }
            }
            
            // Days 60-64: Second EMI reminder
            if($tday >= 60 and $tday <= 64){
                if($user_semi == 1){
                    logMessage("Loan CLL{$user_lid}: Second EMI already paid, skipping");
                } else {
                    $mobile = $user_mobile;
                    $template_id = '1107169460407304545';
                    $message = "Dear Creditlab.in user, It's {$tday} day reminder to repay your loan before CLL{$user_lid}. Doing so avoids Penalty & grow your CIBIL, Experian,Equifax & CRIF scores.";
                    
                    if(send($sender,$mobile,$message,$template_id)) {
                        $sms_sent++;
                    } else {
                        $errors++;
                    }
                }
            }
            
            // Days 66-74: Second EMI overdue warning
            if($tday >= 66 and $tday <= 74){
                if($user_semi == 1){
                    logMessage("Loan CLL{$user_lid}: Second EMI already paid, skipping overdue warning");
                } else {
                    $mobile = $user_mobile;
                    $template_id = '1107169519485631845';
                    $message = "Your Creditlab.in loan EMI DATE is Crossed. Close now to avoid Reminder calls, EXTRA PENALTY & Late Payment reporting to CIBIL/EXPERIAN/CRIF & EQUIFAX";
                    
                    if(send($sender,$mobile,$message,$template_id)) {
                        $sms_sent++;
                    } else {
                        $errors++;
                    }
                }
            }
        }
    }
    
    logMessage("SMS process completed. Total loans: $total_loans, SMS sent: $sms_sent, Errors: $errors");
    
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

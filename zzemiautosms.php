<?php
    include 'db.php';
    $username="finwin";
$password="kiran@100";
$sender="FNWINT";

function send($sender,$mobile,$message,$template_id){
$url="https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey=6xuZOxICzUKo51xyQXjIqA&senderid=$sender&channel=2&DCS=0&flashsms=0&number=$mobile&text=".urlencode($message)."&route=1&EntityId=1101689540000061016&dlttemplateid=$template_id";
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
));
$response = curl_exec($curl);
curl_close($curl);
}

$li = "creditlab.in";
$today = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . " -64 day"));
$newloanquery =  towquery("SELECT uid,id FROM `loan_apply` WHERE `status`='account manager' AND status_date > '{$today}'");
$seauserid = array();
    $i = 0;
    while($a = towfetch($newloanquery)){ $seauserid[$i] = $a['id']; $i++; }
    $seauserid = array_unique($seauserid);
    $ii=1;
    foreach($seauserid as $value){
    $a = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi, loan.limit_inc_prompt FROM user INNER JOIN loan ON loan.uid=user.id WHERE loan.lid=$value");
    if(townum($a) > 0){
    $b = towfetch($a);
    extract($b,EXTR_PREFIX_ALL,"user");
    $tday = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',strtotime($user_processed_date." -1 day")))) / (60 * 60 * 24));
    if($tday >= 25 and $tday <= 29){
        $sal = ceil($user_salary*0.40);
        $mobile = $user_mobile;$template_id = '1107172226054965046';
        if($user_limit_inc_prompt == 0){
            if($user_auto_limit == 1){
                $loans = towfetch(towquery("SELECT COUNT(id) AS loans FROM `loan` WHERE uid=$user_id"))['loans'];
                if ($num % 2 != 0) {
                    $t = date('Y-m-d',strtotime($user_processed_date." +29 day"));
                    $sal = ceil($user_salary*0.40);
                    $loan_limit = towquery("SELECT MIN(amount) as amount FROM `limit_increment` WHERE amount > $user_loan_limit AND amount < $sal");
                    if(townum($loan_limit) > 0){
                        $nincamt = towfetch($loan_limit)['amount'];
                        if(!empty($nincamt) and $nincamt > 0){
                            towquery("UPDATE user SET loan_limit=$nincamt,`old_loan_limit`='$user_loan_limit',limit_inc=0 WHERE id=$user_id");
                            $message = "Congratulations {$user_name} !! Your Credit Limit is now increased to Rs {$nincamt}. Log in & accept the new limit  here {$li} -Creditlab";
                            towquery("UPDATE loan SET limit_inc_prompt=1 WHERE loan.lid=$value");
                            send($sender,$mobile,$message,$template_id);
                        }
                    }
                }
            }
        }else{
            if($user_limit_inc == 0){
                $nlit = $user_loan_limit - $user_old_loan_limit;
                $message = "Congratulations {$user_name} !! Your Credit Limit is now increased to Rs {$user_loan_limit}. Log in & accept the new limit  here {$li} -Creditlab";
                send($sender,$mobile,$message,$template_id);
            }
        }
    }
    if($tday >= 25 and $tday <= 29){
        if($user_femi == 1){}else{
            $mobile = $user_mobile;
            $template_id = '1107169460407304545';
            $t = date('Y-m-d',strtotime($user_processed_date." +29 day"));
            $message = "Dear Creditlab.in user, It's {$tday} day reminder to repay your loan before {$t}. Doing so avoids Penalty & grow your CIBIL, Experian,Equifax & CRIF scores.";
            send($sender,$mobile,$message,$template_id);
            // echo $value."--".$mobile." ".$message;echo "<br><br>";
        }
    }
    if($tday >= 60 and $tday <= 64){
        if($user_semi == 1){}else{
            $mobile = $user_mobile;
            $template_id = '1107169460407304545';
            $t = date('Y-m-d',strtotime($user_processed_date." +64 day"));
            $message = "Dear Creditlab.in user, It's {$tday} day reminder to repay your loan before {$t}. Doing so avoids Penalty & grow your CIBIL, Experian,Equifax & CRIF scores.";
            send($sender,$mobile,$message,$template_id);
            // echo $value."--".$mobile." ".$message;echo "<br><br>";
        }
    }
    
    if($tday >= 31 and $tday <= 39){
        if($user_femi == 1){}else{
            $mobile = $user_mobile;
            $template_id = '1107169519485631845';
            $message = "Your Creditlab.in loan  EMI DATE is Crossed. Close now to avoid Reminder calls, EXTRA PENALTY & Late Payment reporting to CIBIL/EXPERIAN/CRIF & EQUIFAX";
            send($sender,$mobile,$message,$template_id);
            // echo $value."--".$mobile." ".$message;echo "<br><br>";
        }
    }
    if($tday >= 66 and $tday <= 74){
        if($user_semi == 1){}else{
            $mobile = $user_mobile;
            $template_id = '1107169519485631845';
            $message = "Your Creditlab.in loan  EMI DATE is Crossed. Close now to avoid Reminder calls, EXTRA PENALTY & Late Payment reporting to CIBIL/EXPERIAN/CRIF & EQUIFAX";
            send($sender,$mobile,$message,$template_id);
            // echo $value."--".$mobile." ".$message;echo "<br><br>";
        }
    }
    $ii++;
    }}
?>
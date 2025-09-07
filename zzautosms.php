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
    $template_id = '1107169454242181751';
    $message = "Dear {#var#}, you have a pending loan with Creditlab.in, Repay it immediately. Failure leads to DEFAULT/OVERDUE in CIBIL & to Debt collection agency.";

    $template_id = '1107165683116370103';
    $message = "Dear {#var#}, you must have received salary. Repay your Creditlab.in loan now. Failure leads to Penalty & reduce CIBIL/Experian/CRIF/EQUIFAX scores";

    $template_id = '1107169454410947866';
    $message = "Congrats !! Your membership is re-approved by Creditlab.in. You can now avail loans instantly, one-click, 24*7 {#var#}";

    $template_id = '1107165683031278460';
    $message = "Dear Creditlab.in user, You're eligible for limit enhancement. Kindly, clear the outstanding loan to avail new loan with new limit. {#var#} -Creditlab";

    $template_id = '1107165682890770576';
    $message = "As per the commitment given to your Relationship Manager, we urge you to repay today the due amount of Rs {#var#} through this link: {#var#} -Creditlab";

    $template_id = '1107165683213949523';
    $message = "Dear {#var#}, We wish you a very Happy Birthday! May the year ahead add to your joys & we look forward to serve you {#var#} -Creditlab";



$today = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . " -64 day"));
        $newloanquery =  towquery("SELECT uid,id FROM `loan_apply` WHERE `status`='account manager' AND status_date > '{$today}'");
$seauserid = array();
    $i = 0;
    while($a = towfetch($newloanquery)){ $seauserid[$i] = $a['id']; $i++; }
    $seauserid = array_unique($seauserid);
    $ii=1;
    foreach($seauserid as $value){
    $a = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi FROM user INNER JOIN loan ON loan.uid=user.id  WHERE loan.lid=$value");
    if(townum($a) > 0){
    $b = towfetch($a);
    extract($b,EXTR_PREFIX_ALL,"user");
    $tday = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',strtotime($user_processed_date." -1 day")))) / (60 * 60 * 24));
    if($tday >= 25 and $tday <= 29){
        if($user_femi == 1){}else{
            $mobile = $user_mobile;
            $template_id = '1107169460407304545';
            $message = "Dear Creditlab.in user, It's {$tday} day reminder to repay your loan before CLL{$user_lid}. Doing so avoids Penalty & grow your CIBIL, Experian,Equifax & CRIF scores.";
            send($sender,$mobile,$message,$template_id);
        }
    }
    if($tday >= 60 and $tday <= 64){
        if($user_semi == 1){}else{
            $mobile = $user_mobile;
            $template_id = '1107169460407304545';
            $message = "Dear Creditlab.in user, It's {$tday} day reminder to repay your loan before CLL{$user_lid}. Doing so avoids Penalty & grow your CIBIL, Experian,Equifax & CRIF scores.";
            send($sender,$mobile,$message,$template_id);
        }
    }
    
    if($tday >= 31 and $tday <= 39){
        if($user_femi == 1){}else{
            $mobile = $user_mobile;
            $template_id = '1107169519485631845';
            $message = "Your Creditlab.in loan  EMI DATE is Crossed. Close now to avoid Reminder calls, EXTRA PENALTY & Late Payment reporting to CIBIL/EXPERIAN/CRIF & EQUIFAX";
            send($sender,$mobile,$message,$template_id);
        }
    }
    if($tday >= 66 and $tday <= 74){
        if($user_semi == 1){}else{
            $mobile = $user_mobile;
            $template_id = '1107169519485631845';
            $message = "Your Creditlab.in loan  EMI DATE is Crossed. Close now to avoid Reminder calls, EXTRA PENALTY & Late Payment reporting to CIBIL/EXPERIAN/CRIF & EQUIFAX";
            send($sender,$mobile,$message,$template_id);
        }
    }
    $ii++;
    }}
?>
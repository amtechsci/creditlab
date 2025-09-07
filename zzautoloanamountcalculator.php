<?php

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
session_start();
$db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");
function towquery($query)
 {
 	$db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");
  	mysqli_set_charset($db,'utf8');
 	$re = mysqli_query($db,$query);
 	return $re;
 }
 function towquery2($query)
 {
 	$db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");
  	mysqli_set_charset($db,'utf8');
 	$re = mysqli_query($db,$query);
 	$re2 = mysqli_insert_id($db);
 	return $re2;
 }
 function townum($query)
 {
 	$re = mysqli_num_rows($query);
 	return $re;
 }
 function towfetch($query)
 {
 	$re = mysqli_fetch_array($query);
 	return $re;
 }
 function towfetchassoc($query)
 {
 	$re = mysqli_fetch_assoc($query);
 	return $re;
 }
 function towreal($query)
 {
 	$db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");
 	$re = str_replace("<","&lt;",$query);
 	$re = str_replace(">","&gt;",$re);
 	$re = mysqli_real_escape_string($db,$re);
 	return $re;
 }
 function towrealarray($query)
 {
 	$co = mysqli_connect("localhost", "root", "Atul@1012#", "credit");
 	$re = array();
 	foreach ($query as $key => $value) {
 	    if(!is_array($value)){
 	$$key = str_replace("<","&lt;",$value);
 	$$key = str_replace(">","&gt;",$$key);
 	$$key = mysqli_real_escape_string($co,$$key);

 	$re[$key] = $$key;
 	    }else{
 	        $re[$key] = towrealarray($value);
 	    }
    }
 	return $re;
 }
 function towrealarray2($query)
 {
 	$co = mysqli_connect("localhost", "root", "Atul@1012#", "credit");
 	$re = array();
 	foreach ($query as $key => $value) {
 	    if(!is_array($value)){
 	$$key = str_replace("<","&lt;",$value);
 	$$key = str_replace(">","&gt;",$$key);
 	$$key = mysqli_real_escape_string($co,$$key);

 	$re[$key] = $$key;
 	    }else{
 	        $re[$key] = towrealarray2($value);
 	    }
    }
 	return $re;
 }

date_default_timezone_set('Asia/Kolkata');

function getDateTimeDiff($date){
 $now_timestamp = strtotime(date('Y-m-d H:i:s'));
 $diff_timestamp = $now_timestamp - strtotime($date);
 
 if($diff_timestamp<60){
  return 'few seconds ago';
 }
 else if($diff_timestamp>=60 && $diff_timestamp<3600){
  return round($diff_timestamp/60).' mins ago';
 }
 else if($diff_timestamp>=3600 && $diff_timestamp<86400){
  return round($diff_timestamp/3600).' hours ago';
 }
 else if($diff_timestamp>=86400 && $diff_timestamp<(86400*30)){
  return round($diff_timestamp/(86400)).' days ago';
 }
 else if($diff_timestamp>=(86400*30) && $diff_timestamp<(86400*365)){
  return round($diff_timestamp/(86400*30)).' months ago';
 }
 else{
  return round($diff_timestamp/(86400*365)).' years ago';
 }
}
?>
<?php
$date = date('Y-m-d');
$loan_data = towquery("SELECT loan.*,loan_apply.interest_percentage FROM loan INNER JOIN loan_apply ON loan_apply.id = loan.lid  WHERE (status_log='account manager' OR status_log='recovery officer') AND (last_cal_date IS NULL OR last_cal_date < '$date') ORDER BY id DESC LIMIT 5");
// echo "SELECT loan.*,loan_apply.interest_percentage FROM loan INNER JOIN loan_apply ON loan_apply.id = loan.lid  WHERE (status_log='account manager' OR status_log='recovery officer') AND (last_cal_date IS NULL OR last_cal_date < '$date') ORDER BY id DESC LIMIT 1";exit;
// echo "SELECT * FROM loan WHERE (status_log='account manager' OR status_log='recovery officer') AND last_cal_date < '$date' ORDER BY id DESC LIMIT 1";exit;
while($loan_fetch = towfetch($loan_data)){ extract($loan_fetch,EXTR_PREFIX_ALL,"users");
    $user_star = towquery("SELECT * FROM `user` WHERE id=$users_uid");
    $user_a = towfetch($user_star);
    $user_star = $user_a;
    $approvenew = $user_star['approvenew'];
    $user_star = $user_star['star_member'];
    $stop_date = date_create($users_processed_date);
                $sa = date_create(date('Y-m-d 23:59:59'));
                $aa = date_diff($stop_date,$sa);
                $days = $aa->format("%a");
                $t = $users_processed_amount + $users_p_fee + ($users_p_fee*0.18);
                $days++;
                $day =  $days;
                $service_charge = 0;
                if($users_interest_percentage == 1){
                    if($days >= 3 ){
                        $fee = $t * 3 / 100 * 0;
                        $interest = "0%";
                        $days = $days-3;
                        $service_charge +=$fee;
                    }else{
                        $fee = $t * $days / 100 * 0;
                        $interest = "0%";
                        $days = 0;
                        $service_charge +=$fee;
                    }
                    if(($days) >= 7){
                        $fee = $t * 7 / 100 * 0.1;
                        $interest = "0.1%";
                        $days = $days-7;
                        $service_charge +=$fee;
                    }else{
                        $fee = $t * $days / 100 * 0.1;
                        $interest = "0.1%"; 
                        $days = 0;
                        $service_charge +=$fee;
                    }
                    if(($days) >= 20){
                        $fee = $t * 20 / 100 * 0.115;
                        $interest = "0.115%";
                        $days = $days-20;
                        $service_charge +=$fee;
                    }else{
                        $fee = $t * $days / 100 * 0.115;
                        $interest = "0.115%"; 
                        $days = 0;
                        $service_charge +=$fee;
                    }
                    if(($days) >= 1){
                        $fee = $t * $days / 100 * 0.1;
                        $interest = "0.1%";
                        $service_charge +=$fee;
                        $days=0;
                    }
                    }else{
                            $fee = $t * $day / 100 * $users_interest_percentage;
                            $interest = "$users_interest_percentage%";
                            $service_charge +=$fee;
                    }
                    
                    if($day > 30){
                        $penalitydays = $day - 30;
                        $penalitydays--;
                        $penality = (($t)/100)*4;
                            $atnp = ((($t)/100) * 0.2)*$penalitydays;
                            $penality = $penality + $atnp;
                    }else{$penality=0;}
                    $penality = ($penality+($penality*0.18));
                    // echo "UPDATE `loan` SET `exhausted_period` = '$day',`service_charge`=$service_charge,`penality_charge`=$penality, last_cal_date='$date'  WHERE `loan`.`id` = $users_id;";exit;
    towquery("UPDATE `loan` SET `exhausted_period` = '$day',`service_charge`=$service_charge,`penality_charge`=$penality, last_cal_date='$date'  WHERE `loan`.`id` = $users_id;");
    // echo date('Y-m-d H:i:s');
}

?>
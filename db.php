<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = mysqli_connect("localhost", "root", "Atul@1012#", "credit");
mysqli_set_charset($db,'utf8');

// Disable SQL strict mode to prevent syntax errors
mysqli_query($db, "SET sql_mode = 'NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
function towquery($query)
{
	global $db;
	$re = mysqli_query($db,$query);
	if (!$re) {
		error_log("SQL Error: " . mysqli_error($db) . " - Query: " . $query);
		// Return false instead of throwing fatal error
		return false;
	}
	return $re;
}
 function towquery2($query)
{
	global $db;
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
	global $db;
	$re = str_replace("<","&lt;",$query);
	$re = str_replace(">","&gt;",$re);
	$re = mysqli_real_escape_string($db,$re);
	return $re;
}
 function towrealarray($query)
{
	global $db;
	$re = array();
	foreach ($query as $key => $value) {
	    if(!is_array($value)){
		$$key = str_replace("<","&lt;",$value);
		$$key = str_replace(">","&gt;",$$key);
		$$key = mysqli_real_escape_string($db,$$key);

		$re[$key] = $$key;
	    }else{
	        $re[$key] = towrealarray($value);
	    }
   }
	return $re;
}
 function towrealarray2($query)
{
	global $db;
	$re = array();
	foreach ($query as $key => $value) {
	    if(!is_array($value)){
		$$key = str_replace("<","&lt;",$value);
		$$key = str_replace(">","&gt;",$$key);
		$$key = mysqli_real_escape_string($db,$$key);

		$re[$key] = $$key;
	    }else{
	        $re[$key] = towrealarray2($value);
	    }
   }
	return $re;
}
 
if (isset($_SESSION['user'])) {
    $user = towreal($_SESSION['user']);
}elseif(isset($_COOKIE['user'])){
    $user = towreal($_COOKIE['user']);
}

if (isset($_SESSION['admin'])) {
    $admin = towreal($_SESSION['admin']);
}elseif(isset($_COOKIE['admin'])){
    $cookie_admin = towreal($_COOKIE['admin']);
    $chk = towquery("SELECT id, active FROM user WHERE email='".$cookie_admin."' LIMIT 1");
    if ($chk && ($row = towfetchassoc($chk)) && isset($row['active']) && (string)$row['active'] === '2') {
        $_SESSION['admin'] = $cookie_admin;
        $admin = $cookie_admin;
    } else {
        setcookie('admin', '', time() - 3600, '/');
    }
}

if (isset($_SESSION['account_manager'])) {
    $account_manager = towreal($_SESSION['account_manager']);
}elseif(isset($_COOKIE['account_manager'])){
    $account_manager = towreal($_COOKIE['account_manager']);
}

if (isset($_SESSION['recovery_officer'])) {
    $recovery_officer = towreal($_SESSION['recovery_officer']);
}elseif(isset($_COOKIE['recovery_officer'])){
    $recovery_officer = towreal($_COOKIE['recovery_officer']);
}
if (isset($_SESSION['verify_user'])) {
    $verify_user = towreal($_SESSION['verify_user']);
}elseif(isset($_COOKIE['verify_user'])){
    $verify_user = towreal($_COOKIE['verify_user']);
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
// $date = date('Y-m-d');
// $cc = towfetch(towquery("SELECT * FROM `fetchdate` WHERE id=1"))['date'];
// // echo strtotime($date); echo "kk". strtotime($cc);exit;
// if(strtotime($date) > strtotime($cc)){
// towquery("UPDATE `fetchdate` SET `date`='$date' WHERE id=1");
// $loan_data = towquery("SELECT * FROM loan WHERE status_log='account manager' OR status_log='recovery officer' ORDER BY id DESC"); 
//     while($loan_fetch = towfetch($loan_data)){ 
//         extract($loan_fetch,EXTR_PREFIX_ALL,"users");
//         $user_star = towquery("SELECT * FROM `user` WHERE id=$users_uid");
//         $user_a = towfetch($user_star);
//         $user_star = $user_a;
//         $approvenew = $user_star['approvenew'];
//         $user_star = $user_star['star_member'];
//         $stop_date = date_create($users_processed_date);
//                     $sa = date_create(date('Y-m-d 23:59:59'));
//                     $aa = date_diff($stop_date,$sa);
//                     $days = $aa->format("%a");
//                     $t = $users_processed_amount + $users_p_fee + ($users_p_fee*0.18);
//                     $days++;
//                     $day =  $days;
//                     $service_charge = 0;
//                     if($days >= 3 ){
//                         $fee = $t * 3 / 100 * 0;
//                         $interest = "0%";
//                         $days = $days-3;
//                         $service_charge +=$fee;
//                     }else{
//                         $fee = $t * $days / 100 * 0;
//                         $interest = "0%";
//                         $days = 0;
//                         $service_charge +=$fee;
//                     }
//                     if(($days) >= 7){
//                         $fee = $t * 7 / 100 * 0.1;
//                         $interest = "0.1%";
//                         $days = $days-7;
//                         $service_charge +=$fee;
//                     }else{
//                         $fee = $t * $days / 100 * 0.1;
//                         $interest = "0.1%"; 
//                         $days = 0;
//                         $service_charge +=$fee;
//                     }
//                     if(($days) >= 20){
//                         $fee = $t * 20 / 100 * 0.115;
//                         $interest = "0.115%";
//                         $days = $days-20;
//                         $service_charge +=$fee;
//                     }else{
//                         $fee = $t * $days / 100 * 0.115;
//                         $interest = "0.115%"; 
//                         $days = 0;
//                         $service_charge +=$fee;
//                     }
//                     if(($days) >= 1){
//                         $fee = $t * $days / 100 * 0.1;
//                         $interest = "0.1%";
//                         $service_charge +=$fee;
//                         $days=0;
//                     }
//                     if($day > 30){
//                         $penalitydays = $day - 30;
//                         $penalitydays--;
//                         $penality = (($t)/100)*3;
//                         // if($penalitydays >= 29){
//                             $atnp = ((($t)/100) * 0.2)*$penalitydays;
//                             $penality = $penality + $atnp;
//                         // print_r($penality);exit;
//                         // }
//                     }else{$penality=0;}
//         towquery("UPDATE `loan` SET `exhausted_period` = '$day',`service_charge`=$service_charge,`penality_charge`=$penality WHERE `loan`.`id` = $users_id;");
//     }
// }
// exit;
$app_url = "https://creditlab.in";
?>
<?php
// CSRF helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
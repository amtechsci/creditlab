<?php
include '../../db.php';
require 'google-api/vendor/autoload.php';
// Creating new google client instance
$client = new Google_Client();
// Enter your Client ID
$client->setClientId('200990018881-3gja1gr0a3hj8m1v8vp0v28evfk50dd2.apps.googleusercontent.com');
// Enter your Client Secrect
$client->setClientSecret('hCXljx3RiXHoiszCiuUW4m3P');
// Enter the Redirect URL
$client->setRedirectUri('https://rush4cash.in/account/home/login.php');

// Adding those scopes which we want to get (email & profile Information)
$client->addScope("email");
$client->addScope("profile");
$client->addScope("https://www.googleapis.com/auth/user.gender.read");
$client->addScope("https://www.googleapis.com/auth/user.phonenumbers.read");

if(isset($_GET['code'])){

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);

    // getting profile information
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    // showing profile info
    #echo "<pre>";
    #var_dump($google_account_info);
    #print_r($google_account_info);
    #print_r($google_account_info['email']);
    #print_r($google_account_info['name']);
    #print_r($google_account_info['id']);
    
    
    $name = $google_account_info['name'];
    $email= $google_account_info['email'];
    $password= rand(100000,999999)."@".$google_account_info['id'];
     function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
$userip = get_client_ip();

if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
   $userbrowser = 'Internet explorer';
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) //For Supporting IE 11
    $userbrowser = 'Internet explorer';
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE)
   $userbrowser = 'Mozilla Firefox';
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE)
   $userbrowser = 'Google Chrome';
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE)
   $userbrowser = "Opera Mini";
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE)
   $userbrowser = "Opera";
 elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE)
   $userbrowser = "Safari";
 else
   $userbrowser = 'Unknown';
   $login_time = date('Y-m-d H:i:s');
   
    $result=towquery("SELECT * FROM user WHERE email ='$email'");
    if (townum($result)==1) {
   $aa = towfetch($result);
        $id = $aa['id'];
        if($aa['active'] == 1){
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES ($id,'$userbrowser','$userip','$login_time')");
        $_SESSION['user'] = $email;
        setcookie('user', $email, time() + (86400 * 30), "/");
        header("location:../../user/");
    }elseif($aa['active'] == 2){
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES ($id,'$userbrowser','$userip','$login_time')");
        $_SESSION['admin'] = $email;
        setcookie('admin', $email, time() + (86400 * 30), "/");
        header("location:../../admin/");
    }
    }else{
        $sqll="SELECT * FROM verify_user WHERE email ='$email'";
        $result=towquery($sqll);
    if(townum($result)==1){
        $aa = towfetch($result);
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES (700$id,'$userbrowser','$userip','$login_time')");
        $_SESSION['verify_user'] = $email;
        header("location:/verify_user/");
        exit;
    }else{
        $sqll="SELECT * FROM account_manager WHERE email ='$email'";
    $result=towquery($sqll);
    if(townum($result)==1){
        $aa = towfetch($result);
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES (100$id,'$userbrowser','$userip','$login_time')");
        $_SESSION['account_manager'] = $email;
        header("location:/account_manager/");
        exit;
    }else{
    $sqll="SELECT * FROM recovery_officer WHERE email ='$email'";
    $result=towquery($sqll);
    if(townum($result)==1){
        $aa = towfetch($result);
        $id = $aa['id'];
        towquery("INSERT INTO `user_login_details`(`uid`, `browser`, `ip_address`, `login_time`) VALUES (200$id,'$userbrowser','$userip','$login_time')");
        $_SESSION['recovery_officer'] = $email;
        header("location:/recovery_officer/");
        exit;
    }else{
    $rcid = "RC".date('ymdHis');
    $reg_date = date('Y-m-d H:i:s');
    $otp = rand(1000,9999);
    #account_manager
    $aa = towquery("SELECT id FROM `account_manager`");
while($b = towfetch($aa)){
$a = towquery("SELECT assign_account_manager FROM `user` WHERE assign_account_manager=".$b['id']."");
$min[$b['id']] = townum($a);
}
$assign_account = array_keys($min, min($min));
$assign_account = $assign_account[0];

$aaz = towquery("SELECT id FROM `recovery_officer`");
while($bz = towfetch($aaz)){
$az = towquery("SELECT assign_recovery_officer FROM `user` WHERE assign_recovery_officer=".$bz['id']."");
$minz[$bz['id']] = townum($az);
}
$assign_recovery = array_keys($minz, min($minz));
$assign_recovery = $assign_recovery[0];
    #account_manager
    
    $a = towquery("INSERT INTO `user`(`rcid`, `name`, `email`, `password`, `active`, `verify`, `otp`, `validation`, `reg_date`, `status`, `document_password`, `loan_limit`, `assign_account_manager`, `assign_recovery_officer`, `star_member`) VALUES ('$rcid','$name','$email','$password',1,0,$otp,'','$reg_date','waiting','pan no password pan#aadhar no password aadhar#aadha2 no password aadha2#salary no password salary#bank no password bank#address no password address#bank2 no password bank2#bank3 no password bank3',10000,$assign_account,$assign_recovery,2)");
    
    $_SESSION['user'] = $email;
        setcookie('user', $email, time() + (86400 * 30), "/");
        header("location:../../user/");
#     $headers = "MIME-Version: 1.0" . "\r\n";
#$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
#$headers .= 'From: <no-reply@rush4cash.in>' . "\r\n";
#$to=$email;
#$subject="Confirmation Code";
#$message=" Hey you just register in rush4cash. For verification here is your code $otp";
#mail($to,$subject,$message,$headers);
}}}
}}
 ?>

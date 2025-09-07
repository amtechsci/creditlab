<?php
include 'db.php';
if(isset($_GET['bank_id'])){
if(isset($_GET['type'])){
    $uid = towreal($_GET['user_id']);
    $bank_id = towreal($_GET['bank_id']);
    towquery("DELETE FROM `user_bank` WHERE `id`=".$bank_id);
    towquery("UPDATE `loan_apply` SET `ubank_id`=2 WHERE uid='$uid' AND status='disbursal'");
    print_r("<script>alert('".$response['data']['message']."');window.location.replace('/admin/profile.php?id=".$uid."');</script>");
}else{
$bank_id = towreal($_GET['bank_id']);
$f = towfetch(towquery("SELECT user_bank.`ac_name`, user_bank.`ac_no`, user_bank.`ifsc_code`, user_bank.`ac_type`, user_bank.`branch_name`, user_bank.`bank_name`, user_bank.`date`, user_bank.`verify`, user.* FROM `user_bank` INNER JOIN user ON user_bank.`uid` = user.`id` WHERE user_bank.id=".$bank_id));
// print_r($f);echo "<br>";echo "<br>";
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.sandbox.co.in/authenticate',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_HTTPHEADER => array(
    'x-api-key: key_live_5Q0Yy4E1l2laXEHDcO8aAQMeDmi3V8IX',
    'x-api-secret: secret_live_3ye1DEutw64ImhbvVdOWy3DuC9FJd3Mt',
    'x-api-version: 1.0'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
$response = json_decode($response, TRUE);
// print_r($response['access_token']);
$access_token = $response['access_token'];
$curl = curl_init();
$url = 'https://api.sandbox.co.in/bank/'.$f['ifsc_code'].'/accounts/'.$f['ac_no'].'/verify?name='.urlencode($f['name']).'&mobile='.urlencode($f['mobile']).'';
curl_setopt_array($curl, array(
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Authorization: '.$access_token.'',
    'x-api-key: key_live_5Q0Yy4E1l2laXEHDcO8aAQMeDmi3V8IX',
    'x-api-version: 1.0'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
// echo $response;

function isJSON($string){
   return is_string($string) && is_array(json_decode($string, true)) ? true : false;
}
if(isJSON($response)){
$response = json_decode($response, TRUE);
if(isset($response['data']['name_at_bank']) && !empty($response['data']['name_at_bank'])){
towquery("UPDATE `user_bank` SET `ac_name`='".$response['data']['name_at_bank']."',`verify`=1 WHERE `id`=".$bank_id);
}else{
    towquery("UPDATE `user_bank` SET `verify`=1 WHERE `id`=".$bank_id);
}
print_r("<script>alert('".$response['data']['message']."');window.location.replace('/admin/profile.php?id=".$f['id']."');</script>");
// print_r($response['data']);
}else{
   print_r("<script>alert('Not Verify');window.location.replace('/admin/profile.php?id=".$f['id']."');</script>");
}
}}

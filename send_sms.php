<?php
$sender="CREDLB";


$url="https://sms.smswala.in/app/smsapi/index.php?key=2683C705E7CB39&campaign=16613&routeid=30&type=text&contacts=$mobile&senderid=$sender&msg=".urlencode($message)."&template_id=$template_id&pe_id=1401337620000065797";
// $url="https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey=6xuZOxICzUKo51xyQXjIqA&senderid=$sender&channel=2&DCS=0&flashsms=0&number=$mobile&text=".urlencode($message)."&route=1&EntityId=1101689540000061016&dlttemplateid=$template_id";
#$url="https://push.smsc.co.in/api/mt/SendSMS?APIKey=RuMCYwfWOE2r4agQGx0fsw&senderid=$sender&channel=2&DCS=0&flashsms=0&number=$mobile&text=".urlencode($message)."&route=1&EntityId=1101689540000061016&dlttemplateid=$template_id";
// print_r($url);exit;


// senderid=CREDLB&msg=1542 is OTP for Creditlab login verification %26 valid till 2min. Don't share this OTP with anyone.&template_id=1407174844163241940
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
// print_r($url);
?>
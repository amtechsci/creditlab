<?php
require_once __DIR__ . '/config/sms.php';
$sender="CREDLB";

// New SMS Portal: sms.smswala.in
// Credentials: Username: SonuMarketing, Password: Cred@So2025
// Template ID mapping - use ONLY working template IDs that match content
$template_mapping = [
    // Old template IDs -> Correct template IDs from your CSV
    '1107165683325768963' => '1407175016297384512', // General SMS -> clearance & reapply sms
    '1107169454135117024' => '1407175016260362259', // Part payment -> part payment
    '1107165683340185966' => '1407175016944191447', // Account Manager -> Acc Manager assign
    '1107165683293779914' => '1407175015930870249', // Accept agreement -> accept agreement
    '1107169453425832956' => '1407175190737426693', // KYC pending -> kyc pen
    '1107165683279440796' => '1407175016362205820', // Bank account linked -> bank acc linked
    
    // Reference Message Template
    'reference_message' => '1407175291100618275', // Reference Message template
    '1407175291100618275' => '1407175291100618275', // Reference Message template ID (direct mapping)
];

// Use working template ID from mapping
$final_template_id = isset($template_mapping[$template_id]) ? $template_mapping[$template_id] : $template_id;

$url="https://sms.smswala.in/app/smsapi/index.php?key=" . urlencode(SMS_API_KEY) . "&campaign=16613&routeid=30&type=text&contacts=$mobile&senderid=$sender&msg=".urlencode($message)."&template_id=$final_template_id";

// Backup URLs (commented out)
// $url="https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey=6xuZOxICzUKo51xyQXjIqA&senderid=$sender&channel=2&DCS=0&flashsms=0&number=$mobile&text=".urlencode($message)."&route=1&EntityId=1101689540000061016&dlttemplateid=$template_id";
// $url="https://push.smsc.co.in/api/mt/SendSMS?APIKey=RuMCYwfWOE2r4agQGx0fsw&senderid=$sender&channel=2&DCS=0&flashsms=0&number=$mobile&text=".urlencode($message)."&route=1&EntityId=1101689540000061016&dlttemplateid=$template_id";
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
<?php
include '../db.php';
if(isset($user)){
    $userquery = towquery("SELECT * FROM user WHERE email='$user'");
    $userfetch = towfetch($userquery);
    extract($userfetch,EXTR_PREFIX_ALL,"user");
   
}else{
    header('location:../account/');
}
$loanquery = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND (status='account manager' OR status='recovery officer')");
         if(townum($loanquery) > 0){  
             $amount = 0;
while($a = towfetch($loanquery)){
    $amount = $amount + $a['amount']; 
}
if($amount < $user_loan_limit){
    $newloan = $user_loan_limit - $amount;

 $t = $newloan;
    $day =  30;
    
    $apply_date = date('Y-m-d');
    // Get salary_date from user table - ensure it's properly retrieved
    if (isset($user_salary_date) && is_numeric($user_salary_date) && (int)$user_salary_date >= 1 && (int)$user_salary_date <= 31) {
        $salary_date_value = (int)$user_salary_date;
    } else {
        // If not set or invalid, try to get it directly from database
        $salary_query = towquery("SELECT salary_date FROM user WHERE id=$user_id");
        $salary_data = towfetch($salary_query);
        if ($salary_data && isset($salary_data['salary_date']) && is_numeric($salary_data['salary_date']) && (int)$salary_data['salary_date'] >= 1 && (int)$salary_data['salary_date'] <= 31) {
            $salary_date_value = (int)$salary_data['salary_date'];
        } else {
            $salary_date_value = null;
        }
    }
    $calculated_days = calculateLoanDays($apply_date, $salary_date_value);
    
    if ((int)$user_approvenew === 0) {
        // Non-EMI (one-time) loans use calculated days
        $day = $calculated_days;
    } else {
        // EMI loans always use 30 days
        $day = 30;
    }
    
    if(($day) <= 5 ){
        $fee = $t * $day / 100 * 0.6;
        $interest = "0.6%";
    }
    if(($day) > 5){
        if(($day) <= 10){
        $fee = $t * $day / 100 * 0.7;
        $interest = "0.7%"; 
        }else{
        $fee = $t * $day / 100 * 0.8;
        $interest = "0.8%";
        }}

$date = date('Y-m-d H:i:s');
        towquery("INSERT INTO loan_apply (`uid`, `amount`,`processing_fees`, `service_charge`, `days`, `apply_date`, `status`, `status_date`, `created_by`) VALUES ($user_id,$newloan,'500','$fee',$day,'$date','disbursal','$date','user')");
        $headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= 'From: <docs@creditlab.in>' . "\r\n";
$to=$user_email;
$subject="creditlab application submitted";
$message=" Dear $user_name , your application is under process.Please Upload docs for fast disbursal <br>
<br>
- Your KYC (Aadhar, PAN Card)<br>
- Last 3 months (up to date bank statement)<br>
- Last 1 month salary slip/payslip<br>
on email docs@creditlab.in<br>
<br>
Thanks - Team creditlab.in";
mail($to,$subject,$message,$headers);
}}
        header('location:index.php');
         
?>
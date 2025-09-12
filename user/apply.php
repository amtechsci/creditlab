<?php
include '../db.php';
if(isset($user)){
    $userquery = towquery("SELECT * FROM user WHERE mobile='$user'");
    $userfetch = towfetch($userquery);
    extract($userfetch,EXTR_PREFIX_ALL,"user");
}else{
    header('location:../account/');
}

if(isset($_POST['amount']) and isset($_POST['reason'])){
    if($user_verify  == 0 ){
        header('location:index.php');exit;
    }elseif($user_verify  == 1 ){
        $a = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND (status='pending' OR status='disbursal' OR status='follow_up')");
        if(townum($a) == 0){
        $extract = towrealarray($_POST);
        extract($extract);
        $t = $amount;
    $day = $cday =  30;
    $service_charge = 0;
    
        $p_f_per = 14;
    if(isset($user_interest_percentage) && $user_interest_percentage == 1){
        $p_f = ($amount / 100)*14;
                    if($cday >= 3 ){
                        $fee = $t * 3 / 100 * 0;
                        $interest = "0%";
                        $cday = $cday-3;
                        $service_charge +=$fee;
                    }else{
                        $fee = $t * $cday / 100 * 0;
                        $interest = "0%";
                        $cday = 0;
                        $service_charge +=$fee;
                    }
                    if(($cday) >= 7){
                        $fee = $t * 7 / 100 * 0.1;
                        $interest = "0.1%";
                        $cday = $cday-7;
                        $service_charge +=$fee;
                    }else{
                        $fee = $t * $cday / 100 * 0.1;
                        $interest = "0.1%"; 
                        $cday = 0;
                        $service_charge +=$fee;
                    }
                    if(($cday) >= 20){
                        $fee = $t * 20 / 100 * 0.115;
                        $interest = "0.115%";
                        $cday = $cday-20;
                        $service_charge +=$fee;
                    }else{
                        $fee = $t * $cday / 100 * 0.115;
                        $interest = "0.115%"; 
                        $cday = 0;
                        $service_charge +=$fee;
                    }
                    if(($cday) >= 1){
                        $fee = $t * $cday / 100 * 0.1;
                        $interest = "0.1%";
                        $service_charge +=$fee;
                        $cday=0;
                    }
    }else{
                        if($user_member == 0){
                            $user_interest_percentage = 0.1;
                            $p_f_per = 14;
                            $p_f = ($amount / 100)*14;
                        }elseif($user_member == 1){
                            $user_interest_percentage = 0.1;
                            $p_f_per = 13;
                            $p_f = ($amount / 100)*13;
                        }elseif($user_member == 2){
                            $user_interest_percentage = 0.05;
                            $p_f_per = 13;
                            $p_f = ($amount / 100)*13;
                        }elseif($user_member == 3){
                            $user_interest_percentage = 0.1;
                            $p_f_per = 12;
                            $p_f = ($amount / 100)*12;
                        }elseif($user_member == 4){
                            $user_interest_percentage = 0.1;
                            $p_f_per = 14;
                            $p_f = ($amount / 100)*14;
                        }
                        $fee = $t * $day / 100 * $user_interest_percentage;
                        $interest = "$user_interest_percentage%";
                        $service_charge +=$fee;
    }
        
        if(isset($_POST['reason'])){}else{
            $reason = 'No Reason';
        }
        
        $date = date('Y-m-d H:i:s');
        if($user_approvenew == 1){
        $origination_fee = ($amount / 100)*7;
        }else{
        $origination_fee = 0;
        }
        if($user_sloan > 0){
            print_r("INSERT INTO loan_apply (`uid`, `amount`,`processing_fees`,`pro_fee_per`,`interest_percentage`,`origination_fee`, `service_charge`, `days`, `apply_date`, `status`, `status_date`, `created_by`,`reason`,`lat`,`longt`) VALUES ($user_id,$amount-($p_f + $origination_fee + ($p_f*0.18)),'$p_f','$p_f_per','$user_interest_percentage','$origination_fee','$service_charge',$day,'$date','disbursal','$date','user','$reason','$lat','$long')");
            towquery("INSERT INTO loan_apply (`uid`, `amount`,`processing_fees`,`pro_fee_per`,`interest_percentage`,`origination_fee`, `service_charge`, `days`, `apply_date`, `status`, `status_date`, `created_by`,`reason`,`lat`,`longt`) VALUES ($user_id,$amount-($p_f + $origination_fee + ($p_f*0.18)),'$p_f','$p_f_per','$user_interest_percentage','$origination_fee','$service_charge',$day,'$date','disbursal','$date','user','$reason','$lat','$long')");
        towquery("UPDATE `user` SET `status`='disbursal' WHERE id=$user_id");
        }else{
        towquery("INSERT INTO loan_apply (`uid`, `amount`,`processing_fees`,`pro_fee_per`,`interest_percentage`, `origination_fee`,  `service_charge`, `days`, `apply_date`, `status`, `status_date`, `created_by`,`reason`,`lat`,`longt`,`ubank_id`) VALUES ($user_id,$amount-($p_f + $origination_fee + ($p_f*0.18)),'$p_f','$p_f_per','$user_interest_percentage','$origination_fee','$service_charge',$day,'$date','pending','$date','user','$reason','$lat','$long',1)");
        towquery("UPDATE `user` SET `status`='applied' WHERE id=$user_id");
        
        require '../zxc/class/class.phpmailer.php';
	$mail2 = new PHPMailer;
// 	$mail2->SMTPDebug = true;
	$mail2->IsSMTP();								//Sets Mailer to send message using SMTP
	$mail2->Host = 'smtp.hostinger.com';		//Sets the SMTP hosts of your Email hosting, this for Godaddy
	$mail2->Port = '465';								//Sets the default SMTP server port
	$mail2->SMTPAuth = true;							//Sets SMTP authentication. Utilizes the Username and Password variables
	$mail2->Username = 'no-reply@creditlab.in';					//Sets SMTP username
	$mail2->Password = 'No-reply@123';					//Sets SMTP password
	$mail2->SMTPSecure = 'ssl';							//Sets connection prefix. Options are "", "ssl" or "tls"
	$mail2->From = 'no-reply@creditlab.in';			//Sets the From email address for the message
	$mail2->FromName = 'no-reply';			//Sets the From name of the message
	$mail2->AddAddress($user_email, $user_name);		//Adds a "To" address
	$mail2->WordWrap = 50;							//Sets word wrapping on the body of the message to a given number of characters
	$mail2->IsHTML(true);							//Sets message type to HTML				
	#$mail2->AddAttachment($file_name);     				//Adds an attachment from a path on the filesystem
	$mail2->Subject = 'creditlab application submitted';			//Sets the Subject of the message
	$mail2->Body =" Dear $user_name , your application is under process. Please Send docs for fast disbursal <br>
        <br>
        - Your KYC (Aadhar, PAN Card)<br>
        - Last 3 months (up to date bank statement)<br>
        - Last 1 month salary slip/payslip<br>
        on email docs@creditlab.in<br>
        <br>
        Thanks - Team creditlab.in";				//An HTML or plain text message body
$mail2->Send();
        }
        if(isset($_GET['f'])){
        header('location:newloan.php');
        }else{
        header('location:index.php');
        }
    }else{
        if(isset($_GET['f'])){
        header('location:newloan.php');
        }else{
        header('location:index.php');
        }
    }}
}
?>
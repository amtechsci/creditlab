<?php
include_once 'head.php';
if(isset($_GET['id'])){
    $id = towreal($_GET['id']);
    $aaid = towreal($_GET['id']);
  $userprofile = towquery("SELECT * FROM `user` WHERE id=".$id."");
  $userprofetch = towfetch($userprofile);
    extract($userprofetch,EXTR_PREFIX_ALL,"userpro");
    $date = date('Y-m-d H:i:s');
}else{
    print_r("<script>window.location.replace('index.php');</script>");
}
if(isset($_POST['validation'])){
    $extract = towrealarray($_POST);     extract($extract);
    $loan_data = towquery("SELECT * FROM loan_apply WHERE uid='$userpro_id' ORDER BY id DESC");
    $loan_fetch = towfetch($loan_data);
    extract($loan_fetch,EXTR_PREFIX_ALL,"update");
    $processcheck = towrealarray($_POST['processcheck']);
    if($valid_status == "Process"){
    if($update_status == "pending"){$update_status = "follow up";}elseif(in_array("Ready for Disbursal",$processcheck)){
        $update_status = "disbursal";
    }
    }elseif($valid_status == "Not Process"){
        $update_status = "Hold";
    }elseif($valid_status == "Re Process"){
        $update_status = "Hold";
    }elseif($valid_status == "cancel"){
        $update_status = "cancel";
        if(in_array("cancel & hold",$processcheck)){
        $update_status = "Hold";
        }
    }
    $validation = $validation.$valid_status." to ".$update_status." by $user_name on ".date('Y-m-d')." \n";
    if($valid_status == "Not Process"){
        towquery("UPDATE `user` SET `validation`=CONCAT(`validation`,'$validation'), `verify`=4,`status`='Hold',reg_date='".date('Y-m-d H:i:s')."' WHERE id=".$id."");
        towquery("UPDATE `loan_apply` SET `status`='cancel', `status_date`='$date' WHERE uid=".$id." AND id=$update_id");
    }elseif($valid_status == "Re Process"){
        towquery("UPDATE `user` SET `validation`=CONCAT(`validation`,'$validation'), `verify`=3,`status`='Hold',reg_date='".date('Y-m-d H:i:s')."' WHERE id=".$id."");
        towquery("UPDATE `loan_apply` SET `status`='cancel', `status_date`='$date' WHERE uid=".$id." AND id=$update_id");
    }elseif($valid_status == "cancel"){
        towquery("UPDATE `loan_apply` SET `status`='cancel', `status_date`='$date' WHERE uid=".$id." AND id=$update_id");
        towquery("UPDATE `user` SET `loan`=2,`sloan`=0 WHERE id=".$userpro_id."");
        towquery("UPDATE `user` SET `validation`=CONCAT(`validation`,'$validation'), `status`='cancel' WHERE id=".$id."");
        if(in_array("cancel & hold",$processcheck)){
            towquery("UPDATE `user` SET `validation`=CONCAT(`validation`,'$validation'), `verify`=4,`status`='Hold',reg_date='".date('Y-m-d H:i:s')."' WHERE id=".$id."");
        }
    }elseif($valid_status == "unhold"){
        towquery("UPDATE `user` SET `validation`=CONCAT(`validation`,'$validation'), `verify`=0,`status`='waiting' WHERE id=".$id."");
        towquery("UPDATE `user` SET `loan`=0,`sloan`=0 WHERE id=".$userpro_id."");
    }else{
        towquery("UPDATE `user` SET `validation`=CONCAT(`validation`,'$validation') WHERE id=".$id."");
        $valid = towquery("SELECT * FROM loan_apply WHERE uid=".$id." ORDER BY id DESC");
        $validfetch = towfetch($valid);
        $totalamount = $validfetch['amount'] + $validfetch['processing_fees'] + $validfetch['service_charge'];
        if($validfetch['status'] == "pending"){
            towquery("UPDATE `user` SET `status`='follow up' WHERE id=".$id."");
        towquery("UPDATE `loan_apply` SET `status`='follow up', `status_date`='$date' WHERE uid=".$id." AND id=$update_id");
        }elseif(in_array("Ready for Disbursal",$processcheck)){
            towquery("UPDATE `user` SET `status`='disbursal' WHERE id=".$id."");
        towquery("UPDATE `loan_apply` SET `status`='disbursal', `status_date`='$date' WHERE uid=".$id." AND id=$update_id");
        }
    }
    print_r("<script>alert('Your data is successfully updated'); window.location.replace('profile.php?id=".$id."');</script>");
}
?>
<?php
if(isset($_POST['mobile'])){
    $extract = towrealarray($_POST);
    extract($extract);
    if($mobile == $altmobile){
        print_r("<script>alert('please enter mobile number of any family person if you don’t have alternate number');  window.location.replace('profile.php?id=".$id."');</script>");
    }else{
    if(isset($_POST['pus'])){
        $pqu = "UPDATE `user` SET `name`='$name', `pan_name`='$pan_name',`mobile`=$mobile,`altmobile`=$altmobile,`state`='$state',`email`='$email',`altemail`='$altemail',`dob`='$dob',`pan`='$pan',`salary`='$salary',`salarystatus`='$salarystatus',`present_address`='$present_address',`permanent_address`='$permanent_address',`company`='$company',`designation`='$designation'
,`department`='$department',`verify`=1,`status`='Approve',`get_salary`='$get_salary',`marital_status`='$marital_status',`loan_limit`=$loan_limit,`aadhar`='$aadhar',`company_url`='$company_url',`fb_url`='$fb_url',`insta_id`='$insta_id',`father_name`='$father_name',`star_member`='$star_member',`approvenew`='$approvenew',`salary_date`='$salary_date',`assign_account_manager`='$assign_account_manager',`assign_recovery_officer`='$assign_recovery_officer' WHERE id='$userpro_id'";
// print_r($pqu);exit;
    (towquery($pqu) and
    print_r("<script> window.location.replace('profile.php?id=".$id."');</script>")) or print_r("<script>alert('Phone No. already register');  window.location.replace('profile.php?id=".$id."');</script>");
    }elseif(($salary < 20000) and ($salarystatus == "Salaried")){
        towquery("UPDATE `user` SET `verify`=3 WHERE id='$userpro_id'") and
    print_r("<script>window.location.replace('profile.php?id=".$id."');</script>");
    }else{
        towquery("UPDATE `user` SET `verify`=4 WHERE id='$userpro_id'") and
    print_r("<script>window.location.replace('profile.php?id=".$id."');</script>");
    }
}}

if(isset($_POST['bank_name']) and !isset($_POST['bank_detail_update'])){
    $extract = towrealarray($_POST);     extract($extract);
    towquery("INSERT INTO `user_bank`(`uid`, `ac_name`, `ac_no`, `ifsc_code`, `ac_type`, `branch_name`, `bank_name`, `date`) VALUES ($id,'$account_name','$account_no','$ifsc','$account_type','$branch_name','$bank_name','".date('Y-m-d')."')");
    towquery("UPDATE `user` SET `bank_name`='$bank_name',`branch_name`='$branch_name',`ifsc`='$ifsc',`account_no`='$account_no',`account_type`='$account_type',`account_name`='$account_name' WHERE id='$userpro_id'");
    print_r("<script>alert('Your data is successfully updated');  window.location.replace('profile.php?id=".$id."');</script>");
}
    ?>
<?php
if (isset($_POST['submit_refs'])) {
    $user_id = $userpro_id;

    foreach ($_POST['ref'] as $ref_id => $ref) {
        $name = towreal($ref['name']);
        $phone = towreal($ref['phone']);
        $relation = towreal($ref['relation']);
        $status = towreal($ref['status']);

        // If $ref_id is numeric, update an existing referral; otherwise, insert a new referral
        if (is_numeric($ref_id)) {
            // Update existing referral
            towquery("UPDATE user_referrals SET name='$name', phone='$phone', relation='$relation', status='$status' WHERE id='$ref_id' AND uid='$user_id'");
        } else {
            // Insert new referral
            towquery("INSERT INTO user_referrals (uid, name, phone, relation, status) VALUES ('$user_id', '$name', '$phone', '$relation', '$status')");
        }
    }

    echo "<script>window.location.replace('profile.php?id=$id&tab=$tab');</script>";
    exit;
}
if (isset($_POST['delete_ref'])) {
    $ref_id = intval($_POST['delete_ref']); // Get the referral ID to delete

    // Delete the referral from the user_referrals table
    towquery("DELETE FROM user_referrals WHERE id='$ref_id'");

    echo "<script>window.location.replace('profile.php?id=$id&tab=$tab');</script>";
    exit;
}
?>
<?php
if($_POST['user_conta']){
    $a = towquery("SELECT * FROM user_contact_details WHERE uid=$userpro_id");
    if(townum($a) == 0){
    $v = towreal($_POST['user_contact']);
    towquery("INSERT INTO `user_contact_details` (`uid`, `user_contact`) VALUES ($userpro_id, '$v')");
    }else{
        $v = towreal($_POST['user_contact']);
    towquery("UPDATE `user_contact_details` SET `user_contact`='$v' WHERE uid=$userpro_id");
    }
    print_r("<script> window.location.replace('profile.php?id=".$id."');</script>");
}
?>
<?php
if(isset($_POST['conta'])){
    $a = towquery("SELECT * FROM user_contact_details WHERE uid=$userpro_id");
    if(townum($a) == 0){
    $v = towreal($_POST['contact']);
    towquery("INSERT INTO `user_contact_details` (`uid`, `contact`) VALUES ($userpro_id, '$v')");
    }else{
        $v = towreal($_POST['contact']);
    towquery("UPDATE `user_contact_details` SET `contact`='$v' WHERE uid=$userpro_id");
    }
    print_r("<script> window.location.replace('profile.php?id=".$id."');</script>");
}
?>
<?php
if(isset($_POST['document'])){
    
    if(!empty($_FILES["conpanydocument"]["name"])){
    $file_type = strtolower(end(explode('.',$_FILES['conpanydocument']['name'])));
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
    $conpanydocument = $_FILES["conpanydocument"]["name"];
    $conpanydocument = explode(".",$conpanydocument);
    $conpanydocument = end($conpanydocument);
    $conpanydocument = $userpro_email.'conpany'.date('YmdHis').'.'.$conpanydocument;
    move_uploaded_file($_FILES["conpanydocument"]["tmp_name"], '../user/uploads/'.$conpanydocument);
    }else{$conpanydocument = $userpro_conpanydocument;}}else{$conpanydocument = $userpro_conpanydocument;}
    
    if(!empty($_FILES['personaldocument']['name'])){
        $userpro_personaldocument = explode("#",$userpro_personaldocument);
    $personaldocument = array();
    $i = 0;
    while($i < 2){
    $file_type = strtolower(end(explode('.',$_FILES['personaldocument']['name'][$i])));
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
        
        $a = $_FILES['personaldocument']['name'][$i];
$ext = explode(".",$a);
$ext = end($ext);
$new = date('ymdhis');
$personaldocument[$i] = $userpro_email.'personal'.$new.$i.'.'.$ext;
move_uploaded_file($_FILES['personaldocument']['tmp_name'][$i],'../user/uploads/'.$personaldocument[$i]);
        $i++;
        }else{$personaldocument[$i] = $userpro_personaldocument[$i]; $i++;}
    }
    $personaldocument = implode('#',$personaldocument);
    }else{$personaldocument = $userpro_personaldocument;}
    
    
    if(!empty($_FILES["salarydocument"]["name"])){
    $file_type = strtolower(end(explode('.',$_FILES['salarydocument']['name'])));
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
    $salarydocument = $_FILES["salarydocument"]["name"];
    $salarydocument = explode(".",$salarydocument);
    $salarydocument = end($salarydocument);
    $salarydocument = $userpro_email.'salary'.date('YmdHis').'.'.$salarydocument;
    move_uploaded_file($_FILES["salarydocument"]["tmp_name"], '../user/uploads/'.$salarydocument);
    }else{$salarydocument = $userpro_salarydocument;}
    }else{$salarydocument = $userpro_salarydocument;}
    
    if(!empty($_FILES['bankdocument']['name'])){
    $file_type = strtolower(end(explode('.',$_FILES['bankdocument']['name'])));
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
        $a = $_FILES['bankdocument']['name'];
$ext = explode(".",$a);
$ext = end($ext);
$new = date('ymdhis');
$bankdocument = $userpro_email.'bank'.$new.'.'.$ext;
move_uploaded_file($_FILES['bankdocument']['tmp_name'],'../user/uploads/'.$bankdocument);
        $i++;
    }else{$bankdocument = $userpro_bankdocument;}
    }else{
        $bankdocument = $userpro_bankdocument;
    }
    
    if(!empty($_FILES['bankdocument2']['name'])){
    $file_type = strtolower(end(explode('.',$_FILES['bankdocument2']['name'])));
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
        $a = $_FILES['bankdocument2']['name'];
$ext = explode(".",$a);
$ext = end($ext);
$new = date('ymdhis');
$bankdocument2 = $userpro_email.'bank'.$new.'.'.$ext;
move_uploaded_file($_FILES['bankdocument2']['tmp_name'],'../user/uploads/'.$bankdocument2);
        $i++;
    }else{$bankdocument2 = $userpro_bankdocument2;}
    }else{
        $bankdocument2 = $userpro_bankdocument2;
    }
    
    if(!empty($_FILES['bankdocument3']['name'])){
    $file_type = strtolower(end(explode('.',$_FILES['bankdocument3']['name'])));
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
        $a = $_FILES['bankdocument3']['name'];
$ext = explode(".",$a);
$ext = end($ext);
$new = date('ymdhis');
$bankdocument3 = $userpro_email.'bank'.$new.'.'.$ext;
move_uploaded_file($_FILES['bankdocument3']['tmp_name'],'../user/uploads/'.$bankdocument3);
        $i++;
    }else{$bankdocument3 = $userpro_bankdocument3;}
    }else{
        $bankdocument3 = $userpro_bankdocument3;
    }
    
    if(!empty($_FILES["addressdocument"]["name"])){
    $file_type = strtolower(end(explode('.',$_FILES['addressdocument']['name'])));
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
    $addressdocument = $_FILES["addressdocument"]["name"];
    $addressdocument = explode(".",$addressdocument);
    $addressdocument = end($addressdocument);
    $addressdocument = $userpro_email.'address'.date('YmdHis').'.'.$addressdocument;
    move_uploaded_file($_FILES["addressdocument"]["tmp_name"], '../user/uploads/'.$addressdocument);
    }else{
        $addressdocument = "$userpro_addressdocument";
    }}else{
        $addressdocument = "$userpro_addressdocument";
    }
    if(!empty($_FILES["signature"]["name"])){
    $file_type = strtolower(end(explode('.',$_FILES['signature']['name'])));
    $allowed = array("jpeg", "JPEG",  "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if(in_array($file_type, $allowed)) {
    $signature = $_FILES["signature"]["name"];
    $signature = explode(".",$signature);
    $signature = end($signature);
    $signature = $userpro_email.'signature'.date('YmdHis').'.'.$signature;
    move_uploaded_file($_FILES["signature"]["tmp_name"], '../user/uploads/'.$signature);
    }else{
        $signature = "$userpro_signature";
    }}else{
        $signature = "$userpro_signature";
    }
    $document_password = array();
    $extract = towrealarray($_POST);
    extract($extract);
    $userpro_document_password = explode("#",$userpro_document_password);
    if(!empty($pan_pass)){$document_password[0] = "pan".$pan_pass."pan";
    }else{$document_password[0] = "$userpro_document_password[0]";}
    if(!empty($aadhar_pass)){$document_password[1] = "aadhar".$aadhar_pass."aadhar";
    }else{$document_password[1] = "$userpro_document_password[1]";}
    if(!empty($aadhar_pass2)){$document_password[2] = "aadha2".$aadhar_pass2."aadha2";
    }else{$document_password[2] = "$userpro_document_password[2]";}
    if(!empty($salary_pass)){$document_password[3] = "salary".$salary_pass."salary";
    }else{$document_password[3] = "$userpro_document_password[3]";}
    if(!empty($bank_pass)){$document_password[4] = "bank".$bank_pass."bank";
    }else{$document_password[4] = "$userpro_document_password[4]";}
    if(!empty($address_pass)){$document_password[5] = "address".$address_pass."address";
    }else{$document_password[5] = "$userpro_document_password[5]";}
    if(!empty($bank_pass2)){$document_password[6] = "bank2".$bank_pass2."bank2";
    }else{$document_password[6] = "$userpro_document_password[6]";}
    if(!empty($bank_pass3)){$document_password[7] = "bank3".$bank_pass3."bank3";
    }else{$document_password[7] = "$userpro_document_password[7]";}
    $document_password = implode('#',$document_password); 
    
    towquery("UPDATE `user` SET `conpanydocument`='$conpanydocument',`personaldocument`='$personaldocument',`salarydocument`='$salarydocument',`bankdocument`='$bankdocument',`bankdocument2`='$bankdocument2',`bankdocument3`='$bankdocument3',`addressdocument`='$addressdocument',`document_password`='$document_password',`signature`='$signature' WHERE id='$userpro_id'");
    towquery("INSERT INTO `document`(`uid`, `document_name`, `password`, `date`) VALUES ($userpro_id,'$salarydocument','$salary_pass','$date')")
    and print_r("<script>alert('Your data is successfully updated'); window.location.replace('profile.php?id=".$id."');</script>");
}
?>
<?php
if(isset($_POST['loandata'])){
    $extract = towrealarray($_POST);
    extract($extract);
    towquery("UPDATE `loan_apply` SET `amount`=$amount,`processing_fees`='$processing_fees',`days`=$days WHERE id='$cllid'");
    $lidloan = towquery("SELECT * FROM `loan_apply` where id=$cllid");
        $lap = towfetch($lidloan);
        $t = $lap['amount'] + $lap['processing_fees'];
        $day =  $lap['days'];
        // print_r($userpro_star_member);exit;
    if($userpro_star_member == 2){
    if(($day) <= 5 ){
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%";
    }
    if(($day) > 5){
        if(($day) <= 10){
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%"; 
        }else{
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%";
        }}
    }
    elseif($userpro_star_member == 3){
        if(($day) <= 5 ){
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%";
    }
    if(($day) > 5){
        if(($day) <= 10){
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%"; 
        }else{
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%";
        }}
    }
    elseif($userpro_star_member == 4){
        if(($day) <= 5 ){
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%";
    }
    if(($day) > 5){
        if(($day) <= 10){
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%"; 
        }else{
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%";
        }}
    }
    elseif($userpro_star_member == 5){
        if(($day) <= 5 ){
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%";
    }
    if(($day) > 5){
        if(($day) <= 10){
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%"; 
        }else{
        $fee = $t * $day / 100 * 0.5;
        $interest = "0.5%";
        }}
    }
        $total_amount = $fee; 
        towquery("UPDATE `loan_apply` SET `service_charge`='$total_amount' WHERE id='$cllid'") and print_r("<script>alert('Your data is successfully updated'); window.location.replace('profile.php?id=".$id."');</script>");
}
?>
<?php
if(isset($_POST['transaction'])){
    $extract = towrealarray($_POST);
    extract($extract);
    $cllid = explode("CLL",$cllid);
    $cllid = $cllid[1];
    print_r($cllid);
    if(($transaction_flow == "full") or ($transaction_flow == "firstemi") or ($transaction_flow == "secondemi")){
        towquery("INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES ($id,'$cllid','$transaction_number','$transaction_date','$transaction_amount','$transaction_flow')") and print_r("<script>window.location.replace('profile.php?id=".$id."');</script>");
        if($transaction_flow == "firstemi"){
            towquery("UPDATE `loan` SET `femi`=1 WHERE lid=$cllid");
        }
        if($transaction_flow == "secondemi"){
            towquery("UPDATE `loan` SET `semi`=1 WHERE lid=$cllid");
        }
    }elseif($transaction_flow == "R4C To Customer"){
    $valid = towquery("SELECT * FROM loan_apply WHERE id=".$cllid." ORDER BY id DESC");
        $validfetch = towfetch($valid);
     towquery("UPDATE `user` SET `status`='account manager', `loan`=0, `sloan`=`sloan`+1 WHERE id=".$id."");
     if($validfetch['amount'] - 500 > $transaction_amount){
            towquery("UPDATE `user` SET `loan_limit`=$transaction_amount WHERE id=$id");
        }
        towquery("UPDATE `loan_apply` SET `status`='account manager', `status_date`='$date' WHERE uid=".$id." AND id=".$cllid."");
        if($userpro_approvenew == 0){$is_emi = 0;}else{$is_emi = 1;}
        towquery("INSERT INTO `loan`(`lid`, `uid`, `processed_date`, `processed_amount`, `exhausted_period`, `p_fee`, `service_charge`, `penality_charge`, `total_amount`, `status_log`, `action`, `total_time`, `is_emi`) VALUES (".$cllid.",$id,'$date','".$transaction_amount."','0','".$validfetch['processing_fees']."','".$validfetch['service_charge']."','','$totalamount','account manager','no data','".$validfetch['days']."','$is_emi')");
        
    towquery("INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES ($id,'$cllid','$transaction_number','$transaction_date','$transaction_amount','$transaction_flow')") and print_r("<script> window.location.replace('profile.php?id=".$id."');</script>");
}elseif($transaction_flow == "renew"){
    $valid = towquery("SELECT * FROM loan_apply WHERE id=".$cllid." ORDER BY id DESC");
        $validfetch = towfetch($valid);
     towquery("UPDATE `user` SET `status`='account manager', `loan`=0, `sloan`=`sloan`+1 WHERE id=".$id."");
     towquery("UPDATE `loan_apply` SET `status`='account manager', `status_date`='$date' `apply_date`='$date' WHERE uid=".$id." AND id=".$cllid."");
     
     towquery("UPDATE `loan` SET `processed_date`='$date' WHERE uid=".$id." AND lid=".$cllid."");

    towquery("INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES ($id,'$cllid','$transaction_number','$transaction_date','$transaction_amount','$transaction_flow')") and print_r("<script> window.location.replace('profile.php?id=".$id."');</script>");
}elseif($transaction_flow == "part"){
    $valid = towquery("SELECT * FROM loan_apply WHERE id=".$cllid." ORDER BY id DESC");
        $validfetch = towfetch($valid);
     towquery("UPDATE `loan` SET `advance_amount`='$transaction_amount' WHERE uid=".$id." AND lid=".$cllid."");
    towquery("INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES ($id,'$cllid','$transaction_number','$transaction_date','$transaction_amount','$transaction_flow')") and print_r("<script> window.location.replace('profile.php?id=".$id."');</script>");
}
}
?>
<?php
if(isset($_POST['comment'])){
    $extract = towrealarray($_POST);
    extract($extract);
    $comment = "<b>".$comment."</b>, Updated by <b style=\"color:red;\">".$user_name."</b> on ".date('Y-m-d')."<br>";
        towquery("UPDATE `user` SET `comment`=CONCAT(`comment`,'$comment') WHERE id=".$id."");
    print_r("<script>window.location.replace('profile.php?id=".$id."');</script>");
}
?>
<?php
if(isset($_POST['follow_up_mess'])){
    $extract = towrealarray($_POST);
    extract($extract);
    $follow_up_mess = "CLL".$follow_up_id." : <b>".$follow_up_mess."</b>, Updated by <b style=\"color:red;\">".$user_name."</b> on ".date('Y-m-d')."<br>";
    if(isset($advance_amount)){
    towquery("UPDATE `loan` SET `follow_up_mess`=CONCAT(`follow_up_mess`,'$follow_up_mess'),advance_amount='$advance_amount' WHERE lid=".$follow_up_id."");
    }else{
        towquery("UPDATE `loan` SET `follow_up_mess`=CONCAT(`follow_up_mess`,'$follow_up_mess') WHERE lid=".$follow_up_id."");
    }
    towquery("UPDATE `loan_apply` SET `follow_up_date`='$follow_up_date' WHERE id=".$follow_up_id."");
    print_r("<script>window.location.replace('profile.php?id=".$id."');</script>");
}

if(isset($_POST['loan_acc_man'])){
    $extract = towrealarray($_POST);
    extract($extract);
    towquery("INSERT INTO `loan_acc_man`(`uid`, `lid`, `customer_response`, `commitment_date`, `commitment_text`, `default_type`) VALUES ('$id','$loan_id','$customer_response','$commitment_date','$commitment_text','$default_type')");
    print_r("<script>window.location.replace('profile.php?id=".$id."');</script>");
}
?>
<body onload="adddate('<?=date('Y-m-d')?>')">
    <!-- Start Left menu area -->
    <?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>
            <!-- Mobile Menu end -->
          <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                   <p>NAME : <?=$userpro_name;?></p><p>CLID : <?=$userpro_rcid;?></p>
                   <p>Status : <?php if($userpro_status == "waiting"){echo "Just Registered";}else{echo $userpro_status;}?></p>
                </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                    <?php $account_manager = towquery("SELECT * FROM `account_manager` WHERE id=$userpro_assign_account_manager");
                    $account_manager = towfetch($account_manager);
                    ?>
                    <?php $recovery_officer = towquery("SELECT * FROM `recovery_officer` WHERE id=$userpro_assign_recovery_officer");
                    $recovery_officer = towfetch($recovery_officer);
                    ?>
                   <p>Account Manager : <?=$account_manager['name'];?> </p><p>Recovery Officer : <?=$recovery_officer['name'];?></p>
                   <p>Registered <span class="bread-slash">:</span> <span class="bread-blod"><?=getDateTimeDiff($userpro_reg_date);?></span></p>
                </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                   <p>Pan : Y</p>
                   <p>Cibil : Y</p>
                   <p>adhar : Y</p>
                   <p>Bank check : Y</p>
                </div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
          <div class="single-pro-review-area mt-t-30 mg-b-15">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="product-payment-inner-st">
                            <ul id="myTabedu1" class="tab-review-design">
                                <li class="active" style="margin-bottom:30px;"><a href="#Personal">Personal</a></li>
                                <!--<li><a href="#additional"> additional</a></li>-->
                                <li><a href="#INFORMATION">Documents</a></li>
                                <li><a href="#Bank">Bank Information</a></li>
                                <li><a href="#Reference">Reference</a></li>
                                <li><a href="#login_data">Login Data</a></li>
                                <li><a href="#profile_detail">Profile Detail</a></li>
                                <li><a href="#user_contact">Number Contact</a></li>
                                <li><a href="#contact">Contact</a></li>
                                <li><a href="#loan">Apply Loan</a></li>
                                <li><a href="#oldloan">All Loan</a></li>
                                <li><a href="#transaction_details">Transaction Details</a></li>
                                <li><a href="#Validation">Validation</a></li>
                                <li><a href="#follow_up">Follow Up</a></li>
                                <li><a href="#note">Note</a></li>
                                <li><a href="#sms">SMS</a></li>
                                <li><a href="#cibil_analysis">CIBIL ANALYSIS</a></li>
                                <li><a href="#pan_analysis">PAN ANALYSIS</a></li>
                                <li><a href="#adhar_analysis">Adhar ANALYSIS</a></li>
                                <li><a href="#bank_analysis">Bank Statement ANALYSIS</a></li>
                                <li><a href="#mail">Mail</a></li>
                                <li><a href="#manager">Account manager</a></li>
                            </ul>
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade active in" id="Personal">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <form id="add-department" action="" method="post" class="add-department">
                                                    <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                                                <lable>Name</lable>
                                                                <input name="name" type="text" class="form-control" placeholder="Name" value="<?=$userpro_name?>" pattern="(?=.*[a-zA-Z]).{1,}" >
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Pan Name</lable>
                                                                <input name="pan_name" type="text" class="form-control" placeholder="Name" value="<?=$userpro_pan_name?>" pattern="(?=.*[a-zA-Z]).{1,}" >
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <lable>Mobile Number</lable>
                                                            <input name="mobile" id="mobile" type="text" placeholder="Mobile" class="form-control" value="<?=$userpro_mobile?>" pattern="[6789][0-9]{9}">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Alternate Mobile Number</lable>
                                                            <input name="altmobile" id="altmobile" type="text" placeholder="alternate mobile number" class="form-control" value="<?=$userpro_altmobile?>" pattern="[6789][0-9]{9}">
                                                            <p id="mess"></p>
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Email</lable>
                                                            <input name="email" type="email" id="email" placeholder="Email" class="form-control" value="<?=$userpro_email?>" >
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Alternate Email</lable>
                                                            <input name="altemail" id="altemail" type="email" placeholder="alternate Email" class="form-control" value="<?=$userpro_altemail?>">
                                                            <p id="messs"></p>
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Company Name</lable>
                                                                <input type="text" name="company" class="form-control" placeholder="Company Name" pattern="(?=.*[a-zA-Z]).{3,}" title="Company name must be and more then 3 " value="<?=$userpro_company?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Designation</lable>
                                                                <input type="text" name="designation" class="form-control" placeholder="Designation" value="<?=$userpro_designation?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                            </div>
                                                        <div class="form-group">
                                                            <lable>Department</lable>
                                                            <input type="text" class="form-control" placeholder="Department" name="department" value="<?=$userpro_department?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                        </div>
                                                        <div class="form-group">
                                                            <lable>Present Address</lable>
                                                            <input type="text" class="form-control" placeholder="Present Address" name="present_address" value="<?=$userpro_present_address?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                        </div>
                                                        <div class="form-group">
                                                            <lable>Permanent Address</lable>
                                                            <input type="text" class="form-control" placeholder="Permanent Address" name="permanent_address" value="<?=$userpro_permanent_address?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <lable>Company url</lable>
                                                            <input type="text" class="form-control" placeholder="Company url" name="company_url" value="<?=$userpro_company_url?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <lable>Salary Date</lable>
                                                            <input type="text" class="form-control" placeholder="Salary Date" name="salary_date" value="<?=$userpro_salary_date?>">
                                                        </div>
                                                        <div class="form-group">
                                                            <lable>FB url</lable>
                                                            <input type="text" class="form-control" placeholder="FB url" name="fb_url" value="<?=$userpro_fb_url?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                        </div>
                                                        <div class="form-group">
                                                            <lable>Work Year</lable>
                                                            <input type="text" class="form-control" placeholder="Work Year" name="work_year" value="<?=$userpro_work_year?>">
                                                        </div>
                                                        <div class="form-group">
                                                            <lable>EMI</lable>
                                                            <!--<input type="text" class="form-control" placeholder="Work Year" name="work_year" value="<?=$userpro_work_year?>">-->
                                                            <select class="form-control" name="approvenew">
                                                                <option value="0" <?php if($userpro_approvenew == 0){echo 'selected';}?>>Not EMI</option>
                                                                <option value="1" <?php if($userpro_approvenew == 1){echo 'selected';}?>>EMI</option>
                                                            </select>
                                                        </div>
                                                            
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            
                                                            <div class="form-group">
                                                                <lable>City of your job location</lable>
                                                                <select name="state" class="form-control">
<option value="" >City of your job location</option>
<option value="Banglore" <?php if($userpro_state == "Banglore"){echo "selected";} ?>>Banglore</option>
<option value="Pune" <?php if($userpro_state == "Pune"){echo "selected";} ?>>Pune</option>
<option value="Hyderabad" <?php if($userpro_state == "Hyderabad"){echo "selected";} ?>>Hyderabad</option>
<option value="Mumbai" <?php if($userpro_state == "Mumbai"){echo "selected";} ?>>Mumbai</option>
<option value="Gurgaon" <?php if($userpro_state == "Gurgaon"){echo "selected";} ?>>Gurgaon</option>
<option value="Chandigarh" <?php if($userpro_state == "Chandigarh"){echo "selected";} ?>>Chandigarh</option>
<option value="Surath" <?php if($userpro_state == "Surath"){echo "selected";} ?>>Surath</option>
<option value="Chennai" <?php if($userpro_state == "Chennai"){echo "selected";} ?>>Chennai</option>
<option value="Kolkata" <?php if($userpro_state == "Kolkata"){echo "selected";} ?>>Kolkata</option>
<option value="Delhi" <?php if($userpro_state == "Delhi"){echo "selected";} ?>>Delhi</option>
<option value="Ahmedabad" <?php if($userpro_state == "Ahmedabad"){echo "selected";} ?>>Ahmedabad</option>
<option value="Lucknow" <?php if($userpro_state == "Lucknow"){echo "selected";} ?>>Lucknow</option>
<option value="Noida" <?php if($userpro_state == "Noida"){echo "selected";} ?>>Noida</option>
<option value="Vishakapatnam" <?php if($userpro_state == "Vishakapatnam"){echo "selected";} ?>>Vishakapatnam</option>
<option value="Kochi" <?php if($userpro_state == "Kochi"){echo "selected";} ?>>Kochi</option>
<option value="Bhopal" <?php if($userpro_state == "Bhopal"){echo "selected";} ?>>Bhopal</option>
<option value="Indore" <?php if($userpro_state == "Indore"){echo "selected";} ?>>Indore</option>
<option value="Bhubaneswar" <?php if($userpro_state == "Bhubaneswar"){echo "selected";} ?>>Bhubaneswar</option>
<option value="Coimbatore" <?php if($userpro_state == "Coimbatore"){echo "selected";} ?>>Coimbatore</option>
<option value="Ghaziabad" <?php if($userpro_state == "Ghaziabad"){echo "selected";} ?>>Ghaziabad</option>
<option value="Mysuru" <?php if($userpro_state == "Mysuru"){echo "selected";} ?>>Mysuru</option>
<option value="Vijayawada" <?php if($userpro_state == "Vijayawada"){echo "selected";} ?>>Vijayawada</option>
<option value="Faridabad" <?php if($userpro_state == "Faridabad"){echo "selected";} ?>>Faridabad</option>
<option value="Mizoram" <?php if($userpro_state == "Mizoram"){echo "selected";} ?>>Mizoram</option>
</select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Date of Birth</label>
                                                                <div class="input-group" style="width:100% !important">
                                                                <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                                                                <input name="dob" style="width:100% !important; min-width:95%" type="date" class="form-control" placeholder="Date of Birth" value="<?=$userpro_dob?>">
                                                            </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>PAN <span style="color:red;" id="panmess"></span></lable>
                                                                <input name="pan" type="text" class="form-control" placeholder="PAN" pattern="^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$" id="panNumber" value="<?=$userpro_pan?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Monthly Net Salary</lable>
                                                                <input name="salary" type="number" class="form-control" placeholder="Monthly Net Salary" min="10000" value="<?=$userpro_salary?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Salary Status</lable>
                    <select name="salarystatus"  class="form-control">
<option value="">Are You</option>
<option value="Salaried" <?php if($userpro_salarystatus == "Salaried"){echo "selected";}?>>Salaried</option>
<option value="Self-Employed" <?php if($userpro_salarystatus == "Self-Employed"){echo "selected";}?>>Self-Employed</option>
<option value="Student" <?php if($userpro_salarystatus == "Student"){echo "selected";}?>>Student</option>
                    </select>
                                </div>
                                <div class="form-group">
                                    <lable>How you get your salary ?</lable>
                    <select name="get_salary"  class="form-control">
<option value="">How you get your salary ?</option>
<option value="bank transfer" <?php if($userpro_get_salary == "bank transfer"){echo "selected";}?>>bank transfer</option>
<option value="cash" <?php if($userpro_get_salary == "cash"){echo "selected";}?>>cash</option>
<option value="cheque" <?php if($userpro_get_salary == "cheque"){echo "selected";}?>>cheque</option>
                    </select>
                                </div>
                                                            <div class="form-group">
                                                                <lable>Gender</lable>
                                                                <input name="marital_status" type="text" class="form-control" value="<?=$userpro_marital_status;?>" placeholder="Gender" pattern="(?=.*[a-zA-Z]).{1,}">
                                                            </div>
                                                            <!--<div class="form-group">
                                                                <lable>Gender</lable>
              <input class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_marital_status;?>" style = "width:100%;" disabled>
                                                            </div><br><br>-->
                                                            
                                                            <div class="form-group">
                                                                <lable>Loan Limit</lable>
                                                                <input name="loan_limit" type="text" class="form-control" value="<?=$userpro_loan_limit?>" placeholder="Loan Limit">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Credit History</lable>
                                                                <input name="experience" type="text" class="form-control" value="<?=$userpro_experience?>" placeholder="Credit History">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Aadhar No</lable> <span style="color:red;" id="aadharmess"></span>
                                                                <input name="aadhar" type="text" class="form-control" value="<?=$userpro_aadhar?>" placeholder="Aadhar No">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Insta id</lable>
                                                                <input name="insta_id" type="text" class="form-control" value="<?=$userpro_insta_id?>" placeholder="Insta id">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Work From</lable>
                                                                <input name="work_from" type="text" class="form-control" value="<?=$userpro_work_from?>" placeholder="Work From">
                                                            </div><div class="form-group">
                                                                <lable>Average Salary</lable>
                                                                <input name="average_salary" type="text" class="form-control" value="<?=$userpro_average_salary?>" placeholder="Average Salary">
                                                            </div><div class="form-group">
                                                                <lable>Star Membership</lable>
                                                                <!--<input name="star_member" type="text" class="form-control" value="<?=$userpro_star_member?>" placeholder="Star Membership">-->
                                                                <select id="member" name="star_member" class="form-control">
                                                                <option value="" >Select Member Type</option>
                                                                <option value="2" <?php if($userpro_star_member == 2){echo 'selected';}?>>2 star</option>
                                                                <option value="3" <?php if($userpro_star_member == 3){echo 'selected';}?>>3 star</option>
                                                                <option value="4" <?php if($userpro_star_member == 4){echo 'selected';}?>>4 star</option>
                                                                <option value="5" <?php if($userpro_star_member == 5){echo 'selected';}?>>5 star</option>
                                                            </select>
                                                            </div><div class="form-group">
                                                                <lable>Father Name</lable>
                                                                <input type="text" name="father_name" class="form-control" value="<?=$userpro_father_name?>" placeholder="Father Name">
                                                            </div>
                                                            <!--<div class="form-group">-->
                                                            <!--    <lable>Total Emi</lable>-->
                                                            <!--    <input name="star_member" type="text" class="form-control" value="<?=$userpro_total_emi?>" placeholder="Star Membership">-->
                                                            <!--</div>-->
                                                            
                                                        <div class="form-group">
                                                            <lable>Account Manager</lable>
                                                            <select class="form-control" name="assign_account_manager">
                                                                <?php $am = towquery("SELECT * FROM `account_manager`");
                                                                while($amf = towfetch($am)){
                                                                ?>
                                                                <option value="<?=$amf['id']?>" <?php if($userpro_assign_account_manager == $amf['id']){echo 'selected';}?>><?=$amf['name']?></option>
                                                                <?php }?>
                                                            </select>
                                                        </div>    
                                                        <div class="form-group">
                                                            <lable>Recovery Officer</lable>
                                                            <select class="form-control" name="assign_recovery_officer">
                                                                <?php $am = towquery("SELECT * FROM `recovery_officer`");
                                                                while($amf = towfetch($am)){
                                                                ?>
                                                                <option value="<?=$amf['id']?>" <?php if($userpro_assign_recovery_officer == $amf['id']){echo 'selected';}?>><?=$amf['name']?></option>
                                                                <?php }?>
                                                            </select>
                                                        </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div class="payment-adress">
                                                                <p id="mess"></p>
                                                                <button type="submit" class="btn btn-primary waves-effect waves-light" name="pus" id="presub">Submit</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                    </div>
                                
                                </div>
                                <div class="product-tab-list tab-pane fade" id="additional">
                                                <!-- <form action="" method="post">-->
                                                     <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                 <lable>Last College Graduation Year</lable>
                                                            <input class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_graduation_year;?>" style = "width:100%;" disabled>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
              <lable>Gender</lable>
                                                            <input class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_marital_status;?>" style = "width:100%;" disabled>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                            <lable>College Name</lable>
                                                            <input class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_college_name;?>" style = "width:100%;" disabled>
                                                            </div>
                                                            <div class="form-group">
                                                                <br>
                                                                <br>
                            <lable>Frequently used Apps</lable>
                                                            <input class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_freq_app;?>" style = "width:100%;" disabled>
                                                            </div>
                                                          
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                              <div class="form-group">
                                                                  
                            <lable>Experience</lable>
                                                            <input class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_experience;?>" style = "width:100%;" disabled>
                                                            
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                                                                <lable>Residence Type</lable>
                                                            <input class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_residence_type;?>" style = "width:100%;" disabled>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                                                                <lable>Credit Card</lable>
                                                            <input class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_credit_card;?>" style = "width:100%;" disabled>
                                                            </div>
                                                            
                                                        </div>
                                                        
                                                    </div>
                                                    </form>
                                
                                </div>
                                <div class="product-tab-list tab-pane fade" id="INFORMATION">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
												<form action="" method="post" enctype="multipart/form-data">
												<table class="table table-striped">
    <thead>
      <tr>
        <th>Name</th>
        <th>Password</th>
        <th>Download</th>
        <th>Upload</th>
        <th>Password</th>
      </tr>
    </thead>
    <tbody>
        <?php $userpro_document_password = explode("#",$userpro_document_password);?>
        <?php $pan = explode("pan",$userpro_document_password[0]);?>
      <tr>
        <td>Pan</td>
        <td><?=$pan[1];?></td>
        <td><?php if(($userpro_conpanydocument !="no") and ($userpro_conpanydocument !="")){?><a href="../user/uploads/<?=$userpro_conpanydocument;?>" download><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input type="file" name="conpanydocument" class="form-control"></td>
        <td><input type="text" placeholder="Password..." name="pan_pass" class="form-control"></td>
      </tr>
      <?php $aadhar = explode("aadhar",$userpro_document_password[1]);?>
      <?php $aadhar2 = explode("aadha2",$userpro_document_password[2]);?>
      <?php $aadharfile = explode("#",$userpro_personaldocument);?>
      <tr>
        <td>Aadhar front side</td>
        <td><?=$aadhar[1];?></td>
        <td><?php if(($aadharfile[0] !="no") and ($aadharfile[0] !="")){?><a href="../user/uploads/<?=$aadharfile[0]?>" download><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input type="file" name="personaldocument[]" class="form-control"></td>
        <td><input type="text" placeholder="Password..." name="aadhar_pass" class="form-control"></td>
      </tr>
      <tr>
        <td>Aadhar Back side</td>
        <td><?=$aadhar2[1];?></td>
        <td><?php if(($aadharfile[1] !="no") and ($aadharfile[1] !="")){?><a href="../user/uploads/<?=$aadharfile[1]?>" download><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input type="file" name="personaldocument[]" class="form-control"></td>
        <td><input type="text" placeholder="Password..." name="aadhar_pass2" class="form-control"></td>
      </tr>
       <?php $salary = explode("salary",$userpro_document_password[3]);?>
      <tr>
        <td>Salary Document</td>
        <td><?=$salary[1];?></td>
        <td><?php if(($userpro_salarydocument !="no") and ($userpro_salarydocument !="")){?><a href="../user/uploads/<?=$userpro_salarydocument;?>" download><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input type="file" name="salarydocument" class="form-control"></td>
        <td><input type="text" placeholder="Password..." name="salary_pass" class="form-control"></td>
      </tr>
      <?php $bank = explode("bank",$userpro_document_password[4]);?>
      <tr>
        <td>Bank Document</td>
        <td><?=$bank[1];?></td>
        <td><?php if(($userpro_bankdocument !="no") and ($userpro_bankdocument !="")){?><a href="../user/uploads/<?=$userpro_bankdocument;?>" download><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input type="file" name="bankdocument" class="form-control"></td>
        <td><input type="text" placeholder="Password..." name="bank_pass" class="form-control"></td>
      </tr>
      <?php $bank2 = explode("bank2",$userpro_document_password[6]);?>
      <tr>
        <td>Bank Document2</td>
        <td><?=$bank2[1];?></td>
        <td><?php if(($userpro_bankdocument2 !="no") and ($userpro_bankdocument2 !="")){?><a href="../user/uploads/<?=$userpro_bankdocument2;?>" download><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input type="file" name="bankdocument2" class="form-control"></td>
        <td><input type="text" placeholder="Password..." name="bank_pass2" class="form-control"></td>
      </tr>
      <?php $bank3 = explode("bank3",$userpro_document_password[7]);?>
      <tr>
        <td>Bank Document3</td>
        <td><?=$bank3[1];?></td>
        <td><?php if(($userpro_bankdocument3 !="no") and ($userpro_bankdocument3 !="")){?><a href="../user/uploads/<?=$userpro_bankdocument3;?>" download><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input type="file" name="bankdocument3" class="form-control"></td>
        <td><input type="text" placeholder="Password..." name="bank_pass3" class="form-control"></td>
      </tr>
      <?php $address = explode("address",$userpro_document_password[5]);?>
      <tr>
        <td>Address Document</td>
        <td><?=$address[1];?></td>
        <td><?php if(($userpro_addressdocument !="no") and ($userpro_addressdocument !="")){?><a href="../user/uploads/<?=$userpro_addressdocument;?>" download><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input type="file" name="addressdocument" class="form-control"></td>
        <td><input type="text" placeholder="Password..." name="address_pass" class="form-control"></td>
      </tr>
      <tr>
        <td>signature </td>
        <td>no password</td>
        <td><?php if(($userpro_signature !="no") and ($userpro_signature !="")){?><a href="../user/uploads/<?=$userpro_signature;?>" download><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input type="file" name="signature" class="form-control"></td>
      </tr>
    </tbody>
  </table>
  <center><p id="check"></p>
															<button type="submit" class="btn btn-primary waves-effect waves-light" name="document">Submit</button></center>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="Bank">
                                   <form action="" method="post"> <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                                                <lable>Bank Name</lable>
                                                                <input name="bank_name" type="text" class="form-control" placeholder="Bank Name" value="<?=$userpro_bank_name?>">
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <lable>Branch Name</lable>
                                                                <input name="branch_name" type="text" class="form-control" placeholder="Branch Name" value="<?=$userpro_branch_name?>">
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <lable>IFSC Code</lable>
                                                            <input name="ifsc" type="text" placeholder="IFSC Code" class="form-control" pattern="(?=.*[a-zA-Z]).{11}" title="IFSC must be 11 " style="text-transform:uppercase" value="<?=$userpro_ifsc?>">
                                                            </div>
                                                            
                                                          
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                             <div class="form-group">
                                                                <lable>Account Number <span style="color:red;" id="acnomess"></span></lable>
                                                            <input name="account_no" pattern="[0-9].{7,}" title="Account Number must be 8 or more " type="text" placeholder="Account Number" class="form-control" value="<?=$userpro_account_no?>">
                                                            </div>
                                                             
                                                              <div class="form-group">
                                                                  <lable>Account Type</lable>
                                                                <select class="form-control" name="account_type" value="<?=$userpro_account_type?>">
                                
                                                                    <option <?php if($userpro_account_type == "saving"){echo "selected";} ?> value="saving">Saving</option>
                                                                    <option <?php if($userpro_account_type == "current"){echo "selected";} ?> value="current">Current</option>
                            
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Account Name</lable>
                                                                <input name="account_name" type="text" class="form-control" placeholder="Account Name" value="<?=$userpro_account_name?>">
                                                            </div>
                                                            
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <center><button class="btn btn-primary" type="submit">Submit</button></center>
                                                        </div>
                                                    </div></form>
                                    <div>
                                <table class="table table-bordered">
        <thead class="thead-light">
            <tr>                        
                                        <th>Date</th> 
                                        <th>Bank Name</th>        
                                        <th>Account Number</th>        
                                        <th>Branch Name</th>  
                                        <th>Account Type</th>  
                                        <th>IFSC Code</th>  
                                        <th>Account Name</th>  
                                        <th>Action</th>  
                                        <th>Verify</th>  
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php
                                   if(isset($_POST['bank_detail_update'])){
                                       $ext = towrealarray($_POST);
                                       towquery("UPDATE `user_bank` SET `ac_name`='".$ext['ac_name']."',`ac_no`='".$ext['ac_no']."',`ifsc_code`='".$ext['ifsc_code']."',`ac_type`='".$ext['ac_type']."',`branch_name`='".$ext['branch_name']."',`bank_name`='".$ext['bank_name']."' WHERE `id`=".$ext['bank_id']);
                                   }
                                   $ref_data = towquery("SELECT * FROM user_bank WHERE uid='$userpro_id' ORDER BY id DESC"); 
                                   while($bank_fetch = towfetch($ref_data)){
                                   extract($bank_fetch,EXTR_PREFIX_ALL,'ub');
                                   ?>
                                    <tr>
                                        <form method="post">
                                        <td><?=$ub_date?></td><input type="hidden" value="<?=$ub_id;?>" name="bank_id">
                                        <td><input type="text" name="bank_name" value="<?=$ub_bank_name;?>" class="form-control"></td>
                                        <td><input type="text" name="ac_no" value="<?=$ub_ac_no?>" class="form-control"></td>
                                        <td><input type="text" name="branch_name" value="<?=$ub_branch_name?>" class="form-control"></td>
                                        <td><input type="text" name="ac_type" value="<?=$ub_ac_type?>" class="form-control"></td>
                                        <td><input type="text" name="ifsc_code" value="<?=$ub_ifsc_code?>" class="form-control"></td>
                                        <td><input type="text" name="ac_name" value="<?=$ub_ac_name?>" class="form-control"></td>
                                        <td><button type="submit" class="btn btn-success" name="bank_detail_update">Save</button></td>
                                        </form>
                                        <td><?php if($ub_verify == 0){ ?>
                                        <a href="/bankverify.php?bank_id=<?=$ub_id;?>" class="btn btn-success">verify</a>
                                        <?php }else{ ?>
                                        <a href="#" class="btn btn-success">verified</a>
                                        <?php }?></td>
                                        
                                    </tr>
                                    <?php } ?>
                                </tbody>
    </table>                           
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="Reference">
                                    <div class="table-responsive">
        <form action="" method="post" id="referralForm">
    <table class="table table-bordered" id="referralTable">
        <thead class="thead-light">
            <tr>                
                <th>Name</th>
                <th>Phone</th>
                <th>Relation</th>
                <th>Valid</th>
                <!--<th>Action</th>-->
            </tr>
        </thead>
        <tbody>
            <?php
                $existing_refs = towquery("SELECT * FROM user_referrals WHERE uid='$userpro_id' ORDER BY id ASC");
    
                while ($ref = towfetch($existing_refs)) {
                    ?>
                    <tr>
                        <td><input type="text" class="form-control" name="ref[<?= $ref['id'] ?>][name]" value="<?= $ref['name'] ?>"></td>
                        <td><input type="text" class="form-control" name="ref[<?= $ref['id'] ?>][phone]" value="<?= $ref['phone'] ?>"></td>
                        <td><input type="text" class="form-control" name="ref[<?= $ref['id'] ?>][relation]" value="<?= $ref['relation'] ?>"></td>
                        <td><input type="text" class="form-control" name="ref[<?= $ref['id'] ?>][status]" value="<?= $ref['status'] ?>"></td>
                        <!--<td>-->
                            <!--<button type="submit" name="delete_ref" value="<?= $ref['id'] ?>" class="btn btn-danger">Delete</button>-->
                        <!--</td>-->
                    </tr>
                    <?php
                }
            ?>
        </tbody>
    </table>
    <button type="button" id="addReferral" class="btn btn-secondary">Add Referral</button>
    <input type="submit" name="submit_refs" class="btn btn-success" value="Submit">
</form>
<script>
document.getElementById('addReferral').addEventListener('click', function() {
    const table = document.getElementById('referralTable').getElementsByTagName('tbody')[0];
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td><input type="text" class="form-control" name="ref[new][name]"></td>
        <td><input type="text" class="form-control" name="ref[new][phone]"></td>
        <td><input type="text" class="form-control" name="ref[new][relation]"></td>
        <td><input type="text" class="form-control" name="ref[new][status]"></td>
        <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
    `;
    table.appendChild(newRow);

    // Attach event listener to the remove button
    newRow.querySelector('.remove-row').addEventListener('click', function() {
        newRow.remove();
    });
});

// Add event listener to existing remove buttons
document.querySelectorAll('.remove-row').forEach(button => {
    button.addEventListener('click', function() {
        button.closest('tr').remove();
    });
});
</script>
    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="login_data">
                                    <div class="table-responsive">
        <table class="table table-bordered">
        <thead class="thead-light">
            <tr>                
                                        <th>Cus ID</th>        
                                        <th>Browser</th>        
                                        <th>IP Address</th>        
                                        <th>Login Time</th>    
                                        <th>Mobile handset uid</th>    
                                        <th>Latitude</th>    
                                        <th>Longitude</th>    
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php $login_data = towquery("SELECT * FROM user_login_details WHERE uid='$userpro_id' ORDER BY id DESC LIMIT 0, 4"); while($login_fetch = towfetch($login_data)){ extract($login_fetch,EXTR_PREFIX_ALL,"users"); ?>
                                    <tr>
                                        <td data-title="CID"><?=$users_uid?></td>
                                        <td data-title="Name"><?=$users_browser?></td>
                                        <td data-title="Email"><?=$users_ip_address?></td>
                                        <td data-title="Mobile"><?=$users_login_time?></td>
                                        <td data-title="Mobile"><?=$users_mobile_handset_uid?></td>
                                        <td data-title="Mobile"><?=$users_latitude?></td>
                                        <td data-title="Mobile"><?=$users_longitude?></td>
                                    </tr>
                                <?php } ?>
            </tbody>
    </table>
    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="profile_detail">
                                    <div class="table-responsive">
        <table class="table table-bordered">
        <thead class="thead-light">
            <tr>                
                                        <th>CLID</th>        
                                        <th>Name</th>        
                                        <th>Email</th>        
                                        <th>Mobile</th>    
                                        <th>Status</th>    
                                        <th>Actions</th>     
                                    </tr>
        </thead>
        <tbody id="searchtable">
            </tbody>
    </table>
							
    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="user_contact">
                                    <div class="row">
                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                            <?php
                                                        $user_contact = towquery("SELECT * FROM `user_contact_details` WHERE uid=$userpro_id");
                                                        $user_contact = towfetch($user_contact);
                                                        $user_contact = $user_contact['user_contact']
                                                            ?>
                                                            <form action="" method="post">
                                                              <div class="form-group">
                                                            <textarea class="form-control col-xs-12 col-sm-12 pull-left" name="user_contact"><?=$user_contact;?></textarea>
                                                            </div>
                                                            <br>
                                                            <br>
                                                            <input type="submit" name="user_conta" class="btn btn-sucess">
                                                            </form>
                                                        </div>
                                                        
                                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="contact">
                                    <div class="row">
                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                            <?php
                                                        $contact = towquery("SELECT * FROM `user_contact_details` WHERE uid=$userpro_id");
                                                        $contact = towfetch($contact);
                                                        $contact = $contact['contact']
                                                            ?>
                                                            <form action="" method="post">
                                                              <div class="form-group">
                                                            <textarea class="form-control col-xs-12 col-sm-12 pull-left" name="contact"><?=$contact;?></textarea>
                                                            </div>
                                                            <br>
                                                            <br>
                                                            <input type="submit" name="conta" class="btn btn-sucess">
                                                            </form>
                                                        </div>
                                                        
                                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="loan">
                                    <div class="table-responsive">
        <table class="table table-bordered" >
        <thead class="thead-light">
            <tr>                
                                        <th>Loan ID</th>        
                                        <th>Req Amount</th>        
                                        <th>Time Period</th>
                                        <th>P.Fee</th>
                                        <th>Service Charge</th>
                                        <th>Total Amount</th>
                                        <th>Apply Date</th>
                                        <th>Reason</th>
                                        <th>Status</th>        
                                        <th>Status Date</th>    
                                        <th>Action</th>
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php $loan_data = towquery("SELECT * FROM loan_apply WHERE uid='$userpro_id' ORDER BY id DESC"); while($loan_fetch = towfetch($loan_data)){ extract($loan_fetch,EXTR_PREFIX_ALL,"users");
                                   $totalamount = $users_amount + $users_processing_fees + $users_service_charge;
                                   ?>
                                    <form action="" method="post"><tr>
                                        <td>CLL<?=$users_id?></td>
                                        <td><input type="text" class="form-control" name="amount" value="<?=$users_amount?>"></td>
                                        <td><input type="text" class="form-control" name="days" value="<?=$users_days?>"></td>
                                        <td><input type="text" class="form-control" name="processing_fees" value="<?=$users_processing_fees?>"></td>
                                        <td><?=$users_service_charge?></td>
                                        <td><?=$totalamount?></td>
                                        <td><?=$users_apply_date?></td>
                                        <td><?=$users_reason?></td>
                                        <td><?php if(($users_status == 'pending')){echo "applied";}else{echo $users_status;} ?></td>
                                        <td><?=$users_status_date?><input type="hidden" name="cllid" value="<?=$users_id;?>"></td>
                                        <td>
                                        <input type="submit" class="btn btn-primary" name="loandata" value="save">
                                        <?php if($users_is_emi == 0){?>
                                        <br><a href="https://creditlab.in/zxc/?url=https://creditlab.in/admin/sloan_agreement.php?id=<?=$users_id?>&email=<?=$userpro_email?>&pid=<?=$userpro_id?>" class="btn btn-success">Send</a>
                                        <br><a href="sloan_agreement.php?id=<?=$users_id?>" class="btn btn-success">View</a>
                                        <?php }else{?>
                                        <br><a href="https://creditlab.in/zxc/?url=https://creditlab.in/admin/loan_agreement.php?id=<?=$users_id?>&email="<?=$userpro_email?> class="btn btn-success">Send</a>
                                        <br><a href="loan_agreement.php?id=<?=$users_id?>" class="btn btn-success">View</a>
                                        <?php }?>
                                        </td>
                                    </tr></form>
                                <?php } ?>
            </tbody>
    </table>
    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="oldloan">
                                    <div class="table-responsive">
        <table class="table table-bordered" >
        <thead class="thead-light">
            <tr>                
                                        <th>Loan ID</th>        
                                        <th>Processed Date</th>        
                                        <th>Principal Amount</th>        
                                        <th>Processed Amount</th>        
                                        <th>Exhausted Period</th>        
                                        <th>P.fee</th>
                                        <th>gst</th>
                                        <th>O.fee</th>
                                        <th>Interest</th>        
                                        <th>Penalty Charge</th>        
                                        <th>Due date</th>        
                                        <th>Total Amount</th>

                                        <th>EMI Date</th>  
                                        <th>Amount to Repay (inclusive Gst)</th>
                                        <th>Pre close</th>

                                        <th>Status Log</th>       
                                        <th>Paid Amount</th>       
                                        <th>cleared date</th>    
                                        <th>DPD</th>    
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php
                                   function calcPenality($loan_amountc, $percentage, $due_date, $usersd_lid,$pen_day_gap,$t,$fpen = 0){
                                        $today = strtotime(date('Y-m-d'));
                                        $due_date = strtotime($due_date);
                                    
                                        if($due_date < $today){
                                            $check = towquery("SELECT * FROM `transaction_details` WHERE `cllid`='".$usersd_lid."' AND `transaction_flow`='firstemi'");
                                            if((townum($check) == 1) and ($t == 1)){
                                                return 0;
                                            }else{
                                                $penalitydays = round(($today - $due_date) / (60 * 60 * 24)) - 1;
                                                $penality = (($loan_amountc * $percentage) /100)*3;
                                    
                                                if($penalitydays >= 29){
                                                    $penality += (($loan_amountc * $percentage) * 0.004)*min($penalitydays, 29);
                                                    $penalitydays = max($penalitydays - 29, 0);
                                                    if($penalitydays > 0){
                                                        $penality += (($loan_amountc * $percentage) * 0.0035)*min($penalitydays, 60);
                                                        $penalitydays = max($penalitydays - 60, 0);
                                                    }
                                                }
                                                else{
                                                    $penality += (($loan_amountc * $percentage) * 0.004)*$penalitydays;
                                                }
                                            }
                                        }else{
                                            $penality = 0;
                                        }
                                        if($fpen){
                                        $fpen +=$penality;
                                        towquery("UPDATE `loan` SET `penality_charge`='$fpen',`exhausted_period`='$pen_day_gap' WHERE `lid`='".$usersd_lid."'");
                                        }
                                        return $penality;
                                    }
$loan_data = towquery("SELECT * FROM loan WHERE uid='$userpro_id' ORDER BY id DESC");
                                   while($loan_fetch = towfetch($loan_data)){ extract($loan_fetch,EXTR_PREFIX_ALL,"usersd");
                                   if(empty($usersd_penality_charge)){
                                       $usersd_penality_charge = 0;
                                   }
    $lof = towfetch(towquery("SELECT * FROM loan WHERE lid=".$usersd_lid));
    $loan_amountc = $lof['processed_amount'] + $lof['p_fee'] + $lof['origination_fee'];
    $dis_date = date('Y-m-d', strtotime(date_create($lof['processed_date'])->format("Y-m-d") . " -1 day"));
    $di = strtotime($dis_date);
    if($lof['status_log'] == 'cleared'){
        $sa = strtotime(date('Y-m-d'));
    }else{
        $sa = strtotime($lof['cleard_date']);
    }
        $datediff = $sa - $di;
        $day_gap = round($datediff / (60 * 60 * 24));
            if($usersd_is_emi==1){
                $femi_date = date('Y-m-d', strtotime($dis_date . " +30 day"));
                $semi_date = date('Y-m-d', strtotime($femi_date . " +35 day"));
                $fe = strtotime($femi_date);
                $se = strtotime($semi_date);
                $femi_amount = ((($loan_amountc/100) * 70) + ($loan_amountc*0.001*round(($fe-$di) / (60 * 60 * 24)))) + (($loan_amountc/100) * 2);
                $semi_amount = ((($loan_amountc/100) * 30) + $loan_amountc * 0.001 * round(($se-$fe) / (60 * 60 * 24))) + (($loan_amountc/100) * 2);
                $preclose = (($loan_amountc) + ($loan_amountc / 100) * 4) + (($loan_amountc/100) * 2);
            }else{
                
            }
?>
                                    <form action="" method="post"><tr>
                                        <td>CLL<?=$usersd_lid?></td>
                                        <td><?=$usersd_processed_date?></td>
                                        <td>
                                        <?php $azxs = (0.12*$papay)/1.18;
                                        $papay = ($usersd_processed_amount + $usersd_p_fee + ($usersd_p_fee*0.18));
                                        echo $papay;
                                        ?></td>
                                        <td><?=$usersd_processed_amount?></td>
                                        <td><?=$usersd_exhausted_period;?> Days</td>
                                        <td><?php if($usersd_is_emi==1){echo ceil((5*$azxs)/12);}else{echo $usersd_p_fee;}?></td>
                                        <td><?php if($usersd_is_emi==1){echo ceil(0.18*($azxs));}else{echo ceil(0.18*($usersd_p_fee));}?></td>
                                        <td><?php if($usersd_is_emi==1){echo ceil($azxs-((5*$azxs)/12));}else{echo $usersd_origination_fee;}?></td>
                                        <?php if($usersd_is_emi==0){ ?>
                                        <td><?=$usersd_service_charge;?></td>
                                            <td><?=$usersd_penality_charge?></td>
                                            <td><?=date('Y-m-d', strtotime($usersd_processed_date . ' +29 day'))?></td>
                                            <td><?=ceil($papay+$usersd_service_charge+$usersd_penality_charge)?></td>
                                        <?php } if($usersd_is_emi==1){
                                        ?>
                                        <td></td>
                                        <td><?=$usersd_penality_charge?></td>
                                        <td></td>
                                        <td></td>
                                        <td><?=$femi_date?> <br> <?=$semi_date?></td>  
<td style="<?php if(($day_gap > 90) and ($usersd_status_log == 'account manager')){echo "background:red;color:#fff;";}elseif(($day_gap > 90) and ($usersd_status_log == 'cleared')){echo "background:orange;color:#fff;";}elseif(($day_gap > 65) and ($usersd_status_log == 'cleared')){echo "background:yellow;color:#fff;";}elseif(($day_gap < 65) and ($usersd_status_log == 'cleared')){echo "background:green;color:#fff;";} ?>">Rs. <?=$femi_amount?> <?php if($lof['femi']){echo '✔️';}?><br>
    <?php if($usersd_status_log == 'account manager'){echo $fpen = calcPenality($loan_amountc, 0.70, date_create($femi_date)->format("Y-m-d"), $usersd_lid,$day_gap,1);}?> <span style="font-size:11px;">(Penalty)</span>
    <br> Rs. <?=$semi_amount?> <?php if($lof['semi']){echo '✔️';}?><br>
    <?php if($usersd_status_log == 'account manager'){echo calcPenality($loan_amountc, 0.355, date_create($semi_date)->format("Y-m-d"), $usersd_lid,$day_gap,2,$fpen);}?> <span style="font-size:11px;">(Penalty)</span>
</td>
                                        <td>Rs. <?=$preclose?> <?php $tprecol = townum(towquery("SELECT id FROM `transaction_details` WHERE cllid='".$usersd_lid."' AND transaction_flow = 'preclose'")); if($tprecol > 0){echo '✔️';}?></td>
                                        <?php }else{ ?> 
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <?php }?>
                                        <td <?php if($usersd_action == "no data"){ ?>class="bg-success" <?php }?>><?=$usersd_status_log?></td>
                                        <td><?php $paid_amt = towquery("SELECT SUM(transaction_amount) AS paid_amt FROM `transaction_details` WHERE cllid='".$usersd_lid."' AND transaction_flow IN ('firstemi','part','full','secondemi','preclose')"); $paid_amtf = towfetch($paid_amt); echo $paid_amtf['paid_amt'] ? $paid_amtf['paid_amt'] : 0;?></td>
                                        <td><?=$usersd_cleard_date?></td>
                                        <td><?php $dpd = $usersd_exhausted_period-30; if($dpd > 0){echo $dpd;}else{echo 0;} ?></td>
                                    </tr></form>
                                <?php } ?>
            </tbody>
    </table>
    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="transaction_details">
                                    <div class="table-responsive">
        <table class="table table-bordered" >
        <thead class="thead-light">
            <tr>                
                                        <th>CLL ID</th>        
                                        <th>Transaction Number</th>
                                        <th>Transaction Date</th>
                                        <th>Transaction Amount</th>
                                        <th>Transaction Flow</th>
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php $loan_data = towquery("SELECT * FROM transaction_details WHERE uid='$userpro_id' ORDER BY id DESC"); while($loan_fetch = towfetch($loan_data)){ extract($loan_fetch,EXTR_PREFIX_ALL,"users"); ?>
                                    <tr>
                                        <td>CLL<?=$users_cllid?></td>
                                        <td><?=$users_transaction_number?> <a href="edit_loan.php?cllid=<?=$users_cllid?>" class="btn btn-submit">Edit</a></td>
                                        <td><?=$users_transaction_date?></td>
                                        <td><?=$users_transaction_amount?></td>
                                        <td><?=$users_transaction_flow?></td>
                                    </tr>
                                <?php } ?>
                                <form action="" method="post"><tr>
                                        <td><input type="text" name="cllid"></td>
                                        <td><input type="text" name="transaction_number"></td>
                                        <td><input type="text" name="transaction_date"></td>
                                        <td><input type="text" name="transaction_amount"></td>
                                        <td><select name="transaction_flow">
                                            <option value="R4C To Customer">R4C To Customer</option>
                                            <option value="firstemi">First EMI</option>
                                            <option value="secondemi">Second EMI</option>
                                            <option value="full">Full payment</option>
                                            <option value="renew">Renew Payment</option>
                                            <option value="part">Part Payment</option>
                                        </select></td>
                                    </tr>
                                    <button class="btn btn-" name="transaction">Save</button></form>
            </tbody>
    </table>
    </div>
                                </div>
                                 <div class="product-tab-list tab-pane fade" id="Validation">
                                    <div class="row">
                                        <form action="" method="post"><div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                            <select name="valid_status" id="mySelect" onchange="myFunction();">
                                                <option value="">select</option>
                                                <option value="Process">Process</option>
                                                <option value="Not Process">Not Process</option>
                                                <option value="Re Process">Re Process</option>
                                                <option value="cancel">cancel</option>
                                                <option value="unhold">unhold</option>
                                            </select>
                                            <div id="validcheck"></div>
                                            <textarea style="color: red" class="form-control" name="validation" id="text" rows="15"><?=$userpro_validation;?></textarea>
                                            <br>
                                            <center><a onclick="aaaa()">Check</a> <input type="checkbox" name="sendmail" value="ok"> Mail <button class="btn btn-primary" onclick="aaaa()" type="submit">Submit</button></center>
                                        </div></form>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="follow_up">
                                    <div class="row">
                                        <form action="" method="post"><div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                            Loan ID : <select name="follow_up_id">
                                                <?php 
                                                $date = date('Y-m-d');
                                                $loan_data = towquery("SELECT * FROM loan_apply WHERE uid=$userpro_id AND status='account manager' AND follow_up_date <= '$date' ORDER BY id ASC"); while($loan_fetch = towfetch($loan_data)){ extract($loan_fetch,EXTR_PREFIX_ALL,"follow");
                                                echo "<option value='".$follow_id."'>CLL".$follow_id."</option>";
                                                }
                                                ?>
                                            </select> &nbsp;
                                            Follow Up Date : <input type="date" name="follow_up_date" id="date"> 
                                            Check for after 25 days <input type="checkbox" style="height:11px;" onchange="adddate('<?=date('Y-m-d', strtotime($date. ' + 25 days'))?>')"><br><br>
                                            <div class="row">
                                            <div class="col-md-6">
                                            <select onchange="select_folomess();" id="select_folomesss" class="form-control">
                                                <option value="Shall pay by EOD">Shall pay by EOD</option>
                                                <option value="Shall pay tomorrow">Shall pay tomorrow</option>
                                                <option value="Shall pay on time">Shall pay on time</option>
                                                <option value="Need extension">Need extension</option>
                                                <option value="Called back">Called back</option>
                                                <option value="Call not answering">Call not answering</option>
                                                <option value="Cut the call">Cut the call</option>
                                                <option value="Mobile switched off">Mobile switched off</option>
                                                <option value="Out of coverage area">Out of coverage area</option>
                                                <option value="Number not working">Number not working</option>
                                                <option value="Wrong no">Wrong no</option>
                                                <option value="Call lifted by others">Call lifted by others</option>
                                                <option value="Call answered but no proper response">Call answered but no proper response</option>
                                                <option value="Sell pay part payment">Sell pay part payment</option>
                                                <option value="Sell renew the loan">Sell renew the loan</option>
                                                <option value="SMS Sent by mobile">SMS Sent by mobile</option>
                                                <option value="Already Paid">Already Paid</option>
                                                <option value="Customer died">Customer died</option>
                                                <option value="Asking for settlement">Asking for settlement</option>
                                                <option value="Wantedly not repaying the loan">Wantedly not repaying the loan</option>
                                            </select>
                                            </div>
                                            <div class="col-md-6">
                                            <span id="amount"></span>
                                            </div>
                                            </div>
                                            <br><br>
                                            <?php $fa = towquery("SELECT follow_up_mess FROM loan WHERE uid='$id'");
                                            while($f = towfetch($fa)){
                                            if(!empty($f['follow_up_mess'])){
                                            ?>
                                            <p style="border:solid 1px; padding:2px;"><?=$f['follow_up_mess'];?></p>
                                            <?php }} ?>
                                            <textarea style="color: red" class="form-control" id="follow_up_mess" name="follow_up_mess" id="text" rows="3"></textarea>
                                            <br>
                                            <center><button class="btn btn-primary" type="submit">Submit</button></center>
                                        </div></form>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="note">
                                    <div class="row">
                                        <form action="" method="post"><div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                            <br><br>
                                            <?php $fa = towquery("SELECT comment FROM user WHERE id='$id'");
                                            while($f = towfetch($fa)){
                                            if(!empty($f['comment'])){
                                            ?>
                                            <p style="border:solid 1px; padding:2px;"><?=$f['comment'];?></p>
                                            <?php }} ?>
                                            <textarea style="color: red" class="form-control" name="comment" id="comment" id="text" rows="3"></textarea>
                                            <br>
                                            <center><button class="btn btn-primary" type="submit">Submit</button></center>
                                        </div></form>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="sms">
                                    <div class="row">
                                        <form action="" method="post"><div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                            <select class="form-control" name="sms_num">
                                                <option value="<?=$userpro_mobile?>"><?=$userpro_mobile?></option>
                                                <option value="<?=$userpro_altmobile?>"><?=$userpro_altmobile?></option>
                                            </select>
                                            <br>
                                            <span>Message</span>
                                            <textarea class="form-control" name="sms" rows="3"></textarea>
                                            <br>
                                            <center><button class="btn btn-primary" type="submit">Submit</button></center>
                                        </div></form>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="cibil_analysis">
                                    <div class="row">
                                        <form action="" method="post"><div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                            <select class="form-control" name="sms_num">
                                                <option value="<?=$userpro_mobile?>"><?=$userpro_mobile?></option>
                                                <option value="<?=$userpro_altmobile?>"><?=$userpro_altmobile?></option>
                                            </select>
                                            <br>
                                            <span>Message</span>
                                            <textarea class="form-control" name="sms" rows="3"></textarea>
                                            <br>
                                            <center><button class="btn btn-primary" type="submit">Submit</button></center>
                                        </div></form>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="pan_analysis">
                                    <div class="row">
                                        <form action="" method="post"><div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                            <select class="form-control" name="sms_num">
                                                <option value="<?=$userpro_mobile?>"><?=$userpro_mobile?></option>
                                                <option value="<?=$userpro_altmobile?>"><?=$userpro_altmobile?></option>
                                            </select>
                                            <br>
                                            <span>Message</span>
                                            <textarea class="form-control" name="sms" rows="3"></textarea>
                                            <br>
                                            <center><button class="btn btn-primary" type="submit">Submit</button></center>
                                        </div></form>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="adhar_analysis">
                                    <div class="row">
                                        <form action="" method="post"><div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                            <select class="form-control" name="sms_num">
                                                <option value="<?=$userpro_mobile?>"><?=$userpro_mobile?></option>
                                                <option value="<?=$userpro_altmobile?>"><?=$userpro_altmobile?></option>
                                            </select>
                                            <br>
                                            <span>Message</span>
                                            <textarea class="form-control" name="sms" rows="3"></textarea>
                                            <br>
                                            <center><button class="btn btn-primary" type="submit">Submit</button></center>
                                        </div></form>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="bank_analysis">
                                    <div class="row">
                                        <form action="" method="post"><div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                            <select class="form-control" name="sms_num">
                                                <option value="<?=$userpro_mobile?>"><?=$userpro_mobile?></option>
                                                <option value="<?=$userpro_altmobile?>"><?=$userpro_altmobile?></option>
                                            </select>
                                            <br>
                                            <span>Message</span>
                                            <textarea class="form-control" name="sms" rows="3"></textarea>
                                            <br>
                                            <center><button class="btn btn-primary" type="submit">Submit</button></center>
                                        </div></form>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="mail">
                                    <div class="row">
                                        <form action="" method="post"><div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                            <select class="form-control" name="smail">
                                                <option value="<?=$userpro_email?>"><?=$userpro_email?></option>
                                                <option value="<?=$userpro_altemail?>"><?=$userpro_altemail?></option>
                                            </select>
                                            <br>
                                            <span>Message</span>
                                            <textarea class="form-control" name="sms" rows="3"></textarea>
                                            <br>
                                            <center><button class="btn btn-primary" type="submit">Submit</button></center>
                                        </div></form>
                                    </div>
                                </div>
                                <!--manger-->
                                <div class="product-tab-list tab-pane fade" id="manager">
                                   <form action="" method="post"> <div class="row">
                                                        

                                                        <div class="col-md-3">
                                                             
                                                             
                                                              <div class="form-group">
                                                                  <lable>Loan Id</lable>
                                                                <select name="loan_id" class="form-control">
                                                                    <?php 
                                                                    $date = date('Y-m-d');
                                                                    $loan_data = towquery("SELECT * FROM loan_apply WHERE uid=$userpro_id AND status='account manager' ORDER BY id ASC"); while($loan_fetch = towfetch($loan_data)){ extract($loan_fetch,EXTR_PREFIX_ALL,"follow");
                                                                        echo "<option value='".$follow_id."'>CLL".$follow_id."</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                                 </div>
                                                            </div>
                                                             <div class="col-md-3">
                                                              <div class="form-group">
                                                                  <lable>customer response </lable>
                                                                <select class="form-control" name="customer_response" value="<?=$userpro_account_type?>">
                                                                    <option value="shall pay by eod">shall pay by eod</option>
                                                                    <option value="shall pay tomorrow">shall pay tomorrow</option>
                                                                    <option value="shall pay on time">shall pay on time</option>
                                                                    <option value="need extension">need extension</option>
                                                                    <option value="called back">called back</option>
                                                                    <option value="call not answering">call not answering</option>
                                                                    <option value="cutting the call">cutting the call</option>
                                                                    <option value="switched off">switched off</option>
                                                                    <option value="out of coverage">out of coverage</option>
                                                                    <option value="number not working">number not working</option>
                                                                    <option value="wrong number">wrong number</option>
                                                                    <option value="call answered but no proper response">call answered but no proper response</option>
                                                                    <option value="shall pay part payment">shall pay part payment</option>
                                                                    <option value="shall renew the loan">shall renew the loan </option>
                                                                    <option value="sms sent by mobile">sms sent by mobile</option>
                                                                    <option value="already paid">already paid</option>
                                                                </select>
                                                            </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                            <div class="form-group">
                                                                <lable>commitment date </lable>
                                                                <input name="commitment_date" type="date" class="form-control">
                                                            </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                            <div class="form-group">
                                                                <lable>commitment text </lable>
                                                                <input name="commitment_text" type="text" class="form-control">
                                                            </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                            <div class="form-group">
                                                                <lable>type</lable>
                                                                <select class="form-control" name="default_type">
                                                                    <option value="Responding">Responding</option>
                                                                    <option value="Situational default">Situational default</option>
                                                                    <option value="intentional default"> intentional default </option>
                                                                    <option value="connectivity issue">connectivity issue</option>
                                                                </select>
                                                            </div>
                                                            </div>
                                                            
                                                            
                                                       
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <center><button class="btn btn-primary" style="margin-bottom:20px" type="submit" name="loan_acc_man">Submit</button></center>
                                                        </div>
                                                       
                                                    </div></form>
                                    <div>
                                <table class="table table-bordered">
        <thead class="thead-light">
            <tr>                        
                                        <th>S.No</th> 
                                        <th>Loan id</th>        
                                        <th>CST Response</th>        
                                        <th>Commitment date</th>  
                                        <th>Commitment text </th>  
                                        <th>Updated date</th>  
                                        <th>Account manger Name</th>   
                                    </tr>
                                </thead>
                                <tbody>
                  
                                   <?php
                                   $ref_data = towquery("SELECT * FROM loan_acc_man WHERE uid='$userpro_id' ORDER BY id DESC"); 
                                   while($bank_fetch = towfetch($ref_data)){
                                   extract($bank_fetch,EXTR_PREFIX_ALL,'ub');
                                   ?>
                                    <tr>
                                        <form method="post">
                                        <td><?=$ub_id;?></td>
                                        <td><?=$ub_lid;?></td>
                                        <td><?=$ub_customer_response;?></td>
                                        <td><?=$ub_commitment_date;?></td>
                                        <td><?=$ub_commitment_text;?></td>
                                        <td><?=$ub_updated_at;?></td>
                                        <td><?=$account_manager['name'];?></td>
                                        </form>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                                </table>                           
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        include_once 'foot.php';
        ?>
        
        <script>
function myFunction() {
  var x = document.getElementById("mySelect").value;
  if(x == "Process"){
  	document.getElementById("validcheck").innerHTML = '<div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Last 3 month bank statement up to date">Last 3 month bank statement up to date </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Last 2 month bank statement up to date">Last 2 month bank statement up to date </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Last 1 month bank statement up to date">Last 1 month bank statement up to date </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Current month till date bank statement">Current month till date bank statement</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Latest one month pay slip/salary slip">Latest one month pay slip/salary slip </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Pay slip/salary slip">Pay slip/salary slip </label></div></div><div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Aadhar front side">Aadhar front side </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Aadhar back side">Aadhar back side </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Aadhar card password">Aadhar card password</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Passport front side">Passport front side </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Passport back side">Passport back side</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="office address & land line number">Office address & land line number</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Company website URL link">Company website URL link </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Offer letter/appointment letter">Offer letter/appointment letter </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Pan card">Pan card </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="All documents">All documents </label></div></div><div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Present address proof ">Present address proof </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="IFSC code">IFSC code </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="FB url">FB url</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Instagram ID">Instagram ID </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="5 references">5 references </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Linked in ID">Linked in ID </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Bank Account number ">Bank Account number</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Ready for Disbursal">Ready for Disbursal </label></div></div>';
 }else if(x == "Not Process"){
     document.getElementById("validcheck").innerHTML = '<div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Less salary ">Less salary </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Salary not reflecting in statement">Salary not reflecting in statement</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Modified(Bank statement)">Modified(Bank statement) </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Modified(salary slip)">Modified(salary slip)</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Self employed">Self employed</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Resigned job ">Resigned job </label></div></div><div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Red profile">Red profile</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Existing Customer ">Existing Customer </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Waste Customer">Waste Customer</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Wrong number/invalid/not exist">Wrong number/invalid/not exist </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Net banking issue ">Net banking issue </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Doesn\'t have net banking">Doesn\'t have net banking</label></div></div><div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Salary by case/cheque">Salary by case/cheque</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Bank/insurance/media profiles">Bank/insurance/media profiles </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Real estate/police/count profile ">Real estate/police/count profile </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Directory/ceo">Directory/ceo</label></div></div>';
 }else if(x == "Re Process"){
 	document.getElementById("validcheck").innerHTML = '<div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Less salary">Less salary </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Salary not reflecting in Bank">Salary not reflecting in Bank</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Resigned job">Resigned job</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="On bench/notice period">On bench/notice period </label></div></div><div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Net banking issue">Net banking issue </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Doesn\'t have net banking "> Doesn\'t have net banking</label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Wrong references">Wrong references </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Dont want to give references">Dont want to give references </label></div></div><div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Not a good company">Not a good company </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Wrong number/invaild ">Wrong number/invaild </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Number switched off/not exist">Number switched off/not exist</label></div></div>';
 }else if(x == "cancel"){
 	document.getElementById("validcheck").innerHTML = '<div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="cancel the loan">cancel the loan </label></div><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="cancel & hold">cancel & hold</label></div></div>';
 }else if(x == "unhold"){
 	document.getElementById("validcheck").innerHTML = '<div class="col-lg-4"><div class="checkbox"><label><input name="processcheck[]" type="checkbox" value="Unhold ">Unhold </label></div></div>';
 }
}
</script>
<script>
function select_folomess() {
  var x = document.getElementById("select_folomesss").value;
  if(x == "Sell pay part payment"){
      document.getElementById("amount").innerHTML = '<input type="text" name="advance_amount" placeholder="Advance Amount" class="form-control">';
  }
  	document.getElementById("follow_up_mess").innerHTML = x;
}
</script>
<script>
    function adddate(date){
        // document.getElementById("date").innerHTML = date;
        $('#date').val(date);
    }
</script>
        <script>
        $( "#panNumber" ).keyup(function() {
            var panVal = $('#panNumber').val();
var regpan = /^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/;

if(regpan.test(panVal)){
   $('#panNumber').css("border-color", "green");
} else {
   $('#panNumber').css("border-color", "red");
}
});
        </script>
        
<script>
        var email,altemail;
        $( "#altemail" ).keyup(function() {
        email = $('#email').val();
        altemail = $('#altemail').val();
        $("#presub").removeAttr("disabled");
if(email != altemail){
   $('#altemail').css("border-color", "green");
   $("#messs").html("");
} else {
   $('#altemail').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#messs").html("Email and alternate Email should not be same");
}
});
</script>

<script>
        var mobile,altmobile;
        $( "#altmobile" ).keyup(function() {
        mobile = $('#mobile').val();
        altmobile = $('#altmobile').val();
        $("#presub").removeAttr("disabled");
if(mobile != altmobile){
   $('#altmobile').css("border-color", "green");
   $("#mess").html("");
} else {
   $('#altmobile').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#mess").html("please enter mobile number of any family person if you don’t have alternate number");
}
});
</script>

<script>
    $(document).ready(function(){
        $.post("udsearchtable.php",
            {
              id: <?=$userpro_id;?>
            },
             function(data,status) {
                 $('#searchtable').html(data);
             });
    });
</script>
<script>
    $(document).ready(function(){
        $.post("panmess.php",
            {
              panmess: '<?=$userpro_pan;?>',
              id: <?=$userpro_id?>
            },
             function(data,status) {
                 $('#panmess').html(data);
             });
    });
</script>
<script>
    $(document).ready(function(){
        $.post("aadharmess.php",
            {
              aadharmess: '<?=$userpro_aadhar;?>',
              id: <?=$userpro_id?>
            },
             function(data,status) {
                 $('#aadharmess').html(data);
             });
    });
</script>
<script>
    $(document).ready(function(){
        $.post("acnomess.php",
            {
              acnomess: '<?=$userpro_account_no;?>',
              id: <?=$userpro_id?>
            },
             function(data,status) {
                 $('#acnomess').html(data);
             });
    });
</script>
<script>
    var valuea = $('textarea[name="validation"]').val();
</script>
<script>
function aaaa() {
    var i = 1;
    $('textarea[name="validation"]').val('');
  $('input[name^="processcheck"]:checked').each(function() {
      var value = $('textarea[name="validation"]').val();
    $('textarea[name="validation"]').val(value + i + ". " + $(this).val() + ", by <?=$user_name?> <?=date('Y-m-d H:i:s');?> \n");
    i++;
});
}
</script>

<script>
$('textarea').onchange(function () {
  this.setAttribute('style', 'height:' + (this.scrollHeight) + 'px;overflow-y:hidden;');
}).on('input', function () {
    if((this.scrollHeight) == 0){
        
  this.style.height = 'auto';
  this.style.height = 100 + 'px';
    }else{
        this.style.height = 'auto';
  this.style.height = (this.scrollHeight) + 'px';
    }
});
</script>
</body>

</html>
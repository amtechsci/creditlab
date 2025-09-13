<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../db.php';

$today = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . " -64 day"));
$renewloanquery =  mysqli_query($db,"SELECT uid,id FROM `loan_apply` WHERE `status`='account manager' ORDER BY id ASC");

$reseauserid = array();
$i = 0;
while($aa = towfetch($renewloanquery)){
 $reseauserid[$i] = $aa['uid'];
 $i++;
}
$reseauserid = array_unique($reseauserid);

// Set headers for file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Account_manager_data.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, [
    "Name" , "primary number", "alt number", "primary mail" , "alt mail" , "principal loan", "processed amount", "exhausted days" , "outstanding amount"
]);

foreach($reseauserid as $value){
$a = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi, loan_acc_man.customer_response, loan_acc_man.commitment_date, loan_acc_man.updated_at FROM user INNER JOIN loan ON loan.uid=user.id LEFT JOIN loan_acc_man ON loan_acc_man.uid=user.id WHERE user.id=$value AND loan.status_log='account manager' ORDER BY loan.lid");
if(townum($a) > 0){
$b = towfetch($a);
extract($b,EXTR_PREFIX_ALL,"user");
    
    $row = [
        $user_name,
        $user_mobile,
        $user_altmobile,
        $user_email,
        $user_altemail,
        (float)$user_processed_amount+(float)$user_p_fee+((float)$user_p_fee*0.18),
        $user_processed_amount,
        ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',strtotime($user_processed_date." -1 day")))) / (60 * 60 * 24)),
        (float)$user_processed_amount+(float)$user_p_fee+((float)$user_p_fee*0.18)+(float)$user_service_charge+(float)$user_penality_charge
    ];

    // Write the row to the output
    fputcsv($output, $row);
}
}

// Close the output stream
fclose($output);

exit;
?>

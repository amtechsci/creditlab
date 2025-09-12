<?php 
include '../db.php';
$id = towreal($_POST['id']);
// $id = towreal($_GET['id']);
$user = towquery("SELECT * FROM `user` WHERE `id`=$id");
$userfetchmain = towfetch($user);
?>
<tr>
   <td data-title="CID">Primary number in contacts</td>
<?php
$usernumber = array();
$usernumber[0] = $userfetchmain['mobile'];$number = array_filter($usernumber);
   $seauserid = array();
   $i = 0;
   foreach($number as $value){
     $numlength = strlen((string)$value);
       if($numlength >= 9) {
//   $ref = towquery("SELECT * FROM `user_ref` WHERE (`ref_1` LIKE '%$value%' OR `ref_2` LIKE '%$value%' OR `ref_3` LIKE '%$value%' OR `ref_4` LIKE '%$value%' OR `ref_5` LIKE '%$value%')");
   $ref = towquery("SELECT * FROM `user_referrals` WHERE (`phone` LIKE '%$value%')");
   while($reffetch = towfetch($ref)){ extract($reffetch,EXTR_PREFIX_ALL,"users");
   $seauserid[$i] = $users_uid;
   $i++;
   }
   $usercontact = towquery("SELECT * FROM `user_contact_details` WHERE (`user_contact` LIKE '%$value%')");
   while($usercontactfetch = towfetch($usercontact)){ extract($usercontactfetch,EXTR_PREFIX_ALL,"users");
   $seauserid[$i] = $users_uid;
   $i++;
   }
   }}
   $seauserid = array_unique($seauserid);
   if(count($seauserid) > 0){
       $aa = '';
   foreach($seauserid as $value){
   $user = towquery("SELECT id FROM `user` WHERE id=$value AND id != $id");
   if(townum($user) > 0){
   while($userfetch = towfetch($user)){
   $aa .= "<a href='/admin/profile.php?id={$userfetch['id']}'>{$userfetch['id']}</a>,";
   }}}
   ?>
   <td data-title="CID"><?=$aa?></td>
   <?php }else{echo "<td></td>";} ?>
   </tr>
   <tr>
   <td data-title="CID">Alt number in contacts</td>
   <?php
$usernumber = array();
$usernumber[0] = $userfetchmain['altmobile'];$number = array_filter($usernumber);
   $seauserid = array();
   $i = 0;
   foreach($number as $value){
     $numlength = strlen((string)$value);
       if($numlength >= 9) {
   $ref = towquery("SELECT * FROM `user_ref` WHERE (`ref_1` LIKE '%$value%' OR `ref_2` LIKE '%$value%' OR `ref_3` LIKE '%$value%' OR `ref_4` LIKE '%$value%' OR `ref_5` LIKE '%$value%')");
   while($reffetch = towfetch($ref)){ extract($reffetch,EXTR_PREFIX_ALL,"users");
   $seauserid[$i] = $users_uid;
   $i++;
   }
   $usercontact = towquery("SELECT * FROM `user_contact_details` WHERE (`user_contact` LIKE '%$value%')");
   while($usercontactfetch = towfetch($usercontact)){ extract($usercontactfetch,EXTR_PREFIX_ALL,"users");
   $seauserid[$i] = $users_uid;
   $i++;
   }
   }}
   $seauserid = array_unique($seauserid);
   if(count($seauserid) > 0){
       $aa = '';
   foreach($seauserid as $value){
   $user = towquery("SELECT id FROM `user` WHERE id=$value AND id != $id");
   if(townum($user) > 0){
   while($userfetch = towfetch($user)){
   $aa .= "<a href='/admin/profile.php?id={$userfetch['id']}'>{$userfetch['id']}</a>,";
   }}}
   ?>
   <td data-title="CID"><?=$aa?></td>
   <?php }else{echo "<td></td>";} ?>
   </tr>
   <tr>
   <td data-title="CID">Primary / Alt mail in any ID</td>
   <?php
    $seaemail = array(); // Initialize the array
    $useremail1 = $userfetchmain['email'];
    $user = towquery("SELECT * FROM `user` WHERE (email='$useremail1' OR altemail='$useremail1') AND NOT id=$id AND NOT active=2");
   while($userfetch = towfetch($user)){
    // print_r($userfetch);exit;   
    extract($userfetch,EXTR_PREFIX_ALL,"email");
   $seaemail[$i] = $email_id;
   $i++;
   }
   $useremail2 = $userfetchmain['altemail'];
    $user = towquery("SELECT * FROM `user` WHERE (email='$useremail2' OR altemail='$useremail2') AND NOT id=$id AND NOT active=2");
   while($userfetch = towfetch($user)){ extract($userfetch,EXTR_PREFIX_ALL,"email");
   $seaemail[$i] = $email_id;
   $i++;
   }
   $seaemail = array_unique($seaemail ?: []);
   if(count($seaemail) > 0){
       $aa = '';
   foreach($seaemail as $value){
   $user = towquery("SELECT id FROM `user` WHERE id=$value AND id != $id");
   if(townum($user) > 0){
   while($userfetch = towfetch($user)){
   $aa .= "<a href='/admin/profile.php?id={$userfetch['id']}'>{$userfetch['id']}</a>,";
   }}} ?>
   <td data-title="CID"><?=$aa?></td>
   <?php }else{echo "<td></td>";} ?>
   </tr>
   <tr>
   <td data-title="CID">Mobile device unique ID</td>
   <?php
    $buid = $userfetchmain['id'];
    $uban = towquery("SELECT * FROM `user_login_details` WHERE uid=$buid ORDER BY id DESC");
    if(townum($uban) > 0){
    $uban = towfetch($uban)['mobile_handset_uid'];
    $ubanaa = towquery("SELECT uid FROM `user_login_details` WHERE mobile_handset_uid = '$uban' AND NOT uid=$buid ORDER BY id DESC");
    $accc = townum($ubanaa);
    if($accc > 0){
   while($ubana = towfetch($ubanaa)){
  $user = towquery("SELECT id FROM `user` WHERE id='{$ubana['uid']}' AND NOT active=2");
  $userfetch = towfetch($user);
  if($userfetch && isset($userfetch['id'])){
      $seauseridbank[$i] = $userfetch['id'];
      $i++;
  }
   }
   }else{
    $seauseridbank = [];   
   }
   $seauseridbank = array_unique($seauseridbank);
   if(count($seauseridbank) > 0){
       $aa = '';
   foreach($seauseridbank as $value){
   $user = towquery("SELECT id FROM `user` WHERE id=$value AND id != $id");
   if(townum($user) > 0){
   while($userfetch = towfetch($user)){
   $aa .= "<a href='/admin/profile.php?id={$userfetch['id']}'>{$userfetch['id']}</a>,";
   }}} ?>
   <td data-title="CID"><?=$aa?></td>
   <?php }else{echo "<td></td>";}}else{echo "<td></td>";} ?>
   </tr>
   <tr>
   <td data-title="CID">PAN</td>
   <?php
    $pan = $userfetchmain['pan'];
    if(!empty($pan)){
    $seauserid = array();
    $user = towquery("SELECT * FROM `user` WHERE (pan LIKE '%$pan%') AND NOT id=$id AND NOT active=2");
   while($userfetch = towfetch($user)){ extract($userfetch,EXTR_PREFIX_ALL,"users");
   $seauserid[$i] = $users_id;
   $i++;
   }
   $seauserid = array_unique($seauserid);
   if(count($seauserid) > 0){
       $aa = '';
   foreach($seauserid as $value){
   $user = towquery("SELECT id FROM `user` WHERE id=$value AND id != $id");
   if(townum($user) > 0){
   while($userfetch = towfetch($user)){
   $aa .= "<a href='/admin/profile.php?id={$userfetch['id']}'>{$userfetch['id']}</a>,";
   }}} ?>
   <td data-title="CID"><?=$aa?></td>
   <?php }else{echo "<td></td>";}}else{echo "<td></td>";} ?>de
   </tr>
   <tr>
   <td data-title="CID">Adhar</td>
   <?php
    $aadhar = $userfetchmain['aadhar'];
    if(!empty($aadhar)){
    $seauserid = array();
    $user = towquery("SELECT * FROM `user` WHERE (aadhar LIKE '%$aadhar%') AND NOT id=$id AND NOT active=2");
   while($userfetch = towfetch($user)){
       extract($userfetch,EXTR_PREFIX_ALL,"users");
   $seauserid[$i] = $users_id;
   $i++;
   }
   $seauserid = array_unique($seauserid);
   if(count($seauserid) > 0){
       $aa = '';
   foreach($seauserid as $value){
   $user = towquery("SELECT id FROM `user` WHERE id=$value AND id != $id");
   if(townum($user) > 0){
   while($userfetch = towfetch($user)){
   $aa .= "<a href='/admin/profile.php?id={$userfetch['id']}'>{$userfetch['id']}</a>,";
   }}} ?>
   <td data-title="CID"><?=$aa?></td>
   <?php }else{echo "<td></td>";}}else{echo "<td></td>";} ?>
   </tr>
   <tr>
   <td data-title="CID">Bank account number</td>
   <?php
    $buid = $userfetchmain['id'];
    $uban = towquery("SELECT * FROM `user_bank` WHERE uid=$buid ORDER BY id DESC");
    if(townum($uban) > 0){
    $uban = towfetch($uban)['ac_no'];
    $ubanaa = towquery("SELECT uid FROM `user_bank` WHERE ac_no = '$uban' AND NOT uid=$buid ORDER BY id DESC");
    $accc = townum($ubanaa);
    if($accc > 0){
   while($ubana = towfetch($ubanaa)){
  $user = towquery("SELECT id FROM `user` WHERE id='{$ubana['uid']}' AND NOT active=2");
  $userfetch = towfetch($user);
  $seauseridbank[$i] = $userfetch['id'];
  $i++;
   }
   }else{
    $seauseridbank = [];   
   }
   $seauseridbank = array_unique($seauseridbank);
   if(count($seauseridbank) > 0){
       $aa = '';
   foreach($seauseridbank as $value){
   $user = towquery("SELECT id FROM `user` WHERE id=$value AND id != $id");
   if(townum($user) > 0){
   while($userfetch = towfetch($user)){
   $aa .= "<a href='/admin/profile.php?id={$userfetch['id']}'>{$userfetch['id']}</a>,";
   }}} ?>
   <td data-title="CID"><?=$aa?></td>
   <?php }else{echo "<td></td>";}}else{echo "<td></td>";} ?>
   </tr>
   <tr>
   <td data-title="CID">Primary / Alt number in ID</td>
   <?php
$usernumber = array();
$usernumber[0] = $userfetchmain['mobile'];
$usernumber[1] = $userfetchmain['altmobile'];
$number = array_filter($usernumber);
   $seauserid = array();
   $i = 0;
   foreach($number as $value){
     $numlength = strlen((string)$value);
       if($numlength >= 9) {
    $user = towquery("SELECT * FROM `user` WHERE (mobile LIKE '%$value%' OR altmobile LIKE '%$value%') AND NOT id=$id AND NOT active=2");
   while($userfetch = towfetch($user)){ extract($userfetch,EXTR_PREFIX_ALL,"users");
   $seauserid[$i] = $users_id;
   $i++;
   }
   }}
   $seauserid = array_unique($seauserid);
   if(count($seauserid) > 0){
       $aa = '';
   foreach($seauserid as $value){
   $user = towquery("SELECT id FROM `user` WHERE id=$value AND id != $id");
   if(townum($user) > 0){
   while($userfetch = towfetch($user)){
   $aa .= "<a href='/admin/profile.php?id={$userfetch['id']}'>{$userfetch['id']}</a>,";
   }}}
   ?>
   <td data-title="CID"><?=$aa?></td>
   <?php }else{echo "<td></td>";} ?>
   </tr>
   <tr>
   <td data-title="CID">Number of contact</td>
   <?php
    $total = towquery("SELECT total FROM `user_contact_details` WHERE uid=$id ORDER BY id DESC");
    $total_fetch = towfetch($total);
    $total = $total_fetch ? $total_fetch['total'] : 0;
    ?>
   <td data-title="CID"><?=$total?></td>
   </tr>
   <tr>
   <td data-title="CID" rowspan="4">References</td>
   </tr>
<?php
   $refaaaa = towquery("SELECT * FROM `user_referrals` WHERE uid='$id' ORDER BY id DESC");
   if(townum($refaaaa) > 0){
       while($reff = towfetch($refaaaa)){
       $ref1 = $reff['phone'];
?>
<tr>
<td><?php if(!empty($ref1)){ echo $ref1;
$ref = towquery("SELECT DISTINCT uid FROM `user_contact_details` WHERE user_contact LIKE '%{$ref1}%' AND NOT uid=$id ORDER BY id DESC");
echo "<br>";
while($refma = towfetch($ref)){
    echo "<a href='/admin/profile.php?id={$refma['uid']}'>{$refma['uid']}</a>,";
}
}else{}
?></td>
</tr>
<?php }} ?>




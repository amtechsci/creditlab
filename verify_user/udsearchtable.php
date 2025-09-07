<?php 
include '../db.php';
$id = towreal($_POST['id']);
$user = towquery("SELECT * FROM `user` WHERE `id`=$id");
$userfetch = towfetch($user);
$usernumber = array();
$usernumber[0] = $userfetch['mobile'];
$usernumber[1] = $userfetch['altmobile'];
$ref_data = towquery("SELECT * FROM user_ref WHERE uid='$id' ORDER BY id DESC"); 
                                   $ref_fetch = towfetch($ref_data);
                                   extract($ref_fetch,EXTR_PREFIX_ALL,"users");
                                   $ref_1 = explode(",#",$users_ref_1);
                                   $ref_2 = explode(",#",$users_ref_2);
                                   $ref_3 = explode(",#",$users_ref_3);
                                   $ref_4 = explode(",#",$users_ref_4);
                                   $ref_5 = explode(",#",$users_ref_5);
                                   $vaild = explode("#",$users_status);
$refnumber = array();
$refnumber[0] = $ref_1[1];
$refnumber[1] = $ref_2[1];
$refnumber[2] = $ref_3[1];
$refnumber[3] = $ref_4[1];
$refnumber[4] = $ref_5[1];
$contact = towquery("SELECT * FROM user_contact_details WHERE uid='$id' ORDER BY id DESC"); 
                                   $contact_fetch = towfetch($contact);
                                   $user_contact = $contact_fetch['user_contact'];
                                   $user_contact = explode(PHP_EOL,$user_contact);
                                   $marge = array_merge($usernumber,$refnumber);
?>
                  
                                   <?php 
                                   $number = array_merge($marge,$user_contact);
                                   $number = array_filter($number);
                                   $seauserid = array();
                                   $i = 0;
                                   foreach($number as $value){
                                    $user = towquery("SELECT * FROM `user` WHERE (mobile LIKE '%$value%' OR altmobile LIKE '%$value%') AND NOT id=$id AND NOT active=2");
                                   while($userfetch = towfetch($user)){ extract($userfetch,EXTR_PREFIX_ALL,"users");
                                   $seauserid[$i] = $users_id;
                                   $i++;
                                   }
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
                                   }
                                   ?>
                                   
                                   
                                   
                                   
                                <?php
                                #print_r($seauserid);
                                   $seauserid = array_unique($seauserid);
                                   #print_r($seauserid);
                                   foreach($seauserid as $value){
                                   $user = towquery("SELECT * FROM `user` WHERE id=$value AND NOT id=$id");
                                   if(townum($user) > 0){
                                   $userfetch = towfetch($user);
                                   extract($userfetch,EXTR_PREFIX_ALL,"users");
                                ?>
                                <tr>
                                        <td data-title="CID"><?=$users_rcid?></td>
                                        <td data-title="Name"><?=$users_name?><?php if($users_loan > 0){echo "<span style='color:red'>#</span>";}?><?php if($users_sloan > 0){echo "<span style='color:red'>@</span>";}?></td>
                                        <td data-title="Email"><?=$users_email?></td>
                                        <td data-title="Mobile"><?=$users_mobile?></td>
                                        <td data-title="Status" style="color:white; background:<?php if($users_status == "default"){echo "red;";}elseif($users_status == "disbursal"){echo "green;";}else{echo "blue;";}?>"><?=$users_status?></td>
                                        <td data-title="Actions"><a class="btn btn-primary" href="profile.php?id=<?=$users_id?>">View</a></td>
                                    </tr>
                                    <?php } } ?>
<?php 
include '../db.php';
$search = towreal($_POST['search']);
?>   
    <?php 
                                   $seauserid = array();
                                   $i = 0;
                                    $user = towquery("SELECT * FROM `user` WHERE (mobile LIKE '%$search%' OR altmobile LIKE '%$search%') AND NOT active=2");
                                   while($userfetch = towfetch($user)){ extract($userfetch,EXTR_PREFIX_ALL,"users");
                                   $seauserid[$i] = $users_id;
                                   $i++;
                                   }
                                   $ref = towquery("SELECT * FROM `user_ref` WHERE (`ref_1` LIKE '%$search%' OR `ref_2` LIKE '%$search%' OR `ref_3` LIKE '%$search%' OR `ref_4` LIKE '%$search%' OR `ref_5` LIKE '%$search%')");
                                   while($reffetch = towfetch($ref)){ extract($reffetch,EXTR_PREFIX_ALL,"users");
                                   $seauserid[$i] = $users_uid;
                                   $i++;
                                   }
                                   $usercontact = towquery("SELECT * FROM `user_contact_details` WHERE (`user_contact` LIKE '%$search%')");
                                   while($usercontactfetch = towfetch($usercontact)){ extract($usercontactfetch,EXTR_PREFIX_ALL,"users");
                                   $seauserid[$i] = $users_uid;
                                   $i++;
                                   }
                                   
                                   ?>
                                   
                                   
                                   
                                  <table class="table table-bordered" id="loan_history_datatable">
        <thead class="thead-light">
            <tr>                
                                        <th>RCID</th>        
                                        <th>Name</th>        
                                        <th>Email</th>        
                                        <th>Mobile</th>    
                                        <th>Status</th>    
                                        <th>Actions</th>     
                                    </tr>
        </thead>
        <tbody> 
                                <?php
                                #print_r($seauserid);
                                   $seauserid = array_unique($seauserid);
                                   #print_r($seauserid);
                                   foreach($seauserid as $value){
                                   $user = towquery("SELECT * FROM `user` WHERE id=$value");
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
                                     </tbody>
    </table>
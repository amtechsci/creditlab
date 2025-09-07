<?php 
include '../db.php';
$search = towreal($_POST['search']);
$seausersquery = towquery("SELECT * FROM `user` WHERE `rcid` LIKE '%$search%' OR `name` LIKE '%$search%' OR `mobile` LIKE '%$search%' OR `altmobile` LIKE '%$search%' OR `email` LIKE '%$search%' OR `altemail` LIKE '%$search%' OR `pan` LIKE '%$search%' OR `account_no` LIKE '%$search%' OR `aadhar` LIKE '%$search%'");
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
                  
                                   <?php while($sealoanfetch = towfetch($seausersquery)){ extract($sealoanfetch,EXTR_PREFIX_ALL,"users"); ?>
                                    <tr>
                                        <td data-title="CID"><?=$users_rcid?></td>
                                        <td data-title="Name"><?=$users_name?><?php if($users_loan > 0){echo "<span style='color:red'>#</span>";}?><?php if($users_sloan > 0){echo "<span style='color:red'>@</span>";}?></td>
                                        <td data-title="Email"><?=$users_email?></td>
                                        <td data-title="Mobile"><?=$users_mobile?></td>
                                        <td data-title="Status" style="color:white; background:<?php if($users_status == "default"){echo "red;";}elseif($users_status == "disbursal"){echo "green;";}else{echo "blue;";}?>"><?=$users_status?></td>
                                        <td data-title="Actions"><a class="btn btn-primary" href="profile.php?id=<?=$users_id?>">View</a></td>
                                    </tr>
                                <?php } ?>
            </tbody>
    </table>
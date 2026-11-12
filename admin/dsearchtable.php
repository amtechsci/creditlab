<?php 
include '../db.php';
require_once __DIR__ . '/../lib/admin_ui.php';
$search = towreal($_POST['search'] ?? '');
?>   
<?php 
$seauserid = array();
$i = 0;
$user = towquery("SELECT * FROM `user` WHERE (mobile LIKE '%$search%' OR altmobile LIKE '%$search%' OR pan LIKE '%$search%') AND NOT active=2");
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
$usercontact = towquery("SELECT * FROM `transaction_details` WHERE (`transaction_number` LIKE '%$search%')");
while($usercontactfetch = towfetch($usercontact)){ extract($usercontactfetch,EXTR_PREFIX_ALL,"users");
    $seauserid[$i] = $users_uid;
    $i++;
}
?>
<table class="table table-bordered" id="loan_history_datatable">
    <thead class="thead-light">
        <tr>                
            <th>Borrower / RCID</th>        
            <th>Mobile</th>    
            <th style="text-align:center;">Status</th>    
            <th style="text-align:right; width:110px;">Actions</th>     
        </tr>
    </thead>
    <tbody> 
    <?php
    $seauserid = array_unique($seauserid);
    foreach($seauserid as $value){
        $user = towquery("SELECT * FROM `user` WHERE id=$value");
        if(townum($user) > 0){
            $userfetch = towfetch($user);
            extract($userfetch,EXTR_PREFIX_ALL,"users");
    ?>
    <tr>
        <td data-title="Borrower">
            <div style="font-weight:600; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                <?= creditlab_dash_text($users_name ?? '') ?>
                <?php if($users_loan > 0){ echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; } ?>
                <?php if($users_sloan > 0){ echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; } ?>
            </div>
            <div style="margin-top:3px; font-size:11.5px; font-family:monospace; color:var(--cl-primary); font-weight:600;">
                <?= creditlab_dash_text($users_rcid ?? '') ?>
            </div>
        </td>
        <td data-title="Mobile" style="white-space:nowrap;">
            <div style="font-family:monospace; font-size:12.5px; font-weight:500; color:var(--cl-slate-800);">
                <?= creditlab_dash_text($users_mobile ?? '') ?>
            </div>
        </td>
        <td data-title="Status" style="text-align:center; white-space:nowrap;">
            <?= creditlab_status_badge($users_status ?? '') ?>
        </td>
        <td data-title="Actions" style="text-align:right; white-space:nowrap;">
            <a class="btn btn-xs btn-primary" href="profile.php?id=<?=$users_id?>" target="_blank" style="padding:4px 10px;">
                <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
            </a>
        </td>
    </tr>
    <?php } } ?>
    </tbody>
</table>

<?php
include 'head.php';
?>
<div class="table-responsive">
        <table class="table table-bordered">
        <thead class="thead-light">
            <?php
            $user = towquery("SELECT * FROM user WHERE active=2 ORDER BY id DESC"); while($userfetch = towfetch($user)){ extract($userfetch,EXTR_PREFIX_ALL,"users");
            ?>
            <tr>                
                                        <th>admin name</th>        
                                        <th>Browser</th>        
                                        <th>IP Address</th>        
                                        <th>Login Time</th>    
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php
                                   
                                   
                                   $login_data = towquery("SELECT * FROM user_login_details WHERE uid='$users_id' ORDER BY id DESC LIMIT 0, 10"); while($login_fetch = towfetch($login_data)){ extract($login_fetch,EXTR_PREFIX_ALL,"users"); ?>
                                    <tr>
                                        <td data-title="CID"><?=$users_name?></td>
                                        <td data-title="Name"><?=$users_browser?></td>
                                        <td data-title="Email"><?=$users_ip_address?></td>
                                        <td data-title="Mobile"><?=$users_login_time?></td>
                                    </tr>
                                <?php }} ?>
            </tbody>
    </table>
    </div>
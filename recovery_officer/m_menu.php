<div class="mobile-menu-area">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="mobile-menu">
                                <nav id="dropdown">
                                   <ul class="metismenu" id="menu1">
                        <li class="active">
                            <a href="index.php">
                                <span> <i class="fas fa-tachometer-alt"></i></span>
                                 <span class="mini-click-non">Dashboard</span>
                            </a>
                        </li> 
                            
                        <?php $regicount = towquery("SELECT * FROM `user` WHERE `active`=1 AND `verify`=0");?>
                        <li>
                            <a class="" href="newusers.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/newusers.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Just registered (<?=townum($regicount);?>)</span></a>
                            
                        </li>
                        <?php $appcount = towquery("SELECT * FROM `user` WHERE `active`=1 AND `verify`=1");?>
                        <li>
                           <a class="" href="users.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/users.php"){ ?> style="background: whitesmoke;" <?php }?>> <i class="fas fa-user-alt"></i> <span class="mini-click-non">Approved Users (<?=townum($appcount);?>)</span></a>
                        </li>
                        <?php $appicount = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='pending'");?>
                         <li>
                            <a class="" href="newloan.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/newloan.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Applied Loans (<?=townum($appicount);?>)</span></a>
                        </li>
                        <?php $follow_up = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='follow up'");?>
                        <li>
                            <a class="" href="follow_up.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/follow_up.php"){ ?> style="background: whitesmoke;" <?php }?>><i class="fas fa-landmark"></i> <span class="mini-click-non">Follow Up (<?=townum($follow_up);?>)</span></a>
                        </li>
                        <?php $disbursal = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='disbursal'");?>
                         <li>
                            <a class="" href="disbursal.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/disbursal.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Disbursal (<?=townum($disbursal);?>)</span></a>
                        </li>
                        <li>
                            <a class="" href="loan_slider.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/loan_slider.php"){ ?> style="background: whitesmoke;" <?php }?>><i class="fas fa-landmark"></i> <span class="mini-click-non">Loan slider</span></a>
                        </li>
                         <li>
                            <a class="" href="dynamic_search.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/dynamic_search.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Dynamic Search</span></a>
                        </li>
                        <?php $account_manager = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='account manager'");?>
                        <li>
                            <a class="" href="account_manager.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/account_manager.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Account Manager (<?=townum($account_manager);?>)</span></a>
                        </li>
                        <?php $recovery_officer = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='recovery officer'");?>
                        <li>
                            <a class="" href="recovery_officer.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/recovery_officer.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Recovery Officer (<?=townum($recovery_officer);?>)</span></a>
                        </li>
                        <li>
                            <a class="" href="add_user.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/add_user.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Add User</span></a>
                        </li>
                        <li>
                            <a class="" href="email.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/email.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Email</span></a>
                        </li>
                        
                        
                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
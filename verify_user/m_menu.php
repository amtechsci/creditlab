<div class="mobile-menu-area">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="mobile-menu">
                                <nav id="dropdown">
                                   <ul class="metismenu" id="menu1">
                                        <?php $appicount = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='pending'");?>
                                         <li class="active">
                                            <a class="" href="newloan.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/verify_user/newloan.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Applied Loans (<?=townum($appicount);?>)</span></a>
                                        </li>
                                        <?php $follow_up = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='follow up'");?>
                                        <li>
                                            <a class="" href="follow_up.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/verify_user/follow_up.php"){ ?> style="background: whitesmoke;" <?php }?>><i class="fas fa-landmark"></i> <span class="mini-click-non">Follow Up (<?=townum($follow_up);?>)</span></a>
                                        </li>
                                        <?php $disbursal = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='disbursal'");?>
                                         <li>
                                            <a class="" href="disbursal.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/verify_user/disbursal.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Disbursal (<?=townum($disbursal);?>)</span></a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
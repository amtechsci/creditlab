<div class="left-sidebar-pro" style="background-color: #ddd !important;">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="../index.php"><img src="https://creditlab.in/assets/img/logo.jpeg" class="black-logo" style="width:160px;" alt="Logo"></a>
                <strong><a href="index.php"><img src="img/logo/logosnaa.png" alt="" /></a></strong>
            </div>
            <div class="left-custom-menu-adp-wrap comment-scrollbar" >
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                         <li>
                            <a href="index.php" <?php if($_SERVER['REQUEST_URI'] == "/admin/index.php"){ ?> style="background: whitesmoke;" <?php }?>>
                                <span> <i class="fas fa-tachometer-alt"></i></span>
                                <?php $recovery_officer = towquery("SELECT DISTINCT uid FROM `loan_apply` WHERE `status`='recovery officer'");?>
                                 <span class="mini-click-non" >Recovery Officer (<?=townum($recovery_officer);?>)</span>
                            </a>
                        </li>
                        <?php $cancount = towquery("SELECT DISTINCT uid FROM loan_apply Left JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status='cancel'");?>
                         <li>
                            <a class="" href="part_payment.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/part_payment.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="fa fa-user-plus"></span> <span class="mini-click-non">Part Payment (<?=townum($cancount);?>)</span></a>
                        </li>
                         <li>
                            <a class="" href="dynamic_search.php" aria-expanded="false" <?php if($_SERVER['REQUEST_URI'] == "/admin/dynamic_search.php"){ ?> style="background: whitesmoke;" <?php }?>><span class="educate-icon educate-library icon-wrap"></span> <span class="mini-click-non">Dynamic Search</span></a>
                        </li>
                    </ul>
                </nav>
            </div>
        </nav>
    </div>
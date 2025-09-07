<div class="left-sidebar-pro" style="background-color: #ddd !important;">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="../index.php"><img src="https://creditlab.in/logo.svg" class="black-logo" style="width:80px;" alt="Logo"></a>
                <strong><a href="index.php"><img src="img/logo/logosnaa.png" alt=""></a></strong>
            </div>
            <div class="left-custom-menu-adp-wrap comment-scrollbar" >
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                         <li class="active">
                            <a href="index.php">
                                <span><i class="fas fa-tachometer-alt"></i></span>
                                <span class="mini-click-non">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a class="" href="creditlab_score.php" aria-expanded="false"><i class="fa fa-history"></i> <span class="mini-click-non">Creditlab score</span></a>
                        </li>
                        <li>
                            <a class="" href="logout.php" aria-expanded="false"><i class="fa fa-sign-out"></i> <span class="mini-click-non">logout</span></a>
                        </li>
<?php $userloan = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND (status='account manager' OR status='recovery officer')");
                        if(townum($userloan) > 0){
                        if($user_approvenew == 1){
                        ?>
                        <li>
                            <a class="" href="newloan.php" aria-expanded="false"><i class="fa fa-history"></i> <span class="mini-click-non">New loan</span></a>
                        </li>
                        <?php }} ?>
                    </ul>
                </nav>
            </div>
        </nav>
    </div>
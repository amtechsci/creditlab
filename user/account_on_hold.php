<?php
/**
 * Shown when the user's account is on hold and they cannot apply for a new loan.
 */
?>
<body>
<?php include_once 'welcome.php'; ?>
    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="breadcome-list">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <ul>
                                    <li><h4><?=$user_name?></h4></li>
                                    <li><span class="bread-blod"><?=$user_mobile;?></span></li>
                                </ul>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <div class="rc">
                                    <ul class="breadcome-menua">
                                        <li>Your creditlab.in ID: <span class="bread-bloda"><?=$user_rcid;?></span></li>
                                    </ul>
                                    <ul class="breadcome-menua">
                                        <li><span class="bread-blod"><?=$user_email;?></span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="analytics-sparkle-area">
        <div class="container-fluid">
            <h1>Your account is on hold.</h1>
            <p>You cannot apply for a new loan at this time.</p>
            <p>Please mail us as per the grievance redressal mechanism, if you need any assistance.</p>
            <br><br><br><br><br><br><br><br><br><br>
        </div>
    </div>
<?php include_once 'foot.php'; ?>

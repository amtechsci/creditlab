<?php
include_once 'head.php';
$refquery = towquery("SELECT * FROM user_ref WHERE uid=$user_id");
$fff = 1;
?>
<body>
<style>
    .seshow{display:none;}
    .seshowa{display:none;}
    .myflex {
        display: flex;
        flex-direction: row;
        justify-content: center;
    }.myflex tr{display: flex;
    flex-direction: column;}
    .mdn {
        display:none!important;
      }
    @media screen and (max-width: 480px) {
      .dn {
        display:none!important;
      }
      .mdn{
          display:block!important;
      }
    }
</style>
<?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
?>
<div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <ul>
                                            <li><h4><?=$user_name?></h4>
                                            </li>
                                            <li><span class="bread-blod"><?=$user_mobile;?></span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <div class="rc">
                                        <ul class="breadcome-menua">
                                            <li>Your creditlab.in ID: <span class="bread-blod"><?=$user_rcid;?></span>
                                            </li>
                                        </ul>
                                        <ul class="breadcome-menua">
                                            <li><span class="bread-blod"><?=$user_email;?></span>
                                            </li>
                                        </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<div class="container chat" style="background:#fff;">
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in</h4>
        </div>
        <br>
        <?php include 'new_loan_inc.php'; ?>
<?php
include_once 'foot.php';
?>
<script>
function vd() {
    var seshowElements = document.getElementsByClassName('seshow');
    var seshowaElements = document.getElementsByClassName('seshowa');
    var fishowElements = document.getElementsByClassName('fishow');

    for (var i = 0; i < seshowElements.length; i++) {
        seshowElements[i].style.display = 'table-cell';
    }
    for (var i = 0; i < seshowaElements.length; i++) {
        seshowaElements[i].style.display = 'block';
    }
    for (var i = 0; i < fishowElements.length; i++) {
        fishowElements[i].style.display = 'none';
    }
    setTimeout(function() {
        for (var i = 0; i < seshowElements.length; i++) {
            seshowElements[i].style.display = 'none';
        }
        for (var i = 0; i < fishowElements.length; i++) {
            fishowElements[i].style.display = 'table-cell';
        }
        for (var i = 0; i < seshowaElements.length; i++) {
            seshowaElements[i].style.display = 'none';
        }
    }, 10000);
}

</script>
</body>
</html>
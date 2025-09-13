<?php
if(isset($_POST['salarystatus'])){
    $extract = towrealarray2($_POST);
    extract($extract);
    if(($extract['salary'] >= 18000) and ($extract['get_salary'] == "bank transfer") and (($extract['salarystatus'] == "Salaried") or ($extract['salarystatus'] == "Self-Employed"))){
        $loan_limit = $extract['salary'] / 100 *40;
        $loan_limit = round($loan_limit,-3);
        $message="Dear Creditlab.in user,  Your loan is few steps away for disbursal. Complete your KYC & get the instant Credit now http://creditlab.in";
        $template_id='1107169453425832956';
        $mobile=$user_mobile;
        include '../send_sms.php';
        $work_from = isset($work_from) ? $work_from : '';
    (towquery("UPDATE `user` SET `salary`='".$extract['salary']."',`salarystatus`='".$extract['salarystatus']."',`verify`=1,`status`='Approved',`get_salary`='".$extract['get_salary']."',`designation`='".$extract['designation']."',`work_from`='$work_from',`loan_limit`='$loan_limit' WHERE mobile='$user'") and
    print_r("<script>alert('congratulations! your membership has been approved by creditlab.in'); window.location.replace('index.php');</script>")) or print_r("<script>alert(''); window.location.replace('index.php');</script>");
    }elseif(($extract['salary'] < 18000) and ($extract['salarystatus'] == "Salaried")){
        towquery("UPDATE `user` SET `verify`=3,`status`='Hold' WHERE mobile='$user'") and
    print_r("<script>alert('Your membership is on hold. You can reapply after 45 days'); window.location.replace('index.php');</script>");
    }else{
        towquery("UPDATE `user` SET `verify`=4,`status`='Hold' WHERE mobile='$user'") and
    print_r("<script>alert('sorry you are not eligible for loan in creditlab.in'); window.location.replace('index.php');</script>");
    }
}
    ?>
    <div style="background-image: url(img/loan.jpg);
  background-color: #cccccc;
    height:100%;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  position: relative;">
            <!-- Mobile Menu end -->
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
            <?php $wa_num = towfetch(towquery("SELECT * FROM `whatsapp_no` WHERE `page_id`=1"))['wa_phone'];?>
            <h4 style="padding:10px;">Contact us on whatsapp<a href="https://api.whatsapp.com/send?phone=91<?=$wa_num?> &text=CLID : <?=$user_rcid?> I need Help in ..." class="btn btn-default" style="background:#dfdfdf;">
                <img src="/ws.svg" style="width:30px;"> Whatsapp</a>
            </h4>
        </div>
        <div class="container">
          <div class="progress">
            <div class="progress-bar" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100" style="width:10%">
              10%
            </div>
          </div>
        </div>
        <br>
        <div class="analytics-sparkle-area">
            <div class="container-fluid">
                <div style="background :white; padding:15px;"><h4>"CreditLab" operates as a platform / DLA that connects borrowers to RBI-registered NBFCs for loan transactions, with all applications being thoroughly verified, approved and sanctioned by these financial institutions.</h4></div><br>
                <div class="product-tab-list tab-pane fade active in" id="ADDITIONAL">
                                                 <form action="" method="post">
                                                     <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                 
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <center><h4>Get started by Creating your profile</h4></center>
                                                            <div class="form-group">
<select name="salarystatus" id="salarystatus" class="form-control col-xs-12 col-sm-12 pull-left" onchange="salsta()" required>
<option value="">Type of emplyment</option>
<option value="Salaried">Salaried</option>
<option value="Self-Employed">Self-Employed</option>
<option value="Unemployed">Unemployed</option>
<option value="Student">Student</option>
<option value="Retired">Retired</option>
<option value="Home maker">Home maker</option>
</select>
</div>

<div id="salabox"></div>

<div id="selfbox"></div>

</div>
</div>
<center><p style="color:red" id="check"></p><button class="btn btn-primary" id="presub" disabled="disabled" style="background: #00a77b;">Submit</button></center>
</form>
</div>
</div>
</div>

        <br>
        <br>
        <br>
        </div>
<?php
include_once 'foot.php';
?>
<script>
    function salsta(){
        var sta = $('#salarystatus').val();
        if(sta == 'Salaried'){
            $('#salabox').html('<div id="salabox1"><div class="form-group"><input type="number" name="salary" placeholder="Monthly Net Salary [Ex -  28000,35000]" class="form-control col-xs-12 col-sm-12 pull-left" required></div><div class="form-group"><select name="get_salary" class="form-control col-xs-12 col-sm-12 pull-left" required><option value="">Salary payment mode?</option><option value="bank transfer">bank transfer</option><option value="cash">cash</option><option value="cheque">cheque</option></select></div><div class="form-group"><input type="text" name="designation" placeholder="Designation" class="form-control col-xs-12 col-sm-12 pull-left" required></div></div>');
            $('#selfbox1').remove();
        }else if(sta == 'Self-Employed'){
            $('#salabox1').remove();
            $('#selfbox').html('<div id="selfbox1"><div class="form-group"><input type="number" name="salary" placeholder="Avg monthly income [Ex -  28000,35000]" class="form-control col-xs-12 col-sm-12 pull-left" required></div><div class="form-group"><select name="get_salary" class="form-control col-xs-12 col-sm-12 pull-left" required><option value="">Income Receiving mode</option><option value="bank transfer">bank transfer</option><option value="cash">cash</option><option value="cheque">cheque</option></select></div></div>');
        }else{
            $('#salabox1').remove();
            $('#selfbox1').remove();
        }
    }
</script>
<script>
$( document ).ready(function() {
    $("#presub").removeAttr("disabled");
});
</script>

</body>

</html>
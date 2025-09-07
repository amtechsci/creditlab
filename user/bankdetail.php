<?php
if(isset($_POST['bank_name'])){
    $extract = towrealarray($_POST);
    extract($extract);
        $message="Dear {$user_name}, Your bank account is now associated with us. Name : {$bank_name}, No: {$ac_no}. -Creditlab";
        $template_id='1107165683279440796';
        $mobile = $user_mobile;
        include '../send_sms.php';
    (towquery("INSERT INTO `user_bank`(`uid`, `ac_no`, `ifsc_code`, `bank_name`, `verify`) VALUES ('$user_id','$ac_no','".strtoupper($ifsc_code)."','$bank_name', 1)") and
    print_r("<script> window.location.replace('index.php');</script>")) or print_r("<script>window.location.replace('index.php');</script>");
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
            <div class="progress-bar" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width:40%">
              40%
            </div>
          </div>
        </div>
        <div class="row">
            <div class="container">
            <form action="" method="post">
                                                     <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                 
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <h4>Bank Details</h4>
                                                            <h5>(!) please be sure that your are giving your salary / income crediting bank account details<br>
                                                            (!) Loan amount will be transferred to this account only </h5>
<div class="form-group">
<!--<input type="text" name="bank_name" placeholder="Bank Name" class="form-control col-xs-12 col-sm-12 pull-left" required>-->
<select name="bank_name" class="form-control">
    <?php $bnc = towquery("SELECT * FROM `bank_name`"); while($bncf = towfetch($bnc)){?>
    <option value="<?=$bncf['bank_name'];?>"><?=$bncf['bank_name'];?></option>
    <?php }?>
</select>
</div>
<div class="form-group">
<input type="number" name="ac_no" placeholder="Full bank account number" class="form-control col-xs-12 col-sm-12 pull-left" required>
</div>
<div class="form-group">
<input type="text" name="ifsc_code" placeholder="IFSC Code" class="form-control col-xs-12 col-sm-12 pull-left ifsc" required>
</div>
<div class="form-group">
<input type="checkbox" name="ch" class="col-xs-12 col-sm-12" style="width: 30px; height: 30px; padding-top: 15px;" required>I here by confirm that the bank details are correct and that the account belongs only to me. The loan amount, if approved, will be transferred to this account only. If the above information proves to be incorrect, Creditlab and its Lending partners will not be held liable and the loan will be deemed to have been disbursed to me and I will be responsible for repayment.
</div>
</div>
</div>
<center><p style="color:red" id="check"></p><button class="btn btn-primary" id="presub" style="background: #00a77b;">Submit</button></center>
</form>
            </div>
        </div><br><br><br><br><br><br>
<?php
include_once 'foot.php';
?>
<script type="text/javascript">    
$(document).ready(function(){     
        
$(".ifsc").change(function () {      
var inputvalues = $(this).val();      
  var reg = /[A-Z|a-z]{4}[0][a-zA-Z0-9]{6}$/;    
                if (inputvalues.match(reg)) {    
                    return true;    
                }    
                else {    
                    alert("You entered invalid IFSC code");    
                    return false;
                }    
});      
    
});    
</script> 
</body>
</html>
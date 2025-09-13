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
<div class="container chat" style="background:#fff;"><?php $wa_num = towfetch(towquery("SELECT * FROM `whatsapp_no` WHERE `page_id`=1"))['wa_phone'];?>
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in or <a href="https://api.whatsapp.com/send?phone=91<?=$wa_num?> &text=CLID : <?=$user_rcid?> I need Help in ..." class="btn btn-default"><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="25" height="25" viewBox="0 0 48 48">
<path fill="#fff" d="M4.868,43.303l2.694-9.835C5.9,30.59,5.026,27.324,5.027,23.979C5.032,13.514,13.548,5,24.014,5c5.079,0.002,9.845,1.979,13.43,5.566c3.584,3.588,5.558,8.356,5.556,13.428c-0.004,10.465-8.522,18.98-18.986,18.98c-0.001,0,0,0,0,0h-0.008c-3.177-0.001-6.3-0.798-9.073-2.311L4.868,43.303z"></path><path fill="#fff" d="M4.868,43.803c-0.132,0-0.26-0.052-0.355-0.148c-0.125-0.127-0.174-0.312-0.127-0.483l2.639-9.636c-1.636-2.906-2.499-6.206-2.497-9.556C4.532,13.238,13.273,4.5,24.014,4.5c5.21,0.002,10.105,2.031,13.784,5.713c3.679,3.683,5.704,8.577,5.702,13.781c-0.004,10.741-8.746,19.48-19.486,19.48c-3.189-0.001-6.344-0.788-9.144-2.277l-9.875,2.589C4.953,43.798,4.911,43.803,4.868,43.803z"></path><path fill="#cfd8dc" d="M24.014,5c5.079,0.002,9.845,1.979,13.43,5.566c3.584,3.588,5.558,8.356,5.556,13.428c-0.004,10.465-8.522,18.98-18.986,18.98h-0.008c-3.177-0.001-6.3-0.798-9.073-2.311L4.868,43.303l2.694-9.835C5.9,30.59,5.026,27.324,5.027,23.979C5.032,13.514,13.548,5,24.014,5 M24.014,42.974C24.014,42.974,24.014,42.974,24.014,42.974C24.014,42.974,24.014,42.974,24.014,42.974 M24.014,42.974C24.014,42.974,24.014,42.974,24.014,42.974C24.014,42.974,24.014,42.974,24.014,42.974 M24.014,4C24.014,4,24.014,4,24.014,4C12.998,4,4.032,12.962,4.027,23.979c-0.001,3.367,0.849,6.685,2.461,9.622l-2.585,9.439c-0.094,0.345,0.002,0.713,0.254,0.967c0.19,0.192,0.447,0.297,0.711,0.297c0.085,0,0.17-0.011,0.254-0.033l9.687-2.54c2.828,1.468,5.998,2.243,9.197,2.244c11.024,0,19.99-8.963,19.995-19.98c0.002-5.339-2.075-10.359-5.848-14.135C34.378,6.083,29.357,4.002,24.014,4L24.014,4z"></path><path fill="#40c351" d="M35.176,12.832c-2.98-2.982-6.941-4.625-11.157-4.626c-8.704,0-15.783,7.076-15.787,15.774c-0.001,2.981,0.833,5.883,2.413,8.396l0.376,0.597l-1.595,5.821l5.973-1.566l0.577,0.342c2.422,1.438,5.2,2.198,8.032,2.199h0.006c8.698,0,15.777-7.077,15.78-15.776C39.795,19.778,38.156,15.814,35.176,12.832z"></path><path fill="#fff" fill-rule="evenodd" d="M19.268,16.045c-0.355-0.79-0.729-0.806-1.068-0.82c-0.277-0.012-0.593-0.011-0.909-0.011c-0.316,0-0.83,0.119-1.265,0.594c-0.435,0.475-1.661,1.622-1.661,3.956c0,2.334,1.7,4.59,1.937,4.906c0.237,0.316,3.282,5.259,8.104,7.161c4.007,1.58,4.823,1.266,5.693,1.187c0.87-0.079,2.807-1.147,3.202-2.255c0.395-1.108,0.395-2.057,0.277-2.255c-0.119-0.198-0.435-0.316-0.909-0.554s-2.807-1.385-3.242-1.543c-0.435-0.158-0.751-0.237-1.068,0.238c-0.316,0.474-1.225,1.543-1.502,1.859c-0.277,0.317-0.554,0.357-1.028,0.119c-0.474-0.238-2.002-0.738-3.815-2.354c-1.41-1.257-2.362-2.81-2.639-3.285c-0.277-0.474-0.03-0.731,0.208-0.968c0.213-0.213,0.474-0.554,0.712-0.831c0.237-0.277,0.316-0.475,0.474-0.791c0.158-0.317,0.079-0.594-0.04-0.831C20.612,19.329,19.69,16.983,19.268,16.045z" clip-rule="evenodd"></path>
</svg> Whatsapp</a></h4>
        </div>
        <br>
        <?php 
        $userloan = towquery("SELECT * FROM `loan` WHERE uid=$user_id AND (status_log ='account manager' OR status_log ='recovery officer') ORDER BY id DESC");
                    $lc = townum($userloan);
        if($lc == 0){
        include 'new_loan_inc.php';
        }?>
            <div class="container">
                <?php
                    while($userloanfetch = towfetch($userloan)){
                        $re = towquery("SELECT reason FROM loan_apply WHERE id=".$userloanfetch['lid']."");
                        $reason = towfetch($re);
                        $reason = $reason['reason'];?>
                    <div class="row">
                    <div class="col-md-6 fishow card text-center shadow bg-white rounded" style="background:#fff;border:10px solid #F6F8FA; ">
                         <span style="line-height:50px;">CLL<?=$userloanfetch['lid'];?></span>
                    </div>
                    <div class="col-md-6 fishow card text-center shadow bg-white rounded" style="background:#fff;border:10px solid #F6F8FA; ">
                         <span style="line-height:50px;" onclick="vd()">View Details</button>
                    </div>
                    <div class="col-12 col-md-3 seshowa card text-center shadow bg-white rounded" style="background:#fff;border:10px solid #F6F8FA; ">
                         <span style="line-height:100px;">CLL<?=$userloanfetch['lid'];?></span>
                    </div>
                    <div class="col-12 col-md-3 seshowa card text-center shadow bg-white rounded" style="background:#fff;border:10px solid #F6F8FA; ">
                         <span style="line-height:100px;">Approved amount -- Rs<span> <?=(float)$userloanfetch['processed_amount']+(float)$userloanfetch['p_fee']+(float)$userloanfetch['origination_fee'];?></span></span>
                    </div>
                    <div class="col-12 col-md-3 seshowa card text-center" style="background:#fff;border:10px solid #F6F8FA;">
                         <span style="line-height:100px;">Disbursed date -- <span> <?php echo date("Y-m-d", strtotime($userloanfetch['processed_date']));?></span></span>
                    </div>
                    <div class="col-12 col-md-3 seshowa card text-center" style="background:#fff;border:10px solid #F6F8FA;">
                         <span style="line-height:100px;">Reason of loan -- <span><?=$reason?></span></span>
                    </div>
                    </div>
                    <?php } ?>
            </div>
         <?php
         $loidat = 0;
         if($lc > 0){
            $a = towquery("SELECT * FROM `loan_apply` WHERE `uid`=$user_id AND (status = 'account manager') ORDER BY id ASC");
            $talimit = 0;
            $tloan = 0;
            while($b = towfetch($a)){
                $loidat = $b['id'];
             $lo = towquery("SELECT * FROM loan WHERE lid=".$b['id']);
             $lof = towfetch($lo);
             $loan_amountc = (float)$b['amount'] + (float)$b['processing_fees'] + (float)$b['origination_fee'];
             $salary_date = $userpro_salary_date;
             $processed_date = date_create($lof['processed_date']);
             $dis_date = date_format($processed_date,"Y-m-d");
             $dis_date = date('Y-m-d', strtotime( $dis_date . " -1 day"));
             $sal_day = $dis_date;
             $tax = $loan_amountc / 100 * 1.8;
             $di = strtotime($dis_date);
             $sa = strtotime($sal_day);
             $datediff = $sa - $di;
             $day_gap = round($datediff / (60 * 60 * 24)) + 1;
             $femi_date = date('Y-m-d', strtotime( $sal_day . " +30 day"));
             $semi_date = date('Y-m-d', strtotime( $femi_date . " +35 day"));
             $fe = strtotime($femi_date);
             $se = strtotime($semi_date);
             $fedatediff = $fe - $di;
             $feday_gap = round($fedatediff / (60 * 60 * 24));
             $femi_amount = ((($loan_amountc/100) * 70) + ($loan_amountc*0.001*$feday_gap)) + (($loan_amountc/100) * 2);
             $sedatediff = $se - $fe;
             $seday_gap = round($sedatediff / (60 * 60 * 24));
             $semi_amount = ((($loan_amountc/100) * 30) + $loan_amountc * 0.001 * $seday_gap) + (($loan_amountc/100) * 2);
             $preclose = (($loan_amountc) + ($loan_amountc / 100) * 4) + (($loan_amountc/100) * 2);
             $femi_dateaa = $femi_date;
         ?>
         <div class="container" style="background:#fff;border:10px solid #F6F8FA; ">
             <br>
             <?php
                    $today = date('Y-m-d');
                    $today = date_create($today);
                    $femi_datepc = date_create($femi_date);
                    $lessday = date_format($femi_datepc,"Y-m-d");
                        $today = date_format($today,"Y-m-d");
                        $lessday = strtotime($lessday);
                        $today = strtotime($today);
                    if($lessday < $today){
                        $check = towquery("SELECT * FROM `transaction_details` WHERE `cllid`='".$b['id']."' AND `transaction_flow`='firstemi'");
                        if(townum($check) == 1){
                        }else{
                        $extraday = $today - $lessday;
                        $pd = $penalitydays = $extradaya = round($extraday / (60 * 60 * 24));
                        $penalitydays--;
                        $fpenality = ((($loan_amountc)*0.70)/100)*3;
                        if($penalitydays >= 29){
                            $penalitydays = $penalitydays-29;
                            $atnp = ((($loan_amountc)*0.70) * 0.004)*29;
                            $fpenality += $atnp;
                                    if($penalitydays >= 60){
                                        $penalitydays = $penalitydays-60;
                                        $atnp = (((($loan_amountc)*0.70)) * 0.0035)*60;
                                        $fpenality += $atnp;
                                    }else{
                                        $atnp = (((($loan_amountc)*0.70)) * 0.0035)*$penalitydays;
                                        $fpenality += $atnp;
                                        $penalitydays = 0;
                                    }
                        }
                        else{
                            $atnp = ((($loan_amountc)*0.70) * 0.004)*$penalitydays;
                            $fpenality += $atnp;
                            $penalitydays = 0;
                        }
                        $di = strtotime($dis_date);
                        $sa = $today;
                        $datediff = $sa - $di;
                        $day_gap = round($datediff / (60 * 60 * 24)) + 1;
                        towquery("UPDATE `loan` SET `penality_charge`='$fpenality',`exhausted_period`='$day_gap' WHERE `lid`='".$b['id']."'");
                        }
                    }else{
                    $fpenality=0;
                    }?>
                    <?php
                    $today = date('Y-m-d');
                    $today = date_create($today);
                    $semi_datepc = date_create($semi_date);
                    $slessday = date_format($semi_datepc,"Y-m-d");
                        $today = date_format($today,"Y-m-d");
                        $slessday = strtotime($slessday);
                        $today = strtotime($today);
                    if($slessday < $today){
                        $check = towquery("SELECT * FROM `transaction_details` WHERE `cllid`='".$b['id']."' AND `transaction_flow`='firstemi'");
                        if(townum($check) == 1){
                        }else{
                        $extraday = $today - $slessday;
                        $pd = $penalitydays = $extradaya = round($extraday / (60 * 60 * 24));
                        $penalitydays--;
                        $penality = (($semi_amount)/100)*3;
                        if($penalitydays >= 29){
                            $penalitydays = $penalitydays-29;
                            $atnp = (($semi_amount) * 0.004)*29;
                            $penality += $atnp;
                            // echo $penality;exit;
                                    if($penalitydays >= 60){
                                        $penalitydays = $penalitydays-60;
                                        $atnp = ((($semi_amount)) * 0.0035)*60;
                                        $penality += $atnp;
                                    }else{
                                        $atnp = ((($semi_amount)) * 0.0035)*$penalitydays;
                                        $penality += $atnp;
                                        $penalitydays = 0;
                                    }
                        }else{
                            $atnp = (($semi_amount) * 0.004)*$penalitydays;
                            $penality += $atnp;
                            $penalitydays = 0;
                        }
                        towquery("UPDATE `loan` SET `penality_charge`='$penality',`exhausted_period`='$extradaya' WHERE `lid`='".$b['id']."'");
                        }
                    }else{
                    $penality=0;
                    }?>
                    <div class="table-responsive">
                    <table class="table">
                <tbody class="myflex">
                  <tr class="dn">
                    <th>EMI Date </th>
                    <th class="fishow">Amount to Repay (inclusive Gst)</th>
                    <th class="seshow">EMI Amount</th>
                    <th class="seshow">Penalty</th>
                    <th>Action</th>
                  </tr>
                  <tr>
                    <td class="mdn"><b>EMI 1</b></td>
                    <td><?=$femi_date?></td>
                    <td class="fishow">Rs. <?=$femi_amount + $fpenality?> <?php if($lof['femi']){echo '<span class="text-success">Paid</span>';}?></td>
                    <td class="seshow">Rs. <?=$femi_amount?> <?php if($lof['femi']){echo '<span class="text-success">Paid</span>';}?></td>
                    <td class="seshow">Rs. <?=$fpenality?>
                    <td><?php if($lof['femi']){echo '<span class="text-success">Paid</span>';}else{ ?><button class="btn btn-success" data-toggle="modal" data-target="#exampleModal">Pay Now</button><?php } ?></td>
                    </td>
                  </tr>
                  <tr>
                    <td class="mdn"><b>EMI 2</b></td>
                    <td><?=$semi_date?>  </td>
                    <td class="fishow">Rs. <?=$semi_amount + $penality?><?php if($lof['semi']){echo '<span class="text-success">Paid</span>';}?></td>
                    <td class="seshow">Rs. <?=$semi_amount?><?php if($lof['semi']){echo '<span class="text-success">Paid</span>';}?></td>
                    <td class="seshow">Rs. <?=$penality?></td>
                    <td><?php if($lof['semi']){echo '<span class="text-success">Paid</span>';}else{ ?><button class="btn btn-success" data-toggle="modal" data-target="#exampleModal">Pay Now</button><?php } ?></td>
                  </tr>
                  
                </tbody>
              </table>
                    </div>
                <?php 
                if($femi_dateaa > date('Y-m-d')){
                if($lof['femi']){}else{
                ?>
                <span style="color:#007abb;">Pre-Close your Loan ( CLL<?=$loidat;?> ) with less Amount -  (Rs <b style="font-size:20px;"><?php if($lof['femi']){ echo $preclose - $femi_amount;}else{echo $preclose;} ?></b>) on or before “<?=$femi_dateaa?>”  & Reapply the full limit instantly. <button class="btn btn-success" data-toggle="modal" data-target="#exampleModal">Pay Now</button></span><br><br>
                <?php }} ?>
         </div>
         <?php 
         $tloan += $loan_amountc;
         if($lof['femi']){$talimit += (($loan_amountc/100)*70);}
         } ?>
         <?php
         $userloan = towquery("SELECT * FROM `loan_apply` WHERE `uid`=$user_id AND (status = 'pending' OR status = 'disbursal' OR status = 'follow up' OR status = 'account manager') ORDER BY id ASC");
         if(townum($userloan) == 1){
             $a = towquery("SELECT `amount`,`processing_fees`,`origination_fee` FROM `loan_apply` WHERE `uid`=$user_id AND (status = 'pending' OR status = 'disbursal' OR status = 'follow up' OR status = 'account manager')");
            $b = towfetch($a);
            $b['totalamt'] = (float)$b['amount']+(float)$b['processing_fees']+(float)$b['origination_fee'];
         if(($lof['femi']) or ($b['totalamt'] < $user_loan_limit)){
         ?>
         <div class="container" style="background:#fff;border:10px solid #F6F8FA; display: flex;flex-direction: column;justify-content: center;align-items: center;">
         <h4>You can apply for <?php echo $user_loan_limit - ($tloan - $talimit);?> more</h4>
         <?php if($fpenality > 0){?>
         <h3>kindly clear the existing loan to get a new loan </h3>
         <?php }else{ ?>
         <!--<form method="post" action="apply.php">-->
         <!--    <input type="checkbox" required=""> I agree to creditlab <a target="_blank" href="../terms.php">Terms of use</a> and <a target="_blank" href="../privacy.php">privacy policy</a><br>-->
         <!--    <input type="hidden" name="amount" value="<?=$user_loan_limit - ($tloan - $talimit);?>">-->
         <!--    <input type="hidden" name="days" value="30">-->
         <!--    <input type="hidden" name="reason" value="Personal">-->
         <!--    <input type="submit" class="btn btn-success" value="apply">-->
         <!--</form>-->
         <a href="newloan.php" class="btn btn-success">Apply Now</a>
         <?php } ?>
         </div>
         <?php }}?>
         <div class="container" style="background:#fff;border:10px solid #F6F8FA; ">
             <h4>
                 <br>
                 <br>
<span style="color:#178117;">***your repayment impacts your </span><br><br>
<span style="color:#24c524;">
       <p>- CIBIL, EXPERIAN, EQUIFAX & CRIF SCORES</p>
       <p>- social credibility </p>
       <p>- Trust quotient </p>
       <p>- Credit limit in all other platforms</p>
</span>
             </h4>
         </div>
         <div class="container" style="background:#fff;border:10px solid #F6F8FA; ">
             <h4>
                 <br>
                 <p>*Please make the repayments on or before your respective EMI dates . Post that penalty will be charged.</p>
                 <p>* kindly contact your relationship manager for repayment details of your loan</p>
                 <p>* Warning:- We are not liable for any payments made in any other accounts, please make all payments in company's account only with name SUNGOLD CAPITAL LIMITED ( until you make the payment through payment gateway)</p>
             </h4>
         </div>
<?php } ?>
        <br>
        <br>
        <br>
<?php
include_once 'foot.php';
?>
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">REPAYMENT BANK DETAILS</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form >
      <div class="modal-body">
<?php if($lof['processed_date'] > '2024-05-04 00:00:00'){ ?>
<h4>NAME OF ACCOUNT : Matsya Fincap Pvt Ltd</h4>
<br>
<h4>UPI ID : MATSYA.06@cmsidfc</h4>
<br>
<?php }else{ ?>
<h4>NAME OF ACCOUNT : SUNGOLD CAPITAL LIMITED</h4>
<br>
<h4>BANK NAME : HDFC BANK LIMITED </h4>
<br>
<h4>ACCOUNT NUMBER : 50200084336432</h4>
<br>
<h4>IFSC CODE : HDFC0004216</h4>
<br>
<?php } ?>
* For instant clearance, please make an IMPS bank transfer 
<br>
* Please make any repayments to only this bank account. Payments made to any other bank account are not subject to our liability.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" data-dismiss="modal" data-toggle="modal" data-target="#alreadypaid">Already paid ?</button>
      </div>
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="alreadypaid" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">REPAYMENT BANK DETAILS</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="payment.php" method="post" enctype="multipart/form-data">
      <div class="modal-body">
              <div class="form-group">
                  <label>UTR or Reference number</label>
                  <input type="text" class="form-control" placeholder="UTR or Reference number" name="utr_ref">
              </div>
              <div class="form-group">
                  <label>Loan ID</label>
                  <select class="form-control" name="loan_id">
                      <?php $z = towquery("SELECT * FROM `loan_apply` WHERE `uid`=$user_id AND (status = 'pending' OR status = 'disbursal' OR status = 'follow up' OR status = 'account manager') ORDER BY id ASC");
                      while($zz = towfetch($z)){
                      ?>
                      <option value="<?=$zz['id']?>">CLL<?=$zz['id']?></option>
                      <?php } ?>
                  </select>
              </div>
              <div class="form-group">
                  <label>Payment Type</label>
                  <select class="form-control" name="payment_type">
                      <option>First EMI</option>
                      <option>second EMI</option>
                      <option>Pre-Close</option>
                      <option>Full Amount</option>
                  </select>
              </div>
              <div class="form-group">
                  <label>Upload payment screenshot</label>
                  <input type="file" class="form-control" name="payment_screenshot" accept="image/*">
              </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" name="submit" data-toggle="modal" data-target="#alreadypaid">Submit</button>
      </div>
      </form>
    </div>
  </div>
</div>
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
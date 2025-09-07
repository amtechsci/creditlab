<?php $ca = towquery("SELECT SUM(`origination_fee` + `processing_fees` + `amount`) AS total,id FROM loan_apply WHERE uid=$user_id AND (status='pending' OR status='follow up' OR status='disbursal' OR status='account manager') GROUP BY id");
$tl = townum($ca);
if($tl == 0){}else{
    $tamt = 0;
    $talimit = 0;
$fech = 0;
while($caf = towfetch($ca)){
    $tamt += $caf['total'];
    $fem = towquery("SELECT * FROM `loan` WHERE lid={$caf['id']}");
    if(townum($fem) > 0){
        $femf = towfetch($fem);
        if($femf['femi']){$talimit += (($tamt/100)*70);}
        $processed_date = date_create($femf['processed_date']);
             $dis_date = date_format($processed_date,"Y-m-d");
             $dis_date = date('Y-m-d', strtotime( $dis_date . " -1 day"));
             $sal_day = $dis_date;
             $femi_date = date('Y-m-d', strtotime( $sal_day . " +30 day"));
             $today = date('Y-m-d');
                    $today = date_create($today);
             $today = date_format($today,"Y-m-d");
             $lessday = strtotime($femi_date);
             $today = strtotime($today);
             if($lessday < $today){if($femf['femi'] == 0){$fech++;}}
    }
}
$ch = $user_loan_limit - ($tamt - $talimit);
}
$tc = townum(towquery("SELECT id FROM loan_apply WHERE uid=$user_id AND (status='pending' OR status='follow up' OR status='disbursal')"));
// echo $fech;exit;
if($ch > 0 and $tc ==  0 and $tl < 2 and $fech == 0){
    include 'applynow2.php';
}else{
?>
<?php $a = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND (status='pending' OR status='follow up') ORDER BY id DESC");
        if(townum($a) != 0){ ?>
                <div id="alert" style="padding:15px;background:#fff; position:relative; background:#ccffff;">
                <h3>Your loan is applied in creditlab.in and we will get back to you soon through mail or call.<br> Thank you 😊.</h3>
            </div><br>
            <script>
                setTimeout(function(){
                    window.location.replace('index.php');
                }, 5000);
            </script>
            <?php }
            ?>
            <?php $a = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND status='disbursal' ORDER BY id DESC");
        if(townum($a) != 0){
$loanfetch = towfetchassoc($a);
        if(empty($user_selfie)){ ?>
            <div class="container">
            <div class="row">
            <div class="col-md-1"></div>
            <div class="col-md-6">
        <h1>Video KYC</h1>
        <h5>Kindly upload a short video by pronouncing the below sentence & submit.</h5>
        “I AM APPLYING LOAN AT CREDITLAB WITH MY KNOWLEDGE“
        <video style="width: 100%;height: 100%;" autoplay playsinline></video>
        <div id="countdown"></div>
        <button id="start-btn" class="btn btn-success">Take Video / Retake Video</button>
        <p style="color:red;">Note*<br>
        LOOK at the camera in such a way that your complete face is covered in the video<br>

          Don’t wear cap 🧢 <br>
          Don’t wear spects</p>
          <button id="upload-btn" class="btn btn-primary" disabled>Submit</button>
        </div>
        </div>
        </div>
        <script>
    // Get the video element and buttons
    const video = document.querySelector('video');
    const startBtn = document.querySelector('#start-btn');
    const uploadBtn = document.querySelector('#upload-btn');

    // Constraints for capturing video
    const constraints = {
      audio: true,
      video: {
        width: 640,
        height: 480
      }
    };

    let mediaRecorder;
    let chunks = [];
    let blob;
    function startRecording() {
        var timeleft = 10;
var downloadTimer = setInterval(function(){
  if(timeleft <= 0){
    clearInterval(downloadTimer);
    document.getElementById("countdown").innerHTML = "Finished";
  } else {
    document.getElementById("countdown").innerHTML = timeleft + " seconds remaining";
  }
  timeleft -= 1;
}, 1000);
      navigator.mediaDevices.getUserMedia(constraints)
        .then(stream => {
          video.srcObject = stream;
          video.play();
          mediaRecorder = new MediaRecorder(stream);
          mediaRecorder.start();
          setTimeout(stopRecording, 10000);
          mediaRecorder.addEventListener('dataavailable', event => {
            chunks.push(event.data);
          });
        })
        .catch(error => console.log('getUserMedia Error: ', error));
    }

    function stopRecording() {
      uploadBtn.disabled = false;
      startBtn.disabled = false;
      mediaRecorder.stop();
      video.pause();
    }

    function uploadRecording() {
      blob = new Blob(chunks, { type: 'video/mp4' });
      const formData = new FormData();
      formData.append('video', blob, 'video.mp4');
      const xhr = new XMLHttpRequest();
      xhr.open('POST', '/zzz.php');
      xhr.onload = () => {
        if (xhr.status === 200) {
          if(xhr.responseText == 1){
              window.location.replace('index.php');
          }
        } else {
          console.log('Error uploading video.');
        }
      };
      xhr.send(formData);
    }

    // Attach event listeners to the buttons
    startBtn.addEventListener('click', () => {
      startBtn.disabled = true;
      startRecording();
    });
    uploadBtn.addEventListener('click', () => {
      uploadBtn.disabled = true;
      startBtn.disabled = false;
      uploadRecording();
    });
</script>
        <?php 
        include_once 'foot.php';
        exit; }else{
        if($loanfetch['ubank_id'] == 0 or $loanfetch['ubank_id'] == 2){
        $ub = towquery("SELECT * FROM `user_bank` WHERE uid='{$loanfetch['uid']}'");
        if(townum($ub) > 0){ ?>
        <div class="container-fluid" style="background:#fff;">
            <?php if($loanfetch['ubank_id'] == 2){ ?>
            <h4 style="color:red;">** The request for new bank details has been rejected, so please confirm your old bank details to proceed or contact your Relationship manager. **</h4>
            <?php } ?>
            <style>
                .myflex tr{display: flex;flex-direction: row;}
                .mdn {display:none!important;}
                @media screen and (max-width: 480px) {
                .myflex tr{display: flex;flex-direction: column;}
                .myflex tbody{display: flex;flex-direction: row;}
                .dn {display:none!important;}
                .mdn{display:block!important;}
                }
            </style>
        <div class="card">
            <table class="table myflex">
                <tbody>
                <tr>
                    <th>Holder Name</th>
                    <th>Bank Name</th>
                    <th>Account Number</th>
                    <th>IFSC</th>
                    <th class="dn">Action</th>
                </tr>
                <?php $ubf = towfetch($ub);?>
                <tr>
                    <td><?=$ubf['ac_name'] ? $ubf['ac_name'] : 'NA'?></td>
                    <td><?=$ubf['bank_name']?></td>
                    <td><?=$ubf['ac_no']?></td>
                    <td><?=$ubf['ifsc_code']?></td>
                    <td><button class="btn btn-primary" onclick="add_new()">Add New</button><a class="btn btn-success" href="confirmbank.php?id=<?=$loanfetch['id']?>&from=newloan">Confirm</a></td>
                </tr>
                </tbody>
            </table>
<h4>** Creditlab will only change your bank details if your latest salary has been credited to a new account & it is subject to verification **</h4>
            <style>
                .add-new{
                    display:none;
                }
            </style>
            <div class="add-new">
                <form method="post" action="addbank.php" enctype='multipart/form-data'>
                    <input type="hidden" name="id" value="<?=$loanfetch['id']?>">
                    <h3>Add New Bank Details</h3>
                    <td>
                    <label>Account number</label>
                    <input type="text" name="ac_no" placeholder="Account number" class="form-control"></td>
                    <td>
                    <label>IFSC code</label>
                    <input type="text" name="ifsc_code" placeholder="IFSC code" class="form-control"></td>
                    <td><label>Bank statment</label>
                    <input type="file" name="bank_statment" class="form-control" accept="application/pdf">
                    </td>
                    <td><button type="submit" class="btn btn-success">Save</button></td>
                </form>
            </div>
            <script>
                function add_new(){
                    $('.add-new').show();
                }
            </script>
        </div>
        </div>
        <?php }
        }else{
        $cb = towfetch(towquery("SELECT verify FROM `user_bank` WHERE uid='$user_id' ORDER BY `id` DESC"));
        if($cb['verify'] == 0){ ?>
            <div id="alert" style="padding:15px;background:#fff; position:relative; background:#ccffff;">
                <h3>Your bank statement is under verification, new bank details would be updated once verified<br> Thank you 😊.</h3>
            </div><br>
        <?php }elseif($loanfetch['agreement'] == 0){
if($loanfetch['keyid'] == 0){ ?>
<h4>Please read the complete Key fact statement & loan sanction letter</h4>
<br>
<iframe src="https://creditlab.in/key.php?id=<?=$loanfetch['id']?>" style="width:100%;"></iframe>
<form action="agreement.php?from=newloan"><input type="hidden" name="from" value="new_loan">
<center><input type="checkbox" name="id" value="<?=$loanfetch['id']?>" required> I understand & I agree to the loan sanction letter & Key fact statement.<br>
I confirm that I have read, understood and agreed to the Creditlab Product Terms and Conditions and Related Policies. I grant my irrevocable consent to lender and/or any authorized party nominated by lender for making public, in case I commit wilful default. I hereby promise to pay Creditlab /lender or order on demand principal amount together with interest and delayed interest, if any, as prescribed in KFS from due date until repayment thereof.<br>
<button type="submit" class="btn btn-primary">I agree</button></center>
</form>
<?php }else{ 
 ?>
<div id="alert" style="padding:15px;background:#fff; position:relative; background:#ccffff;">
<h4>Please read the complete loan agreement carefully which is attested with your <br>
-ID proof &<br>
-address proof.</h4></div><br>
<iframe src="https://creditlab.in/admin/loan_agreement.php?id=<?=$loanfetch['id']?>" style="width:100%;"></iframe>
<form action="agreement.php?from=newloan"><input type="hidden" name="from" value="new_loan">
<center><input type="checkbox" name="id" value="<?=$loanfetch['id']?>" required> I understand & I accept that I have read the loan agreement with my knowledge.<br>
<button type="submit" class="btn btn-primary">I agree</button></center>
</form>
            
            
<?php }
        }else{
            echo '<h3>your loan is approved and you will get the loan shortly.<br> Thank you 😊.</h3>';
            ?><script>
                setTimeout(function(){
                    window.location.replace('index.php');
                }, 5000);
            </script><?php
        }
        }
        } ?>  
<?php }
if((($ch <= 0 or $tl == 2) and $tc ==  0) or $fech){
?>
        <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                            <div class="breadcome-list">
                                <h4 style="color:#8d8d8d">personal loan</h4>
                                <h3  style="color:#000"><span style="font-size:10px;"><sup>Upto</sup></span> ₹<?=$user_loan_limit*1.5?></h3>
                                <i class="fa fa-lock" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                            <div class="breadcome-list">
                                <h4 style="color:#8d8d8d">personal loan</h4>
                                <h3  style="color:#000"><span style="font-size:10px;"><sup>Upto</sup></span> ₹<?=$user_loan_limit*2?></h3>
                                <i class="fa fa-lock" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                    <div class="breadcome-list">
                        <h3><i class="fa fa-unlock" aria-hidden="true" style="font-size:50px"></i> Unlock your limits</h3>
                        <br><br><br>
                        <h4>[ Your Creditlab Trust Quotient = <?php 
                   $totalfls = towfetch(towquery("SELECT SUM(`transaction_amount`) AS total FROM `transaction_details` WHERE NOT `transaction_flow`='creditlab To Customer' AND uid=$user_id"));
                   $totalflss = $totalfls['total'] ? $totalfls['total'] : 0;
                   
                   $totalflsp = towfetch(towquery("SELECT SUM(`processed_amount`) AS total FROM `loan` WHERE uid=$user_id AND status_log IN ('default','cleared')"));
                   $totalflssp = $totalflsp['total'] ? $totalflsp['total'] : 0;
                   
                   $forgst = towfetch(towquery("SELECT SUM(transaction_amount) AS total FROM `transaction_details` WHERE transaction_flow IN ('firstemi','part','full','secondemi','preclose')"));
                   $forgstf = $forgst['total'] ? $forgst['total'] : 0;
                   
                   $azxs =((0.12*$forgstf)/1.18); ?>
                       <?=(($totalflss-$totalflssp-(ceil(0.18*($azxs))))/$user_loan_limit);?> ]</h4>
                        <h4>* Increase your CTQ to unlock higher loan amounts</h4>
                        <h4>* On time repayments increases your CTQ</h4>
                        <h4>* A good CIBIL score & hike in your net salary may unlock your higher loan amounts.</h4>
                    </div>
                </div>
            </div>
            <script>
                // setTimeout(function(){
                //     window.location.replace('index.php');
                // }, 5000);
            </script>
<?php }} ?>
<style>
.icstu { font-size:12px; }
.fa-lock { font-size: 48px;color:red }
    @media screen and (max-width: 720px) {
      .icstu { font-size:9px; }
      .fa-lock { font-size: 25px;color:red }
    }
.card {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    /*width: 300px;*/
    padding: 10px;
    margin-bottom: 10px;
    text-align: center;
}

.card-content h2 {
    font-size: 18px;
    color: #333333;
    margin-bottom: 20px;
}

.apply-btn {
    background-color: #007BFF;
    color: #ffffff;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    margin-bottom: 20px;
}

.apply-btn:hover {
    background-color: #0056b3;
}

.tenure {
    font-size: 14px;
    color: #666666;
    margin-bottom: 10px;
}

.unlock-message {
    font-size: 14px;
    color: #666666;
    display: none; /* Initially hide the message */
}

.unlock-icon {
    color: #007BFF;
}
.loan-info-container {
    margin: 0;
    padding: 0;
}
.loan-info-card, .outstanding-amount-card, .new-limit-card, .pay-now-card {
    background-color: #f8f9fa;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 5px;
    box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.1);
}
.loan-info-content, .new-limit-content {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.loan-info-content span, .new-limit-content p, .pay-now-card p, .pay-now-card ul {
    margin-bottom: 5px;
}
.pay-now-card ul {
    padding-left: 15px;
}
.apply-btn, .pay-now-btn {
    padding: 8px 15px;
    margin-top: 10px;
    border: none;
    border-radius: 5px;
    background-color: #007bff;
    color: white;
    cursor: pointer;
}
.apply-btn:hover, .pay-now-btn:hover {
    background-color: #0056b3;
}
.bank-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
    z-index: 99999;
}
.bank-popup-content {
    text-align: center;
    padding:20px;
    max-height: 90vh;
    overflow: auto;
}
.bank-popup-close {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 18px;
    cursor: pointer;
}
@media only screen and (max-width: 768px) {
    .loan-info-content span, .new-limit-content p, .pay-now-card p, .pay-now-card ul {
        line-height: 1.4;
    }
    .apply-btn, .pay-now-btn {
        width: 100%;
        margin-top: 10px;
    }
}
.new-limit-content {
    background-color: #e9f7ef;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.limit-title {
    font-size: 1.5em;
    color: #2d7a46;
    margin-bottom: 15px;
}

.apply-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    background-color: #28a745;
    color: white;
    cursor: pointer;
    font-size: 1em;
    transition: background-color 0.3s ease;
}

.apply-btn:hover {
    background-color: #218838;
}

.unlock-message {
    margin-top: 15px;
    font-size: 0.9em;
    color: #555;
}

.unlock-icon {
    color: #28a745;
    font-size: 1.2em;
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
                                <li><h4><?=$user_name?></h4></li>
                                <li><span class="bread-blod"><?=$user_mobile;?></span></li>
                            </ul>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                            <div class="rc">
                                <ul class="breadcome-menua">
                                    <li>Your creditlab.in ID: <span class="bread-blod"><?=$user_rcid;?></span></li>
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

<div class="container chat" style="background:#fff;">
    <?php 
    if(in_array($page_state,[1,2,3,4,5,6,7,8,9])){
                                                    $n = 1;
                                                }elseif(in_array($page_state,[12,13,14,15,16,17])){
                                                    $n = 2;
                                                }else{
                                                    $n = 3;
                                                }
    $a_pending = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND (status='pending' OR status='follow up') ORDER BY id DESC");
    if (townum($a_pending) > 0) { $n = 1;}
                                                 $wa_num = towfetch(towquery("SELECT * FROM `whatsapp_no` WHERE `page_id`='$n'"))['wa_phone'];?>
    <h4 style="padding:10px;">Contact us on whatsapp<a href="https://api.whatsapp.com/send?phone=91<?=$wa_num?> &text=CLID : <?=$user_rcid?> I need Help in ..." class="btn btn-default" style="background:#dfdfdf;">
        <img src="/ws.svg" style="width:30px;"> Whatsapp</a>
    </h4>
</div>

<?php if ($per > 0): ?>
<div class="container">
    <div class="progress">
        <div class="progress-bar" role="progressbar" aria-valuenow="<?=$per?>" aria-valuemin="0" aria-valuemax="100" style="width:<?=$per?>%">
            <?=$per?>%
        </div>
    </div>
</div>
<br>
<?php endif; ?>

<?php

if (townum($a_pending) > 0) { ?>
    <div id="alert" style="padding:15px;background:#fff; position:relative; background:#ccffff;">
        <h3>Your loan is applied in creditlab.in and we will get back to you soon through mail or call.<br> Thank you 😊.</h3>
    </div><br>
<?php } ?>

<?php
// ## 2. DISPLAY CONTENT BASED ON PAGE STATE ##
switch ($page_state) {

    case 12: // Needs Signature
?>
        <style>
            canvas { background-color: #fff; border: 1px solid #000; cursor: crosshair; }
            button { margin-top: 10px; margin-right: 5px; padding: 5px 10px; }
        </style>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h1>Signature</h1>
                    <h5>Please sign below similar to your signature on PAN Card.</h5>
                    <canvas id="signature-pad" style="width:100%"></canvas>
                    <br>
                    <button id="clear" class="btn btn-danger">Clear & re-sign</button><br>
                    I authorize Creditlab to use my electronic signature for signing loan agreements on my behalf for all future loans.<br>
                    <button id="save" class="btn btn-success">Save and submit</button>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('signature-pad');
            const ctx = canvas.getContext('2d');
            let drawing = false;
            let lastPos = { x: 0, y: 0 };

            function getPosition(event) {
                const rect = canvas.getBoundingClientRect();
                return {
                    x: (event.clientX || event.touches[0].clientX) - rect.left,
                    y: (event.clientY || event.touches[0].clientY) - rect.top
                };
            }

            function startDrawing(event) {
                drawing = true;
                lastPos = getPosition(event);
                ctx.beginPath();
                ctx.moveTo(lastPos.x, lastPos.y);
                event.preventDefault();
            }

            function draw(event) {
                if (!drawing) return;
                const pos = getPosition(event);
                if (pos.x !== lastPos.x || pos.y !== lastPos.y) {
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.strokeStyle = '#000';
                    ctx.lineTo(pos.x, pos.y);
                    ctx.stroke();
                    lastPos = pos;
                }
                event.preventDefault();
            }

            function stopDrawing() {
                drawing = false;
                ctx.beginPath();
            }

            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            canvas.addEventListener('touchstart', startDrawing);
            canvas.addEventListener('touchmove', draw);
            canvas.addEventListener('touchend', stopDrawing);
            canvas.addEventListener('touchcancel', stopDrawing);

            document.getElementById('clear').addEventListener('click', () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            });

            document.getElementById('save').addEventListener('click', () => {
                const dataURL = canvas.toDataURL('image/png');
                fetch('save_signature.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image: dataURL })
                }).then(response => response.text()).then(result => {
                    location.reload();
                }).catch(error => console.error('Error:', error));
            });
        });
        </script>
<?php
        break;

    case 13: // Needs Video KYC
?>
        <div class="container">
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-6">
                    <h1>Video KYC</h1>
                    <h5>Kindly upload a short video by pronouncing the below sentence & submit.</h5>
                    “I AM APPLYING LOAN AT CREDITLAB WITH MY KNOWLEDGE“
                    <video id="videoElement" style="width: 100%; height: 100%;" playsinline webkit-playsinline></video>
                    <div id="countdown"></div>
                    <button id="start-btn" class="btn btn-primary">Take Video / Retake Video</button>
                    <p style="color:red;">Note*<br>
                        LOOK at the camera in such a way that your complete face is covered in the video<br>
                        Don’t wear cap 🧢 <br>
                        Don’t wear spects</p>
                    <button id="upload-btn" class="btn btn-success" disabled>Submit</button>
                </div>
            </div>
        </div>
        <script>
        const video = document.querySelector('#videoElement');
        const startBtn = document.querySelector('#start-btn');
        const uploadBtn = document.querySelector('#upload-btn');
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const constraints = { audio: true, video: { width: 640, height: 480, facingMode: 'user' } };
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

            navigator.mediaDevices.getUserMedia(constraints).then(stream => {
                video.srcObject = stream;
                if (isIOS) {
                    video.setAttribute('playsinline', true);
                    video.setAttribute('webkit-playsinline', true);
                }
                video.play();
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.start();
                setTimeout(stopRecording, 10000);
                mediaRecorder.addEventListener('dataavailable', event => chunks.push(event.data));
            }).catch(error => {
                console.error("Error accessing media devices.", error);
                alert("Unable to access camera or microphone. Please check your permissions.");
            });
        }

        function stopRecording() {
            uploadBtn.disabled = false;
            startBtn.disabled = false;
            mediaRecorder.stop();
            video.pause();
            if (isIOS && video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
                video.srcObject = null;
            }
        }

        function uploadRecording() {
            blob = new Blob(chunks, { type: 'video/mp4' });
            const formData = new FormData();
            formData.append('video', blob, 'video.mp4');
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/zzz.php');
            xhr.onload = () => {
                console.log(xhr.responseText);
                if (xhr.status === 200 && xhr.responseText == 1) {
                    window.location.replace('index.php');
                } else {
                    console.log('Error uploading video.');
                }
            };
            xhr.send(formData);
        }

        startBtn.addEventListener('click', () => {
            startBtn.disabled = true;
            chunks = [];
            startRecording();
        });

        uploadBtn.addEventListener('click', () => {
            uploadBtn.disabled = true;
            startBtn.disabled = false;
            uploadRecording();
        });
        </script>
<?php
        break;

    case 14: // Needs E-Mandate
        include 'easebuzz.php';
        break;

    case 15: // Agree to Key Fact Statement
?>
        <h4>Please read the complete Key fact statement & loan sanction letter</h4>
        <br>
        <?php if($user_approvenew == 1): ?>
            <iframe src="https://creditlab.in/key.php?id=<?=$loanfetch['id']?>" style="width:100%; height: 500px;"></iframe>
        <?php else: ?>
            <iframe src="https://creditlab.in/key2.php?id=<?=$loanfetch['id']?>" style="width:100%; height: 500px;"></iframe>
        <?php endif; ?>
        <form action="agreement.php" style="padding: 20px;">
            <center>
                <input type="checkbox" name="id" value="<?=$loanfetch['id']?>" style="width:20px;height:20px; vertical-align: middle;" required>
                <label style="margin-left: 10px;">I understand & I agree to the loan sanction letter & Key fact statement.</label><br><br>
                <p>నేను అర్థం చేసుకుంటున్నాను మరియు రుణ ఆమోద పత్రం మరియు కీలక అంశాల ప్రకటనతో నేను ఒప్పుకుంటున్నాను.</p>
                <p>मैं समझता हूँ और ऋण स्वीकृति पत्र और मुख्य तथ्य विवरण पर सहमत हूँ।</p>
                <button type="submit" class="btn btn-primary">I Agree</button>
            </center>
        </form>
<?php
        break;

    case 16: // Agree to Loan Agreement
?>
        <div id="alert" style="padding:15px;background:#fff; position:relative; background:#ccffff;">
            <h4>Please read the complete loan agreement carefully.</h4>
        </div><br>
        <?php if($user_approvenew == 1): ?>
            <iframe src="https://creditlab.in/admin/loan_agreement.php?id=<?=$loanfetch['id']?>" style="width:100%; height: 500px;"></iframe>
        <?php else: ?>
            <iframe src="https://creditlab.in/admin/loan_agreement2.php?id=<?=$loanfetch['id']?>" style="width:100%; height: 500px;"></iframe>
        <?php endif; ?>
        <form action="agreement.php" style="padding: 20px;">
            <center>
                <input type="checkbox" name="id" value="<?=$loanfetch['id']?>" style="width:20px;height:20px; vertical-align: top;" required>
                <label style="margin-left: 10px; text-align: left; display: inline-block; max-width: 80%;">
                    I understand & I accept that I have read the loan agreement with my knowledge.
                    <br>I confirm that I have read, understood and agreed to the Creditlab/lender Terms and Conditions and Related Policies. I grant my irrevocable consent to lender and/or any authorized party nominated by lender for making public, in case I commit wilful default. I hereby promise to pay Creditlab /lender or order on demand principal amount together with interest and delayed interest, if any, as prescribed in KFS from due date until repayment thereof.
                </label>
                <br><br>
                <button type="submit" class="btn btn-primary">I Agree</button>
            </center>
        </form>
<?php
        break;

    case 17: // Loan Approved / Final Message
        echo '<div class="container"><h3>Your loan is approved and you will get the loan shortly.<br> Thank you 😊.</h3></div>';
        break;
}
?>

<div class="container">
    <?php
    $userloan = towquery("SELECT * FROM `loan` WHERE uid=$user_id AND (status_log ='account manager' OR status_log ='recovery officer') ORDER BY id DESC");
    while($userloanfetch = towfetch($userloan)){
        $dis_date = date('Y-m-d', strtotime(date_create($userloanfetch['processed_date'])->format("Y-m-d") . " -1 day"));
        $di = strtotime($dis_date);
        $sa = strtotime(date('Y-m-d'));
        $datediff = $sa - $di;
        $day_gap = round($datediff / (60 * 60 * 24));
    ?>
        <div class="row loan-info-container">
            <div class="col-12 loan-info-card">
                <div class="loan-info-content">
                    <span class="loan-id">Loan ID: CLL<?=$userloanfetch['lid'];?></span>
                    <span class="loan-availed">Loan availed -- Rs<span> <?=$userloanfetch['processed_amount']+$userloanfetch['p_fee']+($userloanfetch['p_fee']*0.18);?></span></span>
                    <span class="exhausted-days">Exhausted days -- <span> <?=$day_gap;?></span><br>Due Date - (<?=date('Y-m-d', strtotime($userloanfetch['processed_date'] . ' +29 day'))?>)</span>
                </div>
            </div>
            <div class="col-12 outstanding-amount-card">
                <span>Total Outstanding Amount -- <b>Rs<?=ceil($userloanfetch['processed_amount']+$userloanfetch['p_fee']+$userloanfetch['service_charge']+($userloanfetch['p_fee']*0.18)+$userloanfetch['penality_charge']);?></b><br>This amount changes w.r.t number of exhausted days</span>
                <a class="pay-now-btn" href="/user/autopay.php">Pay Now</a>
            </div>
            <?php
            $sal = ceil($user_salary*0.40);
            $loan_limit_query = towquery("SELECT MIN(amount) as amount FROM `limit_increment` WHERE amount > $user_loan_limit");
            $nincamt = towfetch($loan_limit_query)['amount'];
            if($nincamt <= 0){
                $nincamt = $user_loan_limit;
            }
            if($userloanfetch['limit_inc_prompt'] == 0){?>
            <div class="col-12 new-limit-card">
                <div class="new-limit-content">
                    <h2 class="limit-title">You are eligible for a new limit of Rs.<?=$nincamt?>.</h2>
                    <button class="apply-btn" onclick="showUnlockMessage()">Apply Now</button>
                    <p id="unlock-message" class="unlock-message">Unlock <span class="unlock-icon">🔓</span> your new limit by increasing your <a href="/user/creditlab_score.php" class="btn btn-success">Creditlab Score</a>.</p>
                </div>
            </div>
            <?php } ?>
            <div class="col-12 pay-now-card">
                <p class="note">* Close the loan anytime before 30 days with no pre-closing charges.<br>
                * Pay only for the days you use it.</p>
                <p class="impact">Your repayment impacts your:</p>
                <ul class="impact-list">
                    <li>CIBIL / Experian / CRIF & Equifax scores</li>
                    <li>Social Credibility</li>
                    <li>Creditlab Score</li>
                    <li>Credit limits with all Banks & NBFCs</li>
                </ul>
            </div>
        </div>
    <?php } ?>
</div>

<div id="bank-details-popup" class="bank-popup" style="display: none;">
    <div class="bank-popup-content">
        <span class="bank-popup-close btn btn-default" onclick="closePopup()">&times;</span>
        <h3>Bank Details</h3>
        <p><strong>UPI Registered Name:</strong>SONU MARKETING PRIVATE LIMITED</p>
        <p><strong>UPI : </strong><button type="button" class="btn">creditlab.in@ybl</button> <button class="btn btn-primary" onclick="navigator.clipboard.writeText('creditlab.in@ybl').then(() => alert('UPI ID copied!'));">Copy</button></p>
        <br>
        <p>- Please ensure that you repay your loan amount to the account details provided above.</p>
        <p>- ⁠We will not be responsible for payments made to any other accounts. If you have any questions or concerns, please consult with your Relationship Manager before making the payment.</p>
    </div>
</div>

<?php
if(townum($userloan) > 0){
    $a_amt = towquery("SELECT `amount`,`processing_fees`,`origination_fee` FROM `loan_apply` WHERE `uid`=$user_id AND (status = 'pending' OR status = 'disbursal' OR status = 'follow up' OR status = 'account manager')");
    $b = towfetch($a_amt);
    $b['totalamt'] = $b['amount']+$b['processing_fees']+$b['origination_fee'];
    if($b['totalamt'] < $user_loan_limit){
        $amt = $user_loan_limit - $b['totalamt'];
        // Commented out original HTML for "eligible for more"
    }
}
?>
<br><br><br>

<?php include_once 'foot.php'; ?>

<script>
    function alertcut(){
        $("#alert").hide();
    }
    function showUnlockMessage() {
        document.getElementById('unlock-message').style.display = 'block';
    }
    function showBankDetails() {
        document.getElementById('bank-details-popup').style.display = 'block';
    }
    function closePopup() {
        document.getElementById('bank-details-popup').style.display = 'none';
    }
</script>
</body>
</html>
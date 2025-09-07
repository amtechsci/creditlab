<?php
$user_loan_start = $user_loan_limit;
$loanquery = towquery("SELECT SUM(`origination_fee` + `processing_fees` + `amount`) AS total,id FROM loan_apply WHERE uid=$user_id AND (status='pending' OR status='follow up' OR status='disbursal' OR status='account manager' OR status='recovery officer') GROUP BY id");
         if(townum($loanquery) > 0){
            $amount = 0;
            $talimit = 0;
            while($a = towfetch($loanquery)){
                $amount = $amount + $a['total']; 
                $fem = towquery("SELECT * FROM `loan` WHERE lid={$a['id']}");
                if(townum($fem) > 0){
                    $femf = towfetch($fem);
                    if($femf['femi']){$talimit += (($amount/100)*70);}
                }
            }
            $user_loan_start = $user_loan_limit - ($amount - $talimit);
}
?>
<style>
.icstu { font-size:12px; }
.fa-lock { font-size: 48px;color:red }
    @media screen and (max-width: 720px) {
      .icstu { font-size:9px; }
      .fa-lock { font-size: 25px;color:red }
    }
</style>
<div class="breadcome-area">
        <div class="single-pro-review-area mt-t-30 mg-b-15">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="product-payment-inner-st">
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade active in" id="description">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <div id="dropzone1" class="pro-ad">
                                                    <form method="post" class="dropzone dropzone-custom needsclick add-professors" action="apply.php<?php if(isset($fff)){ ?>?f=newloan<?php } ?>">
                                                        <div class="row" style="display: flex;justify-content:center;">
                                                            <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                                                                <div>
                                                                    <h3 class="text-center">Loan Amount</h3>
                                                                <div class="slidecontainer text-center">
                                                                    <input type="range" min="1000" max="<?=$user_loan_start;?>" value="<?=$user_loan_start;?>" class="slider" id="myRange" step="100" name="amount">
                                                                    <h3 id="b"></h3>
                                                                    <h4><span id="demo"></span></h4>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Reason for loan</label>
                                                                <select class="form-control" name="reason">
                                                                    <option value="Personal">Personal</option>
                                                                    <option value="Celebration">Celebration</option>
                                                                    <option value="Advance_Salary">Advance Salary</option>
                                                                    <option value="Credit_card_EMI">Credit card EMI</option>
                                                                    <option value="Self_grooming">Self grooming</option>
                                                                    <option value="Holiday">Holiday</option>
                                                                    <option value="Instant_loan">Instant loan</option>
                                                                    <option value="Emergeny">Emergeny</option>
                                                                    <option value="Medical">Medical</option>
                                                                    <option value="Other">Other</option>
                                                                </select>
                                                                <input type="hidden" name="lat" id="lat" value="">
                                                                <input type="hidden" name="long" id="long" value="">
                                                                    <div class="w-100" style="margin-bottom: 15px;">
                                                                            <div style="background:#f2dada;padding:15px;display: flex;align-items: center;justify-content: space-between;">
                                                                                <div style="width: 70%;">
                                                                                    <span style="color:#000;">2 EMI loan<br><br></span>
                                                                                    <span style="color:#178117;"><?=$user_salary*1.5?></span>
                                                                                    <br>Tenure: 65 days<br>
                                                                                </div>
                                                                                <div style="width: 30%;display: flex;flex-direction: column;align-items: flex-start;"><i class="fa fa-lock"></i></span><br><b class="icstu">increase creditlab score to unlock</b></div>
                                                                            </div>
                                                                    </div>
                                                                    <div class="w-100">
                                                                            <div style="background:#f2dada;padding:15px;display: flex;align-items: center;justify-content: space-between;">
                                                                                <div style="width: 70%;">
                                                                                    <span style="color:#000;">3 EMI loan<br><br></span>
                                                                                    <span style="color:#178117;"><?=$user_salary*2?></span>
                                                                                    <br>Tenure: 95 days<br>
                                                                                </div>
                                                                                <div style="width: 30%;display: flex;flex-direction: column;align-items: flex-start;"><i class="fa fa-lock"></i></span><br><b class="icstu">increase creditlab score to unlock</b></div>
                                                                            </div>
                                                                    </div>
                                                                </div>
                                                                <br>
                                                                        <p style="font-size:12px;">
                                                                            <ul style="list-style: disc;">
                                                                                <li style="margin-bottom:15px;">Final tenure, loan amount, interest rate, and processing fee are subject to the credit risk assessment of the partnered NBFC.</li>
                                                                                <li style="margin-bottom:15px;"> Details of this assessment will be fully disclosed in the Key Facts Statement (KFS) and loan agreement prior to loan disbursement.</li>
                                                                                <li style="margin-bottom:15px;">Please note that “Creditlab” is just a facilitator/platform between borrowers & NBFC.</li>
                                                                            </ul>
                                                                        <details id="showded" style="font-size:12px; display:none;">
                                                                          <summary id="showde">*Creditlab Loan Details</summary>
                                                                          <p style="font-size:12px;">
                                                                            Loan amount : <b id="loan_amount"></b><br>
                                                                            Processing fee : <b id="proc_amp"></b><br>
                                                                            Gst fee : <b id="gst_fee"></b><br>
                                                                            Total Interest : <b id="total_int"></b><br>
                                                                            Account Management fee : <b id="acc_man"></b><br>
                                                                            In - Hand amount : <b id="inhand_amt"></b><br>
                                                                            Total repayment : <b id="femp"></b><br>
                                                                          </p>
                                                                        </details>
                                                                        </p>
                                                                        <div class="form-group">
                            												<div class="checkbox">
                            													<label>
                            														<input type="checkbox" required="">I agree to creditlab.in <br> <a href="/terms.pdf" target="_blank">Terms of use</a> & <a href="/policy.pdf" target="_blank">privacy policy</a>
                            													</label>
                            													<div class="help-block with-errors"></div>
                            												</div>
                            											</div>
                                                                        <div class="col-md-12"><button class="btn btn-success" type="submit" id="al">Apply</button></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       <?php
       include_once 'foot.php';
       ?>
<div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="locationModalLabel">Location Access Required</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        We need your location for the service. Please allow location access.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script>
    var amta = document.getElementById("myRange");
    amta.oninput = function() {
    var amt = document.getElementById("myRange").value;
    document.getElementById("demo").innerHTML = amt;
    // var pamt = ((amt/100)*12);
    // var gst = (((amt/100)*12)*0.18);
    // var acc_man = amt*0.01;
    var pamt = ((amt/100)*13);
    // var gst = 0;
    var gst = (pamt*0.18);
    var acc_man = 0;
    var tint = amt*0.03;
    
    document.getElementById("loan_amount").innerHTML = amt;
    document.getElementById("proc_amp").innerHTML = pamt;
    document.getElementById("gst_fee").innerHTML = gst;
    document.getElementById("total_int").innerHTML = tint;
    document.getElementById("acc_man").innerHTML = acc_man;
    document.getElementById("inhand_amt").innerHTML = (amt-(pamt+gst));
    document.getElementById("femp").innerHTML = (parseInt(amt)+parseInt(acc_man)+parseInt(tint));
    }
    function aa(){
        var amt = document.getElementById("myRange").value;
    document.getElementById("demo").innerHTML = amt;
    }
    aa();

if ("geolocation" in navigator) {
  navigator.geolocation.getCurrentPosition(function(position) {
    $('#lat').val(position.coords.latitude);
    $('#long').val(position.coords.longitude);
    $('#al').attr('disabled',false);
  }, function(error) {
    if (error.code == error.PERMISSION_DENIED) {
      $('#locationModal').modal('show');
      $('#al').attr('disabled',true);
    }
  });
} else {
  console.log("Geolocation is not supported by this browser.");
}
document.getElementById("showde").addEventListener("click", vd);
function vd(){
    setTimeout(function() {
        // document.getElementById('showded').removeAttribute('open')
    }, 2000);
}
var famt = <?=$user_loan_start?>;
document.getElementById("loan_amount").innerHTML = famt;
// var pamt = ((famt/100)*12);
// var gst = (((famt/100)*12)*0.18);
// var acc_man = famt*0.01;
var pamt = ((famt/100)*13);
// var gst = 0;
var gst = (pamt*0.18);
var tint = famt*0.03;
var acc_man = 0;
    document.getElementById("proc_amp").innerHTML = pamt;
    document.getElementById("gst_fee").innerHTML = gst;
    document.getElementById("total_int").innerHTML = tint;
    document.getElementById("acc_man").innerHTML = acc_man;
    document.getElementById("inhand_amt").innerHTML = (famt-(pamt+gst));
    document.getElementById("femp").innerHTML = (famt+acc_man+tint);
</script>
</body>
</html>
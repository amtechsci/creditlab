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
<div class="breadcome-area">
                <!--<div class="container-fluid">-->
                <!--    <div class="row">-->
                <!--        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">-->
                <!--            <div class="breadcome-list">-->
                <!--                <div class="row">-->
                <!--                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">-->
                <!--                        <ul>-->
                <!--                            <li><h4><?=$user_name?></h4>-->
                <!--                            </li>-->
                <!--                            <li><span class="bread-blod"><?=$user_mobile;?></span>-->
                <!--                            </li>-->
                <!--                        </ul>-->
                <!--                    </div>-->
                <!--                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">-->
                <!--                        <div class="rc">-->
                <!--                        <ul class="breadcome-menua">-->
                                            
                <!--                            <li>Your creditlab.in ID: <span class="bread-blod"><?=$user_rcid;?></span>-->
                <!--                            </li>-->
                                            
                <!--                        </ul>-->
                <!--                        <ul class="breadcome-menua">-->
                <!--                            <li><span class="bread-blod"><?=$user_email;?></span>-->
                <!--                            </li>-->
                <!--                        </ul>-->
                <!--                        </div>-->
                <!--                    </div>-->
                <!--                </div>-->
                <!--            </div>-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->
                <!--<div class="row">
                    <?php #$activeloan = towquery("SELECT id FROM loan_apply WHERE uid=$user_id AND status='account manager' ORDER BY id DESC");
                    #$clearedloan = towquery("SELECT id FROM loan WHERE uid=$user_id AND status_log ='cleared' ORDER BY id DESC");
                    ?>
             <div class="col-md-4 card text-center shadow bg-white rounded" style="background:#fff;height:50px;border:10px solid #F6F8FA; ">
                 <span style="line-height:30px;">Active Loan<span> <?php #townum($activeloan);?></span></span>
            </div>
            <div class="col-md-4 card text-center">
            </div>
            <div class="col-md-4 card text-center" style="background:#fff;height:50px;border:10px solid #F6F8FA;">
                 <span style="line-height:30px;">cleared Loan<span> <?php #townum($clearedloan);?></span></span>
            </div>
             </div>-->
            <!--</div>-->
        <!--</div>-->
        <!--<div class="container chat" style="background:#fff;">-->
        <!--<h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in</h4>-->
        <!--</div>-->
        <!--<br>-->
        <!-- Single pro tab review Start-->
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
                                                            <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                                                <div class="text-center" ><h3>Loan Amount</h3>
                                                                    <br>
                                                                <div class="slidecontainer">
                                                                <input type="range" min="1000" max="<?=$user_loan_start;?>" value="<?=$user_loan_start;?>" class="slider" id="myRange" step="1000" name="amount">
                                                                <h3 id="b"></h3>
                                                                <br>
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
                                                                </div>
                                                                <div class="row">
                                                                </div>
                                                                <br>
                                                                <div class="row">
                                                                    <div class="col-md-6"></div>
                                                                    <div class="col-md-6"></div>
                                                                </div>
                                                                <div class="row">
                                                                    <p style="font-size:12px;">
                                                                        *Final Tenure, Loan amount, Intrest & p.fee depends on the credit risk assessment.:
                                                                    <details id="showded" style="font-size:12px;">
                                                                      <summary id="showde">*Creditlab Loan Details</summary>
                                                                      <p style="font-size:12px;">
                                                                        Loan amount : <b id="loan_amount"></b><br>
                                                                        Processing fee : <b id="proc_amp"></b><br>
                                                                        Gst fee : <b id="gst_fee"></b><br>
                                                                        Total Interest : <b id="total_int"></b><br>
                                                                        Account Management fee : <b id="acc_man"></b><br>
                                                                        In - Hand amount : <b id="inhand_amt"></b><br>
                                                                        No . Of EMIs : <b>2</b><br>
                                                                        1ˢᵗ  EMI  amount : <b id="femp"></b><br>
                                                                        2ⁿᵈ EMI amount : <b id="semi"></b><br>
                                                                      </p>
                                                                    </details></p>
                                                            <div class="form-group">
												<div class="checkbox">
													<label>
														<input type="checkbox" required="">I agree to creditlab.in <br> <a href="/terms.pdf" target="_blank">Terms of use</a> & <a href="/policy.pdf" target="_blank">privacy policy</a>
													</label>
													<div class="help-block with-errors"></div>
												</div>
											</div>
                                                                    <br>
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
    
    
    document.getElementById("loan_amount").innerHTML = amt;
    document.getElementById("proc_amp").innerHTML = ((amt/100)*13);
    // document.getElementById("gst_fee").innerHTML = (((amt/100)*12)*0.18);
    document.getElementById("gst_fee").innerHTML = 0;
    document.getElementById("total_int").innerHTML = amt*0.065;
    document.getElementById("acc_man").innerHTML = amt*0.04;
    document.getElementById("inhand_amt").innerHTML = amt*0.88;
    document.getElementById("femp").innerHTML = (amt*0.70)+(amt*0.03)+(amt*0.02);
    document.getElementById("semi").innerHTML = (amt*0.30)+(amt*0.035)+(amt*0.02);
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
    document.getElementById("proc_amp").innerHTML = ((famt/100)*13);
    // document.getElementById("gst_fee").innerHTML = (((famt/100)*12)*0.18);
    document.getElementById("gst_fee").innerHTML = 0;
    document.getElementById("total_int").innerHTML = famt*0.065;
    document.getElementById("acc_man").innerHTML = famt*0.04;
    document.getElementById("inhand_amt").innerHTML = famt*0.88;
    document.getElementById("femp").innerHTML = (famt*0.70)+(famt*0.03)+(famt*0.02);
    document.getElementById("semi").innerHTML = (famt*0.30)+(famt*0.035)+(famt*0.02);
</script>
</body>
</html>
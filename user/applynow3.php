<?php
$loanquery = towquery("SELECT * FROM loan_apply WHERE uid=$user_id ORDER BY id DESC");
         if(townum($loanquery) > 0){  
             $amount = 0;
while($a = towfetch($loanquery)){
    $amount = $amount + $a['amount']; 
}
if($amount < $user_loan_limit){
    $loanquery = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND (status='account manager' OR status='recovery officer') ORDER BY id DESC");
    if(townum($loanquery) > 0){
    $user_loan_limit = $user_loan_limit - $amount;
    $user_loan_start = $user_loan_limit;
}}
}
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
                <!--<div class="row">
                    <?php #$activeloan = towquery("SELECT id FROM loan_apply WHERE uid=$user_id AND status='account manager' ORDER BY id DESC");
                    $$clearedloan = towquery("SELECT id FROM loan WHERE uid=$user_id AND status_log ='cleared' ORDER BY id DESC");
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
            </div>
        </div>
        <div class="container chat" style="background:#fff;">
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in</h4>
        </div>
        <br>
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
    <form method="post" class="dropzone dropzone-custom needsclick add-professors" id="demo1-upload" enctype="multipart/form-data" action="apply.php">
                                                            <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                                                <div class="text-center" >                                  <h3>HOW MUCH MONEY DO YOU NEED?</h3>
                                                                    <br>
                                                                <div class="slidecontainer">
                                                                <input type="range" min="<?if(isset($user_loan_start)){echo $user_loan_start;}?>" max="<?=$user_loan_limit;?>" value="<?=$user_loan_limit;?>" class="slider" id="myRange" step="1000" name="amount">
                                                                <h3 id="b"></h3>
                                                                <br>
                                                                <h4><span id="demo"></span></h4>
                                                                </div>
                                                                <br><?php if(townum($clearedloan) >= 1){echo '<input type="hidden" value="30" class="slider" id="myRangea" name="days">';}else{ ?><br>
                                                                <h3>HOW LONG DO YOU NEED?</h3>
                                                                    <br>
                                                                <div class="slidecontainer">
                                                                <input type="range" min="1" max="30" value="30" class="slider" id="myRangea" name="days">
                                                                <br>
                                                                <h4><span id="demoa"> 30 </span> <span style="font-size: 15px;">(From Today)</span></h4>
                                                                </div>
                                                                <br><br>
                                                                <div class="row">
                                                                    <!--<div class="col-md-6">BORROW AMT</div>
                                                                    <div class="col-md-6">INTEREST & FEE
</div>-->
                                                                    <!--<div class="col-md-4">TOTAL</div>-->
                                                                </div>
                                                                <br>
                                                                
                                                                <div class="row">
                                                                    <div class="col-md-6"></div>
                                                                    <div class="col-md-6"><!--<div class="col-md-3"></div>
                                                                        <div class="col-md-6">-->
                                                                    <!--<h3 id="c" style="float:left;">0.8%</h3>-->
                                                                    <!--<h3 id="fee" style="float:right;">2400</h3>-->
<!--</div><div class="col-md-3"></div>--></div>
                                                                    <!--<div class="col-md-4"><h3 id="total">12400</h3></div>-->
                                                                </div>
                                                                <?php } ?>
                                                                <div class="row">
                                                                    <br>
                                                                    <?php if(townum($clearedloan) >= 1){}else{ ?>
                                                                    <p style="font-size:12px;">*Processing fee is applicable </p>
                                                                
                                                            <div class="form-group">
												<div class="checkbox">
													<label>
														<input type="checkbox" required=""> Please accept that you have read and agreed to<br> <a href="/terms.pdf">Terms of use</a> & <a href="/policy.pdf">privacy policy</a>
													</label>
													<div class="help-block with-errors"></div>
												</div>
											</div>
											<?php }?>
                                                                    <br>
                                                                    <div class="col-md-12"><button class="btn btn-success" type="submit">Apply</button></div>
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

<script>
    amt = document.getElementById("myRange");
    var output = document.getElementById("demo");
    days = document.getElementById("myRangea");
    var outputa = document.getElementById("demoa");
    amt.oninput = function() {
    t = amt.value;
    day =  days.value;
    if((day) <= 5 ){
        fee = t * day / 100 * 0.6;
        interest = "0.6%";
    }
    if((day) > 5){
        if((day) <= 10){
        fee = t * day / 100 * 0.7;
        interest = "0.7%"; 
        }else{
        fee = t * day / 100 * 0.8;
        interest = "0.8%";
        }
    }
    document.getElementById("b").innerHTML = t;
     document.getElementById("c").innerHTML = interest;
//  document.getElementById("fee").innerHTML = Math.round(fee);
//  total = parseInt(fee) + parseInt(t);
//  document.getElementById("total").innerHTML = total;
 document.getElementById("demo").innerHTML = t;
 document.getElementById("demoa").innerHTML = day;
}

days.oninput = function() {
    t = amt.value;
    day =  days.value;
    if((day) <= 5 ){
        fee = t * day / 100 * 0.6;
        interest = "0.6%";
    }
    if((day) > 5){
        if((day) <= 10){
        fee = t * day / 100 * 0.7;
        interest = "0.7%"; 
        }else{
        fee = t * day / 100 * 0.8;
        interest = "0.8%";
        }
    }

 document.getElementById("c").innerHTML = interest;
//  document.getElementById("fee").innerHTML = Math.round(fee);
//  total = parseInt(fee) + parseInt(t);
//  document.getElementById("total").innerHTML = total;
  document.getElementById("demo").innerHTML = t;
 document.getElementById("demoa").innerHTML = days.value;
}
</script>
<script>
$( document ).ready(function() {

    amt = document.getElementById("myRange");
    var output = document.getElementById("demo");
    days = document.getElementById("myRangea");
    var outputa = document.getElementById("demoa");
    t = amt.value;
    day =  days.value;
    if((day) <= 5 ){
        fee = t * day / 100 * 0.6;
        interest = "0.6%";
    }
    if((day) > 5){
        if((day) <= 10){
        fee = t * day / 100 * 0.7;
        interest = "0.7%"; 
        }else{
        fee = t * day / 100 * 0.8;
        interest = "0.8%";
        }
    }
    document.getElementById("b").innerHTML = t;
     document.getElementById("c").innerHTML = interest;
 document.getElementById("fee").innerHTML = Math.round(fee);
 total = parseInt(fee) + parseInt(t);
 document.getElementById("total").innerHTML = total;
 document.getElementById("demo").innerHTML = t;
 document.getElementById("demoa").innerHTML = days.value;
    
});
</script>
    
</body>

</html>

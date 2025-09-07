<?php
if(isset($_POST['limit_inc'])){
    if($_POST['limit_inc'] == 2){
        towquery("UPDATE `user` SET `limit_inc`=1, `loan_limit` = `old_loan_limit` WHERE id='$user_id'");
    }else{
        towquery("UPDATE `user` SET `limit_inc`=1  WHERE id='$user_id'");
    }
    print_r("<script>window.location.replace('index.php');</script>");
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
            </div>
        </div>
        <div class="container chat" style="background:#fff;">
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in or live chat support.</h4>
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
                                                    <form method="post" class="dropzone dropzone-custom needsclick add-professors">
                                                            <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                                                <div class="text-center" ><h3>Credit limit updated
                                                                <div class="row">
                                                                    <br>
                                                                    <p >Your credit limit has been updated to</p>
                                                                    <h3><?=$user_loan_limit?></h3>
                											<div class="form-group">
                												<div class="checkbox">
                													<label style="font-size:12px;">
                														<input type="radio" name="limit_inc" value="1">I acknowledge the offer made by the lenders increasing my credit limit to the aforementioned limit and accept the increased credit limit.
                													</label>
                													<div class="help-block with-errors"></div>
                												</div>
                											</div>
                											<br>
                                                            <?php if($user_loan_limit > $user_old_loan_limit){ ?>
                                                            <div class="form-group">
                												<div class="checkbox">
                													<label style="font-size:12px;">
                														<input type="radio" name="limit_inc" value="2">I reject the offer and proceed
                													</label>
                													<div class="help-block with-errors"></div>
                												</div>
                											</div>
                											<?php } ?>
                                                            <div class="col-md-12"><button class="btn btn-success" type="submit" name="accept">Submit</button></div>
                                                            <!--<div class="col-md-6"><button class="btn btn-danger" type="submit" name="cancel">Reject</button></div>-->
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
    var amta = document.getElementById("myRange");
    amta.oninput = function() {
    var amt = document.getElementById("myRange").value;
    document.getElementById("demo").innerHTML = amt;
    }
    function aa(){
        var amt = document.getElementById("myRange").value;
    document.getElementById("demo").innerHTML = amt;
    }
    aa();
</script>
</body>
</html>
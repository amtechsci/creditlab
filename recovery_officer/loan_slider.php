<?php
include_once 'head.php';
?>
<body>
    <?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>
            <!-- Mobile Menu end -->
            <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <ul class="breadcome-menu">
                                            <li><a href="../user">Home</a> <span class="bread-slash">/</span>
                                            </li>
                                            <li><span class="bread-blod">Add Package</span>
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
    <!--<form method="post" class="dropzone dropzone-custom needsclick add-professors" id="demo1-upload" enctype="multipart/form-data" action="apply.php">-->
                                                            <select id="member">
                                                                <option value="2">2 star</option>
                                                                <option value="3">3 star</option>
                                                                <option value="4">4 star</option>
                                                                <option value="5">5 star</option>
                                                            </select>
                                                            <div class="col-lg-12 col-md-12 col-sm-6 col-xs-12">
                                                                <div class="text-center" >                                  <h3>HOW MUCH MONEY DO YOU NEED?</h3>
                                                                    <br>
                                                                <div class="slidecontainer">
                                                                <input type="range" min="1000" max="10000" value="10000" class="slider" id="myRange" step="500" name="amount">
                                                                <br>
                                                                <script><
                                                                // Space out values
                                                                var vals = opt.max - opt.min;
  for (var i = 0; i <= vals; i++) {
    
    var el = $('<label>'+(i+1)+'k</label>').css('left',(i/vals*100)+'%'); 
  
    $( "#myRange" ).append(el);
    <?=$el?>
    
  }
                                                                </script>
                                                                <h4><span id="demo">10000</span></h4>
                                                                </div>
                                                                <br><br>
                                                                <h3>HOW LONG DO YOU NEED?</h3>
                                                                    <br>
                                                                <div class="slidecontainer">
                                                                <input type="range" min="1" max="30" value="30" class="slider" id="myRangea" name="days">
                                                                <br>
                                                                <h4><span id="demoa"> 30 </span> <span style="font-size: 15px;">(From Today)</span></h4>
                                                                </div>
                                                                <br><br>
                                                                <div class="row">
                                                                    <div class="col-md-4">BORROW AMT</div>
                                                                    <div class="col-md-4">INTEREST & FEE
</div>
                                                                    <div class="col-md-4">TOTAL</div>
                                                                </div>
                                                                <br>
                                                                <div class="row">
                                                                    <div class="col-md-4"><h3 id="b">10000</h3></div>
                                                                    <div class="col-md-4"><div class="col-md-3"></div>
                                                                        <div class="col-md-6">
                                                                    <h3 id="c" style="float:left;">0.8%</h3>
                                                                    <h3 id="fee" style="float:right;">2400</h3>
</div><div class="col-md-3"></div></div>
                                                                    <div class="col-md-4"><h3 id="total">12400</h3></div>
                                                                </div>
                                                                <div class="row">
                                                                    <br>
                                                                    <p style="font-size:12px;">*Processing fee of 500 is applicable</p>
                                                                    
                                                                </div>
                                                                </div>
                                                            </div>
                                                    <!--</form>-->
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
    var days = document.getElementById("myRangea");
    var outputa = document.getElementById("demoa");
    var interest = 0;
    amt.oninput = function() {
    var member = document.getElementById("member").value;
    t = amt.value;
    day =  days.value;
    if(member == 2){
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
    }else if(member == 3){
    if((day) <= 5 ){
        fee = t * day / 100 * 0.5;
        interest = "0.5%";
    }
    if((day) > 5){
        if((day) <= 10){
        fee = t * day / 100 * 0.6;
        interest = "0.6%"; 
        }else{
        fee = t * day / 100 * 0.7;
        interest = "0.7%";
        }
    }
    }else if(member == 4){
    if((day) <= 5 ){
        fee = t * day / 100 * 0.4;
        interest = "0.4%";
    }
    if((day) > 5){
        if((day) <= 10){
        fee = t * day / 100 * 0.5;
        interest = "0.5%"; 
        }else{
        fee = t * day / 100 * 0.6;
        interest = "0.6%";
        }
    }
    }else if(member == 5){
    if((day) <= 5 ){
        fee = t * day / 100 * 0.3;
        interest = "0.3%";
    }
    if((day) > 5){
        if((day) <= 10){
        fee = t * day / 100 * 0.4;
        interest = "0.4%"; 
        }else{
        fee = t * day / 100 * 0.5;
        interest = "0.5%";
        }
    }
    }
    document.getElementById("b").innerHTML = t;
     document.getElementById("c").innerHTML = interest;
 document.getElementById("fee").innerHTML = Math.round(fee);
 total = parseInt(fee) + parseInt(t);
 document.getElementById("total").innerHTML = total;
 document.getElementById("demo").innerHTML = t;
 document.getElementById("demoa").innerHTML = day;
}

days.oninput = function() {
    var member = document.getElementById("member").value;
    t = amt.value;
    day =  days.value;
    if(member == 2){
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
    }else if(member == 3){
    if((day) <= 5 ){
        fee = t * day / 100 * 0.5;
        interest = "0.5%";
    }
    if((day) > 5){
        if((day) <= 10){
        fee = t * day / 100 * 0.6;
        interest = "0.6%"; 
        }else{
        fee = t * day / 100 * 0.7;
        interest = "0.7%";
        }
    }
    }else if(member == 4){
    if((day) <= 5 ){
        fee = t * day / 100 * 0.4;
        interest = "0.4%";
    }
    if((day) > 5){
        if((day) <= 10){
        fee = t * day / 100 * 0.5;
        interest = "0.5%"; 
        }else{
        fee = t * day / 100 * 0.6;
        interest = "0.6%";
        }
    }
    }else if(member == 5){
    if((day) <= 5 ){
        fee = t * day / 100 * 0.3;
        interest = "0.3%";
    }
    if((day) > 5){
        if((day) <= 10){
        fee = t * day / 100 * 0.4;
        interest = "0.4%"; 
        }else{
        fee = t * day / 100 * 0.5;
        interest = "0.5%";
        }
    }
    }

 document.getElementById("c").innerHTML = interest;
 document.getElementById("fee").innerHTML = Math.round(fee);
 total = parseInt(fee) + parseInt(t);
 document.getElementById("total").innerHTML = total;
  document.getElementById("demo").innerHTML = t;
 document.getElementById("demoa").innerHTML = day;
}
</script>

</body>

</html>

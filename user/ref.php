<?php
// if(isset($_POST['submit'])){
//     if((townum($refquery) == 0)){ 
//     $extract = towrealarray($_POST['ref'][1]);     extract($extract,EXTR_PREFIX_ALL,"ref_1");
//     $extract = towrealarray($_POST['ref'][2]);     extract($extract,EXTR_PREFIX_ALL,"ref_2");
//     $extract = towrealarray($_POST['ref'][3]);     extract($extract,EXTR_PREFIX_ALL,"ref_3");

//     $ref_1 = $ref_1_0.",#".$ref_1_1.",#".$ref_1_2;
//     $ref_2 = $ref_2_0.",#".$ref_2_1.",#".$ref_2_2;
//     $ref_3 = $ref_3_0.",#".$ref_3_1.",#".$ref_3_2;
//     towquery("INSERT INTO `user_ref`(`uid`, `ref_1`, `ref_2`, `ref_3`) VALUES ($user_id,'$ref_1','$ref_2','$ref_3')");
//     }
//     $altemail = towreal($_POST['altemail']);
//     $company = towreal($_POST['company']);
//     $altmobile = towreal($_POST['altmobile']);
//     towquery("UPDATE `user` SET `altmobile`='$altmobile',`altemail`='$altemail',`company`='$company' WHERE mobile='$user_mobile'");
//     print_r("<script>window.location.replace('index.php');</script>");
// }
?>
<?php
// print_r($_POST['ref']);exit;
include_once 'head.php';
if (isset($_POST['submit'])) {
    if ((townum($refquery) == 0)) { 
        foreach ($_POST['ref'] as $key => $ref) {
            // Extracting the data for each reference
            $name = towreal($ref[0]);
            $number = towreal($ref[1]);
            $relation = towreal($ref[2]);
            
            // Insert into the new user_referrals table
            towquery("INSERT INTO `user_referrals`(`uid`, `name`, `phone`, `relation`) VALUES ($user_id, '$name', '$number', '$relation')");
            // echo "INSERT INTO `user_referrals`(`uid`, `name`, `number`, `relation`) VALUES ($user_id, '$name', '$number', '$relation')";
        }
    }
// exit;
    $altemail = towreal($_POST['altemail']);
    $company = towreal($_POST['company']);
    $altmobile = towreal($_POST['altmobile']);
    towquery("INSERT INTO `user_referrals`(`uid`, `name`, `phone`, `relation`) VALUES ($user_id, 'Alt num', '$altmobile', '')");
    towquery("UPDATE `user` SET `altmobile`='$altmobile', `altemail`='$altemail', `company`='$company' WHERE mobile='$user_mobile'");
    // exit;
    print_r("<script>window.location.replace('index.php');</script>");
}
?>

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
        </div>
        <div class="container chat" style="background:#fff;">
            <?php $wa_num = towfetch(towquery("SELECT * FROM `whatsapp_no` WHERE `page_id`='$n'"))['wa_phone'];?>
            <h4 style="padding:10px;">Contact us on whatsapp<a href="https://api.whatsapp.com/send?phone=91<?=$wa_num?>&text=CLID : <?=$user_rcid?> I Applied for loan ..." class="btn btn-default" style="background:#dfdfdf;">
                <img src="/ws.svg" style="width:30px;"> Whatsapp</a>
            </h4>
        </div>
        <div class="container">
          <div class="progress">
            <div class="progress-bar" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" style="width:70%">
              70%
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
                            <?php if((townum($refquery) == 0)){ ?>
                            <ul id="myTabedu1" class="tab-review-design">
                                <li class="active"><a href="#description">Please enter 3 different reference numbers of your family or friends for quick transfers </a></li>
                            </ul>
                            <?php }?>
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade active in" id="description">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <div id="dropzone1" class="pro-ad">
                                                    <form method="post" class="dropzone dropzone-custom needsclick add-professors" id="demo1-upload" enctype="multipart/form-data">
                                                        <div class="row">
                                                        <?php if((townum($refquery) == 0)){ ?>
                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                                <div class="row">
                                                        <div class="col-sm-1 col-lg-1" style="padding-top:15px;">
                                                            1.
                                                        </div>
                                                                    <div class="form-group  col-lg-10 ">
                                                                     <input type="text" class="form-control" placeholder="Name" name="ref[1][]" id="ref1" required>
                                                                    </div>
                                                                    </div>
                                                            </div>
                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                                <div class="form-group">
                                                                    <div class="input-group">
                                                                        <span class="input-group-addon">+91</span>
                                                                        <input type="text" class="form-control" placeholder="number" pattern="[0-9]{10}" title="Wrong mobile number" name="ref[1][]" id="ref2" required>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                                    <div class="form-group">
                                                                        <select class="form-control" name="ref[1][]" required>
                                                                        <option value="Family">Family</option>
                                                                        </select>
                                                                    </div>
                                                            </div>
                                                         <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                             <div class="row">
                                                                    <div class="col-md-1" style="padding-top:15px;">
                                                            2.
                                                        </div>
                                                                    <div class="form-group col-md-10">
                                                                        <input type="text" class="form-control" placeholder="Name" name="ref[2][]" id="ref3" required>
                                                                    </div>
                                                                    </div>
                                                            </div>
                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                                    <div class="form-group">
                                                                        <div class="input-group">
                                                                            <span class="input-group-addon">+91</span>
                                                                            <input type="text" class="form-control" placeholder="Number" pattern="[6789][0-9]{9}" title="Wrong mobile number" name="ref[2][]" id="ref4" required>
                                                                        </div>
                                                                    </div>
                                                            </div>
                                                            
                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                                    <div class="form-group">
                                                                        <select class="form-control" name="ref[2][]" required>
                                                                        <option value="Family">Family</option>
                                                                        </select>
                                                                    </div>
                                                            </div>
                                                         <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                             <div class="row">
                                                                    <div class="col-md-1" style="padding-top:15px;">
                                                            3.
                                                        </div>
                                                                    <div class="form-group col-md-10">
                                                                        <input type="text" class="form-control" placeholder="Name" name="ref[3][]" id="ref5" required>
                                                                    </div>
                                                                    </div>
                                                            </div>
                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                                    <div class="form-group">
                                                                        <div class="input-group">
                                                                            <span class="input-group-addon">+91</span>
                                                                            <input type="text" class="form-control" placeholder="Number" pattern="[6789][0-9]{9}" title="Wrong mobile number" name="ref[3][]" id="ref6" required>
                                                                        </div>
                                                                    </div>
                                                            </div>
                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                                    <div class="form-group">
                                                                        <select class="form-control" name="ref[3][]"  required>
                                                                        <option value="Friend">Friend</option>
                                                                        </select>
                                                                    </div>
                                                            </div>
                                                        <?php } ?>
                                                            <div class="col-md-12" style="padding-top:15px; display: flex;">
                                                                        <span style="font-size:20px;">Alternate data & Employement details ( subject to verification)</span>
                                                                    </div>
                                                         <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                             <div class="row">
                                                                    <div class="col-md-1" style="padding-top:15px;">
                                                            4.
                                                        </div>
                                                                    <div class="form-group col-md-10">
                                                                        <span style="font-size:12px;">Enter your alternate mobile number (or) any number from your family [ Alt number can’t be same as reference number ]</span>
                                                                        <div class="input-group">
                                                                            <span class="input-group-addon">+91</span>
                                                                            <input type="number" class="form-control" placeholder="Alt Mobile" name="altmobile" id="ref7" required>
                                                                        </div>
                                                                    </div>
                                                            </div>
                                                            </div>
                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control" placeholder="Company name" name="company" required>
                                                                    </div>
                                                            </div>
                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                                    <div class="form-group">
                                                                        <input type="email" class="form-control" placeholder="Company mail or official mail or corporate mail" name="altemail" required>
                                                                    </div>
                                                            </div>
                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                         <center><input type="submit" id="presub" name="submit" class="btn btn-success" disabled></center>
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
<?php if((townum($refquery) == 0)){ ?>       
<script>
        var ref1,ref2,ref3,ref4,ref5,ref6,ref7,ref8,ref9,ref10;
        $( "input" ).keyup(function() {
        ref1 = $('#ref1').val();
        ref2 = $('#ref2').val();
        ref3 = $('#ref3').val();
        ref4 = $('#ref4').val();
        ref5 = $('#ref5').val();
        ref6 = $('#ref6').val();
        ref7 = $('#ref7').val();
        ref8 = $('#ref8').val();
        ref9 = $('#ref9').val();
        ref10 = $('#ref10').val();
if(((ref1 == ref2) || (ref1 == ref3) || (ref1 == ref4) || (ref1 == ref5) || (ref1 == ref6) || (ref1 == ref7))){
   $('#ref1').css("border-color", "red");
//   $("#presub").attr("disabled",true);
   $("#mess").html("");
}else if(((ref2 == ref1) || (ref2 == ref3) || (ref2 == ref4) || (ref2 == ref5) || (ref2 == ref6) || (ref2 == ref7))){
    $('input').css("border-color", "green");
   $('#ref2').css("border-color", "red");
//   $("#presub").attr("disabled",true);
   $("#mess").html("");
}else if(((ref3 == ref2) || (ref3 == ref1) || (ref3 == ref4) || (ref3 == ref5) || (ref3 == ref6) || (ref3 == ref7))){
    $('input').css("border-color", "green");
   $('#ref3').css("border-color", "red");
//   $("#presub").attr("disabled",true);
   $("#mess").html("");
}else if(((ref4 == ref2) || (ref4 == ref3) || (ref4 == ref1) || (ref4 == ref5) || (ref4 == ref6) || (ref4 == ref7))){
    $('input').css("border-color", "green");
   $('#ref4').css("border-color", "red");
//   $("#presub").attr("disabled",true);
   $("#mess").html("");
}else if(((ref5 == ref2) || (ref5 == ref3) || (ref5 == ref4) || (ref1 == ref5) || (ref5 == ref6) || (ref5 == ref7))){
    $('input').css("border-color", "green");
   $('#ref5').css("border-color", "red");
//   $("#presub").attr("disabled",true);
   $("#mess").html("");
}else if(((ref6 == ref2) || (ref6 == ref3) || (ref6 == ref4) || (ref6 == ref5) || (ref1 == ref6)  || (ref6 == ref7))){
    $('input').css("border-color", "green");
   $('#ref6').css("border-color", "red");
//   $("#presub").attr("disabled",true);
   $("#mess").html("");
} else {
   $('input').css("border-color", "green");
   $("#mess").html("");
   $("#presub").removeAttr("disabled");
}
});
        </script>
<?php }else{ ?>
<script>
    $("#presub").removeAttr("disabled");
</script>
<?php } ?>
</body>

</html>

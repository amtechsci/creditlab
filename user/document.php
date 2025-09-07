<script>
// $(document).ready(function(){
//     $("form").submit(function(){
//         var isValid = false; // Assume nothing is valid first! 🎈

//         // Loop through all visible input fields (only the ones currently shown)
//         $("input[type='file']:visible").each(function(){
//             if ($(this).val() != "") {
//                 isValid = true; // At least one file is selected, form is valid 🎉
//                 $("#check").html(""); // Clear any previous error message
//             }
//         });

//         // If no files are selected, show error
//         if (!isValid) {
//             $("#check").html("Please upload at least one of your documents to continue! 🚨");
//         }

//         return isValid; // Only allow submit if something is uploaded!
//     });
// });
</script>
            <?php include 'breadcome.php';?>
        </div>
        <div class="container chat" style="background:#fff;">
            <?php $wa_num = towfetch(towquery("SELECT * FROM `whatsapp_no` WHERE `page_id`=1"))['wa_phone'];?>
            <h4 style="padding:10px;">Contact us on whatsapp<a href="https://api.whatsapp.com/send?phone=91<?=$wa_num?> &text=CLID : <?=$user_rcid?> I need Help in ..." class="btn btn-default" style="background:#dfdfdf;">
                <img src="/ws.svg" style="width:30px;"> Whatsapp</a>
            </h4>
        </div>
        <div class="container">
          <div class="progress">
            <div class="progress-bar" role="progressbar" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100" style="width:90%">
              90%
            </div>
          </div>
        </div>
        <br>
        <div class="analytics-sparkle-area">
            <div class="container-fluid">
                
        <div class="product-tab-list tab-pane fade active in" id="INFORMATION">
            <div id="alert" style="padding:15px;background:#fff; position:relative; background:#ccffff;">
                <h3>Your loan is applied in creditlab.in and we will get back to you soon through mail or call.<br>Thank you 😊.</h3><span onclick="alertcut()" style="position:absolute; font-size:28px; right:17px; top:0px;">&times;</span>
            </div>
            <br>
            <h4>Please upload the documents for Quick verification & disbursal</h4>
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
												<form action="docu.php" method="post" enctype="multipart/form-data">
                                                    <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                            <div class="devit-card-custom">
                                                
                                                                <?php if(empty($user_conpanydocument)): ?>
                                                                <!-- Pan Card and its password field -->
                                                                <div class="form-group">
                                                                    <label>Pan Card</label>
                                                                    <input type="file" required accept=".pdf, image/*" class="form-control" name="conpanydocument" id="a">(only front side)
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>(Document Password if any)</label>
                                                                    <input type="text" class="form-control" placeholder="Password if any" name="pan_pass">
                                                                </div>
                                                                <?php endif; ?>
                                                
                                                                <?php if(empty($user_personaldocument)): ?>
                                                                <!-- Aadhar Front and its password field -->
                                                                <div class="form-group">
                                                                    <label>Aadhar Card (Front)</label>
                                                                    <input type="file" required accept=".pdf, image/*" class="form-control" name="personaldocument[]" id="b">(Front side)
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>(Document Password if any)</label>
                                                                    <input type="text" class="form-control" placeholder="Password if any" name="aadhar_pass">
                                                                </div>
                                                                <?php endif; ?>
                                                
                                                                <?php if(empty($user_personaldocument)): ?>
                                                                <!-- Aadhar Back and its password field -->
                                                                <div class="form-group">
                                                                    <label>Aadhar Card (Back)</label>
                                                                    <input type="file" accept=".pdf, image/*" class="form-control" name="personaldocument[]" id="c">(Back side)
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>(Document Password if any)</label>
                                                                    <input type="text" class="form-control" placeholder="Password if any" name="aadhar_pass2">
                                                                </div>
                                                                <?php endif; ?>
                                                
                                                                <?php if(empty($user_salarydocument)): ?>
                                                                <!-- Salary Document and its password field -->
                                                                <div class="form-group">
                                                                    <label>Salary Document</label>
                                                                    <input type="file" required accept=".pdf, image/*" class="form-control" name="salarydocument" id="d">(Last month payslip)
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>(Document Password if any)</label>
                                                                    <input type="text" class="form-control" placeholder="Password if any" name="salary_pass">
                                                                </div>
                                                                <?php endif; ?>
                                                
                                                                <?php if(empty($user_bankdocument)): ?>
                                                                <!-- Bank Document and its password field -->
                                                                <div class="form-group">
                                                                    <label>Bank Document</label>
                                                                    <input type="file" required accept=".pdf, image/*" class="form-control" name="bankdocument" id="e">(Last 3 Months bank statement)
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>(Document Password if any)</label>
                                                                    <input type="text" class="form-control" placeholder="Password if any" name="bank_pass">
                                                                </div>
                                                                <?php endif; ?>
                                                
                                                                <?php if(empty($user_companyidcard)): ?>
                                                                <!-- Company ID Card and its password field -->
                                                                <div class="form-group">
                                                                    <label>Company ID Card</label>
                                                                    <input type="file" required accept=".pdf, image/*" class="form-control" name="companyidcard" id="f">(Company ID card)
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>(Document Password if any)</label>
                                                                    <input type="text" class="form-control" placeholder="Password if any" name="comidcard_pass">
                                                                </div>
                                                                <?php endif; ?>
                                                
                                                                <?php if(empty($user_addressdocument)): ?>
                                                                <!-- Address Document and its password field -->
                                                                <div class="form-group">
                                                                    <label>Address Document</label>
                                                                    <input type="file" required accept=".pdf, image/*" class="form-control" name="addressdocument" id="g">(Present Address proof)
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>(Document Password if any)</label>
                                                                    <input type="text" class="form-control" placeholder="Password if any" name="address_pass">
                                                                </div>
                                                                <?php endif; ?>
                                                
                                                            </div>
                                                        </div>
                                                
                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                            <center><p id="check"></p>
                                                            <button type="submit" class="btn btn-primary waves-effect waves-light" name="document" style="background:#00a77b;">Submit</button></center>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
            </div>
        </div>

        <br>
        <br>
        <br>
<?php
include_once 'foot.php';
?>
<script>
    function alertcut(){
        $("#alert").hide();
    }
</script>

</body>

</html>
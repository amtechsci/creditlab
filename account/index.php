<?php
include '../db.php';
include_once 'head.php';
include_once '../head2.php';
if (isset($user)) {
    print_r("<script>window.location.replace('../user');</script>");
}else{ ?>
<style>
  .input-container {
  display: -ms-flexbox; /* IE10 */
  display: flex;
  width: 100%;
  margin-bottom: 15px;
}

.icon {
  padding: 10px;
  background: dodgerblue;
  color: white;
  min-width: 50px;
  text-align: center;
}

.input-field {
  width: 100%;
  padding: 10px;
  outline: none;
}

.input-field:focus {
  border: 2px solid dodgerblue;
}
.g-recaptcha{display:none;}
</style>
<br>
<br>
<br>
		<div class="main-grid">
			<div class="agile-grids">	
				<div class="grids">
					<div class="forms-grids" style="margin-top:10%;">
						<div class="forms3">
						<div class="kagile-validation kls-validation">
						    <div class="panel panel-widget agile-validation register-form">
								<div class="validation-grids widget-shadow" data-example-id="basic-forms" style="background:#fff;"> 
									<div class="input-info">
										<h1 class="ath1">We welcome you to the instant loan platform</h1>
										<ul style="list-style: disc;">
										    <li>100% paperless & instant cash in bank account</li>
                                            <li>Approval in 10 min</li>
                                            <li>5 M + Happy customers</li>
                                            <li>Interest rate upto 3% monthly</li>
                                            <li>Tenure of 1 month to 1 year as per eligibility.</li>
                                        </ul>
									</div>
								</div>
							</div>
							<div class="panel panel-widget agile-validation">
								<div class="validation-grids validation-grids-right login-form">
									<div class="widget-shadow login-form-shadow" data-example-id="basic-forms"> 
										<div class="input-info">
											<h3>Please enter phone number linked to your Aadhaar Card</h3>
										</div>
										<div class="form-body form-body-info mb-5">
											<form data-toggle="validator" action="/user/register.php" onsubmit="che()" method="post">
												<div class="form-group has-feedback">
													<div class="input-container">
													    <span class="icon">+91</span>
													    <input type="text" class="input-field" name="mobile" placeholder="Enter Your Mobile" style="height: 40px;font-size: 2rem;" required pattern="[6789][0-9]{9}" title="Please enter valid phone number">
													</div>
													<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
												</div>
												<div id="box0" class="form-group" >
												<div class="checkbox">
													<input type="checkbox" id="box" title="please check the box to log in" required><label>
														<p class="atp3">By continuing, I hereby agree/authorize the following:</p>
														<p class="atp3">1. Creditlab <a href="https://creditlab.in/terms.pdf" target="_blank">T&C</a> & <a href="https://creditlab.in/policy.pdf" target="_blank">privacy policy</a></p>
														<p class="atp3">2. I am an Indian citizen above 21 years of age.</p>
														<p class="atp3">3. I give my explicit consent and authorize Creditlab and its <a href="https://creditlab.in/lsp.php">partners</a> to contact me via calls, SMS, IVR, auto-calls, WhatsApp and email for transactional, service, and promotional purposes, even if I am registered on DND/NDNC. I confirm that I am applying for a financial product and this consent forms part of my application.</p>
														<p class="atp3">4. I declare that I can read and understand English and agree to receive all documents/   correspondence in English.</p>
														<p id="boxerror" style="color:red"></p>
													</label>
												</div>
											</div>
												<div class="bottom">
													<div class="form-group">
														<button type="submit" name="submit" class="btn btn-primary disabled">Login</button>
														<br>
													</div>
													<div class="clearfix"> </div>
												</div>
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="clear"> </div>
						</div>
					</div>
				</div>
				<!-- //validation -->
			</div>
		</div>
		<!-- footer -->
		<div class="footer">
			<!--<p>Copyright ©2020 rush4cash.in All rights reserved | Designed by Digital supporter</p>-->
		</div>
		<!-- //footer -->
		<!-- input-forms -->
		
		
		<script type="text/javascript" src="js/valida.2.1.6.min.js"></script>
		<script type="text/javascript" >

			$(document).ready(function() {

				// show Valida's version.
				$('#version').valida( 'version' );

				// Exemple 1
				$('.valida').valida();

				

        // setup the partial validation
				$('#partial-1').on('click', function( ev ) {
					ev.preventDefault();
					$('#res-1').click(); // clear form error msgs
					$('form').valida('partial', '#field-1'); // validate only field-1
					$('form').valida('partial', '#field-1-3'); // validate only field-1-3
				});

			});

		</script>
		<!-- //input-forms -->
		<!--validator js-->
		<script src="js/validator.min.js"></script>
		<!--//validator js-->
		<script>
function myFunction() {
  var x = document.getElementById("myTopnav");
  if (x.className === "topnav") {
    x.className += " responsive";
  } else {
    x.className = "topnav";
  }
}


  function che(){
if(document.getElementById('box').checked === false) {
    //I am checked
    document.getElementById("boxerror").innerHTML = "*please check the box to log in";
} else{
    // document.getElementById("boxerror").innerHTML = "I have changed!";
}
}
</script>
 <script src="https://www.google.com/recaptcha/api.js"></script>
  <script>
   function onSubmit(token) {
     document.getElementById("demo-form").submit();
   }
 </script>
 <button class="g-recaptcha" 
        data-sitekey="6Lc773kpAAAAAENVEmsOIcnIHQZGg8QZJc3dpuDS" 
        data-callback='onSubmit' 
        data-action='submit'>Submit</button>
</body>
</html>
<?php 
include '../foot.php';
} ?>
<?php
session_start();
include_once '../db.php';
if(isset($user)) {
    header('Location:index.php');
}else{
if (isset($_POST['submit'])) {
    towreal(extract($_POST));
    $sqll="SELECT * FROM user WHERE email ='$email'";
    $result=towquery($sqll);
    if (townum($result)==1) {
        $fetch = towfetch($result);
        $headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= 'From: rush4cash <no-reply@rush4cash.in>' . "\r\n";
$to=$email;
$subject="Forgot Password";
$message="Hey ".$fetch['name'].", You forgot your rush4cash account password. Here is your password for login to your account <br>'".$fetch['password']."'";
mail($to,$subject,$message,$headers);
$mess = "Hello ".$fetch['name'].", we send your password to ".$fetch['email']."";
    }
    else{
        echo "<script>alert('not match')</script>";
    }
}}
if(isset($_GET['email'])){
    $email = towreal($_GET['email']);
}
?>
<?php
include_once 'head.php';
?>
<body class="dashboard-page">
		<div class="main-grid">
			<div class="agile-grids">	
				<!-- validation -->
				<div class="grids">
					<div class="progressbar-heading grids-heading">
						<h2>Forgot Password?</h2>
					</div>
					
					<div class="forms-grids">
						<div class="forms3">
						<div class="kagile-validation kls-validation">
							
							
							<div class="panel panel-widget agile-validation">
								<div class="validation-grids validation-grids-right">
								    <?php if(isset($mess)){ ?>
								    <h3><?=$mess;?></h3>
								    <?php }else{ ?>
							<div class="widget-shadow login-form-shadow" data-example-id="basic-forms"> 
										<div class="input-info">
											<h3>Forgot Password? </h3>
										</div>
										<div class="form-body form-body-info">
											<form data-toggle="validator" action="" method="post">
												<div class="form-group has-feedback">
													<input type="email" class="form-control" name="email" placeholder="Please Enter your registered mail ID" data-error="Bruh, that email address is invalid" required="">
													<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
												</div>
												
												<div class="bottom">
													
													<div class="form-group">
														<button type="submit" name="submit" class="btn btn-primary disabled">Enter</button>
													</div>
													<div class="clearfix"> </div>
												</div>
											</form>
										</div>
									</div>
									<?php } ?>
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
			<p>Copyright ©2020 rush4cash.in All rights reserved | Designed by Digital supporter</p>
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

				// Exemple 2
				/*
				$('.valida').valida({

					// basic settings
					validate: 'novalidate',
					autocomplete: 'off',
					tag: 'p',

					// default messages
					messages: {
						submit: 'Wait ...',
						required: 'Este campo é obrigatório',
						invalid: 'Field with invalid data',
						textarea_help: 'Digitados <span class="at-counter">{0}</span> de {1}'
					},

					// filters & callbacks
					use_filter: true,

					// a callback function that will be called right before valida runs.
					// it must return a boolan value (true for good results and false for errors)
					before_validate: null,

					// a callback function that will be called right after valida runs.
					// it must return a boolan value (true for good results and false for errors)
					after_validate: null

				});
				*/

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
</body>
</html>

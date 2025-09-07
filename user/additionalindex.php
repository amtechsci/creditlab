<?php
if(isset($_POST['pan'])){
    $extract = towrealarray($_POST);
    extract($extract);
    if($user_mobile == $altmobile){
        print_r("<script>alert('please enter mobile number of any family person if you don’t have alternate number'); window.location.replace('index.php');</script>");
    }else{
    (towquery("UPDATE `user` SET `pan_name`='$pan_name',`altmobile`=$altmobile,`altemail`='$altemail',`dob`='$dob',`pan`='$pan',`company`='$company',`designation`='$designation',`department`='$department',`state`='$state',`marital_status`='$marital_status',`experience`='$experience',`approvenew`=1,`average_salary`='$average_salary',`salary_date`='$salary_date',`total_emi`='$total_emi',`work_year`='$work_year',`work_month`='$work_month' WHERE email='$user'") and
    print_r("<script> window.location.replace('index.php');</script>")) or print_r("<script>alert('Phone No. already register'); window.location.replace('index.php');</script>");
}}
    ?>
    <div style="background-image: url(img/loan.jpg);
  background-color: #cccccc;
    height:100%;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  position: relative;">
            <!-- Mobile Menu end -->
            <?php include 'breadcome.php';?>
        <div class="container chat" style="background:#fff;">
        <h4 style="padding:10px;">Any issues? Feel free to mail us on support@creditlab.in</h4>
        </div>
        <br>
        <div class="analytics-sparkle-area">
            <div class="container-fluid">
                <h4>Employment Verification</h4>
                <div class="product-tab-list tab-pane fade active in" id="ADDITIONAL">
                                                
                                                 <form action="" method="post">
                                                     <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                 <!--<div class="form-group">
                                                                <input name="name" type="text" class="form-control" placeholder="Name" value="<?=$user_name?>" pattern="(?=.*[a-zA-Z]).{1,}" required>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                            <input name="mobile" id="mobile" type="text" placeholder="Mobile" class="form-control" value="<?=$user_mobile?>" pattern="[6789][0-9]{9}" required>
                                                            </div>
                                                            <div class="form-group">
                                                            <input name="altmobile" id="altmobile" type="text" placeholder="alternate mobile number" class="form-control" value="<?=$user_altmobile?>" pattern="[6789][0-9]{9}" required>
                                                            <p id="mess"></p>
                                                            </div>
                                                            <div class="form-group">
                                                            <input name="email" type="email" id="email" placeholder="Email" class="form-control" value="<?=$user_email?>" disabled>
                                                            </div>
                                                            <div class="form-group">
                                                            <input name="altemail" id="altemail" type="email" placeholder="alternate Email" class="form-control" value="<?=$user_altemail?>">
                                                            <p id="messs"></p>
                                                            </div>
                                                            <div class="form-group">
                                                                <input type="text" name="company" class="form-control" placeholder="Company Name" pattern="(?=.*[a-zA-Z]).{3,}" title="Company name must be and more then 3 " value="<?=$user_company?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <input type="text" name="designation" class="form-control" placeholder="Designation" value="<?=$user_designation?>" pattern="(?=.*[a-zA-Z]).{1,}" required>
                                                            </div>
<div class="form-group">
                                                            <input type="text" class="form-control" placeholder="Department" name="department" value="<?=$user_department?>" pattern="(?=.*[a-zA-Z]).{1,}" required>
                                                        </div>
                                                          -->
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            
                                                            <div class="form-group">
                                                                <label>Name</label>
                                                                <input name="name" type="text" class="form-control" placeholder="Name" value="<?=$user_name?>" pattern="(?=.*[a-zA-Z]).{1,}" disabled>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Full name as per PAN Card</label>
                                                                <input name="pan_name" type="text" class="form-control" placeholder="Full name as per PAN Card" value="<?=$user_pan_name?>" pattern="(?=.*[a-zA-Z]).{1,}" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Date Of Birth</label>
                                                                <input name="dob" type="date" class="form-control" value="<?=$user_dob?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Select Gender</label>
                                                                <select name="marital_status" type="date" class="form-control" required>
                                                                    <option value="">Select Gender</option>
                                                                    <option value="male">Male</option>
                                                                    <option value="female">Female</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label>Mobile</label>
                                                            <input name="mobile" id="mobile" type="text" placeholder="Mobile" class="form-control" value="<?=$user_mobile?>" pattern="[6789][0-9]{9}" disabled>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Alternate Mobile Number (facilitates loan approval)</label>
                                                            <input name="altmobile" id="altmobile" type="text" placeholder="alternate mobile number" class="form-control" value="<?=$user_altmobile?>" pattern="[6789][0-9]{9}" required>
                                                            <p id="mess" style="color: #ffecfa;background: #ff0505;"></p>
                                                            </div>
                                                            <!--<div class="form-group">
                                                            <input name="email" type="email" id="email" placeholder="Email" class="form-control" value="<?=$user_email?>" disabled>
                                                            </div>-->
                                                            <div class="form-group">
                                                                <label>Official Mail Id (facilitates loan eligibility)</label>
                                                            <input name="altemail" id="altemail" type="email" placeholder=" Company mail Id" class="form-control" value="<?=$user_altemail?>">
                                                            <p id="messs" style="color: #ffecfa;background: #ff0505;"></p>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>PAN</label>
                                                                <input type="text" name="pan" class="form-control" placeholder="Pan" id="panNumber" value="<?=$user_pan?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Company Name</label>
                                                                <input type="text" name="company" class="form-control" placeholder="Company Name" pattern="(?=.*[a-zA-Z]).{3,}" title="Company name must be and more then 3 " value="<?=$user_company?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Designation</label>
                                                                <input type="text" name="designation" class="form-control" placeholder="Designation" value="<?=$user_designation?>" pattern="(?=.*[a-zA-Z]).{1,}" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Average monthly salary credited to your bank</label>
                                                                <input type="text" name="average_salary" class="form-control" placeholder="Ex: 25000,28000" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Salary date in every month</label>
                                                                <input type="number" name="salary_date" class="form-control" placeholder="Ex: 10,8,6"  required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Total EMI's of running loans:(subject to verification)</label>
                                                                <input type="number" name="total_emi" class="form-control" placeholder="Ex: 10000,20000" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Working experience</label>
                                                                <br>
                                                                <input type="number" name="work_year" class="form-control"  style="display:inline;width:40%" placeholder="Years [Ex - 0,1,2]" required>
                                                                <input type="number" name="work_month" class="form-control" placeholder="Months [Ex - 0,1,2]"  style="display:inline;width:40%" required>
                                                            </div>
                                                        <div class="form-group">
                                                            <label>Department</label>
                                                            <input type="text" class="form-control" placeholder="Department" name="department" value="<?=$user_department?>" pattern="(?=.*[a-zA-Z]).{1,}" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>City of your job location</label>
                                                                <select name="state" class="form-control" required>
<option value="" >City of your job location</option>
<option value="Banglore" <?php if($user_state == "Banglore"){echo "selected";} ?>>Banglore</option>
<option value="Pune" <?php if($user_state == "Pune"){echo "selected";} ?>>Pune</option>
<option value="Hyderabad" <?php if($user_state == "Hyderabad"){echo "selected";} ?>>Hyderabad</option>
<option value="Mumbai" <?php if($user_state == "Mumbai"){echo "selected";} ?>>Mumbai</option>
<option value="Gurgaon" <?php if($user_state == "Gurgaon"){echo "selected";} ?>>Gurgaon</option>
<option value="Chandigarh" <?php if($user_state == "Chandigarh"){echo "selected";} ?>>Chandigarh</option>
<option value="Surath" <?php if($user_state == "Surath"){echo "selected";} ?>>Surath</option>
<option value="Chennai" <?php if($user_state == "Chennai"){echo "selected";} ?>>Chennai</option>
<option value="Kolkata" <?php if($user_state == "Kolkata"){echo "selected";} ?>>Kolkata</option>
<option value="Delhi" <?php if($user_state == "Delhi"){echo "selected";} ?>>Delhi</option>
<option value="Ahmedabad" <?php if($user_state == "Ahmedabad"){echo "selected";} ?>>Ahmedabad</option>
<option value="Lucknow" <?php if($user_state == "Lucknow"){echo "selected";} ?>>Lucknow</option>
<option value="Noida" <?php if($user_state == "Noida"){echo "selected";} ?>>Noida</option>
<option value="Vishakapatnam" <?php if($user_state == "Vishakapatnam"){echo "selected";} ?>>Vishakapatnam</option>
<option value="Kochi" <?php if($user_state == "Kochi"){echo "selected";} ?>>Kochi</option>
<option value="Bhopal" <?php if($user_state == "Bhopal"){echo "selected";} ?>>Bhopal</option>
<option value="Indore" <?php if($user_state == "Indore"){echo "selected";} ?>>Indore</option>
<option value="Bhubaneswar" <?php if($user_state == "Bhubaneswar"){echo "selected";} ?>>Bhubaneswar</option>
<option value="Coimbatore" <?php if($user_state == "Coimbatore"){echo "selected";} ?>>Coimbatore</option>
<option value="Ghaziabad" <?php if($user_state == "Ghaziabad"){echo "selected";} ?>>Ghaziabad</option>
<option value="Mysuru" <?php if($user_state == "Mysuru"){echo "selected";} ?>>Mysuru</option>
<option value="Vijayawada" <?php if($user_state == "Vijayawada"){echo "selected";} ?>>Vijayawada</option>
<option value="Faridabad" <?php if($user_state == "Faridabad"){echo "selected";} ?>>Faridabad</option>
<option value="Mizoram" <?php if($user_state == "Mizoram"){echo "selected";} ?>>Mizoram</option>
<option value="Other" <?php if($user_state == "Other"){echo "selected";} ?>>Other</option>
</select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Credit history</label>
                     <select name="experience" class="form-control col-xs-12 col-sm-12 pull-left" required>
                     <option value="" placeholder>Credit history</option>
                     <option value="I have active credit card">I have active credit card</option>
                     <option value="I have active loan">I have active loan</option>
                     <option value="I have both active credit card and active ">I have both active credit card and active loan </option>
                     <option value="I dont have both of the above">I dont have both of the above</option>
                     
                   </select>
                                                            </div>
                                                            <br>
                                                            <div class="breadcome-list">
<input type="checkbox" required> I authorize creditlab & its representatives to call, email and/or SMS. This will override registry on DND / NDNC if any. I understand that the offer from creditlab is only indicative and final sanction including the Loan amount / tenor is at the sole discretion of the Fintech and subject to my credibility. The Fintech may at its discretion conduct additional verifications to complete this process. Credit at the sole discretion of creditlab. *Terms & conditions apply.
                                                            </div>
                                                            <!--  <div class="form-group">
                     <select name="experience" class="form-control col-xs-12 col-sm-12 pull-left" required>
                     <option value="" placeholder>Experience in Months</option>
                     <option value="01-06">01-06</option>
                     <option value="07-12">07-12</option>
                     <option value="13-18">13-18</option>
                     <option value="19-24">19-24</option>
                     <option value="25-36">25-36</option>
                     <option value="36+">36+</option>
                   </select>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                  <select name="residence_type" id="residence" class="form-control col-xs-12 col-sm-12 pull-left" required>
                  <option value="" placeholder>Residence Type</option>
                  <option value="Owned By Self">Owned By Self</option>
                  <option value="Owned By Parent">Owned By Parent</option>
                  <option value="Rented With Family">Rented With Family</option>
                  <option value="Rented With Friends">Rented With Friends</option>
                  <option value="Rented Staying Alone">Rented Staying Alone</option>
                  <option value="Guest House">Guest House</option>
                  <option value="Hostel">Hostel</option></select>

                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                            <select class="form-control selectpicker col-xs-12 col-sm-12 pull-left" name="credit_card" id="credit_card_list" style="width:100%;" required>
                            <option value="" placeholder>Credit Card Type</option>
                            <option value="Citi Bank Cash Back Visa Credit Card">Citi Bank Cash Back Visa Credit Card</option>
                            <option value="Citi Bank Cash Back Master Credit Card">Citi Bank Cash Back Master Credit Card</option>
                            <option value="American Express MakeMyTrip Credit Card">American Express MakeMyTrip Credit Card</option>
                            <option value="American Express PAYBACK Credit Card">American Express PAYBACK Credit Card</option>
                            <option value="ICICI Bank Expressions Credit Card">ICICI Bank Expressions Credit Card</option>
                            <option value="ICICI Bank HPCL Platinum Visa">ICICI Bank HPCL Platinum Visa</option>
                            <option value="ICICI Bank HPCL Titanium Master Card">ICICI Bank HPCL Titanium Master Card</option>
                            <option value="ICICI Bank Platinum Identity Visa">ICICI Bank Platinum Identity Visa</option>
                            <option value="ICICI Bank Unifare Credit Card">ICICI Bank Unifare Credit Card</option>
                            <option value="RBL Bank RBL Bank Platinum Classic SuperCard">RBL Bank RBL Bank Platinum Classic SuperCard</option>
                            <option value="Axis Bank Insta Easy">Axis Bank Insta Easy</option>
                            <option value="Axis Bank MY Choice">Axis Bank MY Choice</option>
                            <option value="Axis Bank Pride Platinum">Axis Bank Pride Platinum</option>
                            <option value="Axis Bank Titanium Smart Traveler">Axis Bank Titanium Smart Traveler</option>
                            <option value="Standard Chartered Bank Platinum Rewards Credit Card">Standard Chartered Bank Platinum Rewards Credit Card</option>
                            <option value="Standard Chartered Bank Yatra Platinum Credit Card">Standard Chartered Bank Yatra Platinum Credit Card</option>
                            <option value="Standard Chartered Bank Landmark Platinum Rewards Credit Card">Standard Chartered Bank Landmark Platinum Rewards Credit Card</option>
                            <option value="HDFC Bank Business Platinum Visa">HDFC Bank Business Platinum Visa</option>
                            <option value="HDFC Bank Business Platinum Master Card">HDFC Bank Business Platinum Master Card</option>
                            <option value="HDFC Bank Business Money Back Visa">HDFC Bank Business Money Back Visa</option>
                            <option value="HDFC Bank Business Moneyback Master Card">HDFC Bank Business Moneyback Master Card</option>
                            <option value="HDFC Bank Maruti Suzuki NEXA AllMiles Credit Card">HDFC Bank Maruti Suzuki NEXA AllMiles Credit Card</option>
                            <option value="HDFC Bank Money Back Visa">HDFC Bank Money Back Visa</option>
                            <option value="HDFC Bank Titanium Times">HDFC Bank Titanium Times</option>
                            <option value="HDFC Bank Teachers Platinum">HDFC Bank Teachers Platinum</option>
                            <option value="HDFC Bank Platinum Edge Visa">HDFC Bank Platinum Edge Visa</option>
                            <option value="HDFC Bank Platinum Edge Master Card">HDFC Bank Platinum Edge Master Card</option>
                            <option value="HDFC Bank Platinum Times Card">HDFC Bank Platinum Times Card</option>
                            <option value="State Bank Of India Yatra Credit Card">State Bank Of India Yatra Credit Card</option>
                            <option value="State Bank Of India Tata Star Titanium Credit Card">State Bank Of India Tata Star Titanium Credit Card</option>
                            <option value="State Bank Of India Tata Croma Credit Card">State Bank Of India Tata Croma Credit Card</option>
                            <option value="State Bank Of India Tata Titanium Chroma Credit Card">State Bank Of India Tata Titanium Chroma Credit Card</option>
                            <option value="State Bank Of India KVB Credit Card">State Bank Of India KVB Credit Card</option>
                            <option value="State Bank Of India Oriental Bank Of Commerce Credit Card">State Bank Of India Oriental Bank Of Commerce Credit Card</option>
                            <option value="State Bank Of India Bank Of Maharashtra SBI Credit Card">State Bank Of India Bank Of Maharashtra SBI Credit Card</option>
                            <option value="State Bank Of India Federal Bank Gold And More">State Bank Of India Federal Bank Gold And More</option>
                            <option value="State Bank Of India BPCL Card">State Bank Of India BPCL Card</option>
                            <option value="State Bank Of India South Indian Bank Simply Save">State Bank Of India South Indian Bank Simply Save</option>
                            <option value="State Bank Of India Lakshmi Vilas Bank SimplySAVE Credit Card">State Bank Of India Lakshmi Vilas Bank SimplySAVE Credit Card</option>
                            <option value="State Bank Of India Mumbai Metro SBI Card">State Bank Of India Mumbai Metro SBI Card</option>
                            <option value="State Bank Of India Simply Save Visa Credit Card">State Bank Of India Simply Save Visa Credit Card</option>
                            <option value="State Bank Of India Simply Save Master Credit Card">State Bank Of India Simply Save Master Credit Card</option>
                            <option value="State Bank Of India SimplySAVE Advantage Credit Card">State Bank Of India SimplySAVE Advantage Credit Card</option>
                            <option value="State Bank Of India Central Select Visa Credit Card">State Bank Of India Central Select Visa Credit Card</option>
                            <option value="State Bank Of India Simply Click Visa Credit Card">State Bank Of India Simply Click Visa Credit Card</option>
                            <option value="State Bank Of India Simply Click Master Credit Card">State Bank Of India Simply Click Master Credit Card</option>
                            <option value="State Bank Of India Chennai Metro Card">State Bank Of India Chennai Metro Card</option>
                            <option value="State Bank Of India Karnataka Bank Simply Save Credit Card">State Bank Of India Karnataka Bank Simply Save Credit Card</option>
                            <option value="State Bank Of India SimplyCLICK Advantage">State Bank Of India SimplyCLICK Advantage</option>
                            <option value="State Bank Of India Unnati Visa Credit Card">State Bank Of India Unnati Visa Credit Card</option>
                            <option value="State Bank Of India Unnati Master Credit Card">State Bank Of India Unnati Master Credit Card</option>
                            <option value="State Bank Of India STYLEUP Contactless Credit Card">State Bank Of India STYLEUP Contactless Credit Card</option>
                            <option value="State Bank Of India Capital First Credit Card">State Bank Of India Capital First Credit Card</option>
                            <option value="State Bank Of India FBB STYLEUP Credit Card">State Bank Of India FBB STYLEUP Credit Card</option>
                            <option value="State Bank Of India IRCTC Platinum Card">State Bank Of India IRCTC Platinum Card</option>
                            <option value="HSBC Visa Platinum Credit Card">HSBC Visa Platinum Credit Card</option>
                            <option value="Citi Bank IndianOil Platinum Visa Credit Card">Citi Bank IndianOil Platinum Visa Credit Card</option>
                            <option value="Citi Bank IndianOil Citi Titanium Credit Card">Citi Bank IndianOil Citi Titanium Credit Card</option>
                            <option value="Citi Bank Rewards Card">Citi Bank Rewards Card</option>
                            <option value="Citi Bank Rewards Domestic Credit Card">Citi Bank Rewards Domestic Credit Card</option>
                            <option value="American Express Membership Rewards Credit Card">American Express Membership Rewards Credit Card</option>
                            <option value="American Express Platinum Travel Credit Card">American Express Platinum Travel Credit Card</option>
                            <option value="American Express Jet Airways Platinum Credit Card">American Express Jet Airways Platinum Credit Card</option>
                            <option value="American Express Gold Card">American Express Gold Card</option>
                            <option value="ICICI Bank Jet Airways Visa Coral">ICICI Bank Jet Airways Visa Coral</option>
                            <option value="ICICI Bank Coral Master Credit Card">ICICI Bank Coral Master Credit Card</option>
                            <option value="ICICI Bank Coral Visa Credit Card">ICICI Bank Coral Visa Credit Card</option>
                            <option value="ICICI Bank Ferrari Visa Platinum">ICICI Bank Ferrari Visa Platinum</option>
                            <option value="ICICI Bank Carbon">ICICI Bank Carbon</option>
                            <option value="ICICI Bank HPCL Coral Master">ICICI Bank HPCL Coral Master</option>
                            <option value="ICICI Bank HPCL Coral Visa">ICICI Bank HPCL Coral Visa</option>
                            <option value="ICICI Bank Jet Airways AMEX Coral">ICICI Bank Jet Airways AMEX Coral</option>
                            <option value="ICICI Bank HPCL Coral Amex">ICICI Bank HPCL Coral Amex</option>
                            <option value="RBL Bank ShopRite Credit Card">RBL Bank ShopRite Credit Card</option>
                            <option value="RBL Bank Fun + Credit Card">RBL Bank Fun + Credit Card</option>
                            <option value="RBL Bank Platinum Delight Credit Card">RBL Bank Platinum Delight Credit Card</option>
                            <option value="RBL Bank Platinum Prime SuperCard">RBL Bank Platinum Prime SuperCard</option>
                            <option value="RBL Bank HyperCITY Rewards Credit Card">RBL Bank HyperCITY Rewards Credit Card</option>
                            <option value="RBL Bank Crossword Rewards Credit Card">RBL Bank Crossword Rewards Credit Card</option>
                            <option value="RBL Bank Titanium Delight">RBL Bank Titanium Delight</option>
                            <option value="RBL Bank MoneyTap Credit Card">RBL Bank MoneyTap Credit Card</option>
                            <option value="RBL Bank Movies & More Credit Card">RBL Bank Movies & More Credit Card</option>
                            <option value="Axis Bank Vistara Card">Axis Bank Vistara Card</option>
                            <option value="Axis Bank Vistara Signature">Axis Bank Vistara Signature</option>
                            <option value="Axis Bank MY Wings Credit Card">Axis Bank MY Wings Credit Card</option>
                            <option value="Axis Bank Privilege Credit Card">Axis Bank Privilege Credit Card</option>
                            <option value="Axis Bank Pride Signature">Axis Bank Pride Signature</option>
                            <option value="Axis Bank Platinum Credit Card">Axis Bank Platinum Credit Card</option>
                            <option value="Axis Bank MY Zone Visa">Axis Bank MY Zone Visa</option>
                            <option value="Axis Bank MY Zone Master Credit Card">Axis Bank MY Zone Master Credit Card</option>
                            <option value="Axis Bank NEO Credit Card">Axis Bank NEO Credit Card</option>
                            <option value="Axis Bank Buzz Credit Card">Axis Bank Buzz Credit Card</option>
                            <option value="Standard Chartered Bank Manhattan Platinum Credit Card">Standard Chartered Bank Manhattan Platinum Credit Card</option>
                            <option value="Standard Chartered Bank Super Value Titanium Credit Card">Standard Chartered Bank Super Value Titanium Credit Card</option>
                            <option value="HDFC Bank Diners Club Miles">HDFC Bank Diners Club Miles</option>
                            <option value="HDFC Bank JetPrivilege Visa Signature Card">HDFC Bank JetPrivilege Visa Signature Card</option>
                            <option value="HDFC Bank JetPrivilege Visa Platinum">HDFC Bank JetPrivilege Visa Platinum</option>
                            <option value="HDFC Bank JetPrivilege Master Card Platinum">HDFC Bank JetPrivilege Master Card Platinum</option>
                            <option value="HDFC Bank Corporate World Mastercard">HDFC Bank Corporate World Mastercard</option>
                            <option value="HDFC Bank Corporate Visa Signature">HDFC Bank Corporate Visa Signature</option>
                            <option value="HDFC Bank Corporate Platinum Visa">HDFC Bank Corporate Platinum Visa</option>
                            <option value="HDFC Bank Corporate Platinum Master Card">HDFC Bank Corporate Platinum Master Card</option>
                            <option value="HDFC Bank Diners Club Rewardz">HDFC Bank Diners Club Rewardz</option>
                            <option value="HDFC Bank Doctors Superia">HDFC Bank Doctors Superia</option>
                            <option value="HDFC Bank Corporate Visa Card">HDFC Bank Corporate Visa Card</option>
                            <option value="HDFC Bank Corporate Master Card">HDFC Bank Corporate Master Card</option>
                            <option value="HDFC Bank Purchase Visa Card">HDFC Bank Purchase Visa Card</option>
                            <option value="HDFC Bank HDFC Purchase Master Card">HDFC Bank HDFC Purchase Master Card</option>
                            <option value="HDFC Bank Distributor Visa Card">HDFC Bank Distributor Visa Card</option>
                            <option value="HDFC Bank Distributor Master Card">HDFC Bank Distributor Master Card</option>
                            <option value="State Bank Of India Prime Visa Credit Card">State Bank Of India Prime Visa Credit Card</option>
                            <option value="State Bank Of India Prime Mastercard">State Bank Of India Prime Mastercard</option>
                            <option value="State Bank Of India Prime Advantage Visa Credit Card">State Bank Of India Prime Advantage Visa Credit Card</option>
                            <option value="State Bank Of India SBI Prime Advantage Master Credit Card">State Bank Of India SBI Prime Advantage Master Credit Card</option>
                            <option value="State Bank Of India Elite Visa Credit Card">State Bank Of India Elite Visa Credit Card</option>
                            <option value="State Bank Of India SBI Elite Master Credit Card">State Bank Of India SBI Elite Master Credit Card</option>
                            <option value="State Bank Of India Elite Advantage Visa Credit Card">State Bank Of India Elite Advantage Visa Credit Card</option>
                            <option value="State Bank Of India Elite Advantage Master Credit Card">State Bank Of India Elite Advantage Master Credit Card</option>
                            <option value="State Bank Of India Oriental Bank Of Commerce Platinum Credit">State Bank Of India Oriental Bank Of Commerce Platinum Credit</option>
                            <option value="State Bank Of India Central Select+ Credit Card">State Bank Of India Central Select+ Credit Card</option>
                            <option value="State Bank Of India Air India Platinum Card">State Bank Of India Air India Platinum Card</option>
                            <option value="Yes Bank Prosperity Rewards Credit Card">Yes Bank Prosperity Rewards Credit Card</option>
                            <option value="Yes Bank Prosperity Rewards Plus">Yes Bank Prosperity Rewards Plus</option>
                            <option value="ICICI Bank Rubyx Amex Credit Card">ICICI Bank Rubyx Amex Credit Card</option>
                            <option value="142">ICICI Bank Jet Airways Rubyx AMEX</option>
                            <option value="143">ICICI Bank Jet Airways Rubyx Visa</option>
                            <option value="144">ICICI Bank Rubyx Master Card</option>
                            <option value="145">ICICI Bank Rubyx Visa Card</option>
                            <option value="146">ICICI Bank Ferrari Visa Signature</option>
                            <option value="147">RBL Bank Classic Titanium Reward Credit Card</option>
                            <option value="148">RBL Bank Classic Platinum Reward Credit Card</option>
                            <option value="149">RBL Bank India Startup Club Platinum Credit Card</option>
                            <option value="150">RBL Bank Platinum Edge SuperCard</option>
                            <option value="151">RBL Bank Classic Shopper Titanium Credit Cards</option>
                            <option value="152">RBL Bank Classic Shopper Freedom Titanium Card</option>
                            <option value="153">RBL Bank Crossword Black Credit Card</option>
                            <option value="154">RBL Bank Classic Platinum Credit Card</option>
                            <option value="155">Axis Bank Vistara Infinite</option>
                            <option value="157">Axis Bank Miles & More Select World</option>
                            <option value="160">Axis Bank Miles & More World</option>
                            <option value="161">Standard Chartered Bank Emirates World Credit Card</option>
                            <option value="162">Yes Bank Prosperity Edge Credit Card</option>
                            <option value="163">State Bank Of India Tata Star Platinum Card</option>
                            <option value="164">State Bank Of India Tata Platinum Master Card</option>
                            <option value="165">State Bank Of India Air India Signature Card</option>
                            <option value="166">State Bank Of India KVB Signature Card</option>
                            <option value="167">State Bank Of India Karnataka Bank Platinum Credit Card</option>
                            <option value="168">State Bank Of India South Indian Bank Platinum</option>
                            <option value="169">State Bank Of India Lakshmi Vilas Bank Platinum</option>
                            <option value="170">State Bank Of India Federal Bank Platinum Credit Card</option>
                            <option value="171">State Bank Of India KVB Platinum Credit Card</option>
                            <option value="172">State Bank Of India Bank Of Maharashtra Platinum</option>
                            <option value="173">HDFC Bank Business Regalia Visa</option>
                            <option value="174">HDFC Bank Business Regalia Master</option>
                            <option value="175">HDFC Bank Regalia First Visa</option>
                            <option value="176">HDFC Bank Regalia First Master Card</option>
                            <option value="177">HDFC Bank Solitaire Visa Credit Card</option>
                            <option value="178">HDFC Bank HDFC Solitaire Master</option>
                            <option value="181">Citi Bank Premiermiles Visa Credit Card</option>
                            <option value="182">ICICI Bank Sapphiro Platinum Amex</option>
                            <option value="183">ICICI Bank Sapphiro Master Card</option>
                            <option value="184">ICICI Bank Jet Airways Sapphiro AMEX</option>
                            <option value="185">ICICI Bank Sapphiro Visa</option>
                            <option value="ICICI Bank Jet Airways Sapphiro Visa">ICICI Bank Jet Airways Sapphiro Visa</option>
                            <option value="ICICI Bank Instant Platinum Visa Credit Card">ICICI Bank Instant Platinum Visa Credit Card</option>
                            <option value="ICICI Bank Platinum Chip Visa Credit Card">ICICI Bank Platinum Chip Visa Credit Card</option>
                            <option value="ICICI Bank Platinum Chip Master Credit Card">ICICI Bank Platinum Chip Master Credit Card</option>
                            <option value="RBL Bank Insignia Preferred Banking">RBL Bank Insignia Preferred Banking</option>
                            <option value="RBL Bank India Startup World Card">RBL Bank India Startup World Card</option>
                            <option value="RBL Bank Platinum Maxima Credit Card">RBL Bank Platinum Maxima Credit Card</option>
                            <option value="RBL Bank World Max SuperCard">RBL Bank World Max SuperCard</option>
                            <option value="RBL Bank World Prime SuperCard">RBL Bank World Prime SuperCard</option>
                            <option value="RBL Bank IGU NHS Golf World Card">RBL Bank IGU NHS Golf World Card</option>
                            <option value="RBL Bank Icon Credit Card">RBL Bank Icon Credit Card</option>
                            <option value="RBL Bank Blockbuster Credit Card">RBL Bank Blockbuster Credit Card</option>
                            <option value="Axis Bank Select Visa Credit Card">Axis Bank Select Visa Credit Card</option>
                            <option value="Axis Bank Signature Credit Card">Axis Bank Signature Credit Card</option>
                            <option value="HDFC Bank Regalia Visa">HDFC Bank Regalia Visa</option>
                            <option value="HDFC Bank JetPrivilege World Master Card">HDFC Bank JetPrivilege World Master Card</option>
                            <option value="HDFC Bank Diners Club Premium">HDFC Bank Diners Club Premium</option>
                            <option value="State Bank Of India Tata Star Platinum Master Credit Card">State Bank Of India Tata Star Platinum Master Credit Card</option>
                            <option value="State Bank Of India Tata Titanium Card">State Bank Of India Tata Titanium Card</option>
                            <option value="RBL Bank RBL Bank Platinum Max SuperCard">RBL Bank RBL Bank Platinum Max SuperCard</option>
                            <option value="Standard Chartered Bank Ultimate Visa Credit Card">Standard Chartered Bank Ultimate Visa Credit Card</option>
                            <option value="Yes Bank First Preferred Credit Card">Yes Bank First Preferred Credit Card</option>
                            <option value="HSBC HSBC Premier MasterCard Credit Card">HSBC HSBC Premier MasterCard Credit Card</option>
                            <option value="Others">Others</option>
                            <option value="None">None</option>
                            </select>
                                                            </div>
                                                            -->
                                                        </div>
                                                        
                                                    </div>
                                                    
                                                    <center><p style="color:red" id="check"></p><button class="btn btn-primary" id="presub">Submit</button></center>
                                                    </form>
                                                
                                </div>
            </div>
        </div>

        <br>
        <br>
        <br>
        </div>
<?php
include_once 'foot.php';
?>

<script>
        $( "#panNumber" ).keyup(function() {
            var panVal = $('#panNumber').val();
var regpan = /^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/;

if(regpan.test(panVal)){
   $('#panNumber').css("border-color", "green");
   $("#presub").removeAttr("disabled");
} else {
   $('#panNumber').css("border-color", "red");
   $("#presub").attr("disabled",true);
}
});
        </script>
        
<script>
        var email,altemail;
        $( "#altemail" ).keyup(function() {
        email = "<?=$user_email?>";
        altemail = $('#altemail').val();
        $("#presub").removeAttr("disabled");
if(email != altemail){
   $('#altemail').css("border-color", "green");
   $("#messs").html("");
} else {
   $('#altemail').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#messs").html("Email and alternate Email should not be same");
}
});
</script><script>
        var email,altemail;
        $( "#email" ).keyup(function() {
        email = "<?=$user_email?>";
        altemail = $('#altemail').val();
        $("#presub").removeAttr("disabled");
if(email != altemail){
   $('#altemail').css("border-color", "green");
   $("#messs").html("");
} else {
   $('#altemail').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#messs").html("Email and alternate Email should not be same");
}
});
</script>

<script>
        var mobile,altmobile;
        $( "#altmobile" ).keyup(function() {
        mobile = $('#mobile').val();
        altmobile = $('#altmobile').val();
        $("#presub").removeAttr("disabled");
if(mobile != altmobile){
   $('#altmobile').css("border-color", "green");
   $("#mess").html("");
} else {
   $('#altmobile').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#mess").html("please enter mobile number of any family person if you don’t have alternate number");
}
});
</script><script>
        var mobile,altmobile;
        $( "#mobile" ).keyup(function() {
        mobile = $('#mobile').val();
        altmobile = $('#altmobile').val();
        $("#presub").removeAttr("disabled");
if(mobile != altmobile){
   $('#altmobile').css("border-color", "green");
   $("#mess").html("");
} else {
   $('#altmobile').css("border-color", "red");
   $("#presub").attr("disabled",true);
   $("#mess").html("please enter mobile number of any family person if you don’t have alternate number");
}
});
</script>

</body>

</html>
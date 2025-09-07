<div class="product-tab-list tab-pane fade" id="ADDITIONAL">
                                                <?php if(empty($user_graduation_year)){ ?>
                                                 <form action="" method="post">
                                                     <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                                                <select class="form-control col-xs-12 col-sm-12 pull-left" name="graduation_year" required>
                    <option value="" placehoder>Last College Graduation Year</option>
                    <option value="2019">2019</option>
                     <option value="2018">2018</option>
                      <option value="2017">2017</option>
                       <option value="2016">2016</option>
                        <option value="2015">2015</option>
                         <option value="2014">2014</option>
                          <option value="2013">2013</option>
                           <option value="2012">2012</option>
                            <option value="2011">2011</option>
                             <option value="2010">2010</option>
                              <option value="2009">2009</option>
                               <option value="2008">2008</option>
                               <option value="2007">2007</option>
                                <option value="2006">2006</option>
                                 <option value="2005">2005</option>
                                  <option value="2004">2004</option>
                                   <option value="2003">2003</option>
                                    <option value="2002">2002</option>
                                     <option value="2001">2001</option>
                                   <option value="others">Others</option>
                                 </select>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                                                            <label class="containeri" style="float:left;">
              Married 
                <input type="radio" checked="checked" value="Married" name="marital_status">
                <span class="checkmark"></span>
              </label>
              <label class="containeri" style="float:left;"> &nbsp;&nbsp;&nbsp; Unmarried 
                <input type="radio" name="marital_status" value="Unmarried">
                <span class="checkmark"></span>
              </label>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                            <input class="form-control col-xs-12 col-sm-12 pull-left" placeholder="College Name" name="college_name" id="clg_name_list"  style = "width:100%;" required>
                                                            </div>
                                                        
                                                            <div class="form-group">
                                                                <br>
                                                             <div class="col-md-12 ">
  <span class="pull-left ">
      <label for="Frequently used Apps"><br><b>Frequently Used Apps</b><br></label>
      
      </span>
      
    </div>
  </div>
  <div class="row">
  <div class="col-md-12 ">
<span class="pull-left">
        <label class = "checkbox-inline">
               <input type="checkbox" name="freq_app[]" value="Facebook/Instagram/Snapchat" >Facebook/Instagram/Snapchat
            </label>
          </span>
          </div>
  </div>
          <div class="row">
  <div class="col-md-12 ">
        <span class="pull-left">
            <label class = "checkbox-inline">
                   <input type="checkbox" name="freq_app[]" value="Swiggy/Zomato/Uber Eats" >Swiggy/Zomato/Uber Eats
                </label>
            </span>
            </div>
  </div>
  <div class="row">
  <div class="col-md-12 ">
            <span class="pull-left">
                <label class = "checkbox-inline">
                       <input type="checkbox" name="freq_app[]" value="Amazon/Flipkart/Myntra" >Amazon/Flipkart/Myntra
                    </label>
               </span>
                </div>
  </div>
               <div class="row">
  <div class="col-md-12 ">
               <span class="pull-left">
                    <label class = "checkbox-inline">
                           <input type="checkbox" name="freq_app[]" value="LinkedIn/Indeed/Naukri" >LinkedIn/Indeed/Naukri
                        </label>
                   </span>
                    </div>
  </div>
                   <div class="row">
  <div class="col-md-12 ">
                   <span class="pull-left">
                        <label class = "checkbox-inline">
                               <input type="checkbox" name="freq_app[]" value="Uber/Ola/QuickRide" >Uber/Ola/QuickRide
                            </label>
                       </span>
                        </div>
  </div>
                       <div class="row">
  <div class="col-md-12 ">
                       <span class="pull-left">
                            <label class = "checkbox-inline">
                                   <input type="checkbox" name="freq_app[]" value="FundsIndia/MyCams/InvesTap" />FundsIndia/MyCams/InvesTap
                                </label>
                               </span>
                                </div>
  </div>
                               <div class="row">
  <div class="col-md-12 ">
                               <span class="pull-left">
                                <label class = "checkbox-inline">
                                       <input type="checkbox" name="freq_app[]" value="Quora/TOI/ET/partnered network ads" >Quora/TOI/ET/partnered network ads 
                                    </label>
                             </span>
                              </div>
  
                                                            </div>
                                                          
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                              <div class="form-group">
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
                                                            
                                                        </div>
                                                        
                                                    </div>
                                                    <center><button class="btn btn-primary">Submit</button></center>
                                                    </form>
                                                <?php }else{ ?>
                                                 <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                                                <select class="form-control col-xs-12 col-sm-12 pull-left" name="graduation_year" disabled>
        <option value="" placehoder>Last College Graduation Year</option>
        <option value="2019" <?php if($user_graduation_year == "2019"){echo "selected";} ?>>2019</option>
        <option value="2018" <?php if($user_graduation_year == "2018"){echo "selected";} ?>>2018</option>
        <option value="2017" <?php if($user_graduation_year == "2017"){echo "selected";} ?>>2017</option>
        <option value="2016" <?php if($user_graduation_year == "2016"){echo "selected";} ?>>2016</option>
        <option value="2015" <?php if($user_graduation_year == "2015"){echo "selected";} ?>>2015</option>
        <option value="2014" <?php if($user_graduation_year == "2014"){echo "selected";} ?>>2014</option>
        <option value="2013" <?php if($user_graduation_year == "2013"){echo "selected";} ?>>2013</option>
        <option value="2012" <?php if($user_graduation_year == "2012"){echo "selected";} ?>>2012</option>
        <option value="2011" <?php if($user_graduation_year == "2011"){echo "selected";} ?>>2011</option>
        <option value="2010" <?php if($user_graduation_year == "2010"){echo "selected";} ?>>2010</option>
        <option value="2009" <?php if($user_graduation_year == "2009"){echo "selected";} ?>>2009</option>
        <option value="2008" <?php if($user_graduation_year == "2008"){echo "selected";} ?>>2008</option>
        <option value="2007" <?php if($user_graduation_year == "2007"){echo "selected";} ?>>2007</option>
        <option value="2006" <?php if($user_graduation_year == "2006"){echo "selected";} ?>>2006</option>
        <option value="2005" <?php if($user_graduation_year == "2005"){echo "selected";} ?>>2005</option>
        <option value="2004" <?php if($user_graduation_year == "2004"){echo "selected";} ?>>2004</option>
        <option value="2003" <?php if($user_graduation_year == "2003"){echo "selected";} ?>>2003</option>
        <option value="2002" <?php if($user_graduation_year == "2002"){echo "selected";} ?>>2002</option>
        <option value="2001" <?php if($user_graduation_year == "2001"){echo "selected";} ?>>2001</option>
        <option value="others" <?php if($user_graduation_year == "others"){echo "selected";} ?>>Others</option>
                                 </select>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>                       
                <input type="text" class="form-control" value="<?=$user_marital_status?>" disabled>
                                </div>
                                                            <div class="form-group">
                            <input class="form-control col-xs-12 col-sm-12 pull-left" placeholder="College Name"  value="<?=$user_college_name;?>" style = "width:100%;" disabled>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                              <div class="form-group">
                     <select name="experience" class="form-control col-xs-12 col-sm-12 pull-left" disabled>
                     <option value="">Experience in Months</option>
                    <option value="01-06" <?php if($user_experience == "01-06"){echo "selected";} ?>>01-06</option>
                    <option value="07-12" <?php if($user_experience == "07-12"){echo "selected";} ?>>07-12</option>
                    <option value="13-18" <?php if($user_experience == "13-18"){echo "selected";} ?>>13-18</option>
                    <option value="19-24" <?php if($user_experience == "19-24"){echo "selected";} ?>>19-24</option>
                    <option value="25-36" <?php if($user_experience == "25-36"){echo "selected";} ?>>25-36</option>
                    <option value="36+" <?php if($user_experience == "36+"){echo "selected";} ?>>36+</option>
                   </select>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                  <select name="residence_type" id="residence" class="form-control col-xs-12 col-sm-12 pull-left" disabled>
                  <option value="" placeholder>Residence Type</option>
<option value="Owned By Self" <?php if($user_residence_type == "Owned By Self"){echo "selected";} ?>>Owned By Self</option>
<option value="Owned By Parent" <?php if($user_residence_type == "Owned By Parent"){echo "selected";} ?>>Owned By Parent</option>
<option value="Rented With Family" <?php if($user_residence_type == "Rented With Family"){echo "selected";} ?>>Rented With Family
</option>
<option value="Rented With Friends" <?php if($user_residence_type == "Rented With Friends"){echo "selected";} ?>>Rented With Friends
</option>
<option value="Rented Staying Alone" <?php if($user_residence_type == "Rented Staying Alone"){echo "selected";} ?>>Rented Staying Alone
</option>
<option value="Guest House" <?php if($user_residence_type == "Guest House"){echo "selected";} ?>>Guest House</option>
<option value="Hostel" <?php if($user_residence_type == "Hostel"){echo "selected";} ?>>Hostel</option></select>

                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                            <input type="text" class="form-control col-xs-12 col-sm-12 pull-left" value="<?=$user_credit_card?>" disabled>
                                                            </div>
                                                            
                                                        </div>
                                                       </div> <br>  
                                                    <center><a class="btn btn-primary" id="next2">Next</a></center>
                                                    
                                                <?php } ?>
                                </div>
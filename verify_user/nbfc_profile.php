<?php
if(isset($_GET['id'])){
    $id = towreal($_GET['id']);
    $aaid = towreal($_GET['id']);
  $userprofile = towquery("SELECT * FROM `user` WHERE id=".$id."");
  $userprofetch = towfetch($userprofile);
    extract($userprofetch,EXTR_PREFIX_ALL,"userpro");
    $date = date('Y-m-d H:i:s');
}else{
    print_r("<script>window.location.replace('index.php');</script>");
}
?>
<body onload="adddate('<?=date('Y-m-d')?>')">
    <!-- Start Left menu area -->
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
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                   <p>NAME : <?=$userpro_name;?></p><p>CLID : <?=$userpro_rcid;?></p>
                   <p>Status : <?php if($userpro_status == "waiting"){echo "Just Registered";}else{echo $userpro_status;}?></p>
                   <p>Mobile : <?=maskPhone($userpro_mobile);?></p>
                </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                    <?php $account_manager = towquery("SELECT * FROM `account_manager` WHERE id=$userpro_assign_account_manager");
                    $account_manager = towfetch($account_manager);
                    ?>
                    <?php $recovery_officer = towquery("SELECT * FROM `recovery_officer` WHERE id=$userpro_assign_recovery_officer");
                    $recovery_officer = towfetch($recovery_officer);
                    ?>
                   <p>Account Manager : <?=$account_manager['name'];?> </p><p>Recovery Officer : <?=$recovery_officer['name'];?></p>
                   <p>Registered <span class="bread-slash">:</span> <span class="bread-blod"><?=getDateTimeDiff($userpro_reg_date);?></span></p> 
                   <?php $totalfls = towfetch(towquery("SELECT SUM(`transaction_amount`) AS total FROM `transaction_details` WHERE NOT `transaction_flow`='creditlab To Customer' AND uid=$userpro_id"));
                   $totalflss = $totalfls['total'] ? $totalfls['total'] : 0;
                   $totalflsp = towfetch(towquery("SELECT SUM(`processed_amount`) AS total FROM `loan` WHERE uid=$userpro_id AND status_log IN ('default','cleared')"));
                   $totalflssp = $totalflsp['total'] ? $totalflsp['total'] : 0;
                   $forgst = towfetch(towquery("SELECT SUM(`processed_amount`) + SUM(`p_fee`) + SUM(`origination_fee`) AS total FROM `loan` WHERE uid=$userpro_id AND status_log IN ('default','cleared')"));
                   $forgstf = $forgst['total'] ? $forgst['total'] : 0;
                   ?>
                   <p>limit score <span class="bread-slash">:</span> <span class="bread-blod">
                    <?php $azxs =((0.12*$forgstf)/1.18); ?>
                       <?=(($totalflss-$totalflssp-(ceil(0.18*($azxs))))/$userpro_loan_limit);?></span></p>
                </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                   <p>Pan : Y</p>
                   <p>Enach : <?=$userpro_easebuzz ? 'Yes' : 'No'?></p>
                   <p>adhar : Y</p>
                   <p>Bank check : Y</p>
                </div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
          <div class="single-pro-review-area mt-t-30 mg-b-15">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="product-payment-inner-st">
                            <ul id="myTabedu1" class="tab-review-design">
                                <li class="active" style="margin-bottom:30px;"><a href="#Personal">Personal</a></li>
                                <!--<li><a href="#additional"> additional</a></li>-->
                                <li><a href="#INFORMATION">Documents</a></li>
                                <li><a href="#Bank">Bank Information</a></li>
                                <li><a href="#Reference">Reference</a></li>
                                <li><a href="#login_data">Login Data</a></li>
                                <li><a href="#loan">Apply Loan</a></li>
                                <li><a href="#oldloan">All Loan</a></li>
                                <li><a href="#transaction_details">Transaction Details</a></li>
                                <li><a href="#cibil_analysis">CIBIL ANALYSIS</a></li>
                                <li><a href="#pan_analysis">PAN ANALYSIS</a></li>
                                <li><a href="#adhar_analysis">Adhar ANALYSIS</a></li>
                                <li><a href="#bank_analysis">Bank Statement ANALYSIS</a></li>
                            </ul>
                            <div id="myTabContent" class="tab-content custom-product-edit">
                                <div class="product-tab-list tab-pane fade active in" id="Personal">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <form id="add-department" action="" method="post" class="add-department">
                                                    <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                                                <lable>Name</lable>
                                                                <input disabled name="name" type="text" class="form-control" placeholder="Name" value="<?=$userpro_name?>" pattern="(?=.*[a-zA-Z]).{1,}" >
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Pan Name</lable>
                                                                <input disabled name="pan_name" type="text" class="form-control" placeholder="Name" value="<?=$userpro_pan_name?>" pattern="(?=.*[a-zA-Z]).{1,}" >
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <lable>Mobile Number</lable>
                                                            <input disabled name="mobile" id="mobile" type="text" placeholder="Mobile" class="form-control" value="<?=maskPhone($userpro_mobile)?>" pattern="[6789][0-9]{9}">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Alternate Mobile Number</lable>
                                                            <input disabled name="altmobile" id="altmobile" type="text" placeholder="alternate mobile number" class="form-control" value="<?=maskPhone($userpro_altmobile)?>" pattern="[6789][0-9]{9}">
                                                            <p id="mess"></p>
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Email</lable>
                                                            <input disabled name="email" type="email" id="email" placeholder="Email" class="form-control" value="<?=maskEmail($userpro_email)?>" >
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Alternate Email</lable>
                                                            <input disabled name="altemail" id="altemail" type="email" placeholder="alternate Email" class="form-control" value="<?=maskEmail($userpro_altemail)?>">
                                                            <p id="messs"></p>
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Company Name</lable>
                                                                <input disabled type="text" name="company" class="form-control" placeholder="Company Name" pattern="(?=.*[a-zA-Z]).{3,}" title="Company name must be and more then 3 " value="<?=$userpro_company?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Designation</lable>
                                                                <input disabled type="text" name="designation" class="form-control" placeholder="Designation" value="<?=$userpro_designation?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                            </div>
                                                        <div class="form-group">
                                                            <lable>Department</lable>
                                                            <input disabled type="text" class="form-control" placeholder="Department" name="department" value="<?=$userpro_department?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                        </div>
                                                        <div class="form-group">
                                                            <lable>Present Address</lable>
                                                            <input disabled type="text" class="form-control" placeholder="Present Address" name="present_address" value="<?=$userpro_present_address?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                        </div>
                                                        <div class="form-group">
                                                            <lable>Permanent Address</lable>
                                                            <input disabled type="text" class="form-control" placeholder="Permanent Address" name="permanent_address" value="<?=$userpro_permanent_address?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <lable>State code</lable>
                                                            <input name="state_code" class="form-control" value="<?=$userpro_state_code?>">
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <lable>Company url</lable>
                                                            <input disabled type="text" class="form-control" placeholder="Company url" name="company_url" value="<?=$userpro_company_url?>" pattern="(?=.*[a-zA-Z]).{1,}">
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <lable>Salary Date</lable>
                                                            <input disabled type="text" class="form-control" placeholder="Salary Date" name="salary_date" value="<?=$userpro_salary_date?>">
                                                        </div>
                                                        <div class="form-group">
                                                            <lable>Pin code</lable>
                                                            <input disabled type="text" class="form-control" placeholder="Pin code" name="pincode" value="<?=$userpro_pincode?>">
                                                        </div>
                                                        <div class="form-group">
                                                            <lable>Work Year</lable>
                                                            <input disabled type="text" class="form-control" placeholder="Work Year" name="work_year" value="<?=$userpro_work_year?>">
                                                        </div>
                                                            
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            
                                                            <div class="form-group">
                                                                <lable>City of your job location</lable>
                                                                <select name="state" class="form-control">
                                                                    <option value="" >City of your job location</option>
                                                                    <option value="Banglore" <?php if($userpro_state == "Banglore"){echo "selected";} ?>>Banglore</option>
                                                                    <option value="Pune" <?php if($userpro_state == "Pune"){echo "selected";} ?>>Pune</option>
                                                                    <option value="Hyderabad" <?php if($userpro_state == "Hyderabad"){echo "selected";} ?>>Hyderabad</option>
                                                                    <option value="Mumbai" <?php if($userpro_state == "Mumbai"){echo "selected";} ?>>Mumbai</option>
                                                                    <option value="Gurgaon" <?php if($userpro_state == "Gurgaon"){echo "selected";} ?>>Gurgaon</option>
                                                                    <option value="Chandigarh" <?php if($userpro_state == "Chandigarh"){echo "selected";} ?>>Chandigarh</option>
                                                                    <option value="Surath" <?php if($userpro_state == "Surath"){echo "selected";} ?>>Surath</option>
                                                                    <option value="Chennai" <?php if($userpro_state == "Chennai"){echo "selected";} ?>>Chennai</option>
                                                                    <option value="Kolkata" <?php if($userpro_state == "Kolkata"){echo "selected";} ?>>Kolkata</option>
                                                                    <option value="Delhi" <?php if($userpro_state == "Delhi"){echo "selected";} ?>>Delhi</option>
                                                                    <option value="Ahmedabad" <?php if($userpro_state == "Ahmedabad"){echo "selected";} ?>>Ahmedabad</option>
                                                                    <option value="Lucknow" <?php if($userpro_state == "Lucknow"){echo "selected";} ?>>Lucknow</option>
                                                                    <option value="Noida" <?php if($userpro_state == "Noida"){echo "selected";} ?>>Noida</option>
                                                                    <option value="Vishakapatnam" <?php if($userpro_state == "Vishakapatnam"){echo "selected";} ?>>Vishakapatnam</option>
                                                                    <option value="Kochi" <?php if($userpro_state == "Kochi"){echo "selected";} ?>>Kochi</option>
                                                                    <option value="Bhopal" <?php if($userpro_state == "Bhopal"){echo "selected";} ?>>Bhopal</option>
                                                                    <option value="Indore" <?php if($userpro_state == "Indore"){echo "selected";} ?>>Indore</option>
                                                                    <option value="Bhubaneswar" <?php if($userpro_state == "Bhubaneswar"){echo "selected";} ?>>Bhubaneswar</option>
                                                                    <option value="Coimbatore" <?php if($userpro_state == "Coimbatore"){echo "selected";} ?>>Coimbatore</option>
                                                                    <option value="Ghaziabad" <?php if($userpro_state == "Ghaziabad"){echo "selected";} ?>>Ghaziabad</option>
                                                                    <option value="Mysuru" <?php if($userpro_state == "Mysuru"){echo "selected";} ?>>Mysuru</option>
                                                                    <option value="Vijayawada" <?php if($userpro_state == "Vijayawada"){echo "selected";} ?>>Vijayawada</option>
                                                                    <option value="Faridabad" <?php if($userpro_state == "Faridabad"){echo "selected";} ?>>Faridabad</option>
                                                                    <option value="Mizoram" <?php if($userpro_state == "Mizoram"){echo "selected";} ?>>Mizoram</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Date of Birth</label>
                                                                <div class="input-group" style="width:100% !important">
                                                                <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                                                                <input disabled name="dob" style="width:100% !important; min-width:95%" type="date" class="form-control" placeholder="Date of Birth" value="<?=$userpro_dob?>">
                                                            </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>PAN <span style="color:red;" id="panmess"></span></lable>
                                                                <input disabled name="pan" type="text" class="form-control" placeholder="PAN" pattern="^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$" id="panNumber" value="<?=$userpro_pan?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Monthly Net Salary</lable>
                                                                <input disabled name="salary" type="number" class="form-control" placeholder="Monthly Net Salary" min="10000" value="<?=$userpro_salary?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Salary Status</lable>
                    <select name="salarystatus"  class="form-control">
<option value="">Are You</option>
<option value="Salaried" <?php if($userpro_salarystatus == "Salaried"){echo "selected";}?>>Salaried</option>
<option value="Self-Employed" <?php if($userpro_salarystatus == "Self-Employed"){echo "selected";}?>>Self-Employed</option>
<option value="Student" <?php if($userpro_salarystatus == "Student"){echo "selected";}?>>Student</option>
                    </select>
                                </div>
                                <div class="form-group">
                                    <lable>How you get your salary ?</lable>
                    <select name="get_salary"  class="form-control">
<option value="">How you get your salary ?</option>
<option value="bank transfer" <?php if($userpro_get_salary == "bank transfer" or $userpro_get_salary == "Bank Transfer"){echo "selected";}?>>bank transfer</option>
<option value="cash" <?php if($userpro_get_salary == "cash"){echo "selected";}?>>cash</option>
<option value="cheque" <?php if($userpro_get_salary == "cheque"){echo "selected";}?>>cheque</option>
                    </select>
                                </div>
                                                            <div class="form-group">
                                                                <lable>Gender</lable>
                                                                <input disabled name="marital_status" type="text" class="form-control" value="<?=$userpro_marital_status;?>" placeholder="Gender" pattern="(?=.*[a-zA-Z]).{1,}">
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <lable>Credit History</lable>
                                                                <input disabled name="experience" type="text" class="form-control" value="<?=$userpro_experience?>" placeholder="Credit History">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Aadhar No</lable> <span style="color:red;" id="aadharmess"></span>
                                                                <input disabled name="aadhar" type="text" class="form-control" value="<?=maskPhone($userpro_aadhar)?>" placeholder="Aadhar No">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Insta id</lable>
                                                                <input disabled name="insta_id" type="text" class="form-control" value="<?=$userpro_insta_id?>" placeholder="Insta id">
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Work From</lable>
                                                                <input disabled name="work_from" type="text" class="form-control" value="<?=$userpro_work_from?>" placeholder="Work From">
                                                            </div><div class="form-group">
                                                                <lable>Average Salary</lable>
                                                                <input disabled name="average_salary" type="text" class="form-control" value="<?=$userpro_average_salary?>" placeholder="Average Salary">
                                                            </div><div class="form-group">
                                                                <lable>Star Membership</lable>
                                                                <select id="member" name="star_member" class="form-control">
                                                                <option value="" >Select Member Type</option>
                                                                <option value="2" <?php if($userpro_star_member == 2){echo 'selected';}?>>2 star</option>
                                                                <option value="3" <?php if($userpro_star_member == 3){echo 'selected';}?>>3 star</option>
                                                                <option value="4" <?php if($userpro_star_member == 4){echo 'selected';}?>>4 star</option>
                                                                <option value="5" <?php if($userpro_star_member == 5){echo 'selected';}?>>5 star</option>
                                                            </select>
                                                            </div><div class="form-group">
                                                                <lable>Father Name</lable>
                                                                <input disabled type="text" name="father_name" class="form-control" value="<?=$userpro_father_name?>" placeholder="Father Name">
                                                            </div>
                                                            <!--<div class="form-group">-->
                                                            <!--    <lable>Total Emi</lable>-->
                                                            <!--    <input disabled name="star_member" type="text" class="form-control" value="<?=$userpro_total_emi?>" placeholder="Star Membership">-->
                                                            <!--</div>-->
                                                            
                                                        <div class="form-group">
                                                            <lable>Account Manager</lable>
                                                            <select class="form-control" name="assign_account_manager">
                                                                <?php $am = towquery("SELECT * FROM `account_manager`");
                                                                while($amf = towfetch($am)){
                                                                ?>
                                                                <option value="<?=$amf['id']?>" <?php if($userpro_assign_account_manager == $amf['id']){echo 'selected';}?>><?=$amf['name']?></option>
                                                                <?php }?>
                                                            </select>
                                                        </div>    
                                                        <div class="form-group">
                                                            <lable>Recovery Officer</lable>
                                                            <select class="form-control" name="assign_recovery_officer">
                                                                <?php $am = towquery("SELECT * FROM `recovery_officer`");
                                                                while($amf = towfetch($am)){
                                                                ?>
                                                                <option value="<?=$amf['id']?>" <?php if($userpro_assign_recovery_officer == $amf['id']){echo 'selected';}?>><?=$amf['name']?></option>
                                                                <?php }?>
                                                            </select>
                                                        </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                    </div>
                                
                                </div>
                                <div class="product-tab-list tab-pane fade" id="additional">
                                                <!-- <form action="" method="post">-->
                                                     <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                 <lable>Last College Graduation Year</lable>
                                                            <input disabled class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_graduation_year;?>" style = "width:100%;" disabled>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
              <lable>Gender</lable>
                                                            <input disabled class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_marital_status;?>" style = "width:100%;" disabled>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                            <lable>College Name</lable>
                                                            <input disabled class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_college_name;?>" style = "width:100%;" disabled>
                                                            </div>
                                                            <div class="form-group">
                                                                <br>
                                                                <br>
                            <lable>Frequently used Apps</lable>
                                                            <input disabled class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_freq_app;?>" style = "width:100%;" disabled>
                                                            </div>
                                                          
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                              <div class="form-group">
                                                                  
                            <lable>Experience</lable>
                                                            <input disabled class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_experience;?>" style = "width:100%;" disabled>
                                                            
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                                                                <lable>Residence Type</lable>
                                                            <input disabled class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_residence_type;?>" style = "width:100%;" disabled>
                                                            </div>
                                                            <br>
                                                            <div class="form-group">
                                                                <br>
                                                                <lable>Credit Card</lable>
                                                            <input disabled class="form-control col-xs-12 col-sm-12 pull-left" placeholder="" value="<?=$userpro_credit_card;?>" style = "width:100%;" disabled>
                                                            </div>
                                                            
                                                        </div>
                                                        
                                                    </div>
                                                    </form>
                                
                                </div>
                                <div class="product-tab-list tab-pane fade" id="INFORMATION">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
												<form action="" method="post" enctype="multipart/form-data">
												<table class="table table-striped">
    <thead>
      <tr>
        <th>Name</th>
        <th>Password</th>
        <th>Download</th>
        <th>Upload</th>
        <th>Password</th>
      </tr>
    </thead>
    <tbody>
        <?php $userpro_document_password = explode("#",$userpro_document_password);?>
        <?php $pan = explode("pan",$userpro_document_password[0]);?>
      <tr>
        <td>Pan</td>
        <td><?php if(isset($pan[1])){echo $pan[1];}?></td>
        <td><?php if(($userpro_conpanydocument !="no") and ($userpro_conpanydocument !="")){?><a href="../user/uploads/<?=$userpro_conpanydocument;?>" target="_blank"><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input disabled type="file" name="conpanydocument" class="form-control"></td>
        <td><input disabled type="text" placeholder="Password..." name="pan_pass" class="form-control"></td>
      </tr>
      <?php $aadhar = explode("aadhar",$userpro_document_password[1]);?>
      <?php $aadhar2 = explode("aadha2",$userpro_document_password[2]);?>
      <?php $aadharfile = explode("#",$userpro_personaldocument);?>
      <tr>
        <td>Aadhar front side</td>
        <td><?php if(isset($aadhar[1])){ echo $aadhar[1]; }?></td>
        <td><?php if(isset($aadharfile)){ if(($aadharfile[0] !="no") and ($aadharfile[0] !="")){?><a href="../user/uploads/<?=$aadharfile[0]?>" target="_blank"><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php }} ?></td>
        <td><input disabled type="file" name="personaldocument[]" class="form-control"></td>
        <td><input disabled type="text" placeholder="Password..." name="aadhar_pass" class="form-control"></td>
      </tr>
      <tr>
        <td>Aadhar Back side</td>
        <td><?php if(isset($aadhar2[1])){echo $aadhar2[1]; }?></td>
        <td><?php if(isset($aadharfile[1])){
        if(($aadharfile[1] !="no") and ($aadharfile[1] !="")){ ?>
        <a href="../user/uploads/<?=$aadharfile[1]?>" target="_blank"><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php }
        } ?></td>
        <td><input disabled type="file" name="personaldocument[]" class="form-control"></td>
        <td><input disabled type="text" placeholder="Password..." name="aadhar_pass2" class="form-control"></td>
      </tr>
       <?php $salary = explode("salary",$userpro_document_password[3]);?>
      <tr>
        <td>Salary Document</td>
        <td><?php if(isset($salary[1])){echo $salary[1]; }?></td>
        <td><?php if(($userpro_salarydocument !="no") and ($userpro_salarydocument !="")){?><a href="../user/uploads/<?=$userpro_salarydocument;?>" target="_blank"><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input disabled type="file" name="salarydocument" class="form-control"></td>
        <td><input disabled type="text" placeholder="Password..." name="salary_pass" class="form-control"></td>
      </tr>
      <?php $bank = explode("bank",$userpro_document_password[4]);?>
      <tr>
        <td>Bank Document</td>
        <td><?php if(isset($bank[1])){echo $bank[1]; }?></td>
        <td><?php if(($userpro_bankdocument !="no") and ($userpro_bankdocument !="")){?><a href="../user/uploads/<?=$userpro_bankdocument;?>" target="_blank"><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input disabled type="file" name="bankdocument" class="form-control"></td>
        <td><input disabled type="text" placeholder="Password..." name="bank_pass" class="form-control"></td>
      </tr>
      <?php $bank2 = explode("bank2",$userpro_document_password[6]);?>
      <tr>
        <td>Bank Document2</td>
        <td><?php if(isset($bank2[1])){echo $bank2[1]; }?></td>
        <td><?php if(($userpro_bankdocument2 !="no") and ($userpro_bankdocument2 !="")){?><a href="../user/uploads/<?=$userpro_bankdocument2;?>" target="_blank"><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input disabled type="file" name="bankdocument2" class="form-control"></td>
        <td><input disabled type="text" placeholder="Password..." name="bank_pass2" class="form-control"></td>
      </tr>
      <?php $bank3 = explode("bank3",$userpro_document_password[7]);?>
      <tr>
        <td>Bank Document3</td>
        <td><?=$bank3[1];?></td>
        <td><?php if(($userpro_bankdocument3 !="no") and ($userpro_bankdocument3 !="")){?><a href="../user/uploads/<?=$userpro_bankdocument3;?>" target="_blank"><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input disabled type="file" name="bankdocument3" class="form-control"></td>
        <td><input disabled type="text" placeholder="Password..." name="bank_pass3" class="form-control"></td>
      </tr>
      <?php $address = explode("address",$userpro_document_password[5]);?>
      <tr>
        <td>Address Document</td>
        <td><?=$address[1];?></td>
        <td><?php if(($userpro_addressdocument !="no") and ($userpro_addressdocument !="")){?><a href="../user/uploads/<?=$userpro_addressdocument;?>" target="_blank"><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input disabled type="file" name="addressdocument" class="form-control"></td>
        <td><input disabled type="text" placeholder="Password..." name="address_pass" class="form-control"></td>
      </tr>
      <tr>
        <td>signature </td>
        <td></td>
        <td><?php if(($userpro_signature !="no") and ($userpro_signature !="")){?><a href="../user/uploads/<?=$userpro_signature;?>" target="_blank"><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input disabled type="file" name="signature" class="form-control"></td>
        <td></td>
      </tr>
      <tr>
        <td>Video </td>
        <td></td>
        <td><?php if(($userpro_selfie !="no") and ($userpro_selfie !="")){?><a href="../user/uploads/<?=$userpro_selfie;?>" target="_blank"><i class="fa fa-download" style="text-align: center;font-size: 30px;"></i></a><?php } ?></td>
        <td><input disabled type="file" name="selfie" class="form-control"></td>
        <td></td>
      </tr>
    </tbody>
  </table>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="Bank">
                                   <form action="" method="post"> <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                                                <lable>Bank Name</lable>
                                                                <select name="bank_name" class="form-control">
                                                                    <?php $bnc = towquery("SELECT * FROM `bank_name`"); while($bncf = towfetch($bnc)){?>
                                                                    <option value="<?=$bncf['bank_name'];?>"><?=$bncf['bank_name'];?></option>
                                                                    <?php }?>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Branch Name</lable>
                                                                <input disabled name="branch_name" type="text" class="form-control" placeholder="Branch Name" value="<?=$userpro_branch_name?>">
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <lable>IFSC Code</lable>
                                                            <input disabled name="ifsc" type="text" placeholder="IFSC Code" class="form-control" pattern="(?=.*[a-zA-Z]).{11}" title="IFSC must be 11 " style="text-transform:uppercase" value="<?=$userpro_ifsc?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                             <div class="form-group">
                                                                <lable>Account Number <span style="color:red;" id="acnomess"></span></lable>
                                                            <input disabled name="account_no" pattern="[0-9].{7,}" title="Account Number must be 8 or more " type="text" placeholder="Account Number" class="form-control" value="<?=$userpro_account_no?>">
                                                            </div>
                                                             
                                                              <div class="form-group">
                                                                  <lable>Account Type</lable>
                                                                <select class="form-control" name="account_type" value="<?=$userpro_account_type?>">
                                                                    <option <?php if($userpro_account_type == "saving"){echo "selected";} ?> value="saving">Saving</option>
                                                                    <option <?php if($userpro_account_type == "current"){echo "selected";} ?> value="current">Current</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <lable>Account Name</lable>
                                                                <input disabled name="account_name" type="text" class="form-control" placeholder="Account Name" value="<?=$userpro_ac_name?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    </form>
                                    <div>
                                <table class="table table-bordered">
        <thead class="thead-light">
            <tr>                        
                                        <th>Bank Name</th>        
                                        <th>Account Number</th>        
                                        <th>Branch Name</th>  
                                        <th>Account Type</th>  
                                        <th>IFSC Code</th>  
                                        <th>Account Name</th>  
                                        <th>Bank Statment</th>
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php
                                   if(isset($_POST['bank_detail_update'])){
                                       $ext = towrealarray($_POST);
                                       towquery("UPDATE `user_bank` SET `ac_name`='".$ext['ac_name']."',`ac_no`='".$ext['ac_no']."',`ifsc_code`='".$ext['ifsc_code']."',`ac_type`='".$ext['ac_type']."',`branch_name`='".$ext['branch_name']."',`bank_name`='".$ext['bank_name']."' WHERE `id`=".$ext['bank_id']);
                                   }
                                   $ref_data = towquery("SELECT * FROM user_bank WHERE uid='$userpro_id' ORDER BY id DESC"); 
                                   while($bank_fetch = towfetch($ref_data)){
                                   extract($bank_fetch,EXTR_PREFIX_ALL,'ub');
                                   ?>
                                    <tr>
                                        <td>
                                            <select name="bank_name" class="form-control" id="bname">
                                               <?php $bnc = towquery("SELECT * FROM `bank_name`"); while($bncf = towfetch($bnc)){?>
                                               <option value="<?=$bncf['bank_name'];?>"><?=$bncf['bank_name'];?></option>
                                            <?php }?>
                                            </select>
                                            <script>document.getElementById("bname").value= "<?=$ub_bank_name?>";</script>
                                        </td>
                                        <td><input disabled type="text" name="ac_no" value="<?=$ub_ac_no?>" class="form-control"></td>
                                        <td><input disabled type="text" name="branch_name" value="<?=$ub_branch_name?>" class="form-control"></td>
                                        <td><input disabled type="text" name="ac_type" value="<?=$ub_ac_type?>" class="form-control"></td>
                                        <td><input disabled type="text" name="ifsc_code" value="<?=$ub_ifsc_code?>" class="form-control"></td>
                                        <td><input disabled type="text" name="ac_name" value="<?=$ub_ac_name?>" class="form-control"></td>
                                        <td><a href="../user/uploads/<?=$ub_bank_statment?>" target="_blank">View</a></td>
                                        
                                    </tr>
                                    <?php } ?>
                                </tbody>
    </table>                           
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="Reference">
                                    <div class="table-responsive">
            <table class="table table-bordered">
        <thead class="thead-light">
            <tr>                
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Relation</th>
                                        <th>Valid</th>
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php $ref_data = towquery("SELECT * FROM user_ref WHERE uid='$userpro_id' ORDER BY id DESC"); 
                                   $ref_fetch = towfetch($ref_data);
                                   extract($ref_fetch,EXTR_PREFIX_ALL,"users");
                                   $ref_1 = explode(",#",$users_ref_1);
                                   $ref_2 = explode(",#",$users_ref_2);
                                   $ref_3 = explode(",#",$users_ref_3);
                                   $vaild = explode("#",$users_status);
                                   ?>
                                    <tr>
                                        <td data-title="CID"><input disabled type="text" class="form-control" name="ref[1][]" value="<?=$ref_1[0];?>"></td>
                                        <td data-title="Name"><input disabled type="text" class="form-control" name="ref[1][]" value="<?=$ref_1[1];?>"></td>
                                        <td data-title="Email"><input disabled type="text" class="form-control" name="ref[1][]" value="<?=$ref_1[2];?>"></td>
                                        <td data-title="Email"><input type="text" class="form-control" name="vaild[0]" value="<?=$vaild[0];?>"></td>
                                        <td data-title="Email">
                                        <?php 
                                        $ref = towquery("SELECT DISTINCT uid FROM `user_contact_details` WHERE user_contact LIKE '%{$ref_1[1]}%' AND uid=$id ORDER BY id DESC");
                                        if(townum($ref) > 0){
                                            echo '<i class="fa fa-check" aria-hidden="true"></i>';
                                        }else{echo "";}?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td data-title="CID"><input disabled type="text" class="form-control" name="ref[2][]"  value="<?=$ref_2[0];?>"></td>
                                        <td data-title="Name"><input disabled type="text" class="form-control" name="ref[2][]"  value="<?=$ref_2[1];?>"></td>
                                        <td data-title="Email"><input disabled type="text" class="form-control" name="ref[2][]"  value="<?=$ref_2[2];?>"></td>
                                        <td data-title="Email"><input type="text" class="form-control" name="vaild[1]" value="<?=$vaild[1];?>"></td>
                                        <td data-title="Email">
                                            <?php 
                                        $ref = towquery("SELECT DISTINCT uid FROM `user_contact_details` WHERE user_contact LIKE '%{$ref_2[1]}%' AND uid=$id ORDER BY id DESC");
                                        if(townum($ref) > 0){
                                            echo '<i class="fa fa-check" aria-hidden="true"></i>';
                                        }else{echo "";}?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td data-title="CID"><input disabled type="text" class="form-control" name="ref[3][]" value="<?=$ref_3[0];?>"></td>
                                        <td data-title="Name"><input disabled type="text" class="form-control" name="ref[3][]" value="<?=$ref_3[1];?>"></td>
                                        <td data-title="Email"><input disabled type="text" class="form-control" name="ref[3][]" value="<?=$ref_3[2];?>"></td>
                                        <td data-title="Email"><input type="text" class="form-control" name="vaild[2]" value="<?=$vaild[2];?>"></td>
                                        <td data-title="Email">
                                        <?php 
                                        $ref = towquery("SELECT DISTINCT uid FROM `user_contact_details` WHERE user_contact LIKE '%{$ref_3[1]}%' AND uid=$id ORDER BY id DESC");
                                        if(townum($ref) > 0){
                                            echo '<i class="fa fa-check" aria-hidden="true"></i>';
                                        }else{echo "";}?>
                                        </td>
                                    </tr>
                                
            </tbody>
    </table>
    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="login_data">
                                    <div class="table-responsive">
        <table class="table table-bordered">
        <thead class="thead-light">
            <tr>                
                                        <th>Cus ID</th>        
                                        <th>Browser</th>        
                                        <th>IP Address</th>        
                                        <th>Login Time</th>    
                                        <th>Mobile handset uid</th>    
                                        <th>Lat,Long</th>    
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php $login_data = towquery("SELECT * FROM user_login_details WHERE uid='$userpro_id' ORDER BY id DESC LIMIT 0, 4"); while($login_fetch = towfetch($login_data)){ extract($login_fetch,EXTR_PREFIX_ALL,"users"); ?>
                                    <tr>
                                        <td data-title="CID"><?=$users_uid?></td>
                                        <td data-title="Name"><?=$users_browser?></td>
                                        <td data-title="Email"><?=$users_ip_address?></td>
                                        <td data-title="Mobile"><?=$users_login_time?></td>
                                        <td data-title="Mobile"><?=$users_mobile_handset_uid?></td>
                                        <td data-title="Mobile"><?=$users_latitude?>,<?=$users_longitude?></td>
                                    </tr>
                                <?php } ?>
            </tbody>
    </table>
    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="loan">
                                    <div class="table-responsive">
        <table class="table table-bordered" >
        <thead class="thead-light">
            <tr>                
                                        <th>Loan ID</th>        
                                        <th>principal Amount</th>      
                                        <th>PF%</th>      
                                        <th>Disb Amount</th>      
                                        <th>Time Period</th>
                                        <th>P.Fee</th>
                                        <th>GST</th>
                                        <th>Interest</th>
                                        <th>Total Amount</th>
                                        <th>Apply Date</th>
                                        <th>Reason</th>
                                        <th>Status</th>        
                                        <th>Status Date</th>   
                                        <th>Mail Status</th>   
                                        <th>Action</th>
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php $loan_data = towquery("SELECT * FROM loan_apply WHERE uid='$userpro_id' ORDER BY id DESC"); while($loan_fetch = towfetch($loan_data)){ extract($loan_fetch,EXTR_PREFIX_ALL,"users");
                                   $gst = ($users_processing_fees*0.18);
                                   $totalamount = $users_amount + $users_processing_fees + $users_service_charge + $gst;
                                   ?>
                                    <form action="" method="post"><tr>
                                        <td>CLL<?=$users_id?></td>
                                        <td><input disabled type="text" class="form-control" name="principal_amount" value="<?=$users_amount+$users_processing_fees+$gst?>"></td>
                                        <td><input disabled type="number" class="form-control" name="pf_per" value="<?=$users_pro_fee_per?>"></td>
                                        <td><?=$users_amount?></td>
                                        <td><?=$users_days?></td>
                                        <td><?=$users_processing_fees?></td>
                                        <td><?=$gst?></td>
                                        <td><?=$users_service_charge?></td>
                                        <td><?=$totalamount?></td>
                                        <td><?=$users_apply_date?></td>
                                        <td><?=$users_reason?></td>
                                        <td><?php if(($users_status == 'pending')){echo "applied";}else{echo $users_status;} ?></td>
                                        <td><?=$users_status_date?><input disabled type="hidden" name="cllid" value="<?=$users_id;?>"></td>
                                        <td><?=$users_mail_status ? 'Sent' : 'Not Sent'?></td>
                                        <td>
                                        <?php if($userpro_approvenew == 0){?>
                                        <?php if($users_status == "account manager" or $users_status == "cleared"){?>
                                        <br><a href="/zxc/uploads/<?=hash('md5',"https://creditlab.in/admin/loan_agreement2.php?id=$users_id").".pdf"?>" class="btn btn-success">View</a>
                                        <br><a href="/zxc/uploads/<?=hash('md5',"https://creditlab.in/key2.php?id=$users_id").".pdf"?>" class="btn btn-success">View kfs</a>
                                        <?php }else{?>
                                        <br><a href="https://creditlab.in/admin/loan_agreement2.php?id=<?=$users_id?>" class="btn btn-success">View</a>
                                        <br><a href="https://creditlab.in/key2.php?id=<?=$users_id?>" class="btn btn-success">View kfs</a>
                                        <?php }}?>
                                        </td>
                                    </tr></form>
                                <?php } ?>
            </tbody>
    </table>
    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="oldloan">
                                    <div class="table-responsive">
        <table class="table table-bordered" >
        <thead class="thead-light">
            <tr>                
                                        <th>Loan ID</th>        
                                        <th>Processed Date</th>        
                                        <th>Principal Amount</th>        
                                        <th>Processed Amount</th>        
                                        <th>Exhausted Period</th>        
                                        <th>P.fee</th>
                                        <th>gst</th>
                                        <th>O.fee</th>
                                        <th>Interest</th>        
                                        <th>Penalty Charge</th>        
                                        <th>Due date</th>        
                                        <th>Total Amount</th>

                                        <th>EMI Date</th>  
                                        <th>Amount to Repay (inclusive Gst)</th>
                                        <th>Pre close</th>

                                        <th>Status Log</th>       
                                        <th>Paid Amount</th>       
                                        <th>cleared date</th>    
                                        <th>DPD</th>    
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php
                                   function calcPenality($loan_amountc, $percentage, $due_date, $usersd_lid,$pen_day_gap,$t,$fpen = 0){
                                        $today = strtotime(date('Y-m-d'));
                                        $due_date = strtotime($due_date);
                                    
                                        if($due_date < $today){
                                            $check = towquery("SELECT * FROM `transaction_details` WHERE `cllid`='".$usersd_lid."' AND `transaction_flow`='firstemi'");
                                            if((townum($check) == 1) and ($t == 1)){
                                                return 0;
                                            }else{
                                                $penalitydays = round(($today - $due_date) / (60 * 60 * 24)) - 1;
                                                $penality = (($loan_amountc * $percentage) /100)*3;
                                    
                                                if($penalitydays >= 29){
                                                    $penality += (($loan_amountc * $percentage) * 0.004)*min($penalitydays, 29);
                                                    $penalitydays = max($penalitydays - 29, 0);
                                                    if($penalitydays > 0){
                                                        $penality += (($loan_amountc * $percentage) * 0.0035)*min($penalitydays, 60);
                                                        $penalitydays = max($penalitydays - 60, 0);
                                                    }
                                                }
                                                else{
                                                    $penality += (($loan_amountc * $percentage) * 0.004)*$penalitydays;
                                                }
                                            }
                                        }else{
                                            $penality = 0;
                                        }
                                        if($fpen){
                                        $fpen +=$penality;
                                        towquery("UPDATE `loan` SET `penality_charge`='$fpen',`exhausted_period`='$pen_day_gap' WHERE `lid`='".$usersd_lid."'");
                                        }
                                        return $penality;
                                    }
$loan_data = towquery("SELECT * FROM loan WHERE uid='$userpro_id' ORDER BY id DESC");
                                   while($loan_fetch = towfetch($loan_data)){ extract($loan_fetch,EXTR_PREFIX_ALL,"usersd");
                                   if(empty($usersd_penality_charge)){
                                       $usersd_penality_charge = 0;
                                   }
    $lof = towfetch(towquery("SELECT * FROM loan WHERE lid=".$usersd_lid));
    $loan_amountc = (float)$lof['processed_amount'] + (float)$lof['p_fee'] + (float)$lof['origination_fee'];
    $dis_date = date('Y-m-d', strtotime(date_create($lof['processed_date'])->format("Y-m-d") . " -1 day"));
    $di = strtotime($dis_date);
    if($lof['status_log'] == 'cleared'){
        $sa = strtotime(date('Y-m-d'));
    }else{
        $sa = strtotime($lof['cleard_date']);
    }
        $datediff = $sa - $di;
        $day_gap = round($datediff / (60 * 60 * 24));
            if($usersd_is_emi==1){
                $femi_date = date('Y-m-d', strtotime($dis_date . " +30 day"));
                $semi_date = date('Y-m-d', strtotime($femi_date . " +35 day"));
                $fe = strtotime($femi_date);
                $se = strtotime($semi_date);
                $femi_amount = ((($loan_amountc/100) * 70) + ($loan_amountc*0.001*round(($fe-$di) / (60 * 60 * 24)))) + (($loan_amountc/100) * 2);
                $semi_amount = ((($loan_amountc/100) * 30) + $loan_amountc * 0.001 * round(($se-$fe) / (60 * 60 * 24))) + (($loan_amountc/100) * 2);
                $preclose = (($loan_amountc) + ($loan_amountc / 100) * 4) + (($loan_amountc/100) * 2);
            }else{
                
            }
?>
                                    <form action="" method="post"><tr>
                                        <td>CLL<?=$usersd_lid?></td>
                                        <td><?=$usersd_processed_date?></td>
                                        <td>
                                        <?php $azxs = (0.12*$papay)/1.18;
                                        echo $papay = ($usersd_processed_amount + $usersd_p_fee + ($usersd_p_fee*0.18));
                                        ?></td>
                                        <td><?=$usersd_processed_amount?></td>
                                        <td><?=$usersd_exhausted_period;?> Days</td>
                                        <td><?php if($usersd_is_emi==1){echo ceil((5*$azxs)/12);}else{echo $usersd_p_fee;}?></td>
                                        <td><?php if($usersd_is_emi==1){echo ceil(0.18*($azxs));}else{echo ceil(0.18*($usersd_p_fee));;}?></td>
                                        <td><?php if($usersd_is_emi==1){echo ceil($azxs-((5*$azxs)/12));}else{echo $usersd_origination_fee;}?></td>
                                        <?php if($usersd_is_emi==0){ ?>
                                        <td><?=$usersd_service_charge;?></td>
                                            <td><?=$usersd_penality_charge?></td>
                                            <td><?=date('Y-m-d', strtotime($usersd_processed_date . ' +30 day'))?></td>
                                            <td><?=ceil($papay+$usersd_service_charge+$usersd_penality_charge)?></td>
                                        <?php } if($usersd_is_emi==1){
                                        ?>
                                        <td></td>
                                        <td><?=$usersd_penality_charge?></td>
                                        <td></td>
                                        <td></td>
                                        <td><?=$femi_date?> <br> <?=$semi_date?></td>  
<td style="<?php if(($day_gap > 90) and ($usersd_status_log == 'account manager')){echo "background:red;color:#fff;";}elseif(($day_gap > 90) and ($usersd_status_log == 'cleared')){echo "background:orange;color:#fff;";}elseif(($day_gap > 65) and ($usersd_status_log == 'cleared')){echo "background:yellow;color:#fff;";}elseif(($day_gap < 65) and ($usersd_status_log == 'cleared')){echo "background:green;color:#fff;";} ?>">Rs. <?=$femi_amount?> <?php if($lof['femi']){echo '✔️';}?><br>
    <?php if($usersd_status_log == 'account manager'){echo $fpen = calcPenality($loan_amountc, 0.70, date_create($femi_date)->format("Y-m-d"), $usersd_lid,$day_gap,1);}?> <span style="font-size:11px;">(Penalty)</span>
    <br> Rs. <?=$semi_amount?> <?php if($lof['semi']){echo '✔️';}?><br>
    <?php if($usersd_status_log == 'account manager'){echo calcPenality($loan_amountc, 0.355, date_create($semi_date)->format("Y-m-d"), $usersd_lid,$day_gap,2,$fpen);}?> <span style="font-size:11px;">(Penalty)</span>
</td>
                                        <td>Rs. <?=$preclose?> <?php $tprecol = townum(towquery("SELECT id FROM `transaction_details` WHERE cllid='".$usersd_lid."' AND transaction_flow = 'preclose'")); if($tprecol > 0){echo '✔️';}?></td>
                                        <?php }else{ ?> 
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <?php }?>
                                        <td <?php if($usersd_action == "no data"){ ?>class="bg-success" <?php }?>><?=$usersd_status_log?></td>
                                        <td><?php $paid_amt = towquery("SELECT SUM(transaction_amount) AS paid_amt FROM `transaction_details` WHERE cllid='".$usersd_lid."' AND transaction_flow IN ('firstemi','part','full','secondemi','preclose')"); $paid_amtf = towfetch($paid_amt); echo $paid_amtf['paid_amt'] ? $paid_amtf['paid_amt'] : 0;?></td>
                                        <td><?=$usersd_cleard_date?></td>
                                        <td><?php $dpd = $usersd_exhausted_period-30; if($dpd > 0){echo $dpd;}else{echo 0;} ?></td>
                                    </tr></form>
                                <?php } ?>
            </tbody>
    </table>
    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="transaction_details">
                                    <div class="table-responsive">
        <table class="table table-bordered" >
        <thead class="thead-light">
            <tr>                
                                        <th>CLL ID</th>        
                                        <th>Transaction Number</th>
                                        <th>Transaction Date</th>
                                        <th>Transaction Amount</th>
                                        <th>Transaction Flow</th>
                                    </tr>
        </thead>
        <tbody>
                  
                                   <?php $loan_data = towquery("SELECT * FROM transaction_details WHERE uid='$userpro_id' ORDER BY id DESC"); while($loan_fetch = towfetch($loan_data)){ extract($loan_fetch,EXTR_PREFIX_ALL,"users"); ?>
                                    <tr>
                                        <td>CLL<?=$users_cllid?></td>
                                        <td><?=$users_transaction_number?> <a href="edit_loan.php?cllid=<?=$users_id?>" class="btn btn-submit">Edit</a></td>
                                        <td><?=$users_transaction_date?></td>
                                        <td><?=$users_transaction_amount?></td>
                                        <td><?=$users_transaction_flow?></td>
                                    </tr>
                                <?php } ?>
                                    </tr>
            </tbody>
    </table>
    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="cibil_analysis">
                                    <div class="row">
                                        <p>work in progress</p>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="pan_analysis">
                                    <div class="row">
                                        <p>work in progress</p>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="adhar_analysis">
                                    <div class="row">
                                        <p>work in progress</p>
                                    </div>
                                </div>
                                <div class="product-tab-list tab-pane fade" id="bank_analysis">
                                    <div class="row">
                                        <p>work in progress</p>
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
</body>
</html>
 <div class="product-tab-list tab-pane fade active in" id="Personal">
                                    <div class="row">

                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <form id="add-department" action="" method="post" class="add-department">
                                                    <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            
                                                            
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                                                <select name="salary"  class="form-control" required>
<option value="">Monthly Net Salary</option>
<option value="10000" <?php if($user_salary == "10000"){echo "selected";}?>>Less than Rs 20,000</option>
<option value="21000" <?php if($user_salary == "21000"){echo "selected";}?>>More than Rs 20,000</option>
<option value="20000" <?php if($user_salary == "20000"){echo "selected";}?>>Exactly Rs 20,000</option>
                    </select>
                                                            </div>
                                                            <div class="form-group">
                    <select name="salarystatus"  class="form-control" required>
<option value="">Type of emplyment</option>
<option value="Salaried" <?php if($user_salarystatus == "Salaried"){echo "selected";}?>>Salaried</option>
<option value="Self-Employed" <?php if($user_salarystatus == "Self-Employed"){echo "selected";}?>>Self-Employed</option>
<option value="Student" <?php if($user_salarystatus == "Student"){echo "selected";}?>>Student</option>
<option value="Retired" <?php if($user_salarystatus == "Retired"){echo "selected";}?>>Retired</option>
<option value="Home maker" <?php if($user_salarystatus == "Home maker"){echo "selected";}?>>Home maker</option>
                    </select>
                                </div>
                                <div class="form-group">
                    <select name="get_salary"  class="form-control" required>
<option value="">Salary payment mode?</option>
<option value="bank transfer" <?php if($user_get_salary == "bank transfer"){echo "selected";}?>>bank transfer</option>
<option value="cash" <?php if($user_get_salary == "cash"){echo "selected";}?>>cash</option>
<option value="cheque" <?php if($user_get_salary == "cheque"){echo "selected";}?>>cheque</option>
                    </select>
                                </div>
                                <div class="form-group">
                   <input type="number" name="mobile" class="form-control" placeholder="Mobile">
                                </div>
                                                            
                                                        </div>
                                                        <div class="col-lg-12">
                                                            <div class="payment-adress">
                                                                <p id="mess"></p>
                                                                <button type="submit" class="btn btn-primary waves-effect waves-light" id="presub" style="margin-bottom:20px;">Submit</button><br>
                                                                
                                                            </div>
                                                        </div>
                                                        
                                                        </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                                
<div class="product-tab-list tab-pane fade" id="Professional">
                                    <div class="row">
                                        <?php if(empty($user_company) or empty($user_designation)){ ?>
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <form action="" method="post"><div class="row">
                                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                        <div class="devit-card-custom">
                                                            <div class="form-group">
                                                                <input type="text" name="company" class="form-control" placeholder="Company Name" pattern="(?=.*[a-zA-Z]).{6,}" title="Company name must be and more then 6 " value="<?=$user_company?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <input type="text" name="designation" class="form-control" placeholder="Designation" value="<?=$user_designation?>" pattern="(?=.*[a-zA-Z]).{1,}" required>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <input type="text" class="form-control" placeholder="Office Phone Number" name="office_number" pattern="[0-9]{9,}" value="<?=$user_office_number?>" required>
                                                            </div>
                                                             <div class="form-group">
                                                            <input type="text" class="form-control" placeholder="Department" name="department" value="<?=$user_department?>" pattern="(?=.*[a-zA-Z]).{1,}" required>
                                                        </div>
                                                        </div>

                                                    </div>
                                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                       
                                                        <div class="form-group">
                                                            <select class="form-control" name="annual_income" required>
                <option value="#" selected >CTC(Annual Income)</option>
                <option value="below1" <?php if($user_annual_income == "below1"){echo "selected";} ?> > &lt; 1 Lak</option>
                <option value="1" <?php if($user_annual_income == "1"){echo "selected";} ?>>1 Lakh</option>
                <option value="2" <?php if($user_annual_income == "2"){echo "selected";} ?> >2 Lakh</option>
                <option value="3" <?php if($user_annual_income == "3"){echo "selected";} ?> >3 Lakh</option>
                <option value="4" <?php if($user_annual_income == "4"){echo "selected";} ?> >4 Lakh</option>
                <option value="5" <?php if($user_annual_income == "5"){echo "selected";} ?> >5 Lakh</option>
                <option value="6" <?php if($user_annual_income == "6"){echo "selected";} ?> >6 Lakh</option>
                <option value="7" <?php if($user_annual_income == "7"){echo "selected";} ?> >7 Lakh</option>
                <option value="8" <?php if($user_annual_income == "8"){echo "selected";} ?> >8 Lakh</option>
                <option value="9" <?php if($user_annual_income == "9"){echo "selected";} ?> >9 Lakh</option>
                <option value="10" <?php if($user_annual_income == "10"){echo "selected";} ?> >10 Lakh</option>
                                            <option value="10+" <?php if($user_annual_income == "10+"){echo "selected";} ?> > &gt; 10 Lakh</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="text" class="form-control" placeholder="Office Pincode" name="office_pincode" value="<?=$user_office_pincode?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="text" class="form-control" placeholder="Office Address Line1" name="office_address_line1" value="<?=$user_office_address_line1?>" pattern="(?=.*[a-zA-Z]).{1,}" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="text" class="form-control" placeholder="Office Address Line2" name="office_address_line2" value="<?=$user_office_address_line2?>" pattern="(?=.*[a-zA-Z]).{1,}" required>
                                                        </div>
                                                </div></div>
                                                <div class="row">
                                                        <div class="col-lg-12">
                                                            <div class="payment-adress">
                                                        <button type="submit" class="btn btn-primary waves-effect waves-light">Submit</button>
                                                    </div>
                                                </div>
                                            </div>
                                            </form>
                                            </div>
                                        </div>
                                        <?php }else{?>
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="review-content-section">
                                                <div class="row">
                                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                        <div class="devit-card-custom">
                                                            <div class="form-group">
                                                                <input type="text" class="form-control" value="<?=$user_company?>" disabled>
                                                            </div>
                                                            <div class="form-group">
                                                                <input type="text" class="form-control" value="<?=$user_designation?>" disabled>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <input type="text" class="form-control" value="<?=$user_office_number?>" disabled>
                                                            </div>
                                                             <div class="form-group">
                                                            <input type="text" class="form-control" value="<?=$user_department?>" disabled>
                                                        </div>
                                                        </div>

                                                    </div>
                                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                       
                                                        <div class="form-group">
                                                            <select class="form-control" name="annual_income" disabled>
                <option value="#" selected >CTC(Annual Income)</option>
                <option value="below1" <?php if($user_annual_income == "below1"){echo "selected";} ?> > &lt; 1 Lak</option>
                <option value="1" <?php if($user_annual_income == "1"){echo "selected";} ?>>1 Lakh</option>
                <option value="2" <?php if($user_annual_income == "2"){echo "selected";} ?> >2 Lakh</option>
                <option value="3" <?php if($user_annual_income == "3"){echo "selected";} ?> >3 Lakh</option>
                <option value="4" <?php if($user_annual_income == "4"){echo "selected";} ?> >4 Lakh</option>
                <option value="5" <?php if($user_annual_income == "5"){echo "selected";} ?> >5 Lakh</option>
                <option value="6" <?php if($user_annual_income == "6"){echo "selected";} ?> >6 Lakh</option>
                <option value="7" <?php if($user_annual_income == "7"){echo "selected";} ?> >7 Lakh</option>
                <option value="8" <?php if($user_annual_income == "8"){echo "selected";} ?> >8 Lakh</option>
                <option value="9" <?php if($user_annual_income == "9"){echo "selected";} ?> >9 Lakh</option>
                <option value="10" <?php if($user_annual_income == "10"){echo "selected";} ?> >10 Lakh</option>
                                            <option value="10+" <?php if($user_annual_income == "10+"){echo "selected";} ?> > &gt; 10 Lakh</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="text" class="form-control" value="<?=$user_office_pincode?>" disabled>
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="text" class="form-control" value="<?=$user_office_address_line1?>" disabled>
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="text" class="form-control" value="<?=$user_office_address_line2?>" disabled>
                                                        </div>
                                                </div></div>
                                                <div class="row">
                                                        <div class="col-lg-12">
                                                            <div class="payment-adress">
                                                        <a class="btn btn-primary waves-effect waves-light" id="next3">next</a>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            </div>
                                        </div>
                                        <?php }?>
                                    </div>
                                </div>
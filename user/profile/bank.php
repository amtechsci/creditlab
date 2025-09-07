<div class="product-tab-list tab-pane fade" id="Bank">
                                                 <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="form-group">
                                                                <input type="text" class="form-control" placeholder="Bank Name" value="<?=$user_bank_name?>" disabled >
                                                            </div>
                                                            <div class="form-group">
                                                            <input type="text" placeholder="IFSC Code" class="form-control" value="<?=$user_ifsc?>" disabled >
                                                            </div>
                                                            <div class="form-group">
                                                            <input type="text" placeholder="Account Number" class="form-control" value="<?=$user_account_no?>" disabled>
                                                            </div>
                                                          
                                                        </div>

                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                              <div class="form-group">
                                                                <select class="form-control" name="account_type" value="<?=$user_account_type?>" disabled>
                                
                                                                    <option <?php if($user_account_type == "saving"){echo "selected";} ?> value="saving">Saving</option>
                                                                    <option <?php if($user_account_type == "current"){echo "selected";} ?> value="current">Current</option>
                            
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <input type="text" class="form-control" placeholder="Account Holder Name" value="<?=$user_account_name?>" disabled >
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                </div>
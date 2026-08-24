<?php
if (!isset($pd) || !is_array($pd)) {
    return;
}
extract($pd, EXTR_SKIP);
?>
<div id="performance-dashboard" class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="product-payment-inner-st">
                <h3 style="padding: 15px 20px 0;">Performance Dashboard</h3>
                <ul id="myTabedu1" class="tab-review-design">
                    <li class="<?=$view_mode == 'userwise' ? 'active' : '';?>"><a href="<?= htmlspecialchars($userwise_url) ?>">User-wise Report</a></li>
                    <li class="<?=$view_mode == 'updates' ? 'active' : '';?>"><a href="<?= htmlspecialchars($updates_url) ?>">Updates Report</a></li>
                </ul>
                <div id="myTabContent" class="tab-content custom-product-edit">
                    <div class="product-tab-list tab-pane fade <?=$view_mode == 'userwise' ? 'active in' : '';?>" id="userwise">
                        <?php if ($view_mode !== 'userwise'): ?>
                        <p style="padding: 20px;">Open the User-wise Report tab to load this data.</p>
                        <?php else: ?>
                        <form method="GET" action="index.php" class="form-inline" style="padding: 20px;">
                            <input type="hidden" name="view" value="userwise">
                            <div class="form-group" style="margin-right: 20px;">
                                <label for="perf_date" style="margin-right: 10px;">Date:</label>
                                <input type="date" name="date" id="perf_date" class="form-control" value="<?=$selected_date;?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>

                        <div style="margin-top: 10px; padding: 0 20px 20px;">
                            <h4 style="margin-bottom: 20px;">User-wise Performance Report (<?=date('d-M-Y', strtotime($selected_date));?>)</h4>
                            <div class="row">
                                <?php
                                foreach ($user_wise_rows as $user_data) {
                                    $user_total = $user_data['total_calls'];
                                    $user_responding = $user_data['responding_count'];
                                    $user_not_responding = $user_data['not_responding_count'];
                                    $user_performance = ($user_total > 0) ? round(($user_responding / $user_total) * 100, 2) : 0;
                                    $by = $user_data['updated_by'];
                                    $repayment_data = [
                                        'today_count' => $today_repay_by_user[$by]['count'] ?? 0,
                                        'today_amount' => $today_repay_by_user[$by]['amount'] ?? 0,
                                        'monthly_count' => $monthly_repay_by_user[$by]['count'] ?? 0,
                                        'monthly_amount' => $monthly_repay_by_user[$by]['amount'] ?? 0,
                                    ];
                                    ?>
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12" style="margin-bottom: 30px;">
                                        <div class="product-payment-inner-st" style="border: 1px solid #ddd; border-radius: 5px; padding: 20px;">
                                            <h3 style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">
                                                <?=htmlspecialchars($user_data['updated_by']);?>
                                            </h3>
                                            <div class="row">
                                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                    <div style="text-align: center; padding: 15px; background-color: #E3F2FD; border-radius: 5px; margin-bottom: 10px;">
                                                        <h5 style="margin: 0;">Responding</h5>
                                                        <h2 style="margin: 5px 0 0; color: #2196F3;"><?=$user_responding;?></h2>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                    <div style="text-align: center; padding: 15px; background-color: #FFEBEE; border-radius: 5px; margin-bottom: 10px;">
                                                        <h5 style="margin: 0;">Not Responding</h5>
                                                        <h2 style="margin: 5px 0 0; color: #F44336;"><?=$user_not_responding;?></h2>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                    <div style="text-align: center; padding: 15px; background-color: #FFF3E0; border-radius: 5px; margin-bottom: 10px;">
                                                        <h5 style="margin: 0;">Total</h5>
                                                        <h2 style="margin: 5px 0 0; color: #FF9800;"><?=$user_total;?></h2>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                    <div style="text-align: center; padding: 15px; background-color: #E8F5E9; border-radius: 5px; margin-bottom: 10px;">
                                                        <h5 style="margin: 0;">Performance</h5>
                                                        <h2 style="margin: 5px 0 0; color: #4CAF50;"><?=$user_performance;?>%</h2>
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
                                                <h4 style="margin-bottom: 15px; color: #666;">Repayments</h4>
                                                <div class="row">
                                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                        <div style="padding: 10px; background-color: #f5f5f5; border-radius: 5px; margin-bottom: 10px;">
                                                            <h6 style="margin: 0 0 5px 0; color: #666; font-size: 12px;">Today_Cleared Follow-up</h6>
                                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                                <span style="font-size: 18px; font-weight: bold; color: #2196F3;"><?=$repayment_data['today_count'];?></span>
                                                                <span style="font-size: 14px; color: #666;">₹<?=number_format($repayment_data['today_amount'], 2);?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                        <div style="padding: 10px; background-color: #f5f5f5; border-radius: 5px; margin-bottom: 10px;">
                                                            <h6 style="margin: 0 0 5px 0; color: #666; font-size: 12px;">Monthly_Cleared Follow-up</h6>
                                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                                <span style="font-size: 18px; font-weight: bold; color: #4CAF50;"><?=$repayment_data['monthly_count'];?></span>
                                                                <span style="font-size: 14px; color: #666;">₹<?=number_format($repayment_data['monthly_amount'], 2);?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                                if (empty($user_wise_rows)) {
                                    echo '<div class="col-lg-12"><div class="alert alert-info">No data found for the selected date.</div></div>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-tab-list tab-pane fade <?=$view_mode == 'updates' ? 'active in' : '';?>" id="updates">
                        <?php if ($view_mode !== 'updates'): ?>
                        <p style="padding: 20px;">Open the Updates Report tab to load this data.</p>
                        <?php else: ?>
                        <form method="GET" action="index.php" class="form-inline" style="padding: 20px;">
                            <input type="hidden" name="view" value="updates">
                            <div class="form-group" style="margin-right: 15px;">
                                <label for="from_date" style="margin-right: 10px;">From Date:</label>
                                <input type="date" name="from_date" id="from_date" class="form-control" value="<?=$from_date;?>" required>
                            </div>
                            <div class="form-group" style="margin-right: 15px;">
                                <label for="to_date" style="margin-right: 10px;">To Date:</label>
                                <input type="date" name="to_date" id="to_date" class="form-control" value="<?=$to_date;?>" required>
                            </div>
                            <div class="form-group" style="margin-right: 15px;">
                                <label for="perf_user" style="margin-right: 10px;">Filter by User:</label>
                                <select name="user" id="perf_user" class="form-control">
                                    <option value="">All Users</option>
                                    <?php foreach ($account_managers as $am): ?>
                                        <option value="<?=htmlspecialchars($am['updated_by']);?>" <?=$selected_user == $am['updated_by'] ? 'selected' : '';?>>
                                            <?=htmlspecialchars($am['updated_by']);?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success" style="margin-right: 10px;">Apply Filters</button>
                            <a href="index.php?view=updates" class="btn btn-default">Reset</a>
                        </form>

                        <div class="analytics-sparkle-area" style="margin-top: 20px;">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                        <div class="analytics-sparkle-line reso-mg-b-30">
                                            <div class="analytics-content">
                                                <h5>Total Updates</h5>
                                                <h2><span class="counter"><?=$total_updates;?></span></h2>
                                                <span class="text-muted">From <?=date('d M Y', strtotime($from_date));?> to <?=date('d M Y', strtotime($to_date));?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 30px; padding: 0 20px;">
                            <h3 style="margin-bottom: 20px;">Summary by User</h3>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Updated By</th>
                                            <th>Total Updates</th>
                                            <th>First Update</th>
                                            <th>Last Update</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $row_num = 1;
                                        foreach ($summary_rows as $summary) {
                                            $first_update = $summary['first_update'] ? date('d M Y H:i', strtotime($summary['first_update'])) : 'N/A';
                                            $last_update = $summary['last_update'] ? date('d M Y H:i', strtotime($summary['last_update'])) : 'N/A';
                                            ?>
                                            <tr>
                                                <td><?=$row_num++;?></td>
                                                <td><strong><?=htmlspecialchars($summary['updated_by']);?></strong></td>
                                                <td><span class="badge badge-primary" style="font-size: 14px;"><?=$summary['update_count'];?></span></td>
                                                <td><?=$first_update;?></td>
                                                <td><?=$last_update;?></td>
                                                <td>
                                                    <a href="index.php?view=updates&from_date=<?=$from_date;?>&to_date=<?=$to_date;?>&user=<?=urlencode($summary['updated_by']);?>" class="btn btn-sm btn-info">View Details</a>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        if ($row_num == 1) {
                                            echo "<tr><td colspan='6' class='text-center'>No updates found for the selected date range.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div style="margin-top: 30px; padding: 0 20px 20px;">
                            <h3 style="margin-bottom: 20px;">All Updates Details</h3>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="updatesTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Updated By</th>
                                            <th>Customer Name</th>
                                            <th>Loan ID</th>
                                            <th>Customer Response</th>
                                            <th>Commitment Date</th>
                                            <th>Commitment Text</th>
                                            <th>Default Type</th>
                                            <th>Updated At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $detail_num = 1;
                                        foreach ($details_rows as $detail) {
                                            $updated_at = $detail['updated_at'] ? date('d M Y H:i:s', strtotime($detail['updated_at'])) : 'N/A';
                                            $commitment_date = $detail['commitment_date'] ? date('d M Y', strtotime($detail['commitment_date'])) : 'N/A';
                                            ?>
                                            <tr>
                                                <td><?=$detail_num++;?></td>
                                                <td><strong><?=htmlspecialchars($detail['updated_by']);?></strong></td>
                                                <td>
                                                    <?php if($detail['customer_name']): ?>
                                                        <a href="profile.php?id=<?=$detail['uid'];?>" target="_blank">
                                                            <?=htmlspecialchars($detail['customer_name']);?>
                                                        </a>
                                                    <?php else: ?>
                                                        User ID: <?=$detail['uid'];?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>CLL<?=$detail['lid'];?></td>
                                                <td><?=htmlspecialchars($detail['customer_response']);?></td>
                                                <td><?=$commitment_date;?></td>
                                                <td><?=htmlspecialchars(substr($detail['commitment_text'], 0, 50));?><?=strlen($detail['commitment_text']) > 50 ? '...' : '';?></td>
                                                <td><?=htmlspecialchars($detail['default_type']);?></td>
                                                <td><?=$updated_at;?></td>
                                            </tr>
                                            <?php
                                        }
                                        if ($detail_num == 1) {
                                            echo "<tr><td colspan='9' class='text-center'>No updates found for the selected filters.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

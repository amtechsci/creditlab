<?php
if (!isset($pd) || !is_array($pd)) {
    return;
}
extract($pd, EXTR_SKIP);
?>
<style>
#performance-dashboard { padding-top: 2px; }
#performance-dashboard .perf-subnav {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    list-style: none;
    margin: 0 0 16px;
    padding: 0;
}
#performance-dashboard .perf-subnav a {
    display: inline-block;
    padding: 7px 16px;
    border-radius: 20px;
    background: #eef2f7;
    color: #4b5563;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    line-height: 1.3;
}
#performance-dashboard .perf-subnav a:hover,
#performance-dashboard .perf-subnav a:focus {
    background: #e0e7ef;
    color: #1f2937;
    text-decoration: none;
}
#performance-dashboard .perf-subnav li.active a {
    background: #006DF0;
    color: #fff;
}
#performance-dashboard .perf-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 12px;
    padding: 12px 14px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 16px;
}
#performance-dashboard .perf-toolbar .form-group {
    margin: 0;
}
#performance-dashboard .perf-toolbar label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #6b7280;
    margin-bottom: 4px;
}
#performance-dashboard .perf-toolbar .form-control {
    height: 34px;
    min-width: 150px;
    border-radius: 4px;
}
#performance-dashboard .perf-toolbar .btn {
    height: 34px;
    padding: 6px 14px;
    line-height: 20px;
    margin: 0;
}
#performance-dashboard .perf-stat {
    display: inline-flex;
    align-items: baseline;
    gap: 10px;
    padding: 10px 16px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 16px;
}
#performance-dashboard .perf-stat .n {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}
#performance-dashboard .perf-stat .l {
    font-size: 12px;
    color: #6b7280;
}
#performance-dashboard .perf-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 14px;
    margin-bottom: 16px;
    background: #fff;
}
#performance-dashboard .perf-card h5 {
    margin: 0 0 12px;
    font-size: 15px;
    font-weight: 700;
    padding-bottom: 8px;
    border-bottom: 1px solid #f3f4f6;
}
#performance-dashboard .perf-metric {
    text-align: center;
    padding: 10px 6px;
    border-radius: 6px;
    margin-bottom: 8px;
}
#performance-dashboard .perf-metric span {
    display: block;
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
}
#performance-dashboard .perf-metric strong {
    display: block;
    font-size: 20px;
    font-weight: 700;
    margin-top: 2px;
}
#performance-dashboard .perf-section-title {
    font-size: 14px;
    font-weight: 700;
    margin: 0 0 10px;
    color: #374151;
}
#performance-dashboard .table {
    margin-bottom: 0;
}
#performance-dashboard .table th {
    background: #f3f4f6;
    font-size: 12px;
    white-space: nowrap;
}
#performance-dashboard .table td {
    font-size: 13px;
    vertical-align: middle;
}
#performance-dashboard .perf-block {
    margin-bottom: 20px;
}
#performance-dashboard .badge-count {
    display: inline-block;
    min-width: 36px;
    padding: 3px 8px;
    border-radius: 12px;
    background: #e8f1fe;
    color: #006DF0;
    font-weight: 700;
    font-size: 12px;
}
</style>
<div id="performance-dashboard">
    <ul class="perf-subnav">
        <li class="<?= $view_mode == 'userwise' ? 'active' : '' ?>">
            <a href="<?= htmlspecialchars($userwise_url) ?>">User-wise Report</a>
        </li>
        <li class="<?= $view_mode == 'updates' ? 'active' : '' ?>">
            <a href="<?= htmlspecialchars($updates_url) ?>">Updates Report</a>
        </li>
    </ul>

    <?php if ($view_mode === 'userwise'): ?>
        <form method="GET" action="index.php" class="perf-toolbar">
            <input type="hidden" name="tab" value="performance">
            <input type="hidden" name="view" value="userwise">
            <div class="form-group">
                <label for="perf_date">Date</label>
                <input type="date" name="date" id="perf_date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <p class="perf-section-title">Results for <?= date('d M Y', strtotime($selected_date)) ?></p>
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
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="perf-card">
                        <h5><?= htmlspecialchars($user_data['updated_by']) ?></h5>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="perf-metric" style="background:#E3F2FD;">
                                    <span>Responding</span>
                                    <strong style="color:#2196F3;"><?= (int) $user_responding ?></strong>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="perf-metric" style="background:#FFEBEE;">
                                    <span>Not responding</span>
                                    <strong style="color:#F44336;"><?= (int) $user_not_responding ?></strong>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="perf-metric" style="background:#FFF3E0;">
                                    <span>Total</span>
                                    <strong style="color:#FF9800;"><?= (int) $user_total ?></strong>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="perf-metric" style="background:#E8F5E9;">
                                    <span>Performance</span>
                                    <strong style="color:#4CAF50;"><?= $user_performance ?>%</strong>
                                </div>
                            </div>
                        </div>
                        <p class="perf-section-title" style="margin-top:8px;">Repayments</p>
                        <div class="row">
                            <div class="col-xs-6">
                                <div style="padding:8px 10px; background:#f8fafc; border-radius:6px; margin-bottom:8px;">
                                    <div style="font-size:11px; color:#6b7280;">Today cleared</div>
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <strong style="color:#2196F3;"><?= (int) $repayment_data['today_count'] ?></strong>
                                        <span style="font-size:12px; color:#6b7280;">₹<?= number_format($repayment_data['today_amount'], 2) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div style="padding:8px 10px; background:#f8fafc; border-radius:6px; margin-bottom:8px;">
                                    <div style="font-size:11px; color:#6b7280;">Monthly cleared</div>
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <strong style="color:#4CAF50;"><?= (int) $repayment_data['monthly_count'] ?></strong>
                                        <span style="font-size:12px; color:#6b7280;">₹<?= number_format($repayment_data['monthly_amount'], 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            if (empty($user_wise_rows)) {
                echo '<div class="col-lg-12"><div class="alert alert-info" style="margin:0;">No data found for the selected date.</div></div>';
            }
            ?>
        </div>

    <?php else: ?>
        <form method="GET" action="index.php" class="perf-toolbar">
            <input type="hidden" name="tab" value="performance">
            <input type="hidden" name="view" value="updates">
            <div class="form-group">
                <label for="from_date">From date</label>
                <input type="date" name="from_date" id="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>" required>
            </div>
            <div class="form-group">
                <label for="to_date">To date</label>
                <input type="date" name="to_date" id="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>" required>
            </div>
            <div class="form-group">
                <label for="perf_user">Filter by user</label>
                <select name="user" id="perf_user" class="form-control">
                    <option value="">All users</option>
                    <?php foreach ($account_managers as $am): ?>
                        <option value="<?= htmlspecialchars($am['updated_by']) ?>" <?= $selected_user == $am['updated_by'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($am['updated_by']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Apply filters</button>
            <a href="index.php?tab=performance&view=updates" class="btn btn-default">Reset</a>
        </form>

        <div class="perf-stat">
            <span class="n"><?= (int) $total_updates ?></span>
            <span class="l">total updates · <?= date('d M Y', strtotime($from_date)) ?> – <?= date('d M Y', strtotime($to_date)) ?></span>
        </div>

        <div class="perf-block">
            <p class="perf-section-title">Summary by user</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Updated by</th>
                            <th>Total updates</th>
                            <th>First update</th>
                            <th>Last update</th>
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
                                <td><?= $row_num++ ?></td>
                                <td><strong><?= htmlspecialchars($summary['updated_by']) ?></strong></td>
                                <td><span class="badge-count"><?= (int) $summary['update_count'] ?></span></td>
                                <td><?= $first_update ?></td>
                                <td><?= $last_update ?></td>
                                <td>
                                    <a href="index.php?tab=performance&view=updates&from_date=<?= htmlspecialchars($from_date) ?>&to_date=<?= htmlspecialchars($to_date) ?>&user=<?= urlencode($summary['updated_by']) ?>" class="btn btn-xs btn-info">View details</a>
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

        <div class="perf-block">
            <p class="perf-section-title">All updates details</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="updatesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Updated by</th>
                            <th>Customer name</th>
                            <th>Loan ID</th>
                            <th>Customer response</th>
                            <th>Commitment date</th>
                            <th>Commitment text</th>
                            <th>Default type</th>
                            <th>Updated at</th>
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
                                <td><?= $detail_num++ ?></td>
                                <td><strong><?= htmlspecialchars($detail['updated_by']) ?></strong></td>
                                <td>
                                    <?php if ($detail['customer_name']): ?>
                                        <a href="profile.php?id=<?= (int) $detail['uid'] ?>" target="_blank"><?= htmlspecialchars($detail['customer_name']) ?></a>
                                    <?php else: ?>
                                        User ID: <?= (int) $detail['uid'] ?>
                                    <?php endif; ?>
                                </td>
                                <td>CLL<?= htmlspecialchars((string) $detail['lid']) ?></td>
                                <td><?= htmlspecialchars((string) $detail['customer_response']) ?></td>
                                <td><?= $commitment_date ?></td>
                                <td><?= htmlspecialchars(substr((string) $detail['commitment_text'], 0, 50)) ?><?= strlen((string) $detail['commitment_text']) > 50 ? '...' : '' ?></td>
                                <td><?= htmlspecialchars((string) $detail['default_type']) ?></td>
                                <td><?= $updated_at ?></td>
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

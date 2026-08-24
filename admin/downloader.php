<?php
include_once 'head.php';

$allowed_tabs = ['export', 'reports'];
$active_tab = isset($_GET['tab']) ? strtolower(trim((string) $_GET['tab'])) : 'export';
if (!in_array($active_tab, $allowed_tabs, true)) {
    $active_tab = 'export';
}

$date_filter = null;
$type_filter = null;
$email_sent_filter = null;
$result = null;

if ($active_tab === 'reports') {
    require_once __DIR__ . '/../lib/s3_aws_sdk.php';

    $date_filter = isset($_GET['date']) ? towreal($_GET['date']) : null;
    $type_filter = isset($_GET['type']) ? towreal($_GET['type']) : null;
    $email_sent_filter = isset($_GET['email_sent']) ? $_GET['email_sent'] : null;

    $sql = "SELECT * FROM `download_links` WHERE 1=1";
    if ($date_filter) {
        $sql .= " AND `report_date` = '$date_filter'";
    }
    if ($type_filter) {
        $sql .= " AND `report_type` = '$type_filter'";
    }
    if ($email_sent_filter !== null && $email_sent_filter !== '') {
        $email_sent_filter = (int) $email_sent_filter;
        $sql .= " AND `email_sent` = $email_sent_filter";
    }
    $sql .= " ORDER BY `created_at` DESC LIMIT 100";
    $result = towquery($sql);
}

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
?>
<body>
<?php include_once 'Left_menu.php'; include_once 'welcome.php'; include_once 'm_menu.php'; ?>

            <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <h2 style="margin:0 0 6px;"><i class="fa fa-download"></i> Downloader</h2>
                                <p style="margin:0;color:#666;">Export reports and saved download links</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .settings-nav.nav-tabs {
                display: flex;
                flex-wrap: wrap;
                margin: 0 0 22px;
                padding: 0;
                list-style: none;
                border: 0;
                border-bottom: 2px solid #e8ecef;
                background: #fff;
            }
            .settings-nav > li { float: none; margin: 0; }
            .settings-nav > li > a {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 14px 22px;
                color: #6b7280;
                font-weight: 600;
                font-size: 15px;
                text-decoration: none;
                border: 0 !important;
                border-bottom: 3px solid transparent !important;
                margin-bottom: -2px;
                background: transparent !important;
                border-radius: 0;
            }
            .settings-nav > li > a:hover,
            .settings-nav > li > a:focus {
                background: transparent !important;
                color: #00a57d;
                text-decoration: none;
            }
            .settings-nav > li.active > a,
            .settings-nav > li.active > a:hover,
            .settings-nav > li.active > a:focus {
                color: #00a57d;
                background: transparent !important;
                border-bottom-color: #00c195 !important;
            }
            .download-panel {
                background: #fff;
                padding: 20px;
                margin-bottom: 20px;
                border: 1px solid #e7e7e7;
                border-radius: 8px;
            }
            .download-buttons { margin-top: 16px; }
            .download-buttons a { display: inline-block; margin: 0 10px 10px 0; }
        </style>

        <div class="single-pro-review-area mt-t-30 mg-b-15">
            <div class="container-fluid">
                <ul class="nav nav-tabs settings-nav">
                    <li class="<?= $active_tab === 'export' ? 'active' : '' ?>">
                        <a href="downloader.php?tab=export"><i class="fa fa-file-excel-o"></i> Export</a>
                    </li>
                    <li class="<?= $active_tab === 'reports' ? 'active' : '' ?>">
                        <a href="downloader.php?tab=reports"><i class="fa fa-download"></i> Report Downloads</a>
                    </li>
                </ul>

                <?php if ($active_tab === 'export'): ?>
                    <div class="download-panel">
                        <h4>Select date range for reports</h4>
                        <form id="dateRangeForm" style="display:flex;align-items:flex-end;gap:15px;flex-wrap:wrap;">
                            <div>
                                <label for="from_date">From Date</label>
                                <input type="date" id="from_date" name="from_date" class="form-control" style="width:200px;" value="<?= htmlspecialchars($from_date) ?>">
                            </div>
                            <div>
                                <label for="to_date">To Date</label>
                                <input type="date" id="to_date" name="to_date" class="form-control" style="width:200px;" value="<?= htmlspecialchars($to_date) ?>">
                            </div>
                            <div>
                                <button type="button" onclick="updateDownloadLinks()" class="btn btn-success">Apply Date Range</button>
                                <button type="button" onclick="resetDateRange()" class="btn btn-default">Reset</button>
                            </div>
                        </form>
                    </div>
                    <div id="downloadButtons" class="download-panel download-buttons">
                        <a href="/downloader/disbursal.php" target="_blank" class="download-link" data-file="disbursal.php"><button class="btn btn-success">Disbursal</button></a>
                        <a href="/downloader/cleared.php" target="_blank" class="download-link" data-file="cleared.php"><button class="btn btn-success">Cleared</button></a>
                        <a href="/downloader/default.php" target="_blank" class="download-link" data-file="default.php"><button class="btn btn-success">Default</button></a>
                        <a href="/downloader/part_payment.php" target="_blank" class="download-link" data-file="part_payment.php"><button class="btn btn-success">Part payment</button></a>
                        <a href="/downloader/settlement.php" target="_blank" class="download-link" data-file="settlement.php"><button class="btn btn-success">Settlement</button></a>
                        <a href="/downloader/bs_repayment.php" target="_blank" class="download-link" data-file="bs_repayment.php"><button class="btn btn-success">BS Repayment</button></a>
                        <a href="/downloader/bs_disbursal.php" target="_blank" class="download-link" data-file="bs_disbursal.php"><button class="btn btn-success">BS Disbursal</button></a>
                        <a href="/downloader/applied.php" target="_blank" class="download-link" data-file="applied.php"><button class="btn btn-success">Applied</button></a>
                        <a href="/downloader/recoveryagency.php" target="_blank" class="download-link" data-file="recoveryagency.php"><button class="btn btn-success">Recovery agency</button></a>
                        <a href="/downloader/agency_payments.php" target="_blank" class="download-link" data-file="agency_payments.php"><button class="btn btn-success">Agency wise payments</button></a>
                    </div>
                    <script>
                        function updateDownloadLinks() {
                            var fromDate = document.getElementById('from_date').value;
                            var toDate = document.getElementById('to_date').value;
                            if (!fromDate || !toDate) {
                                alert('Please select both From Date and To Date');
                                return;
                            }
                            if (new Date(fromDate) > new Date(toDate)) {
                                alert('From Date cannot be greater than To Date');
                                return;
                            }
                            document.querySelectorAll('.download-link').forEach(function(link) {
                                var file = link.getAttribute('data-file');
                                link.setAttribute('href', '/downloader/' + file + '?from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate));
                            });
                        }
                        function resetDateRange() {
                            document.getElementById('from_date').value = '<?= date('Y-m-01') ?>';
                            document.getElementById('to_date').value = '<?= date('Y-m-d') ?>';
                            updateDownloadLinks();
                        }
                        updateDownloadLinks();
                    </script>

                <?php else: ?>
                    <div class="download-panel">
                        <h4>Saved report downloads</h4>
                        <form method="GET" action="downloader.php" class="form-inline" style="margin-bottom:16px;">
                            <input type="hidden" name="tab" value="reports">
                            <div class="form-group" style="margin-right:15px;">
                                <label style="margin-right:5px;">Date:</label>
                                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter ?? '') ?>">
                            </div>
                            <div class="form-group" style="margin-right:15px;">
                                <label style="margin-right:5px;">Report Type:</label>
                                <select name="type" class="form-control">
                                    <option value="">All</option>
                                    <?php
                                    $types = [
                                        'disbursal' => 'Disbursal',
                                        'cleared' => 'Cleared',
                                        'default' => 'Default',
                                        'part_payment' => 'Part Payment',
                                        'settlement' => 'Settlement',
                                        'bs_repayment' => 'BS Repayment',
                                        'bs_disbursal' => 'BS Disbursal',
                                        'applied' => 'Applied',
                                        'recoveryagency' => 'Recovery Agency',
                                    ];
                                    foreach ($types as $value => $label) {
                                        $sel = ($type_filter == $value) ? ' selected' : '';
                                        echo '<option value="' . htmlspecialchars($value) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-right:15px;">
                                <label style="margin-right:5px;">Email Sent:</label>
                                <select name="email_sent" class="form-control">
                                    <option value="">All</option>
                                    <option value="1"<?= $email_sent_filter === '1' || $email_sent_filter === 1 ? ' selected' : '' ?>>Yes</option>
                                    <option value="0"<?= $email_sent_filter === '0' || $email_sent_filter === 0 ? ' selected' : '' ?>>No</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">Filter</button>
                            <a href="downloader.php?tab=reports" class="btn btn-default">Clear</a>
                        </form>

                        <?php if ($result && townum($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Report Name</th>
                                            <th>Report Type</th>
                                            <th>Report Date</th>
                                            <th>Period</th>
                                            <th>Date Range</th>
                                            <th>Download</th>
                                            <th>Email Sent</th>
                                            <th>Email Sent At</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php while ($row = towfetch($result)): ?>
                                        <?php
                                        $download_url = $row['s3_url'];
                                        $s3_key = $row['s3_key'];
                                        $key_for_presigned = $s3_key;
                                        if (empty($key_for_presigned) && preg_match('#/(uploads/.+)$#', $row['s3_url'], $matches)) {
                                            $key_for_presigned = $matches[1];
                                        }
                                        if (!empty($key_for_presigned)) {
                                            list($success, $presigned_url) = s3_get_file_url($key_for_presigned, '+7 days');
                                            if ($success) {
                                                $download_url = $presigned_url;
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['id']) ?></td>
                                            <td><?= htmlspecialchars($row['report_name']) ?></td>
                                            <td><?= htmlspecialchars($row['report_type']) ?></td>
                                            <td><?= htmlspecialchars($row['report_date']) ?></td>
                                            <td><?= htmlspecialchars($row['report_period']) ?></td>
                                            <td><?= htmlspecialchars($row['from_date']) ?> to <?= htmlspecialchars($row['to_date']) ?></td>
                                            <td><a href="<?= htmlspecialchars($download_url) ?>" target="_blank" class="btn btn-success btn-sm" title="Presigned URL, valid for 7 days"><i class="fa fa-download"></i> Download</a></td>
                                            <td><span class="<?= $row['email_sent'] ? 'text-success' : 'text-danger' ?>"><?= $row['email_sent'] ? 'Yes' : 'No' ?></span></td>
                                            <td><?= $row['email_sent_at'] ? htmlspecialchars($row['email_sent_at']) : '-' ?></td>
                                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No download links found.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

<?php include_once 'foot.php'; ?>
</body>
</html>

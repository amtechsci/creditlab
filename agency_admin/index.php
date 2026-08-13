<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/loan_dpd.php';
require_once __DIR__ . '/../lib/loan_acc_man.php';

$pageno = isset($_GET['pageno']) ? max(1, (int) $_GET['pageno']) : 1;
$no_of_records_per_page = 50;
$offset = ($pageno - 1) * $no_of_records_per_page;

$amLoans = creditlab_account_manager_loan_rows();
$default_loans_all = $amLoans['default'];
$total_rows = count($default_loans_all);
$total_pages = max(1, (int) ceil($total_rows / $no_of_records_per_page));
$default_loans_paged = array_slice($default_loans_all, $offset, $no_of_records_per_page);
?>
<body>
<?php
include_once 'Left_menu.php';
include_once 'welcome.php';
include_once 'm_menu.php';
?>
<div class="breadcome-area">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcome-list">
                    <p><strong><?= htmlspecialchars($agency_admin_agency_name ?? 'Agency') ?></strong> — loans with DPD &gt; 35 only</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-payment-inner-st">
                    <a href="download_recovery.php" class="btn btn-primary" style="color:#fff; float: right;">Download</a>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="loan_history_datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th><input type="checkbox"></th>
                                    <th>SLNO</th>
                                    <th>CID</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>city</th>
                                    <th>Total Loans</th>
                                    <th>principal loan Amt</th>
                                    <th>loan exhausted days</th>
                                    <th>DPD</th>
                                    <th>outstanding Amount</th>
                                    <th>loan ID</th>
                                    <th>St response</th>
                                    <th>commitment date</th>
                                    <th>updated date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $ii = 1;
                                foreach ($default_loans_paged as $b) {
                                    extract($b, EXTR_PREFIX_ALL, 'user');

                                    $responses = [];
                                    $commit_dates = [];
                                    $updated_ats = [];
                                    $agencyId = (int) ($agency_admin_agency_id ?? 0);
                                    $recentUpdates = creditlab_loan_acc_man_recent_rows((int) $user_lid, $agencyId, 3);
                                    $responses = $recentUpdates['responses'];
                                    $commit_dates = $recentUpdates['commit_dates'];
                                    $updated_ats = $recentUpdates['updated_ats'];
                                    $concatenated_responses = implode('<br><br>', $responses);
                                    $concatenated_dates = implode('<br><br>', $commit_dates);
                                    $concatenated_updates = implode('<br><br>', $updated_ats);

                                    $loan_count_query = towquery('SELECT COUNT(*) AS total_loans FROM `loan` WHERE uid=' . (int) $user_uid);
                                    $loan_count_row = $loan_count_query ? towfetch($loan_count_query) : null;
                                    $loan_count = $loan_count_row ? (int) $loan_count_row['total_loans'] : 0;
                                    $loan_count_style = $loan_count === 1 ? 'font-weight:bold;color:red;' : '';
                                    $loan_count_markup = '<span style="' . $loan_count_style . '">No. of Loans: ' . $loan_count . '</span>';

                                    $salary_value = isset($user_salary) ? (float) $user_salary : 0.0;
                                    $loan_limit_value = isset($user_loan_limit) ? (float) $user_loan_limit : 0.0;
                                    $limit_percentage = $salary_value > 0 ? (($loan_limit_value / $salary_value) * 100) : null;
                                    $limit_percentage_formatted = $limit_percentage !== null ? number_format($limit_percentage, 2) : null;
                                    $limit_percentage_style = ($limit_percentage !== null && $limit_percentage > 15) ? 'font-weight:bold;color:red;' : '';
                                    $limit_percentage_markup = $limit_percentage !== null
                                        ? '<span style="' . $limit_percentage_style . '">Limit vs Salary: ' . $limit_percentage_formatted . '%</span>'
                                        : '<span>Limit vs Salary: N/A</span>';

                                    $membership_label = '';
                                    switch ((int) $user_member) {
                                        case 0:
                                            $membership_label = 'silver';
                                            break;
                                        case 1:
                                            $membership_label = 'gold';
                                            break;
                                        case 2:
                                            $membership_label = 'diamond';
                                            break;
                                        case 3:
                                            $membership_label = 'Platinum';
                                            break;
                                        case 4:
                                            $membership_label = '<b style="color:red; font-size:22px;">RISKY</b>';
                                            break;
                                    }
                                    ?>
                                <tr>
                                    <th><input type="checkbox" name="check[]" value="<?= (int) $user_id ?>"></th>
                                    <th><?= $ii ?></th>
                                    <td data-title="CID"><?= htmlspecialchars($user_rcid ?? '') ?></td>
                                    <td data-title="Name"><?= htmlspecialchars($user_name ?? '') ?><?php if (isset($user_loan) && $user_loan > 0) {
                                        echo "<span style='color:red'>#</span>";
                                    } ?><?php if (isset($users_sloan) && $users_sloan > 0) {
                                        echo "<span style='color:red'>@</span>";
                                    } ?><br>
                                    <?= $membership_label ?><br><?= $loan_count_markup ?><br><?= $limit_percentage_markup ?></td>
                                    <td data-title="Mobile"><?= htmlspecialchars($user_mobile ?? '') ?></td>
                                    <td data-title="Mobile"></td>
                                    <td data-title="Total Loans"><?= $loan_count_markup ?></td>
                                    <td data-title="Mobile"><?= htmlspecialchars($user_processed_amount ?? '') ?></td>
                                    <td data-title="Mobile"><?= !empty($user_processed_date) ? ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($user_processed_date . ' -1 day')))) / 86400) : '' ?></td>
                                    <td data-title="DPD"><?= (int) ($user_calculated_dpd ?? 0) ?></td>
                                    <td data-title="Mobile"><?= htmlspecialchars($user_total_amount ?? '') ?></td>
                                    <td data-title="Mobile">CLL<?= (int) $user_lid ?></td>
                                    <td data-title="Customer Response"><?= $concatenated_responses ?></td>
                                    <td data-title="Commitment Date"><?= $concatenated_dates ?></td>
                                    <td data-title="Updated At"><?= $concatenated_updates ?></td>
                                    <td data-title="Actions"><a class="btn btn-primary" href="profile.php?id=<?= (int) $user_id ?>" target="_blank">View</a></td>
                                </tr>
                                    <?php
                                    $ii++;
                                } ?>
                            </tbody>
                        </table>
                        <nav aria-label="Page navigation example">
                            <ul class="pagination">
                                <li class="page-item">
                                    <a class="page-link" href="<?php if ($pageno <= 1) {
                                        echo '#';
                                    } else {
                                        echo '?pageno=' . ($pageno - 1);
                                    } ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                <li class="page-item <?= $pageno == $i ? 'active' : '' ?>">
                                    <a class="page-link" href="?pageno=<?= $i ?>"><?= $i ?></a>
                                </li>
                                <?php } ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php if ($pageno >= $total_pages) {
                                        echo '#';
                                    } else {
                                        echo '?pageno=' . ($pageno + 1);
                                    } ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                        <span class="sr-only">Next</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once 'foot.php'; ?>
</body>
</html>

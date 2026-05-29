<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/loan_dpd.php';
require_once __DIR__ . '/../lib/loan_outstanding.php';

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
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>SLNO</th>
                                    <th>CID</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Total Loans</th>
                                    <th>principal loan Amt</th>
                                    <th>loan exhausted days</th>
                                    <th>DPD</th>
                                    <th>outstanding Amount</th>
                                    <th>loan ID</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $ii = 1;
                                foreach ($default_loans_paged as $b) {
                                    extract($b, EXTR_PREFIX_ALL, 'user');
                                    $loan_count_query = towquery('SELECT COUNT(*) AS total_loans FROM `loan` WHERE uid=' . (int) $user_uid);
                                    $loan_count_row = $loan_count_query ? towfetch($loan_count_query) : null;
                                    $loan_count = $loan_count_row ? (int) $loan_count_row['total_loans'] : 0;
                                    $outstanding = creditlab_loan_outstanding_amount($b);
                                    $exhausted = '';
                                    if (!empty($user_processed_date)) {
                                        $exhausted = (int) ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($user_processed_date . ' -1 day')))) / 86400);
                                    }
                                    ?>
                                <tr>
                                    <td><?= $ii++ ?></td>
                                    <td><?= htmlspecialchars($user_rcid ?? '') ?></td>
                                    <td><?= htmlspecialchars($user_name ?? '') ?></td>
                                    <td><?= htmlspecialchars($user_mobile ?? '') ?></td>
                                    <td><?= $loan_count ?></td>
                                    <td><?= htmlspecialchars($user_processed_amount ?? '') ?></td>
                                    <td><?= $exhausted ?></td>
                                    <td><?= (int) ($user_calculated_dpd ?? 0) ?></td>
                                    <td><?= number_format($outstanding, 2) ?></td>
                                    <td>CLL<?= (int) $user_lid ?></td>
                                    <td><a class="btn btn-primary" href="profile.php?id=<?= (int) $user_id ?>">View</a></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                <li class="page-item <?= $pageno == $i ? 'active' : '' ?>">
                                    <a class="page-link" href="?pageno=<?= $i ?>"><?= $i ?></a>
                                </li>
                                <?php } ?>
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

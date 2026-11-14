<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/staff_context.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'daily';
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;

        // Single source: all account-manager loans (same as admin/account_manager.php). Split by DPD so no IDs are missing.
        $daily_loans_all = [];
        $default_loans_all = [];
        $all_am_query = towquery("SELECT loan_apply.id as laid, user.id as uid FROM loan_apply INNER JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status = 'account manager'");
        $all_am_uids = [];
        while ($r = towfetch($all_am_query)) { $all_am_uids[] = $r['uid']; }
        $all_am_uids = array_unique($all_am_uids);

        foreach ($all_am_uids as $uid) {
            $q = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan_apply.follow_up_date, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi, loan_apply.days as loan_apply_days FROM user INNER JOIN loan ON loan.uid=user.id INNER JOIN loan_apply ON loan_apply.id=loan.lid WHERE user.id=$uid AND loan.status_log='account manager'");
            while ($b = towfetch($q)) {
                if (empty($b['processed_date'])) continue;
                $processed_date_str = date('Y-m-d', strtotime($b['processed_date'] . " -1 day"));
                $tday = ceil((strtotime(date('Y-m-d')) - strtotime($processed_date_str)) / (60 * 60 * 24));
                $loan_days_raw = isset($b['loan_apply_days']) ? (int)$b['loan_apply_days'] : 30;
                $loan_is_emi = isset($b['is_emi']) ? (int)$b['is_emi'] : 0;
                $loan_days = ($loan_is_emi === 1) ? 30 : $loan_days_raw;
                $dpd = $tday - $loan_days;
                $b['calculated_dpd'] = $dpd;
                if ($dpd <= 35) {
                    $daily_loans_all[] = $b;
                } else {
                    $default_loans_all[] = $b;
                }
            }
        }

        usort($daily_loans_all, function($a, $b) { return $b['calculated_dpd'] <=> $a['calculated_dpd']; });
        usort($default_loans_all, function($a, $b) { return $a['calculated_dpd'] <=> $b['calculated_dpd']; });

        $total_rows_daily = count($daily_loans_all);
        $total_pages_daily = ceil($total_rows_daily / $no_of_records_per_page);
        $daily_loans_paged = array_slice($daily_loans_all, ($active_tab == 'daily' ? $offset : 0), $no_of_records_per_page);

        $total_rows_default = count($default_loans_all);
        $total_pages_default = ceil($total_rows_default / $no_of_records_per_page);
        $default_loans_paged = array_slice($default_loans_all, ($active_tab == 'default' ? $offset : 0), $no_of_records_per_page);

        $am_profile_href_fn = static function (array $row): string {
            $id = (int) ($row['id'] ?? 0);
            $lid = (int) ($row['lid'] ?? 0);
            return 'profile.php?id=' . $id . '&tab=manager&lid=' . $lid;
        };
?>
<body>
    <?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>
        <div class="breadcome-area">
            <div class="container-fluid">
                <ul class="breadcome-menu">
                    <li><a href="index.php"><i class="fa-solid fa-house" style="font-size:12px;"></i> Home</a> <span class="bread-slash">/</span></li>
                    <li><span class="bread-blod">Account Manager</span></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="single-pro-review-area mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
                        <div class="cl-card-header">
                            <div class="cl-card-header-left">
                                <h3 class="cl-card-header-title">
                                    <i class="fa-solid fa-briefcase"></i> Account Manager Delinquency Follow-ups
                                </h3>
                                <p class="cl-card-header-desc">Active collections pipeline categorized by DPD risk thresholds</p>
                            </div>
                            <div class="cl-card-header-actions">
                                <?php if (creditlab_can_download_account_manager_data()) { ?>
                                    <a href="<?= htmlspecialchars(creditlab_downloader_href('zz.php')) ?>" class="btn btn-primary cl-card-download-btn" target="_blank" rel="noopener" style="font-weight:600; display:inline-flex; align-items:center; gap:6px; position:relative; z-index:2;">
                                        <i class="fa-solid fa-file-arrow-down"></i> Download Data
                                    </a>
                                <?php } ?>
                            </div>
                        </div>

                        <ul id="myTabedu1" class="tab-review-design" style="margin-bottom:16px !important;">
                            <li class="<?= $active_tab == 'daily' ? 'active' : '' ?>">
                                <a href="?tab=daily"><i class="fa-solid fa-calendar-day"></i> Daily Follow-ups (&le; 35 DPD) <span class="cl-badge-pill"><?= number_format($total_rows_daily) ?></span></a>
                            </li>
                            <li class="<?= $active_tab == 'default' ? 'active' : '' ?>">
                                <a href="?tab=default"><i class="fa-solid fa-triangle-exclamation"></i> Default (&gt; 35 DPD) <span class="cl-badge-pill" style="background:#fee2e2; color:#dc2626;"><?= number_format($total_rows_default) ?></span></a>
                            </li>
                        </ul>

                        <?= function_exists('creditlab_render_table_legend') ? creditlab_render_table_legend() : '' ?>

                        <?php include __DIR__ . '/../includes/account_manager_loans.php'; ?>
                    </div>
                </div>
            </div>
        </div><br>
       <?php
       include_once 'foot.php';
       ?>

</body>

</html>

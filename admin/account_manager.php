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
        
        // Single source: all account-manager loans (same as download). Split by DPD so no IDs are missing.
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
                    <li><a href="index.php"><i class="fa-solid fa-house" style="font-size:12px;"></i> Admin</a> <span class="bread-slash">/</span></li>
                    <li><a href="recovery_officer.php">Collections</a> <span class="bread-slash">/</span></li>
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

                        <?= creditlab_render_table_legend() ?>

                        <div id="myTabContent" class="tab-content custom-product-edit">
                            <div class="product-tab-list tab-pane fade <?= $active_tab == 'daily' ? 'active in' : '' ?>" id="description">
                                <div class="table-responsive">
                                    <table class="table table-bordered am-loan-table" id="am_loan_table_daily">
                                        <thead>
                                            <tr>                
                                                <th style="width:34px; text-align:center;"><input type="checkbox" id="selectAllDaily" title="Select all on this page" aria-label="Select all"></th>   
                                                <th style="width:48px;">SL</th>    
                                                <th>Borrower / CID</th>        
                                                <th>Contact</th>    
                                                <th style="text-align:right;">Outstanding / Principal</th>    
                                                <th style="text-align:center;">DPD & Tenure</th>    
                                                <th>Follow-up History</th>    
                                                <th style="text-align:right; width:110px;">Actions</th>     
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $ii = ($pageno - 1) * 50 + 1;
                                            foreach($daily_loans_paged as $b){
                                                extract($b, EXTR_PREFIX_ALL, "user");

                                                $query_result = towquery("SELECT customer_response, commitment_date, updated_at FROM `loan_acc_man` WHERE lid=".intval($user_lid)." ORDER BY id DESC LIMIT 2");
                                                $followup_rows = [];
                                                while ($row = towfetch($query_result)) {
                                                    $followup_rows[] = $row;
                                                }

                                                $loan_count_query = towquery("SELECT COUNT(*) AS total_loans FROM `loan` WHERE uid=".intval($user_uid));
                                                $loan_count_row = $loan_count_query ? towfetch($loan_count_query) : null;
                                                $loan_count = $loan_count_row ? (int)$loan_count_row['total_loans'] : 0;

                                                $membership_badge = '';
                                                switch ((int)$user_member) {
                                                    case 0: $membership_badge = '<span class="badge" style="background:#e2e8f0; color:#475569; font-size:10px;">Silver</span>'; break;
                                                    case 1: $membership_badge = '<span class="badge" style="background:#fef3c7; color:#b45309; font-size:10px;">Gold</span>'; break;
                                                    case 2: $membership_badge = '<span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:10px;">Diamond</span>'; break;
                                                    case 3: $membership_badge = '<span class="badge" style="background:#f3e8ff; color:#7e22ce; font-size:10px;">Platinum</span>'; break;
                                                    case 4: $membership_badge = '<span class="badge" style="background:#fee2e2; color:#b91c1c; font-size:10px; font-weight:700;">RISKY</span>'; break;
                                                }

                                                $principal_amt = (float)$user_processed_amount + (float)$user_p_fee + ((float)$user_p_fee * 0.18);
                                                $outstanding_amt = $principal_amt + (float)$user_service_charge + (float)$user_penality_charge;
                                                $exhausted_days = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($user_processed_date . " -1 day")))) / (60 * 60 * 24));
                                                $dpd_val = isset($user_calculated_dpd) ? (int)$user_calculated_dpd : 0;
                                            ?>
                                            <tr>
                                                <td style="text-align:center;"><input type="checkbox" class="am-row-check" name="check[]" value="<?=$user_id?>"></td>   
                                                <td style="color:var(--cl-slate-500); font-size:12px;"><?=$ii?></td> 
                                                <td data-title="Borrower">
                                                    <div style="font-weight:600; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                        <?= creditlab_dash_text($user_name ?? '') ?>
                                                        <?php if($user_loan > 0){ echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; } ?>
                                                        <?php if(isset($user_sloan) && $user_sloan > 0){ echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; } ?>
                                                        <?= $membership_badge ?>
                                                    </div>
                                                    <div style="margin-top:3px; font-size:11.5px; color:var(--cl-slate-500);">
                                                        <span style="font-family:monospace; color:var(--cl-primary); font-weight:600;"><?= creditlab_dash_text($user_rcid ?? '') ?></span>
                                                        <span style="color:var(--cl-slate-300); margin:0 3px;">·</span>
                                                        <span style="font-family:monospace; color:var(--cl-slate-600);">CLL<?=$user_lid?></span>
                                                        <span style="color:var(--cl-slate-300); margin:0 3px;">·</span>
                                                        <span style="font-size:11px;"><?= $loan_count ?> loan<?= $loan_count !== 1 ? 's' : '' ?></span>
                                                    </div>
                                                </td>
                                                <td data-title="Contact" style="white-space:nowrap;">
                                                    <div style="font-family:monospace; font-size:12.5px; font-weight:500; color:var(--cl-slate-800);">
                                                        <?= creditlab_dash_text($user_mobile ?? '') ?>
                                                    </div>
                                                    <?php if(!empty($user_altmobile)): ?>
                                                        <div style="margin-top:2px; font-family:monospace; font-size:11px; color:var(--cl-slate-500);">
                                                            <span style="color:var(--cl-slate-400);">Alt:</span> <?= htmlspecialchars($user_altmobile) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-title="Financials" style="text-align:right; white-space:nowrap;">
                                                    <div style="font-weight:700; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                        <span style="font-size:10px; font-weight:600; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Outst</span>
                                                        <?= creditlab_currency($outstanding_amt) ?>
                                                    </div>
                                                    <div style="margin-top:3px; font-size:12px; line-height:1.2; color:var(--cl-slate-600);">
                                                        <span style="font-size:10px; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Principal</span>
                                                        <?= creditlab_currency($principal_amt) ?>
                                                    </div>
                                                </td>
                                                <td data-title="DPD & Tenure" style="text-align:center; white-space:nowrap;">
                                                    <div>
                                                        <span class="badge" style="background:<?= $dpd_val > 0 ? '#fee2e2' : '#f1f5f9' ?>; color:<?= $dpd_val > 0 ? '#b91c1c' : '#475569' ?>; font-weight:700; font-size:11px; padding:3px 8px; border-radius:10px;">
                                                            DPD: <?= $dpd_val ?> d
                                                        </span>
                                                    </div>
                                                    <div style="margin-top:3px; font-size:11px; color:var(--cl-slate-500);">
                                                        <?= $exhausted_days ?> d exhausted
                                                    </div>
                                                </td>
                                                <td data-title="Follow-up History">
                                                    <?php if (!empty($followup_rows)): ?>
                                                        <?php foreach ($followup_rows as $f): ?>
                                                            <div style="font-size:12px; line-height:1.3; margin-bottom:4px; color:var(--cl-slate-800);">
                                                                <span style="font-weight:600; color:var(--cl-slate-700);"><?= htmlspecialchars($f['customer_response'] ?: 'No response') ?></span>
                                                                <?php if (!empty($f['commitment_date'])): ?>
                                                                    <span style="color:#d97706; font-size:11px; margin-left:4px;">· Commit: <?= htmlspecialchars($f['commitment_date']) ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span style="color:var(--cl-slate-400); font-size:12px;">No follow-up logged</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-title="Actions" style="text-align:right; white-space:nowrap;">
                                                    <a class="btn btn-xs btn-primary" href="profile.php?id=<?=$user_id?>" target="_blank" style="padding:4px 10px;">
                                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php $ii++; } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div style="padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; border-top:1px solid var(--cl-slate-100);">
                                    <div style="font-size:12.5px; color:var(--cl-slate-500);">
                                        Showing page <strong><?= $pageno ?></strong> of <strong><?= $total_pages_daily ?></strong> (<?= number_format($total_rows_daily) ?> records)
                                    </div>
                                    <?= creditlab_render_pagination($pageno, $total_pages_daily, '?tab=daily&pageno=') ?>
                                </div>
                            </div>
                            <div class="product-tab-list tab-pane fade <?= $active_tab == 'default' ? 'active in' : '' ?>" id="INFORMATION">
                                <div class="table-responsive">
                                    <table class="table table-bordered am-loan-table" id="am_loan_table_default">
                                        <thead>
                                            <tr>                
                                                <th style="width:34px; text-align:center;"><input type="checkbox" id="selectAllDefault" title="Select all on this page" aria-label="Select all"></th>   
                                                <th style="width:48px;">SL</th>    
                                                <th>Borrower / CID</th>        
                                                <th>Contact</th>    
                                                <th style="text-align:right;">Outstanding / Principal</th>    
                                                <th style="text-align:center;">DPD & Tenure</th>    
                                                <th>Follow-up History</th>    
                                                <th style="text-align:right; width:110px;">Actions</th>     
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $ii = ($pageno - 1) * 50 + 1;
                                            foreach($default_loans_paged as $b){
                                                extract($b, EXTR_PREFIX_ALL, "user");

                                                $query_result = towquery("SELECT customer_response, commitment_date, updated_at FROM `loan_acc_man` WHERE lid=".intval($user_lid)." ORDER BY id DESC LIMIT 2");
                                                $followup_rows = [];
                                                while ($row = towfetch($query_result)) {
                                                    $followup_rows[] = $row;
                                                }

                                                $loan_count_query = towquery("SELECT COUNT(*) AS total_loans FROM `loan` WHERE uid=".intval($user_uid));
                                                $loan_count_row = $loan_count_query ? towfetch($loan_count_query) : null;
                                                $loan_count = $loan_count_row ? (int)$loan_count_row['total_loans'] : 0;

                                                $membership_badge = '';
                                                switch ((int)$user_member) {
                                                    case 0: $membership_badge = '<span class="badge" style="background:#e2e8f0; color:#475569; font-size:10px;">Silver</span>'; break;
                                                    case 1: $membership_badge = '<span class="badge" style="background:#fef3c7; color:#b45309; font-size:10px;">Gold</span>'; break;
                                                    case 2: $membership_badge = '<span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:10px;">Diamond</span>'; break;
                                                    case 3: $membership_badge = '<span class="badge" style="background:#f3e8ff; color:#7e22ce; font-size:10px;">Platinum</span>'; break;
                                                    case 4: $membership_badge = '<span class="badge" style="background:#fee2e2; color:#b91c1c; font-size:10px; font-weight:700;">RISKY</span>'; break;
                                                }

                                                $principal_amt = (float)$user_processed_amount;
                                                $outstanding_amt = (float)$user_total_amount;
                                                $exhausted_days = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($user_processed_date . " -1 day")))) / (60 * 60 * 24));
                                                $dpd_val = isset($user_calculated_dpd) ? (int)$user_calculated_dpd : 0;
                                            ?>
                                            <tr>
                                                <td style="text-align:center;"><input type="checkbox" class="am-row-check" name="check[]" value="<?=$user_id?>"></td>   
                                                <td style="color:var(--cl-slate-500); font-size:12px;"><?=$ii?></td> 
                                                <td data-title="Borrower">
                                                    <div style="font-weight:600; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                        <?= creditlab_dash_text($user_name ?? '') ?>
                                                        <?php if($user_loan > 0){ echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; } ?>
                                                        <?php if(isset($user_sloan) && $user_sloan > 0){ echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; } ?>
                                                        <?= $membership_badge ?>
                                                    </div>
                                                    <div style="margin-top:3px; font-size:11.5px; color:var(--cl-slate-500);">
                                                        <span style="font-family:monospace; color:var(--cl-primary); font-weight:600;"><?= creditlab_dash_text($user_rcid ?? '') ?></span>
                                                        <span style="color:var(--cl-slate-300); margin:0 3px;">·</span>
                                                        <span style="font-family:monospace; color:var(--cl-slate-600);">CLL<?=$user_lid?></span>
                                                        <span style="color:var(--cl-slate-300); margin:0 3px;">·</span>
                                                        <span style="font-size:11px;"><?= $loan_count ?> loan<?= $loan_count !== 1 ? 's' : '' ?></span>
                                                    </div>
                                                </td>
                                                <td data-title="Contact" style="white-space:nowrap;">
                                                    <div style="font-family:monospace; font-size:12.5px; font-weight:500; color:var(--cl-slate-800);">
                                                        <?= creditlab_dash_text($user_mobile ?? '') ?>
                                                    </div>
                                                </td>
                                                <td data-title="Financials" style="text-align:right; white-space:nowrap;">
                                                    <div style="font-weight:700; color:var(--cl-slate-900); font-size:13.5px; line-height:1.25;">
                                                        <span style="font-size:10px; font-weight:600; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Outst</span>
                                                        <?= creditlab_currency($outstanding_amt) ?>
                                                    </div>
                                                    <div style="margin-top:3px; font-size:12px; line-height:1.2; color:var(--cl-slate-600);">
                                                        <span style="font-size:10px; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Principal</span>
                                                        <?= creditlab_currency($principal_amt) ?>
                                                    </div>
                                                </td>
                                                <td data-title="DPD & Tenure" style="text-align:center; white-space:nowrap;">
                                                    <div>
                                                        <span class="badge" style="background:#fee2e2; color:#b91c1c; font-weight:700; font-size:11px; padding:3px 8px; border-radius:10px;">
                                                            DPD: <?= $dpd_val ?> d
                                                        </span>
                                                    </div>
                                                    <div style="margin-top:3px; font-size:11px; color:var(--cl-slate-500);">
                                                        <?= $exhausted_days ?> d exhausted
                                                    </div>
                                                </td>
                                                <td data-title="Follow-up History">
                                                    <?php if (!empty($followup_rows)): ?>
                                                        <?php foreach ($followup_rows as $f): ?>
                                                            <div style="font-size:12px; line-height:1.3; margin-bottom:4px; color:var(--cl-slate-800);">
                                                                <span style="font-weight:600; color:var(--cl-slate-700);"><?= htmlspecialchars($f['customer_response'] ?: 'No response') ?></span>
                                                                <?php if (!empty($f['commitment_date'])): ?>
                                                                    <span style="color:#d97706; font-size:11px; margin-left:4px;">· Commit: <?= htmlspecialchars($f['commitment_date']) ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span style="color:var(--cl-slate-400); font-size:12px;">No follow-up logged</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-title="Actions" style="text-align:right; white-space:nowrap;">
                                                    <a class="btn btn-xs btn-primary" href="profile.php?id=<?=$user_id?>" target="_blank" style="padding:4px 10px;">
                                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View Profile
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php $ii++; } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div style="padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; border-top:1px solid var(--cl-slate-100);">
                                    <div style="font-size:12.5px; color:var(--cl-slate-500);">
                                        Showing page <strong><?= $pageno ?></strong> of <strong><?= $total_pages_default ?></strong> (<?= number_format($total_rows_default) ?> records)
                                    </div>
                                    <?= creditlab_render_pagination($pageno, $total_pages_default, '?tab=default&pageno=') ?>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><br>
       <?php
       include_once 'foot.php';
       ?>
<script>
(function () {
    function wireSelectAll(masterId, tableId) {
        var master = document.getElementById(masterId);
        var table = document.getElementById(tableId);
        if (!master || !table) {
            return;
        }
        master.addEventListener('change', function () {
            var checked = master.checked;
            table.querySelectorAll('tbody input.am-row-check').forEach(function (cb) {
                cb.checked = checked;
            });
        });
    }
    wireSelectAll('selectAllDaily', 'am_loan_table_daily');
    wireSelectAll('selectAllDefault', 'am_loan_table_default');
})();
</script>

</body>

</html>
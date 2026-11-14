<?php
/**
 * Shared Account Manager Daily / Default loan tables.
 *
 * Expected variables set by the caller:
 * - $active_tab, $pageno
 * - $daily_loans_paged, $default_loans_paged
 * - $total_rows_daily, $total_pages_daily
 * - $total_rows_default, $total_pages_default
 * - $am_profile_href_fn (callable): function(array $row): string — profile URL for Actions
 */
if (!isset($am_profile_href_fn) || !is_callable($am_profile_href_fn)) {
    $am_profile_href_fn = static function (array $row): string {
        $id = (int) ($row['id'] ?? 0);
        return 'profile.php?id=' . $id;
    };
}

/**
 * @param array $b loan+user row
 * @param string $mode daily|default
 * @return array{followup_rows:array,loan_count:int,membership_badge:string,principal_amt:float,outstanding_amt:float,exhausted_days:int,dpd_val:int,latest_commit:?string,latest_updated:?string}
 */
if (!function_exists('creditlab_am_prepare_row')) {
function creditlab_am_prepare_row(array $b, string $mode = 'daily'): array
{
    extract($b, EXTR_PREFIX_ALL, 'user');

    $query_result = towquery('SELECT customer_response, commitment_date, updated_at FROM `loan_acc_man` WHERE lid=' . intval($user_lid) . ' ORDER BY id DESC LIMIT 2');
    $followup_rows = [];
    while ($row = towfetch($query_result)) {
        $followup_rows[] = $row;
    }

    $latest_commit = null;
    $latest_updated = null;
    if (!empty($followup_rows[0])) {
        $latest_commit = !empty($followup_rows[0]['commitment_date']) ? $followup_rows[0]['commitment_date'] : null;
        $latest_updated = !empty($followup_rows[0]['updated_at']) ? $followup_rows[0]['updated_at'] : null;
    }

    $loan_count_query = towquery('SELECT COUNT(*) AS total_loans FROM `loan` WHERE uid=' . intval($user_uid));
    $loan_count_row = $loan_count_query ? towfetch($loan_count_query) : null;
    $loan_count = $loan_count_row ? (int) $loan_count_row['total_loans'] : 0;

    $membership_badge = '';
    switch ((int) $user_member) {
        case 0: $membership_badge = '<span class="badge" style="background:#e2e8f0; color:#475569; font-size:10px;">Silver</span>'; break;
        case 1: $membership_badge = '<span class="badge" style="background:#fef3c7; color:#b45309; font-size:10px;">Gold</span>'; break;
        case 2: $membership_badge = '<span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:10px;">Diamond</span>'; break;
        case 3: $membership_badge = '<span class="badge" style="background:#f3e8ff; color:#7e22ce; font-size:10px;">Platinum</span>'; break;
        case 4: $membership_badge = '<span class="badge" style="background:#fee2e2; color:#b91c1c; font-size:10px; font-weight:700;">RISKY</span>'; break;
    }

    if ($mode === 'default') {
        $principal_amt = (float) $user_processed_amount;
        $outstanding_amt = (float) $user_total_amount;
    } else {
        $principal_amt = (float) $user_processed_amount + (float) $user_p_fee + ((float) $user_p_fee * 0.18);
        $outstanding_amt = $principal_amt + (float) $user_service_charge + (float) $user_penality_charge;
    }

    $exhausted_days = ceil((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($user_processed_date . ' -1 day')))) / (60 * 60 * 24));
    $dpd_val = isset($user_calculated_dpd) ? (int) $user_calculated_dpd : 0;

    return compact('followup_rows', 'loan_count', 'membership_badge', 'principal_amt', 'outstanding_amt', 'exhausted_days', 'dpd_val', 'latest_commit', 'latest_updated');
}
}

if (!function_exists('creditlab_am_format_date')) {
function creditlab_am_format_date(?string $value): string
{
    if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '—';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return htmlspecialchars($value);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return date('d M Y', $ts);
    }
    return date('d M Y, H:i', $ts);
}
}
?>
                        <div id="myTabContent" class="tab-content custom-product-edit">
                            <div class="product-tab-list tab-pane fade <?= $active_tab == 'daily' ? 'active in' : '' ?>" id="description">
                                <div class="table-responsive am-loan-table-wrap">
                                    <table class="table table-bordered am-loan-table" id="am_loan_table_daily">
                                        <thead>
                                            <tr>
                                                <th style="width:34px; text-align:center;"><input type="checkbox" id="selectAllDaily" title="Select all on this page" aria-label="Select all"></th>
                                                <th style="width:40px;">SL</th>
                                                <th>Borrower / CID</th>
                                                <th>Contact</th>
                                                <th style="text-align:right;">Outstanding / Principal</th>
                                                <th style="text-align:center;">DPD &amp; Tenure</th>
                                                <th>Follow-up</th>
                                                <th style="text-align:center;">Commitment date</th>
                                                <th style="text-align:center;">Updated date</th>
                                                <th style="text-align:right; width:96px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $ii = ($pageno - 1) * 50 + 1;
                                            foreach ($daily_loans_paged as $b) {
                                                extract($b, EXTR_PREFIX_ALL, 'user');
                                                $prep = creditlab_am_prepare_row($b, 'daily');
                                                extract($prep);
                                                $profile_href = call_user_func($am_profile_href_fn, $b);
                                            ?>
                                            <tr>
                                                <td style="text-align:center;"><input type="checkbox" class="am-row-check" name="check[]" value="<?= (int) $user_id ?>"></td>
                                                <td style="color:var(--cl-slate-500); font-size:12px;"><?= $ii ?></td>
                                                <td data-title="Borrower">
                                                    <div style="font-weight:600; color:var(--cl-slate-900); font-size:12.5px; line-height:1.25;">
                                                        <?= creditlab_dash_text($user_name ?? '') ?>
                                                        <?php if ($user_loan > 0) { echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; } ?>
                                                        <?php if (isset($user_sloan) && $user_sloan > 0) { echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; } ?>
                                                        <?= $membership_badge ?>
                                                    </div>
                                                    <div style="margin-top:2px; font-size:11px; color:var(--cl-slate-500);">
                                                        <span style="font-family:monospace; color:var(--cl-primary); font-weight:600;"><?= creditlab_dash_text($user_rcid ?? '') ?></span>
                                                        <span style="color:var(--cl-slate-300); margin:0 2px;">·</span>
                                                        <span style="font-family:monospace; color:var(--cl-slate-600);">CLL<?= (int) $user_lid ?></span>
                                                        <span style="color:var(--cl-slate-300); margin:0 2px;">·</span>
                                                        <span><?= (int) $loan_count ?> loan<?= $loan_count !== 1 ? 's' : '' ?></span>
                                                    </div>
                                                </td>
                                                <td data-title="Contact">
                                                    <div style="font-family:monospace; font-size:12px; font-weight:500; color:var(--cl-slate-800);">
                                                        <?= creditlab_dash_text($user_mobile ?? '') ?>
                                                    </div>
                                                    <?php if (!empty($user_altmobile)): ?>
                                                        <div style="margin-top:2px; font-family:monospace; font-size:10.5px; color:var(--cl-slate-500);">
                                                            <span style="color:var(--cl-slate-400);">Alt:</span> <?= htmlspecialchars($user_altmobile) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-title="Financials" style="text-align:right;">
                                                    <div style="font-weight:700; color:var(--cl-slate-900); font-size:12.5px; line-height:1.25;">
                                                        <span style="font-size:9.5px; font-weight:600; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Outst</span>
                                                        <?= creditlab_currency($outstanding_amt) ?>
                                                    </div>
                                                    <div style="margin-top:2px; font-size:11px; line-height:1.2; color:var(--cl-slate-600);">
                                                        <span style="font-size:9.5px; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Principal</span>
                                                        <?= creditlab_currency($principal_amt) ?>
                                                    </div>
                                                </td>
                                                <td data-title="DPD & Tenure" style="text-align:center;">
                                                    <div>
                                                        <span class="badge" style="background:<?= $dpd_val > 0 ? '#fee2e2' : '#f1f5f9' ?>; color:<?= $dpd_val > 0 ? '#b91c1c' : '#475569' ?>; font-weight:700; font-size:10.5px; padding:2px 7px; border-radius:10px;">
                                                            DPD: <?= $dpd_val ?> d
                                                        </span>
                                                    </div>
                                                    <div style="margin-top:2px; font-size:10.5px; color:var(--cl-slate-500);">
                                                        <?= (int) $exhausted_days ?> d exhausted
                                                    </div>
                                                </td>
                                                <td data-title="Follow-up History" class="am-followup-cell">
                                                    <?php if (!empty($followup_rows)): ?>
                                                        <?php foreach ($followup_rows as $f): ?>
                                                            <div style="font-size:11.5px; line-height:1.3; margin-bottom:3px; color:var(--cl-slate-800); word-break:break-word;">
                                                                <span style="font-weight:600; color:var(--cl-slate-700);"><?= htmlspecialchars($f['customer_response'] ?: 'No response') ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span style="color:var(--cl-slate-400); font-size:11.5px;">No follow-up logged</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-title="Commitment date" style="text-align:center; font-size:11.5px; color:var(--cl-slate-700);">
                                                    <?= creditlab_am_format_date($latest_commit) ?>
                                                </td>
                                                <td data-title="Updated date" style="text-align:center; font-size:11.5px; color:var(--cl-slate-700);">
                                                    <?= creditlab_am_format_date($latest_updated) ?>
                                                </td>
                                                <td data-title="Actions" style="text-align:right;">
                                                    <a class="btn btn-xs btn-primary" href="<?= htmlspecialchars($profile_href) ?>" target="_blank" style="padding:3px 8px;">
                                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php $ii++; } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div style="padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; border-top:1px solid var(--cl-slate-100);">
                                    <div style="font-size:12.5px; color:var(--cl-slate-500);">
                                        Showing page <strong><?= (int) $pageno ?></strong> of <strong><?= (int) $total_pages_daily ?></strong> (<?= number_format($total_rows_daily) ?> records)
                                    </div>
                                    <?= creditlab_render_pagination($pageno, $total_pages_daily, '?tab=daily&pageno=') ?>
                                </div>
                            </div>
                            <div class="product-tab-list tab-pane fade <?= $active_tab == 'default' ? 'active in' : '' ?>" id="INFORMATION">
                                <div class="table-responsive am-loan-table-wrap">
                                    <table class="table table-bordered am-loan-table" id="am_loan_table_default">
                                        <thead>
                                            <tr>
                                                <th style="width:34px; text-align:center;"><input type="checkbox" id="selectAllDefault" title="Select all on this page" aria-label="Select all"></th>
                                                <th style="width:40px;">SL</th>
                                                <th>Borrower / CID</th>
                                                <th>Contact</th>
                                                <th style="text-align:right;">Outstanding / Principal</th>
                                                <th style="text-align:center;">DPD &amp; Tenure</th>
                                                <th>Follow-up</th>
                                                <th style="text-align:center;">Commitment date</th>
                                                <th style="text-align:center;">Updated date</th>
                                                <th style="text-align:right; width:96px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $ii = ($pageno - 1) * 50 + 1;
                                            foreach ($default_loans_paged as $b) {
                                                extract($b, EXTR_PREFIX_ALL, 'user');
                                                $prep = creditlab_am_prepare_row($b, 'default');
                                                extract($prep);
                                                $profile_href = call_user_func($am_profile_href_fn, $b);
                                            ?>
                                            <tr>
                                                <td style="text-align:center;"><input type="checkbox" class="am-row-check" name="check[]" value="<?= (int) $user_id ?>"></td>
                                                <td style="color:var(--cl-slate-500); font-size:12px;"><?= $ii ?></td>
                                                <td data-title="Borrower">
                                                    <div style="font-weight:600; color:var(--cl-slate-900); font-size:12.5px; line-height:1.25;">
                                                        <?= creditlab_dash_text($user_name ?? '') ?>
                                                        <?php if ($user_loan > 0) { echo '<span class="cl-symbol-loan" title="Active Loan (#)">#</span>'; } ?>
                                                        <?php if (isset($user_sloan) && $user_sloan > 0) { echo '<span class="cl-symbol-sloan" title="Repeat Customer (@)">@</span>'; } ?>
                                                        <?= $membership_badge ?>
                                                    </div>
                                                    <div style="margin-top:2px; font-size:11px; color:var(--cl-slate-500);">
                                                        <span style="font-family:monospace; color:var(--cl-primary); font-weight:600;"><?= creditlab_dash_text($user_rcid ?? '') ?></span>
                                                        <span style="color:var(--cl-slate-300); margin:0 2px;">·</span>
                                                        <span style="font-family:monospace; color:var(--cl-slate-600);">CLL<?= (int) $user_lid ?></span>
                                                        <span style="color:var(--cl-slate-300); margin:0 2px;">·</span>
                                                        <span><?= (int) $loan_count ?> loan<?= $loan_count !== 1 ? 's' : '' ?></span>
                                                    </div>
                                                </td>
                                                <td data-title="Contact">
                                                    <div style="font-family:monospace; font-size:12px; font-weight:500; color:var(--cl-slate-800);">
                                                        <?= creditlab_dash_text($user_mobile ?? '') ?>
                                                    </div>
                                                </td>
                                                <td data-title="Financials" style="text-align:right;">
                                                    <div style="font-weight:700; color:var(--cl-slate-900); font-size:12.5px; line-height:1.25;">
                                                        <span style="font-size:9.5px; font-weight:600; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Outst</span>
                                                        <?= creditlab_currency($outstanding_amt) ?>
                                                    </div>
                                                    <div style="margin-top:2px; font-size:11px; line-height:1.2; color:var(--cl-slate-600);">
                                                        <span style="font-size:9.5px; color:var(--cl-slate-400); text-transform:uppercase; margin-right:2px;">Principal</span>
                                                        <?= creditlab_currency($principal_amt) ?>
                                                    </div>
                                                </td>
                                                <td data-title="DPD & Tenure" style="text-align:center;">
                                                    <div>
                                                        <span class="badge" style="background:#fee2e2; color:#b91c1c; font-weight:700; font-size:10.5px; padding:2px 7px; border-radius:10px;">
                                                            DPD: <?= $dpd_val ?> d
                                                        </span>
                                                    </div>
                                                    <div style="margin-top:2px; font-size:10.5px; color:var(--cl-slate-500);">
                                                        <?= (int) $exhausted_days ?> d exhausted
                                                    </div>
                                                </td>
                                                <td data-title="Follow-up History" class="am-followup-cell">
                                                    <?php if (!empty($followup_rows)): ?>
                                                        <?php foreach ($followup_rows as $f): ?>
                                                            <div style="font-size:11.5px; line-height:1.3; margin-bottom:3px; color:var(--cl-slate-800); word-break:break-word;">
                                                                <span style="font-weight:600; color:var(--cl-slate-700);"><?= htmlspecialchars($f['customer_response'] ?: 'No response') ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span style="color:var(--cl-slate-400); font-size:11.5px;">No follow-up logged</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-title="Commitment date" style="text-align:center; font-size:11.5px; color:var(--cl-slate-700);">
                                                    <?= creditlab_am_format_date($latest_commit) ?>
                                                </td>
                                                <td data-title="Updated date" style="text-align:center; font-size:11.5px; color:var(--cl-slate-700);">
                                                    <?= creditlab_am_format_date($latest_updated) ?>
                                                </td>
                                                <td data-title="Actions" style="text-align:right;">
                                                    <a class="btn btn-xs btn-primary" href="<?= htmlspecialchars($profile_href) ?>" target="_blank" style="padding:3px 8px;">
                                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php $ii++; } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div style="padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; border-top:1px solid var(--cl-slate-100);">
                                    <div style="font-size:12.5px; color:var(--cl-slate-500);">
                                        Showing page <strong><?= (int) $pageno ?></strong> of <strong><?= (int) $total_pages_default ?></strong> (<?= number_format($total_rows_default) ?> records)
                                    </div>
                                    <?= creditlab_render_pagination($pageno, $total_pages_default, '?tab=default&pageno=') ?>
                                </div>
                            </div>
                        </div>
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

<?php
/**
 * Shared DPD calculation for account manager / agency admin lists.
 */

function creditlab_loan_days_from_row(array $loanRow): int
{
    $loan_days_raw = isset($loanRow['loan_apply_days']) ? (int) $loanRow['loan_apply_days'] : 30;
    $loan_is_emi = isset($loanRow['is_emi']) ? (int) $loanRow['is_emi'] : 0;
    return ($loan_is_emi === 1) ? 30 : ($loan_days_raw > 0 ? $loan_days_raw : 30);
}

function creditlab_calculate_dpd(array $loanRow): int
{
    if (empty($loanRow['processed_date'])) {
        return 0;
    }
    $processed_date_str = date('Y-m-d', strtotime($loanRow['processed_date'] . ' -1 day'));
    $tday = (int) ceil((strtotime(date('Y-m-d')) - strtotime($processed_date_str)) / 86400);
    $loan_days = creditlab_loan_days_from_row($loanRow);
    return $tday - $loan_days;
}

function creditlab_account_manager_loan_rows(): array
{
    $daily_loans_all = [];
    $default_loans_all = [];
    $all_am_query = towquery("SELECT loan_apply.id as laid, user.id as uid FROM loan_apply INNER JOIN user ON loan_apply.uid = user.id WHERE loan_apply.status = 'account manager'");
    $all_am_uids = [];
    if ($all_am_query) {
        while ($r = towfetch($all_am_query)) {
            $all_am_uids[] = (int) $r['uid'];
        }
    }
    $all_am_uids = array_unique($all_am_uids);

    foreach ($all_am_uids as $uid) {
        $uid = (int) $uid;
        $q = towquery("SELECT user.*, loan.lid, loan.uid, loan.processed_date, loan.processed_amount, loan.exhausted_period, loan.p_fee, loan.service_charge, loan.penality_charge, loan.total_amount, loan.status_log, loan.action, loan.follow_up_mess, loan_apply.follow_up_date, loan.advance_amount, loan.total_time, loan.femi, loan.semi, loan.is_emi, loan_apply.days as loan_apply_days FROM user INNER JOIN loan ON loan.uid=user.id INNER JOIN loan_apply ON loan_apply.id=loan.lid WHERE user.id=$uid AND loan.status_log='account manager'");
        if (!$q) {
            continue;
        }
        while ($b = towfetch($q)) {
            if (empty($b['processed_date'])) {
                continue;
            }
            $dpd = creditlab_calculate_dpd($b);
            $b['calculated_dpd'] = $dpd;
            if ($dpd <= 35) {
                $daily_loans_all[] = $b;
            } else {
                $default_loans_all[] = $b;
            }
        }
    }

    usort($daily_loans_all, function ($a, $b) {
        return $b['calculated_dpd'] <=> $a['calculated_dpd'];
    });
    usort($default_loans_all, function ($a, $b) {
        return $a['calculated_dpd'] <=> $b['calculated_dpd'];
    });

    return [
        'daily' => $daily_loans_all,
        'default' => $default_loans_all,
    ];
}

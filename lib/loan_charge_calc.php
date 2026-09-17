<?php
/**
 * Shared loan service charge and penalty (cron, E-NACH, admin totals).
 */

/**
 * Repayment base: disbursed amount + processing fee + 18% GST on fee.
 * Same pack the dashboard / autopay / cron use (not net disbursal alone).
 */
function creditlab_loan_repayment_base(float $processedAmount, float $pFee): float
{
    return $processedAmount + $pFee + ($pFee * 0.18);
}

/**
 * Tenure in days: matches due date on profile (loan.total_time) when set.
 */
function creditlab_loan_tenure_days(array $loanRow, int $loanApplyDays = 30): int
{
    $isEmi = isset($loanRow['is_emi']) ? (int) $loanRow['is_emi'] : 0;
    if ($isEmi === 1) {
        return 30;
    }
    $totalTime = isset($loanRow['total_time']) ? (int) $loanRow['total_time'] : 0;
    if ($totalTime > 0) {
        return $totalTime;
    }
    return $loanApplyDays > 0 ? $loanApplyDays : 30;
}

/**
 * @return array{exhausted_period:int,service_charge:float,penality_charge:float,loan_tenure:int,dpd:int}
 */
function creditlab_calculate_loan_charges(
    string $processedDate,
    float $processedAmount,
    float $pFee,
    $interestPercentage,
    int $loanApplyDays,
    array $loanRow = []
): array {
    $stop_date = date_create($processedDate);
    $sa = date_create(date('Y-m-d 23:59:59'));
    $aa = date_diff($stop_date, $sa);
    $tday = (int) $aa->format('%a');
    $exhausted_period = $tday + 1;

    $t = creditlab_loan_repayment_base($processedAmount, $pFee);
    $service_charge = 0.0;
    $days = $exhausted_period;

    $loan_tenure = creditlab_loan_tenure_days($loanRow, $loanApplyDays);
    $interest_percentage = $interestPercentage;

    if ($interest_percentage == 1) {
        if ($days >= 3) {
            $fee = $t * 3 / 100 * 0;
            $days = $days - 3;
            $service_charge += $fee;
        } else {
            $fee = $t * $days / 100 * 0;
            $days = 0;
            $service_charge += $fee;
        }
        if ($days >= 7) {
            $fee = $t * 7 / 100 * 0.1;
            $days = $days - 7;
            $service_charge += $fee;
        } else {
            $fee = $t * $days / 100 * 0.1;
            $days = 0;
            $service_charge += $fee;
        }
        if ($days >= 20) {
            $fee = $t * 20 / 100 * 0.115;
            $days = $days - 20;
            $service_charge += $fee;
        } else {
            $fee = $t * $days / 100 * 0.115;
            $days = 0;
            $service_charge += $fee;
        }
        if ($days >= 1) {
            $fee = $t * $days / 100 * 0.1;
            $service_charge += $fee;
        }
    } else {
        $fee = $t * $exhausted_period / 100 * (float) $interest_percentage;
        $service_charge += $fee;
    }

    // DPD = calendar days past tenure (day 46 with 45-day tenure → DPD 1 on first overdue day)
    $dpd = $exhausted_period - $loan_tenure;
    $penality = 0.0;
    if ($dpd > 0) {
        $penalitydays = $dpd - 1;
        $penality = (($t) / 100) * 4;
        if ($penalitydays > 0) {
            $atnp = ((($t) / 100) * 0.2) * $penalitydays;
            $penality = $penality + $atnp;
        }
    }
    // Penalty is a late fee; GST is not added on penalty (only on processing fee).

    return [
        'exhausted_period' => $exhausted_period,
        'service_charge' => $service_charge,
        'penality_charge' => $penality,
        'loan_tenure' => $loan_tenure,
        'dpd' => $dpd,
    ];
}

/**
 * Interest on principal for N days (KFS slabs when rate is 1, else % per day).
 */
function creditlab_interest_on_principal_for_days(float $principal, $interestPercentage, int $days): float
{
    if ($days <= 0 || $principal <= 0) {
        return 0.0;
    }

    $service_charge = 0.0;
    if ((float) $interestPercentage == 1.0) {
        $remaining = $days;
        if ($remaining >= 3) {
            $remaining -= 3;
        } else {
            $remaining = 0;
        }
        if ($remaining >= 7) {
            $service_charge += $principal * 7 / 100 * 0.1;
            $remaining -= 7;
        } else {
            $service_charge += $principal * $remaining / 100 * 0.1;
            $remaining = 0;
        }
        if ($remaining >= 20) {
            $service_charge += $principal * 20 / 100 * 0.115;
            $remaining -= 20;
        } else {
            $service_charge += $principal * $remaining / 100 * 0.115;
            $remaining = 0;
        }
        if ($remaining >= 1) {
            $service_charge += $principal * $remaining / 100 * 0.1;
        }
        return $service_charge;
    }

    return $principal * $days / 100 * (float) $interestPercentage;
}

/**
 * Daily overdue interest rate on principal (KFS: 0.1%/day for the 1% product).
 */
function creditlab_overdue_daily_interest_rate($interestPercentage): float
{
    if ((float) $interestPercentage == 1.0) {
        return 0.001;
    }
    return ((float) $interestPercentage) / 100.0;
}

/**
 * eNACH presentment: repayment base (incl. PF GST) + interest to due date + overdue
 * interest + penalty (no GST on penalty). Presentment DPD = calendar DPD + 1.
 *
 * @return array{
 *   principal:float,processed_amount:float,p_fee:float,p_fee_gst:float,
 *   kfs_interest:float,calendar_dpd:int,presentment_dpd:int,
 *   overdue_interest:float,penalty:float,penalty_gst:float,penalty_with_gst:float,total:float,
 *   loan_tenure:int
 * }
 */
function creditlab_enach_presentment_breakdown(array $loan, array $loan_apply, ?string $asOfDate = null): array
{
    $processedAmount = (float) ($loan['processed_amount'] ?? 0);
    $pFee = (float) ($loan['p_fee'] ?? $loan_apply['processing_fees'] ?? 0);
    $pFeeGst = $pFee * 0.18;
    $principal = creditlab_loan_repayment_base($processedAmount, $pFee);
    $interestPercentage = $loan_apply['interest_percentage'] ?? 1;
    $loanApplyDays = isset($loan_apply['days']) ? (int) $loan_apply['days'] : 30;
    $loan_tenure = creditlab_loan_tenure_days($loan, $loanApplyDays);

    $processedDate = (string) ($loan['processed_date'] ?? date('Y-m-d'));
    $asOf = $asOfDate ? date('Y-m-d', strtotime($asOfDate)) : date('Y-m-d');
    $stop_date = date_create($processedDate);
    $sa = date_create($asOf . ' 23:59:59');
    $tday = 0;
    if ($stop_date instanceof DateTimeInterface && $sa instanceof DateTimeInterface) {
        $tday = (int) date_diff($stop_date, $sa)->format('%a');
    }

    $calendar_dpd = $tday - $loan_tenure;
    if ($calendar_dpd < 0) {
        $calendar_dpd = 0;
    }
    $presentment_dpd = $calendar_dpd > 0 ? $calendar_dpd + 1 : 0;

    $kfs_interest = creditlab_interest_on_principal_for_days($principal, $interestPercentage, $loan_tenure);

    $daily_overdue = creditlab_overdue_daily_interest_rate($interestPercentage);
    $overdue_interest = $principal * $daily_overdue * $presentment_dpd;

    $penalty = 0.0;
    if ($presentment_dpd >= 1) {
        $penalty = $principal * 0.04;
        if ($presentment_dpd >= 2) {
            $penalty += $principal * 0.002 * ($presentment_dpd - 1);
        }
    }
    $penalty_gst = 0.0;

    $total = $principal + $kfs_interest + $penalty + $overdue_interest;

    return [
        'principal' => $principal,
        'processed_amount' => $processedAmount,
        'p_fee' => $pFee,
        'p_fee_gst' => $pFeeGst,
        'kfs_interest' => $kfs_interest,
        'calendar_dpd' => $calendar_dpd,
        'presentment_dpd' => $presentment_dpd,
        'overdue_interest' => $overdue_interest,
        'penalty' => $penalty,
        'penalty_gst' => $penalty_gst,
        'penalty_with_gst' => $penalty + $penalty_gst,
        'total' => $total,
        'loan_tenure' => $loan_tenure,
    ];
}

/**
 * Flattened presentment fields for auto/manual/zz eNACH logs.
 *
 * @return array{
 *   days:int,p_fee_gst:float,service_charge:float,penalty_charge:float,penalty_gst:float,
 *   total_amount:float,processed_amount:float,repayment_base:float,p_fee:float,
 *   kfs_interest:float,overdue_interest:float,calendar_dpd:int,presentment_dpd:int
 * }
 */
function creditlab_enach_amount_log_fields(array $loan, array $loan_apply): array
{
    $b = creditlab_enach_presentment_breakdown($loan, $loan_apply);
    return [
        'days' => $b['presentment_dpd'],
        'p_fee_gst' => $b['p_fee_gst'],
        'service_charge' => $b['kfs_interest'] + $b['overdue_interest'],
        'penalty_charge' => $b['penalty'],
        'penalty_gst' => $b['penalty_gst'],
        'total_amount' => $b['total'],
        'processed_amount' => $b['processed_amount'],
        'repayment_base' => $b['principal'],
        'p_fee' => $b['p_fee'],
        'kfs_interest' => $b['kfs_interest'],
        'overdue_interest' => $b['overdue_interest'],
        'calendar_dpd' => $b['calendar_dpd'],
        'presentment_dpd' => $b['presentment_dpd'],
    ];
}

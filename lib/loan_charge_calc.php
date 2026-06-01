<?php
/**
 * Shared loan service charge and penalty (cron, E-NACH, admin totals).
 */

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

    $t = $processedAmount + $pFee + ($pFee * 0.18);
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
    $penality = $penality + ($penality * 0.18);

    return [
        'exhausted_period' => $exhausted_period,
        'service_charge' => $service_charge,
        'penality_charge' => $penality,
        'loan_tenure' => $loan_tenure,
        'dpd' => $dpd,
    ];
}

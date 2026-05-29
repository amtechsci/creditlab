<?php
/**
 * Outstanding amount for active loans (matches user/autopay.php).
 */

function creditlab_fetch_loan_by_internal_id(int $loanInternalId): ?array
{
    $loanInternalId = (int) $loanInternalId;
    $q = towquery("SELECT loan.*, loan_apply.days AS loan_apply_days FROM loan INNER JOIN loan_apply ON loan_apply.id = loan.lid WHERE loan.id = $loanInternalId LIMIT 1");
    if (!$q || townum($q) < 1) {
        return null;
    }
    return towfetch($q);
}

function creditlab_fetch_loan_by_lid(int $loanLid): ?array
{
    $loanLid = (int) $loanLid;
    $q = towquery("SELECT loan.*, loan_apply.days AS loan_apply_days FROM loan INNER JOIN loan_apply ON loan_apply.id = loan.lid WHERE loan.lid = $loanLid LIMIT 1");
    if (!$q || townum($q) < 1) {
        return null;
    }
    return towfetch($q);
}

function creditlab_active_loans_for_user(int $uid): array
{
    $uid = (int) $uid;
    $statuses = "'account manager','recovery officer','default'";
    $q = towquery("SELECT loan.*, loan_apply.days AS loan_apply_days, loan_apply.status AS apply_status FROM loan INNER JOIN loan_apply ON loan_apply.id = loan.lid WHERE loan.uid = $uid AND loan.status_log IN ($statuses) ORDER BY loan.id DESC");
    $rows = [];
    if ($q) {
        while ($r = towfetch($q)) {
            $rows[] = $r;
        }
    }
    return $rows;
}

function creditlab_loan_outstanding_amount(array $loanRow): float
{
    return (float) ceil(
        (float) ($loanRow['processed_amount'] ?? 0)
        + (float) ($loanRow['p_fee'] ?? 0)
        + (float) ($loanRow['service_charge'] ?? 0)
        + ((float) ($loanRow['p_fee'] ?? 0) * 0.18)
        + (float) ($loanRow['penality_charge'] ?? 0)
    );
}

function creditlab_loan_outstanding_breakdown(array $loanRow): array
{
    $principal = (float) ($loanRow['processed_amount'] ?? 0);
    $pfee = (float) ($loanRow['p_fee'] ?? 0);
    $service = (float) ($loanRow['service_charge'] ?? 0);
    $gst = $pfee * 0.18;
    $penalty = (float) ($loanRow['penality_charge'] ?? 0);
    $total = creditlab_loan_outstanding_amount($loanRow);
    return [
        'processed_amount' => $principal,
        'p_fee' => $pfee,
        'service_charge' => $service,
        'gst_on_pfee' => $gst,
        'penality_charge' => $penalty,
        'total' => $total,
    ];
}

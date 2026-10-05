<?php
/**
 * Helpers for telling an Easebuzz repayment amount apart from the figure that
 * includes the gateway's own service charge and tax.
 *
 * `amount` is what the customer repaid against the loan. `net_amount_debit`
 * (PG responses) and `net_amount` (e-NACH presentments) add Easebuzz's
 * service_charge + service_tax, which never reaches us and must not be credited.
 */

/** Amounts within this many rupees are treated as the same figure. */
if (!defined('CREDITLAB_FEE_EPSILON')) {
    define('CREDITLAB_FEE_EPSILON', 0.01);
}

function creditlab_fee_num($value): ?float
{
    if (!is_scalar($value)) {
        return null;
    }
    $text = trim((string) $value);
    return is_numeric($text) ? (float) $text : null;
}

function creditlab_fee_money(float $value): string
{
    return number_format($value, 2, '.', '');
}

function creditlab_fee_same(?float $a, ?float $b): bool
{
    return $a !== null && $b !== null && abs($a - $b) < CREDITLAB_FEE_EPSILON;
}

/**
 * Pull the true and gateway-inflated amounts out of one archived webhook payload.
 *
 * Returns null unless the payload is a successful payment whose net figure
 * actually exceeds the repayment amount.
 *
 * @return array{source:string,txnid:string,bank_ref:string,loan_lid:int,true:float,inflated:float}|null
 */
function creditlab_fee_extract_candidate(array $post, string $raw): ?array
{
    $loanLid = 0;

    if (isset($post['txnid']) || isset($post['net_amount_debit'])) {
        $source = 'pg';
        $status = strtolower(trim((string) ($post['status'] ?? '')));
        $true = creditlab_fee_num($post['amount'] ?? null);
        if ($true === null) {
            $true = creditlab_fee_num($post['settlement_amount'] ?? null);
        }
        $inflated = creditlab_fee_num($post['net_amount_debit'] ?? null);
        $txnid = trim((string) ($post['txnid'] ?? ''));
        $bankRef = trim((string) ($post['bank_ref_num'] ?? ''));
        if ($bankRef === '' || strtoupper($bankRef) === 'NA') {
            $bankRef = trim((string) ($post['easepayid'] ?? ''));
        }
    } else {
        $json = json_decode($raw, true);
        $data = (is_array($json) && is_array($json['data'] ?? null)) ? $json['data'] : null;
        if ($data === null) {
            return null;
        }
        $source = 'enach';
        $status = strtolower(trim((string) ($data['status'] ?? '')));
        $true = creditlab_fee_num($data['amount'] ?? null);
        if ($true === null) {
            $true = creditlab_fee_num($data['remaining_amount'] ?? null);
        }
        $inflated = creditlab_fee_num($data['net_amount'] ?? null);
        if ($inflated === null) {
            $inflated = creditlab_fee_num($data['net_amount_debit'] ?? null);
        }
        $txnid = trim((string) ($data['pg_transaction_id'] ?? ''));
        if ($txnid === '') {
            $txnid = trim((string) ($data['transaction_reference_number'] ?? ''));
        }
        $bankRef = trim((string) ($data['bank_reference_number'] ?? ''));
        if (preg_match('/CLL_AUTO_(\d+)/', (string) ($data['merchant_request_number'] ?? ''), $m)) {
            $loanLid = (int) $m[1];
        }
    }

    if ($status !== 'success' || $true === null || $inflated === null || $true <= 0) {
        return null;
    }
    if ($inflated - $true < CREDITLAB_FEE_EPSILON) {
        return null;
    }
    if (strtoupper($bankRef) === 'NA') {
        $bankRef = '';
    }
    if ($txnid === '' && $bankRef === '') {
        return null;
    }

    return [
        'source' => $source,
        'txnid' => $txnid,
        'bank_ref' => $bankRef,
        'loan_lid' => $loanLid,
        'true' => $true,
        'inflated' => $inflated,
    ];
}

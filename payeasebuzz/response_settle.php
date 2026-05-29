<?php
/**
 * Shared payment success handler for payeasebuzz/response.php
 */
require_once __DIR__ . '/../lib/pg_link_settlement.php';

function creditlab_payeasebuzz_handle_success(string $txnid, float $amount, string $bankRef, string $paymentMethod): array
{
    global $db;
    return creditlab_process_pg_payment_success($db, $txnid, $amount, $bankRef, $paymentMethod);
}

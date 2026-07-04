<?php
/**
 * Apply PG link payment success (full close or part payment).
 */
require_once __DIR__ . '/loan_outstanding.php';
require_once __DIR__ . '/app_url.php';
require_once __DIR__ . '/zxc_mail.php';
require_once __DIR__ . '/http_fetch.php';
require_once __DIR__ . '/sms_loan_cleared.php';
require_once __DIR__ . '/pg_db_bootstrap.php';

/** Payment tolerance (₹) for rounding / gateway amount strings. */
function creditlab_pg_amount_tolerance(): float
{
    return 2.0;
}

function creditlab_pg_is_agency_manual_link(?array $pgLink, array $pgTx): bool
{
    $linkType = $pgLink['link_type'] ?? ($pgTx['link_type'] ?? '');
    if ($linkType !== 'manual') {
        return false;
    }
    if (!empty($pgLink['agency_id']) || !empty($pgTx['agency_id'])) {
        return true;
    }
    $role = $pgLink['created_by_role'] ?? ($pgTx['created_by_role'] ?? '');
    return $role === 'agency_admin';
}

/**
 * Full close when customer paid the link/initiated amount or current outstanding.
 */
function creditlab_pg_payment_is_full_settlement(array $loan, array $pgTx, ?array $pgLink, float $amountPaid): bool
{
    $linkType = $pgLink['link_type'] ?? ($pgTx['link_type'] ?? '');

    // Manual links never auto-close the loan (agency staff review in agency payment report).
    if ($linkType === 'manual') {
        return false;
    }

    $tolerance = creditlab_pg_amount_tolerance();
    $outstanding = creditlab_loan_outstanding_amount($loan);

    if ($linkType === 'total_outstanding') {
        $linkAmount = (float) ($pgLink['amount'] ?? $pgTx['amount'] ?? 0);
        if ($linkAmount > 0 && $amountPaid + $tolerance >= $linkAmount) {
            return true;
        }
        $initiated = (float) ($pgTx['amount'] ?? 0);
        if ($initiated > 0 && $amountPaid + $tolerance >= $initiated) {
            return true;
        }
    }

    return $amountPaid + $tolerance >= $outstanding;
}

function creditlab_pg_link_by_txnid(string $txnid): ?array
{
    $txnid = towreal($txnid);
    $q = towquery("SELECT * FROM pg_payment_link WHERE txnid='$txnid' LIMIT 1");
    if (!$q || townum($q) < 1) {
        return null;
    }
    return towfetch($q);
}

function creditlab_is_staff_pg_txnid(string $txnid): bool
{
    if (strpos($txnid, 'PG_') === 0) {
        return true;
    }
    return creditlab_pg_link_by_txnid($txnid) !== null;
}

/**
 * @return array{ok:bool,message:string,flow?:string}
 */
function creditlab_settle_pg_payment(
    $mysqliConn,
    string $txnid,
    float $amount,
    string $bankRef,
    string $paymentMethod = 'easebuzz'
): array {
    creditlab_pg_bind_mysqli($mysqliConn);
    if (!$mysqliConn) {
        return ['ok' => false, 'message' => 'Database unavailable'];
    }
    $txnidEsc = mysqli_real_escape_string($mysqliConn, $txnid);
    $pgLink = creditlab_pg_link_by_txnid($txnid);

    $pgTxQ = towquery("SELECT * FROM pg_transaction WHERE txnid='$txnidEsc' LIMIT 1");
    if (!$pgTxQ || townum($pgTxQ) < 1) {
        return ['ok' => false, 'message' => 'Transaction not found'];
    }
    $pgTx = towfetch($pgTxQ);
    $loanInternalId = (int) $pgTx['loan_id'];
    $loan = creditlab_fetch_loan_by_internal_id($loanInternalId);
    if (!$loan) {
        return ['ok' => false, 'message' => 'Loan not found'];
    }

    $uid = (int) $loan['uid'];
    $loanLid = (int) $loan['lid'];
    $linkType = $pgLink['link_type'] ?? ($pgTx['link_type'] ?? 'total_outstanding');

    $userQ = towquery("SELECT * FROM user WHERE id=$uid LIMIT 1");
    if (!$userQ || townum($userQ) < 1) {
        return ['ok' => false, 'message' => 'User not found'];
    }
    $userDetails = towfetch($userQ);

    if ($loan['status_log'] === 'cleared') {
        creditlab_pg_mark_tx_success($mysqliConn, $txnid, $amount, $bankRef, $paymentMethod, $pgLink);
        return ['ok' => true, 'message' => 'Already cleared', 'flow' => 'skipped'];
    }

    // Agency manual PG: record payment only — no loan clear, no transaction_details, no SMS.
    if (creditlab_pg_is_agency_manual_link($pgLink, $pgTx)) {
        creditlab_pg_mark_tx_success($mysqliConn, $txnid, $amount, $bankRef, $paymentMethod, $pgLink);
        error_log("PG agency manual recorded: txnid=$txnid CLL$loanLid paid=$amount (loan unchanged)");
        return ['ok' => true, 'message' => 'Agency manual payment recorded', 'flow' => 'agency_manual'];
    }

    $pgAlreadySuccess = (($pgTx['status'] ?? '') === 'success');
    if ($pgAlreadySuccess) {
        error_log("PG repair: txnid=$txnid success but loan CLL$loanLid still {$loan['status_log']}");
    }

    $agencyId = $pgLink['agency_id'] ?? $pgTx['agency_id'] ?? null;
    $agencyName = $pgLink['agency_name'] ?? $pgTx['agency_name'] ?? null;
    $pgLinkId = $pgLink['id'] ?? null;

    $isFull = creditlab_pg_payment_is_full_settlement($loan, $pgTx, $pgLink, $amount);
    if (!$isFull) {
        error_log("PG part settlement: txnid=$txnid CLL$loanLid paid=$amount outstanding=" . creditlab_loan_outstanding_amount($loan) . " initiated=" . ($pgTx['amount'] ?? ''));
    }

    mysqli_autocommit($mysqliConn, false);
    try {
        $amountEsc = mysqli_real_escape_string($mysqliConn, (string) $amount);
        $bankEsc = mysqli_real_escape_string($mysqliConn, $bankRef);
        $dt = mysqli_real_escape_string($mysqliConn, date('Y-m-d H:i:s'));
        $dateOnly = mysqli_real_escape_string($mysqliConn, date('Y-m-d'));

        if ($isFull) {
            $loanApplyQ = towquery("SELECT days FROM loan_apply WHERE id=$loanLid LIMIT 1");
            $loanApply = $loanApplyQ ? towfetch($loanApplyQ) : null;
            $loanDays = isset($loanApply['days']) && (int) $loanApply['days'] > 0 ? (int) $loanApply['days'] : 30;
            $dpd = (int) $loan['exhausted_period'] - $loanDays;
            if ($dpd > 30) {
                $point = -50;
            } elseif ($dpd > 10) {
                $point = -8;
            } elseif ($dpd > 0) {
                $point = 2;
            } else {
                $point = 8;
            }

            $loanIdEsc = mysqli_real_escape_string($mysqliConn, (string) $loanInternalId);
            $loanLidEsc = mysqli_real_escape_string($mysqliConn, (string) $loanLid);
            $uidEsc = mysqli_real_escape_string($mysqliConn, (string) $uid);

            if (!towquery("UPDATE user SET sloan=sloan+1, credit_score=credit_score+$point, status='cleared' WHERE id='$uidEsc'")) {
                throw new Exception('Failed to update user');
            }
            $agencySet = '';
            if ($agencyName) {
                $an = mysqli_real_escape_string($mysqliConn, $agencyName);
                $ai = $agencyId ? (int) $agencyId : 'NULL';
                $pl = $pgLinkId ? (int) $pgLinkId : 'NULL';
                $agencySet = ", paid_via_agency_id=$ai, paid_via_agency_name='$an', paid_via_pg_link_id=$pl";
            }
            if (!towquery("UPDATE loan SET action='cleared', status_log='cleared', cleard_date='$dateOnly'$agencySet WHERE id='$loanIdEsc'")) {
                throw new Exception('Failed to clear loan');
            }
            if (!towquery("UPDATE loan_apply SET status='cleared' WHERE id='$loanLidEsc'")) {
                throw new Exception('Failed to clear loan apply');
            }
            towquery("DELETE FROM pay_ref WHERE loan_id='$loanLidEsc'");
            if (!towquery("INSERT INTO transaction_details (uid, cllid, transaction_number, transaction_date, transaction_amount, transaction_flow) VALUES ('$uidEsc','$loanLidEsc','$bankEsc','$dt','$amountEsc','full')")) {
                throw new Exception('Failed to insert transaction');
            }
            $flow = 'full';
        } else {
            $loanIdEsc = mysqli_real_escape_string($mysqliConn, (string) $loanInternalId);
            $loanLidEsc = mysqli_real_escape_string($mysqliConn, (string) $loanLid);
            $uidEsc = mysqli_real_escape_string($mysqliConn, (string) $uid);
            if (!towquery("UPDATE loan SET advance_amount='$amountEsc' WHERE id='$loanIdEsc'")) {
                throw new Exception('Failed to update advance_amount');
            }
            if (!towquery("INSERT INTO transaction_details (uid, cllid, transaction_number, transaction_date, transaction_amount, transaction_flow) VALUES ('$uidEsc','$loanLidEsc','$bankEsc','$dt','$amountEsc','part')")) {
                throw new Exception('Failed to insert part transaction');
            }
            $flow = 'part';
            $mobile = $userDetails['mobile'] ?? '';
            if ($mobile !== '') {
                $template_id = '1107169454135117024';
                $message = "We got a part payment of Rs {$amount} w.r.t your Creditlab.in loan CLL{$loanLid}. Pay the balance to close the loan. Discuss with your RM & settle immediately.";
                if (!defined('CREDITLAB_SMS_INCLUDE')) {
                    define('CREDITLAB_SMS_INCLUDE', true);
                }
                include __DIR__ . '/../send_sms.php';
            }
        }

        creditlab_pg_mark_tx_success($mysqliConn, $txnid, $amount, $bankRef, $paymentMethod, $pgLink);
        mysqli_commit($mysqliConn);

        $smsOk = false;
        if ($isFull) {
            $smsOk = creditlab_pg_notify_loan_cleared($userDetails, $loanLid);
            if (!$smsOk) {
                error_log("PG full settle: cleared SMS failed txnid=$txnid CLL$loanLid");
            }
        }

        return ['ok' => true, 'message' => 'Settled', 'flow' => $flow, 'sms_ok' => $smsOk];
    } catch (Exception $e) {
        mysqli_rollback($mysqliConn);
        error_log('PG settlement failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => $e->getMessage()];
    } finally {
        mysqli_autocommit($mysqliConn, true);
    }
}

function creditlab_pg_mark_tx_success($mysqliConn, string $txnid, float $amount, string $bankRef, string $paymentMethod, ?array $pgLink): void
{
    creditlab_pg_bind_mysqli($mysqliConn);
    $txnidEsc = mysqli_real_escape_string($mysqliConn, $txnid);
    $amountEsc = mysqli_real_escape_string($mysqliConn, (string) $amount);
    $bankEsc = mysqli_real_escape_string($mysqliConn, $bankRef);
    $pmEsc = mysqli_real_escape_string($mysqliConn, $paymentMethod);
    towquery("UPDATE pg_transaction SET status='success', amount='$amountEsc', payment_method='$pmEsc', bank_reference_number='$bankEsc' WHERE txnid='$txnidEsc'");
    if ($pgLink) {
        if (!empty($pgLink['agency_name'])) {
            $an = mysqli_real_escape_string($mysqliConn, $pgLink['agency_name']);
            $agencyIdSql = !empty($pgLink['agency_id']) ? ', agency_id=' . (int) $pgLink['agency_id'] : '';
            towquery("UPDATE pg_transaction SET agency_name='$an'$agencyIdSql WHERE txnid='$txnidEsc' AND (agency_name IS NULL OR agency_name = '')");
        }
        $id = (int) $pgLink['id'];
        towquery("UPDATE pg_payment_link SET status='paid', paid_at=NOW(), bank_ref_num='$bankEsc' WHERE id=$id AND status='created'");
    }
}

function creditlab_pg_mark_tx_failure($mysqliConn, string $txnid): void
{
    creditlab_pg_bind_mysqli($mysqliConn);
    $txnidEsc = mysqli_real_escape_string($mysqliConn, $txnid);
    towquery("UPDATE pg_transaction SET status='failure' WHERE txnid='$txnidEsc'");
    towquery("UPDATE pg_payment_link SET status='failed' WHERE txnid='$txnidEsc' AND status='created'");
}

/**
 * Legacy user autopay full clearance (non-PG_ txn without pg_payment_link row).
 */
function creditlab_settle_legacy_pg_full($mysqliConn, string $txnid, float $amount, string $bankRef, string $paymentMethod = 'easebuzz'): array
{
    creditlab_pg_bind_mysqli($mysqliConn);
    $txnidEsc = mysqli_real_escape_string($mysqliConn, $txnid);
    $pgTxQ = towquery("SELECT * FROM pg_transaction WHERE txnid='$txnidEsc' LIMIT 1");
    if (!$pgTxQ || townum($pgTxQ) < 1) {
        return ['ok' => false, 'message' => 'Not found'];
    }
    $pgTx = towfetch($pgTxQ);
    $loan = creditlab_fetch_loan_by_internal_id((int) $pgTx['loan_id']);
    if (!$loan) {
        return ['ok' => false, 'message' => 'Loan not found'];
    }
    if ($loan['status_log'] === 'cleared') {
        towquery("UPDATE pg_transaction SET status='success' WHERE txnid='$txnidEsc'");
        return ['ok' => true, 'message' => 'Already cleared', 'flow' => 'skipped'];
    }
    if (($pgTx['status'] ?? '') === 'success') {
        error_log("PG legacy repair: txnid=$txnid loan still active");
    }

    $uid = (int) $loan['uid'];
    $loanLid = (int) $loan['lid'];
    $loanInternalId = (int) $loan['id'];
    $userQ = towquery("SELECT * FROM user WHERE id=$uid LIMIT 1");
    $userDetails = towfetch($userQ);

    $loanApplyQ = towquery("SELECT days FROM loan_apply WHERE id=$loanLid LIMIT 1");
    $loanApply = towfetch($loanApplyQ);
    $loanDays = isset($loanApply['days']) && (int) $loanApply['days'] > 0 ? (int) $loanApply['days'] : 30;
    $dpd = (int) $loan['exhausted_period'] - $loanDays;
    if ($dpd > 30) {
        $point = -50;
    } elseif ($dpd > 10) {
        $point = -8;
    } elseif ($dpd > 0) {
        $point = 2;
    } else {
        $point = 8;
    }

    mysqli_autocommit($mysqliConn, false);
    try {
        $amountEsc = mysqli_real_escape_string($mysqliConn, (string) $amount);
        $bankEsc = mysqli_real_escape_string($mysqliConn, $bankRef);
        $dt = mysqli_real_escape_string($mysqliConn, date('Y-m-d H:i:s'));
        $dateOnly = mysqli_real_escape_string($mysqliConn, date('Y-m-d'));
        $loanIdEsc = mysqli_real_escape_string($mysqliConn, (string) $loanInternalId);
        $loanLidEsc = mysqli_real_escape_string($mysqliConn, (string) $loanLid);
        $uidEsc = mysqli_real_escape_string($mysqliConn, (string) $uid);
        $pmEsc = mysqli_real_escape_string($mysqliConn, $paymentMethod);

        if (!towquery("UPDATE user SET sloan=sloan+1, credit_score=credit_score+$point, status='cleared' WHERE id='$uidEsc'")) {
            throw new Exception('Failed to update user (legacy)');
        }
        if (!towquery("UPDATE loan SET action='cleared', status_log='cleared', cleard_date='$dateOnly' WHERE id='$loanIdEsc'")) {
            throw new Exception('Failed to clear loan (legacy)');
        }
        if (!towquery("UPDATE loan_apply SET status='cleared' WHERE id='$loanLidEsc'")) {
            throw new Exception('Failed to clear loan apply (legacy)');
        }
        if (!towquery("INSERT INTO transaction_details (uid, cllid, transaction_number, transaction_date, transaction_amount, transaction_flow) VALUES ('$uidEsc','$loanLidEsc','$bankEsc','$dt','$amountEsc','full')")) {
            throw new Exception('Failed to insert transaction (legacy)');
        }
        if (!towquery("UPDATE pg_transaction SET status='success', amount='$amountEsc', payment_method='$pmEsc', bank_reference_number='$bankEsc' WHERE txnid='$txnidEsc'")) {
            throw new Exception('Failed to update pg_transaction (legacy)');
        }
        mysqli_commit($mysqliConn);

        $smsOk = creditlab_pg_notify_loan_cleared($userDetails, $loanLid);
        if (!$smsOk) {
            error_log("PG legacy full settle: cleared SMS failed txnid=$txnid CLL$loanLid");
        }
        return ['ok' => true, 'message' => 'legacy full', 'flow' => 'full', 'sms_ok' => $smsOk];
    } catch (Exception $e) {
        mysqli_rollback($mysqliConn);
        return ['ok' => false, 'message' => $e->getMessage()];
    } finally {
        mysqli_autocommit($mysqliConn, true);
    }
}

function creditlab_process_pg_payment_success($mysqliConn, string $txnid, float $amount, string $bankRef, string $paymentMethod = 'easebuzz'): array
{
    creditlab_pg_bind_mysqli($mysqliConn);
    if (creditlab_is_staff_pg_txnid($txnid) || creditlab_pg_link_by_txnid($txnid)) {
        return creditlab_settle_pg_payment($mysqliConn, $txnid, $amount, $bankRef, $paymentMethod);
    }
    return creditlab_settle_legacy_pg_full($mysqliConn, $txnid, $amount, $bankRef, $paymentMethod);
}

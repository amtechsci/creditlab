<?php
/**
 * Apply PG link payment success (full close or part payment).
 */
require_once __DIR__ . '/loan_outstanding.php';
require_once __DIR__ . '/zxc_mail.php';
require_once __DIR__ . '/http_fetch.php';

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
    return strpos($txnid, 'PG_') === 0 || creditlab_pg_link_by_txnid($txnid) !== null;
}

/**
 * @return array{ok:bool,message:string,flow?:string}
 */
function creditlab_settle_pg_payment(
    $db,
    string $txnid,
    float $amount,
    string $bankRef,
    string $paymentMethod = 'easebuzz'
): array {
    global $db;
    if (!$db) {
        return ['ok' => false, 'message' => 'Database unavailable'];
    }
    $txnidEsc = mysqli_real_escape_string($db, $txnid);
    $pgLink = creditlab_pg_link_by_txnid($txnid);

    $pgTxQ = towquery("SELECT * FROM pg_transaction WHERE txnid='$txnidEsc' LIMIT 1");
    if (!$pgTxQ || townum($pgTxQ) < 1) {
        return ['ok' => false, 'message' => 'Transaction not found'];
    }
    $pgTx = towfetch($pgTxQ);
    if (($pgTx['status'] ?? '') === 'success') {
        return ['ok' => true, 'message' => 'Already settled', 'flow' => 'skipped'];
    }

    $loanInternalId = (int) $pgTx['loan_id'];
    $loan = creditlab_fetch_loan_by_internal_id($loanInternalId);
    if (!$loan) {
        return ['ok' => false, 'message' => 'Loan not found'];
    }

    $uid = (int) $loan['uid'];
    $loanLid = (int) $loan['lid'];
    $linkType = $pgLink['link_type'] ?? ($pgTx['link_type'] ?? 'total_outstanding');
    $outstanding = creditlab_loan_outstanding_amount($loan);
    $tolerance = 1.0;

    $userQ = towquery("SELECT * FROM user WHERE id=$uid LIMIT 1");
    if (!$userQ || townum($userQ) < 1) {
        return ['ok' => false, 'message' => 'User not found'];
    }
    $userDetails = towfetch($userQ);

    if ($loan['status_log'] === 'cleared') {
        creditlab_pg_mark_tx_success($db, $txnid, $amount, $bankRef, $paymentMethod, $pgLink);
        return ['ok' => true, 'message' => 'Already cleared', 'flow' => 'skipped'];
    }

    $agencyId = $pgLink['agency_id'] ?? $pgTx['agency_id'] ?? null;
    $agencyName = $pgLink['agency_name'] ?? $pgTx['agency_name'] ?? null;
    $pgLinkId = $pgLink['id'] ?? null;

    $isFull = ($linkType === 'total_outstanding') || ($amount + $tolerance >= $outstanding);

    mysqli_autocommit($db, false);
    try {
        $amountEsc = mysqli_real_escape_string($db, (string) $amount);
        $bankEsc = mysqli_real_escape_string($db, $bankRef);
        $dt = mysqli_real_escape_string($db, date('Y-m-d H:i:s'));
        $dateOnly = mysqli_real_escape_string($db, date('Y-m-d'));

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

            $loanIdEsc = mysqli_real_escape_string($db, (string) $loanInternalId);
            $loanLidEsc = mysqli_real_escape_string($db, (string) $loanLid);
            $uidEsc = mysqli_real_escape_string($db, (string) $uid);

            if (!towquery("UPDATE user SET sloan=sloan+1, credit_score=credit_score+$point, status='cleared' WHERE id='$uidEsc'")) {
                throw new Exception('Failed to update user');
            }
            $agencySet = '';
            if ($agencyName) {
                $an = mysqli_real_escape_string($db, $agencyName);
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
            $loanIdEsc = mysqli_real_escape_string($db, (string) $loanInternalId);
            $loanLidEsc = mysqli_real_escape_string($db, (string) $loanLid);
            $uidEsc = mysqli_real_escape_string($db, (string) $uid);
            if (!towquery("UPDATE loan SET advance_amount='$amountEsc' WHERE id='$loanIdEsc'")) {
                throw new Exception('Failed to update advance_amount');
            }
            if (!towquery("INSERT INTO transaction_details (uid, cllid, transaction_number, transaction_date, transaction_amount, transaction_flow) VALUES ('$uidEsc','$loanLidEsc','$bankEsc','$dt','$amountEsc','part')")) {
                throw new Exception('Failed to insert part transaction');
            }
            $flow = 'part';
            $mobile = $userDetails['mobile'] ?? '';
            if ($mobile !== '' && file_exists(__DIR__ . '/../send_sms.php')) {
                $message = "We got a part payment of Rs {$amount} w.r.t your Creditlab.in loan CLL{$loanLid}. Pay the balance to close the loan. Discuss with your RM & settle immediately.";
                define('CREDITLAB_SMS_INCLUDE', true);
                include __DIR__ . '/../send_sms.php';
            }
        }

        creditlab_pg_mark_tx_success($db, $txnid, $amount, $bankRef, $paymentMethod, $pgLink);
        mysqli_commit($db);

        if ($isFull) {
            $base = creditlab_get_base_url();
            creditlab_zxc_mail_trigger(creditlab_zxc_mail_url($base, $userDetails['email'], null, null, $base . '/no-due-certificate2.php?id=' . $loanLid));
            $mobile = $userDetails['mobile'] ?? '';
            if ($mobile !== '' && file_exists(__DIR__ . '/../send_sms.php')) {
                $message = "Dear {$userDetails['name']}, we acknowledge the repayment of your loan CLL{$loanLid} & it's cleared. You can apply again. {$base}/ -Creditlab";
                define('CREDITLAB_SMS_INCLUDE', true);
                include __DIR__ . '/../send_sms.php';
            }
        }

        return ['ok' => true, 'message' => 'Settled', 'flow' => $flow];
    } catch (Exception $e) {
        mysqli_rollback($db);
        error_log('PG settlement failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => $e->getMessage()];
    } finally {
        mysqli_autocommit($db, true);
    }
}

function creditlab_pg_mark_tx_success($db, string $txnid, float $amount, string $bankRef, string $paymentMethod, ?array $pgLink): void
{
    global $db;
    $txnidEsc = mysqli_real_escape_string($db, $txnid);
    $amountEsc = mysqli_real_escape_string($db, (string) $amount);
    $bankEsc = mysqli_real_escape_string($db, $bankRef);
    $pmEsc = mysqli_real_escape_string($db, $paymentMethod);
    towquery("UPDATE pg_transaction SET status='success', amount='$amountEsc', payment_method='$pmEsc', bank_reference_number='$bankEsc' WHERE txnid='$txnidEsc'");
    if ($pgLink) {
        $id = (int) $pgLink['id'];
        towquery("UPDATE pg_payment_link SET status='paid', paid_at=NOW(), bank_ref_num='$bankEsc' WHERE id=$id AND status='created'");
    }
}

function creditlab_pg_mark_tx_failure($db, string $txnid): void
{
    global $db;
    $txnidEsc = mysqli_real_escape_string($db, $txnid);
    towquery("UPDATE pg_transaction SET status='failure' WHERE txnid='$txnidEsc'");
    towquery("UPDATE pg_payment_link SET status='failed' WHERE txnid='$txnidEsc' AND status='created'");
}

/**
 * Legacy user autopay full clearance (non-PG_ txn without pg_payment_link row).
 */
function creditlab_settle_legacy_pg_full($db, string $txnid, float $amount, string $bankRef, string $paymentMethod = 'easebuzz'): array
{
    global $db;
    $txnidEsc = mysqli_real_escape_string($db, $txnid);
    $pgTxQ = towquery("SELECT * FROM pg_transaction WHERE txnid='$txnidEsc' LIMIT 1");
    if (!$pgTxQ || townum($pgTxQ) < 1) {
        return ['ok' => false, 'message' => 'Not found'];
    }
    $pgTx = towfetch($pgTxQ);
    if (($pgTx['status'] ?? '') === 'success') {
        return ['ok' => true, 'message' => 'Already settled', 'flow' => 'skipped'];
    }
    $loan = creditlab_fetch_loan_by_internal_id((int) $pgTx['loan_id']);
    if (!$loan || $loan['status_log'] === 'cleared') {
        towquery("UPDATE pg_transaction SET status='success' WHERE txnid='$txnidEsc'");
        return ['ok' => true, 'message' => 'skipped', 'flow' => 'skipped'];
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

    mysqli_autocommit($db, false);
    try {
        $amountEsc = mysqli_real_escape_string($db, (string) $amount);
        $bankEsc = mysqli_real_escape_string($db, $bankRef);
        $dt = mysqli_real_escape_string($db, date('Y-m-d H:i:s'));
        $dateOnly = mysqli_real_escape_string($db, date('Y-m-d'));
        $loanIdEsc = mysqli_real_escape_string($db, (string) $loanInternalId);
        $loanLidEsc = mysqli_real_escape_string($db, (string) $loanLid);
        $uidEsc = mysqli_real_escape_string($db, (string) $uid);
        $pmEsc = mysqli_real_escape_string($db, $paymentMethod);

        towquery("UPDATE user SET sloan=sloan+1, credit_score=credit_score+$point, status='cleared' WHERE id='$uidEsc'");
        towquery("UPDATE loan SET action='cleared', status_log='cleared', cleard_date='$dateOnly' WHERE id='$loanIdEsc'");
        towquery("UPDATE loan_apply SET status='cleared' WHERE id='$loanLidEsc'");
        towquery("INSERT INTO transaction_details (uid, cllid, transaction_number, transaction_date, transaction_amount, transaction_flow) VALUES ('$uidEsc','$loanLidEsc','$bankEsc','$dt','$amountEsc','full')");
        towquery("UPDATE pg_transaction SET status='success', amount='$amountEsc', payment_method='$pmEsc', bank_reference_number='$bankEsc' WHERE txnid='$txnidEsc'");
        mysqli_commit($db);

        $base = creditlab_get_base_url();
        if (!empty($userDetails['email'])) {
            creditlab_zxc_mail_trigger(creditlab_zxc_mail_url($base, $userDetails['email'], null, null, $base . '/no-due-certificate2.php?id=' . $loanLid));
        }
        return ['ok' => true, 'message' => 'legacy full', 'flow' => 'full'];
    } catch (Exception $e) {
        mysqli_rollback($db);
        return ['ok' => false, 'message' => $e->getMessage()];
    } finally {
        mysqli_autocommit($db, true);
    }
}

function creditlab_process_pg_payment_success($db, string $txnid, float $amount, string $bankRef, string $paymentMethod = 'easebuzz'): array
{
    if (creditlab_is_staff_pg_txnid($txnid) || creditlab_pg_link_by_txnid($txnid)) {
        return creditlab_settle_pg_payment($db, $txnid, $amount, $bankRef, $paymentMethod);
    }
    return creditlab_settle_legacy_pg_full($db, $txnid, $amount, $bankRef, $paymentMethod);
}

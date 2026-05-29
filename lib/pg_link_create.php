<?php
require_once __DIR__ . '/staff_context.php';
require_once __DIR__ . '/loan_outstanding.php';
require_once __DIR__ . '/easebuzz_payment.php';

/**
 * @return array{ok:bool,error?:string,row?:array}
 */
function creditlab_create_pg_link(int $uid, int $loanInternalId, string $linkType, ?float $manualAmount = null): array
{
    if (!creditlab_can_create_pg_link()) {
        return ['ok' => false, 'error' => 'Not authorized'];
    }

    $actor = creditlab_staff_actor();
    if (!$actor) {
        return ['ok' => false, 'error' => 'Not logged in'];
    }

    $linkType = $linkType === 'manual' ? 'manual' : 'total_outstanding';
    $loan = creditlab_fetch_loan_by_internal_id($loanInternalId);
    if (!$loan || (int) $loan['uid'] !== $uid) {
        return ['ok' => false, 'error' => 'Invalid loan for this customer'];
    }

    if (!in_array($loan['status_log'], ['account manager', 'recovery officer', 'default'], true)) {
        return ['ok' => false, 'error' => 'Loan is not active for payment link'];
    }

    $outstanding = creditlab_loan_outstanding_amount($loan);
    if ($linkType === 'total_outstanding') {
        $amount = $outstanding;
    } else {
        $amount = (float) $manualAmount;
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Enter a valid amount'];
        }
    }

    $userQ = towquery("SELECT id, name, email, mobile FROM user WHERE id=" . (int) $uid . " LIMIT 1");
    if (!$userQ || townum($userQ) < 1) {
        return ['ok' => false, 'error' => 'Customer not found'];
    }
    $customer = towfetch($userQ);

    $role = $actor['role'];
    if (!in_array($role, ['admin', 'account_manager', 'agency_admin'], true)) {
        return ['ok' => false, 'error' => 'Role cannot create PG links'];
    }

    $txnid = 'PG_' . $role . '_' . (int) $actor['id'] . '_' . (int) $loanInternalId . '_' . strtoupper(bin2hex(random_bytes(4)));
    $loanLid = (int) $loan['lid'];
    $agencyId = $actor['agency_id'];
    $agencyName = $actor['agency_name'];
    $createdByName = $actor['name'];
    $createdById = (int) $actor['id'];

    $txnidEsc = towreal($txnid);
    $amountFmt = number_format($amount, 2, '.', '');
    $firstname = towreal($customer['name'] ?? 'Customer');
    $email = towreal($customer['email'] ?? 'customer@creditlab.in');
    $phone = towreal($customer['mobile'] ?? '9999999999');
    $productinfo = towreal('CLL' . $loanLid . ' Payment');
    $loanInternalEsc = (int) $loanInternalId;
    $uidEsc = (int) $uid;
    $loanLidEsc = (int) $loanLid;
    $agencyIdSql = $agencyId ? (int) $agencyId : 'NULL';
    $agencyNameSql = $agencyName ? "'" . towreal($agencyName) . "'" : 'NULL';

    $pgLinkId = towquery2("INSERT INTO pg_payment_link (txnid, loan_lid, loan_internal_id, uid, link_type, amount, status, created_by_role, created_by_id, created_by_name, agency_id, agency_name)
        VALUES ('$txnidEsc', $loanLidEsc, $loanInternalEsc, $uidEsc, '$linkType', '$amountFmt', 'created', '$role', $createdById, '" . towreal($createdByName) . "', $agencyIdSql, $agencyNameSql)");
    if (!$pgLinkId) {
        $q = towquery("SELECT id FROM pg_payment_link WHERE txnid='$txnidEsc' LIMIT 1");
        $row = $q ? towfetch($q) : null;
        $pgLinkId = $row ? (int) $row['id'] : 0;
    }

    $pgTxOk = @towquery("INSERT INTO pg_transaction (txnid, loan_id, amount, firstname, email, phone, productinfo, status, pg_link_id, link_type, created_by_role, created_by_name, agency_id, agency_name)
        VALUES ('$txnidEsc', $loanInternalEsc, '$amountFmt', '$firstname', '$email', '$phone', '$productinfo', 'initiated', " . (int) $pgLinkId . ", '$linkType', '$role', '" . towreal($createdByName) . "', $agencyIdSql, $agencyNameSql)");
    if (!$pgTxOk) {
        towquery("INSERT INTO pg_transaction (txnid, loan_id, amount, firstname, email, phone, productinfo, status)
            VALUES ('$txnidEsc', $loanInternalEsc, '$amountFmt', '$firstname', '$email', '$phone', '$productinfo', 'initiated')");
    }

    $pay = creditlab_easebuzz_initiate_link([
        'txnid' => $txnid,
        'amount' => $amount,
        'firstname' => $customer['name'] ?? 'Customer',
        'email' => $customer['email'] ?? 'customer@creditlab.in',
        'phone' => $customer['mobile'] ?? '9999999999',
        'productinfo' => 'CLL' . $loanLid,
        'udf1' => (string) $loanLid,
        'udf2' => (string) $pgLinkId,
        'udf3' => $linkType,
        'udf4' => $role,
        'udf5' => substr(preg_replace('/[^a-zA-Z0-9]/', '', $createdByName), 0, 32),
    ]);

    if (!$pay['ok']) {
        towquery("UPDATE pg_payment_link SET status='failed' WHERE txnid='$txnidEsc'");
        towquery("UPDATE pg_transaction SET status='failure' WHERE txnid='$txnidEsc'");
        return ['ok' => false, 'error' => $pay['error'] ?? 'Gateway error'];
    }

    $urlEsc = towreal($pay['payment_url']);
    towquery("UPDATE pg_payment_link SET payment_url='$urlEsc' WHERE txnid='$txnidEsc'");

    $typeLabel = $linkType === 'total_outstanding' ? 'total outstanding' : 'manual';
    return [
        'ok' => true,
        'row' => [
            'id' => (int) $pgLinkId,
            'txnid' => $txnid,
            'loan_id' => 'CLL' . $loanLid,
            'type' => $typeLabel,
            'amount' => $amountFmt,
            'created_by' => $createdByName,
            'payment_url' => $pay['payment_url'],
            'status' => 'created',
        ],
    ];
}

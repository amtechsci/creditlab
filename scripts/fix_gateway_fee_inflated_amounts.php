<?php
require_once __DIR__ . '/../lib/guard_cli.php';
/**
 * Correct settled amounts that were recorded as the gateway-inflated figure.
 *
 * Easebuzz reports both `amount` (the loan repayment) and `net_amount_debit`
 * (`amount` + service_charge + service_tax). The payment webhook used to settle
 * against net_amount_debit, so rows written before that fix carry the gateway's
 * fee as part of the repayment. The raw payloads kept in enach_webhook_inbox are
 * the source of truth for the correct figure.
 *
 * Only amounts are rewritten. Loan status, clearance dates and credit score are
 * left untouched, so a loan that was closed stays closed.
 *
 * Usage:
 *   php scripts/fix_gateway_fee_inflated_amounts.php              # report only
 *   php scripts/fix_gateway_fee_inflated_amounts.php --apply      # write corrections
 *   php scripts/fix_gateway_fee_inflated_amounts.php --days=90    # limit scan window
 *
 * On production (.env readable only by www-data):
 *   sudo -u www-data php scripts/fix_gateway_fee_inflated_amounts.php
 *   sudo -u www-data php scripts/fix_gateway_fee_inflated_amounts.php --apply
 */
date_default_timezone_set('Asia/Kolkata');

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . '/.env';

require_once $projectRoot . '/lib/env.php';
require_once $projectRoot . '/lib/database.php';

if (!is_readable($envPath)) {
    fwrite(STDERR, "Cannot read {$envPath}\n");
    fwrite(STDERR, "Run as the web user: sudo -u www-data php scripts/fix_gateway_fee_inflated_amounts.php\n");
    fwrite(STDERR, "Or export DB_HOST, DB_USER, DB_PASSWORD, DB_NAME in your shell.\n");
    exit(1);
}

$creds = creditlab_db_credentials();
if ($creds['pass'] === '' || $creds['pass'] === null) {
    fwrite(STDERR, "DB_PASSWORD is empty. Check {$envPath} or use: sudo -u www-data php scripts/fix_gateway_fee_inflated_amounts.php\n");
    exit(1);
}

$db = creditlab_db_connect();
if (!$db) {
    fwrite(STDERR, "Database connection failed for user '{$creds['user']}'@'{$creds['host']}'.\n");
    exit(1);
}

$GLOBALS['db'] = $db;
if (!defined('CREDITLAB_SKIP_SESSION')) {
    define('CREDITLAB_SKIP_SESSION', true);
}
require_once $projectRoot . '/db.php';
require_once $projectRoot . '/lib/easebuzz_fee_amounts.php';

$argv = $_SERVER['argv'] ?? [];
$apply = in_array('--apply', $argv, true);
$days = 0;
foreach ($argv as $arg) {
    if (preg_match('/^--days=(\d+)$/', (string) $arg, $m)) {
        $days = (int) $m[1];
    }
}

$tableCheck = towquery("SHOW TABLES LIKE 'enach_webhook_inbox'");
if (!$tableCheck || townum($tableCheck) === 0) {
    fwrite(STDERR, "Table enach_webhook_inbox not found — no archived payloads to reconcile against.\n");
    exit(1);
}

// Paged by id: payload_json holds up to 20KB per row, so the whole table must
// not be buffered at once.
$window = $days > 0 ? "AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)" : '';
$scanned = 0;
$candidates = [];
$afterId = 0;
while (true) {
    $inbox = towquery("SELECT id, payload_json, created_at
        FROM enach_webhook_inbox
        WHERE id > $afterId $window
        ORDER BY id ASC
        LIMIT 500");
    if (!$inbox) {
        fwrite(STDERR, "Failed to read enach_webhook_inbox\n");
        exit(1);
    }
    if (townum($inbox) === 0) {
        break;
    }
    while ($row = towfetch($inbox)) {
        $scanned++;
        $afterId = (int) $row['id'];
        $payload = json_decode((string) $row['payload_json'], true);
        if (!is_array($payload)) {
            continue;
        }
        $post = is_array($payload['post'] ?? null) ? $payload['post'] : [];
        $rawBody = is_string($payload['raw'] ?? null) ? $payload['raw'] : '';
        $candidate = creditlab_fee_extract_candidate($post, $rawBody);
        if ($candidate === null) {
            continue;
        }
        $candidate['seen_at'] = (string) $row['created_at'];
        $key = $candidate['txnid'] !== '' ? 'txn:' . $candidate['txnid'] : 'ref:' . $candidate['bank_ref'];
        $candidates[$key] = $candidate;
    }
    mysqli_free_result($inbox);
}

echo "Scanned $scanned archived webhook payload(s)" . ($days > 0 ? " from the last $days day(s)" : '') . ".\n";
echo "Found " . count($candidates) . " successful payment(s) where the gateway fee inflated the net figure.\n\n";

$plans = [];
foreach ($candidates as $candidate) {
    $updates = [];
    $trueAmount = creditlab_fee_money($candidate['true']);
    $loanId = 0;
    $loanLid = $candidate['loan_lid'];

    if ($candidate['txnid'] !== '') {
        $txnEsc = towreal($candidate['txnid']);
        $pgQ = towquery("SELECT id, loan_id, amount FROM pg_transaction WHERE txnid='$txnEsc' LIMIT 1");
        if ($pgQ && townum($pgQ) > 0) {
            $pgTx = towfetch($pgQ);
            $loanId = (int) $pgTx['loan_id'];
            $stored = creditlab_fee_num($pgTx['amount']);
            if (creditlab_fee_same($stored, $candidate['inflated'])) {
                $updates[] = [
                    'label' => "pg_transaction#{$pgTx['id']}.amount",
                    'from' => creditlab_fee_money((float) $stored),
                    'sql' => "UPDATE pg_transaction SET amount='$trueAmount' WHERE id=" . (int) $pgTx['id'],
                ];
            }
        }
    }

    if ($loanId > 0) {
        $loanQ = towquery("SELECT id, lid, advance_amount FROM loan WHERE id=$loanId LIMIT 1");
        if ($loanQ && townum($loanQ) > 0) {
            $loan = towfetch($loanQ);
            $loanLid = (int) $loan['lid'];
            $advance = creditlab_fee_num($loan['advance_amount']);
            if (creditlab_fee_same($advance, $candidate['inflated'])) {
                $updates[] = [
                    'label' => "loan#{$loan['id']}.advance_amount",
                    'from' => creditlab_fee_money((float) $advance),
                    'sql' => "UPDATE loan SET advance_amount='$trueAmount' WHERE id=" . (int) $loan['id'],
                ];
            }
        }
    } elseif ($loanLid > 0) {
        $loanQ = towquery("SELECT id, advance_amount FROM loan WHERE lid=$loanLid LIMIT 1");
        if ($loanQ && townum($loanQ) > 0) {
            $loan = towfetch($loanQ);
            $advance = creditlab_fee_num($loan['advance_amount']);
            if (creditlab_fee_same($advance, $candidate['inflated'])) {
                $updates[] = [
                    'label' => "loan#{$loan['id']}.advance_amount",
                    'from' => creditlab_fee_money((float) $advance),
                    'sql' => "UPDATE loan SET advance_amount='$trueAmount' WHERE id=" . (int) $loan['id'],
                ];
            }
        }
    }

    // transaction_details keys the payment by bank reference; the txnid is used as
    // a fallback reference by some settlement paths.
    $refs = [];
    foreach ([$candidate['bank_ref'], $candidate['txnid']] as $ref) {
        if ($ref !== '') {
            $refs[] = "'" . towreal($ref) . "'";
        }
    }
    if ($refs !== []) {
        $tdWhere = 'transaction_number IN (' . implode(',', array_unique($refs)) . ')';
        if ($loanLid > 0) {
            $tdWhere .= " AND cllid='" . towreal((string) $loanLid) . "'";
        }
        $tdQ = towquery("SELECT id, transaction_amount FROM transaction_details WHERE $tdWhere");
        if ($tdQ) {
            while ($td = towfetch($tdQ)) {
                $stored = creditlab_fee_num($td['transaction_amount']);
                if (creditlab_fee_same($stored, $candidate['inflated'])) {
                    $updates[] = [
                        'label' => "transaction_details#{$td['id']}.transaction_amount",
                        'from' => creditlab_fee_money((float) $stored),
                        'sql' => "UPDATE transaction_details SET transaction_amount='$trueAmount' WHERE id=" . (int) $td['id'],
                    ];
                }
            }
        }
    }

    if ($updates === []) {
        continue;
    }
    $plans[] = [
        'candidate' => $candidate,
        'loan_lid' => $loanLid,
        'updates' => $updates,
    ];
}

if ($plans === []) {
    echo "No stored amounts match the inflated figure. Nothing to correct.\n";
    exit(0);
}

$overcredited = 0.0;
foreach ($plans as $plan) {
    $candidate = $plan['candidate'];
    $gap = $candidate['inflated'] - $candidate['true'];
    $overcredited += $gap;
    $loanLabel = $plan['loan_lid'] > 0 ? 'CLL' . $plan['loan_lid'] : 'loan unknown';
    $ref = $candidate['txnid'] !== '' ? $candidate['txnid'] : $candidate['bank_ref'];
    echo "{$ref} ({$candidate['source']}, {$loanLabel}, {$candidate['seen_at']})\n";
    echo "  recorded " . creditlab_fee_money($candidate['inflated'])
        . " -> correct " . creditlab_fee_money($candidate['true'])
        . "  (gateway fee " . creditlab_fee_money($gap) . ")\n";
    foreach ($plan['updates'] as $update) {
        echo "    {$update['label']}: {$update['from']} -> " . creditlab_fee_money($candidate['true']) . "\n";
    }
}

echo "\n" . count($plans) . " payment(s) affected, "
    . creditlab_fee_money($overcredited) . " of gateway fee credited as repayment.\n";

if (!$apply) {
    echo "\nDry run only. Re-run with --apply to write the corrections.\n";
    exit(0);
}

mysqli_autocommit($db, false);
$applied = 0;
$failed = 0;
try {
    foreach ($plans as $plan) {
        foreach ($plan['updates'] as $update) {
            if (towquery($update['sql'])) {
                $applied++;
            } else {
                $failed++;
                throw new Exception("Failed: {$update['label']}");
            }
        }
    }
    mysqli_commit($db);
    echo "\nApplied $applied update(s).\n";
} catch (Exception $e) {
    mysqli_rollback($db);
    fwrite(STDERR, "\nRolled back — " . $e->getMessage() . "\n");
    exit(1);
} finally {
    mysqli_autocommit($db, true);
}

exit($failed > 0 ? 1 : 0);

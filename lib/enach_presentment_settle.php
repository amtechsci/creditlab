<?php
/**
 * Poll Autocollect presentment status and auto-close loans on debit success.
 * Complements payment/autocollect_webhook.php when webhooks are delayed/missing.
 */

require_once __DIR__ . '/enach_presentment_policy.php';
require_once __DIR__ . '/easebuzz_autocollect.php';
require_once __DIR__ . '/easebuzz_enach_webhook.php';
require_once __DIR__ . '/easebuzz_enach_user_log.php';
require_once __DIR__ . '/app_url.php';

/**
 * Recover merchant_request_number for older presentment_run rows (pre merchant_ref column).
 */
function creditlab_enach_backfill_merchant_ref_for_run(array $run): string
{
    global $db;
    $existing = trim((string) ($run['merchant_ref'] ?? ''));
    if ($existing !== '') {
        return $existing;
    }
    if (!isset($db)) {
        return '';
    }

    $lid = (int) ($run['lid'] ?? 0);
    if ($lid <= 0) {
        return '';
    }

    $like = mysqli_real_escape_string($db, '%CLL_AUTO_' . $lid . '_%');
    $q = mysqli_query(
        $db,
        "SELECT meta FROM easebuzz_enach_event_log
         WHERE stage='presentment' AND outcome='success'
           AND meta LIKE '$like'
         ORDER BY id DESC LIMIT 10"
    );
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $meta = json_decode((string) ($row['meta'] ?? ''), true);
            if (!is_array($meta)) {
                continue;
            }
            foreach (['merchant_request_number', 'merchant_debit_id', 'merchant_ref'] as $key) {
                $ref = trim((string) ($meta[$key] ?? ''));
                if ($ref !== '' && strpos($ref, 'CLL_AUTO_' . $lid) === 0) {
                    $esc = mysqli_real_escape_string($db, $ref);
                    $id = (int) ($run['id'] ?? 0);
                    if ($id > 0) {
                        mysqli_query($db, "UPDATE enach_presentment_run SET merchant_ref='$esc' WHERE id=$id LIMIT 1");
                    }
                    return $ref;
                }
            }
            $blob = json_encode($meta);
            if (is_string($blob) && preg_match('/CLL_AUTO_' . $lid . '_\d+/', $blob, $m)) {
                $ref = $m[0];
                $esc = mysqli_real_escape_string($db, $ref);
                $id = (int) ($run['id'] ?? 0);
                if ($id > 0) {
                    mysqli_query($db, "UPDATE enach_presentment_run SET merchant_ref='$esc' WHERE id=$id LIMIT 1");
                }
                return $ref;
            }
        }
    }

    return '';
}

/**
 * Ensure pending rows exist for loans still flagged enach_request=1.
 * Creates a stub presentment_run when cron recorded nothing yet (legacy).
 */
function creditlab_enach_ensure_pending_runs_for_open_debits(int $lookback_days = 10): void
{
    global $db;
    if (!isset($db)) {
        return;
    }
    creditlab_enach_ensure_presentment_run_table();
    $lookback_days = max(1, min(60, $lookback_days));
    $since = date('Y-m-d', strtotime('-' . $lookback_days . ' days'));

    $q = mysqli_query(
        $db,
        "SELECT l.lid, l.uid, l.enach_request_date
         FROM loan l
         WHERE l.enach_request=1
           AND l.action <> 'cleared'
           AND (l.status_log IS NULL OR l.status_log <> 'cleared')
           AND l.enach_request_date >= '$since'
         ORDER BY l.lid DESC
         LIMIT 200"
    );
    if (!$q) {
        return;
    }

    while ($loan = mysqli_fetch_assoc($q)) {
        $lid = (int) $loan['lid'];
        $uid = (int) $loan['uid'];
        $ymd = (string) ($loan['enach_request_date'] ?: date('Y-m-d'));
        $chk = mysqli_query(
            $db,
            "SELECT id, merchant_ref FROM enach_presentment_run
             WHERE lid=$lid AND outcome='success'
               AND settlement_status IN ('pending','')
             ORDER BY id DESC LIMIT 1"
        );
        if ($chk && mysqli_num_rows($chk) > 0) {
            $run = mysqli_fetch_assoc($chk);
            if (trim((string) ($run['merchant_ref'] ?? '')) === '') {
                creditlab_enach_backfill_merchant_ref_for_run($run);
            }
            continue;
        }

        $stub = [
            'id' => 0,
            'lid' => $lid,
            'merchant_ref' => '',
        ];
        $ref = creditlab_enach_backfill_merchant_ref_for_run($stub);
        if ($ref === '') {
            continue;
        }
        creditlab_enach_record_presentment(
            $lid,
            $uid,
            'lid:' . $lid,
            'status_backfill',
            0,
            'success',
            $ymd,
            [
                'merchant_ref' => $ref,
                'api' => 'autocollect',
                'settlement_status' => 'pending',
            ]
        );
    }
}

/**
 * Poll pending Autocollect presentments and clear loans on success.
 *
 * @param array{lookback_days?:int,limit?:int,dry_run?:bool,base_url?:string} $opts
 * @return array{checked:int,cleared:int,failed:int,pending:int,errors:int,details:array}
 */
function creditlab_enach_settle_pending_presentments($db, array $opts = []): array
{
    creditlab_enach_ensure_presentment_run_table();

    $lookback_days = max(1, min(60, (int) ($opts['lookback_days'] ?? 10)));
    $limit = max(1, min(500, (int) ($opts['limit'] ?? 100)));
    $dry_run = !empty($opts['dry_run']);
    $base_url = rtrim((string) ($opts['base_url'] ?? creditlab_get_base_url()), '/');
    if ($base_url === '') {
        $base_url = 'https://creditlab.in';
    }

    creditlab_enach_ensure_pending_runs_for_open_debits($lookback_days);

    $since = date('Y-m-d', strtotime('-' . $lookback_days . ' days'));
    $summary = [
        'checked' => 0,
        'cleared' => 0,
        'failed' => 0,
        'pending' => 0,
        'errors' => 0,
        'details' => [],
    ];

    $q = mysqli_query(
        $db,
        "SELECT r.* FROM enach_presentment_run r
         INNER JOIN loan l ON l.lid = r.lid
         WHERE r.outcome='success'
           AND r.settlement_status IN ('pending', '')
           AND r.presented_on >= '$since'
           AND (l.action IS NULL OR l.action <> 'cleared')
           AND (l.status_log IS NULL OR l.status_log <> 'cleared')
         ORDER BY r.id ASC
         LIMIT $limit"
    );
    if (!$q) {
        $summary['errors']++;
        $summary['details'][] = ['error' => 'query_failed', 'message' => mysqli_error($db)];
        return $summary;
    }

    while ($run = mysqli_fetch_assoc($q)) {
        $summary['checked']++;
        $run_id = (int) $run['id'];
        $lid = (int) $run['lid'];
        $merchant_ref = trim((string) ($run['merchant_ref'] ?? ''));
        if ($merchant_ref === '') {
            $merchant_ref = creditlab_enach_backfill_merchant_ref_for_run($run);
        }
        if ($merchant_ref === '') {
            $summary['errors']++;
            $summary['details'][] = [
                'lid' => $lid,
                'run_id' => $run_id,
                'action' => 'missing_merchant_ref',
            ];
            $now = date('Y-m-d H:i:s');
            mysqli_query(
                $db,
                "UPDATE enach_presentment_run SET last_poll_at='$now',
                 poll_error='missing merchant_ref' WHERE id=$run_id LIMIT 1"
            );
            continue;
        }

        if ($dry_run) {
            $summary['pending']++;
            $summary['details'][] = [
                'lid' => $lid,
                'merchant_ref' => $merchant_ref,
                'action' => 'dry_run_would_poll',
            ];
            continue;
        }

        $retrieve = creditlab_autocollect_retrieve_presentment($merchant_ref);
        $event = creditlab_autocollect_parse_presentment_status($retrieve);
        if (!$event) {
            $summary['errors']++;
            $err = 'retrieve_failed http=' . (int) ($retrieve['http_code'] ?? 0);
            if (!empty($retrieve['error'])) {
                $err .= ' ' . $retrieve['error'];
            }
            creditlab_enach_mark_presentment_settled($merchant_ref, 'pending', '', $err);
            $summary['details'][] = [
                'lid' => $lid,
                'merchant_ref' => $merchant_ref,
                'action' => 'retrieve_error',
                'http_code' => $retrieve['http_code'] ?? 0,
            ];
            continue;
        }

        if (trim((string) $event['merchant_ref']) === '') {
            $event['merchant_ref'] = $merchant_ref;
        }

        $outcome = $event['outcome'] ?? 'pending';
        if ($outcome === 'pending') {
            $summary['pending']++;
            creditlab_enach_mark_presentment_settled(
                $merchant_ref,
                'pending',
                (string) ($event['bank_ref_num'] ?? ''),
                'status=' . ($event['raw_status'] ?? 'pending')
            );
            $summary['details'][] = [
                'lid' => $lid,
                'merchant_ref' => $merchant_ref,
                'action' => 'pending',
                'status' => $event['raw_status'] ?? '',
            ];
            continue;
        }

        $result = creditlab_enach_webhook_handle_presentment($db, $event, $base_url);
        $action = (string) ($result['action'] ?? '');

        if (in_array($action, ['cleared', 'already_cleared'], true)) {
            $summary['cleared']++;
            creditlab_enach_mark_presentment_settled(
                $merchant_ref,
                'success',
                (string) ($event['bank_ref_num'] ?? '')
            );
            // Presentment settled — allow future presentments if somehow still open.
            if ($action === 'cleared') {
                mysqli_query($db, "UPDATE loan SET enach_request=0 WHERE lid=$lid LIMIT 1");
            }
        } elseif ($action === 'failure_logged' || $outcome === 'failure') {
            $summary['failed']++;
            creditlab_enach_mark_presentment_settled(
                $merchant_ref,
                'failure',
                (string) ($event['bank_ref_num'] ?? ''),
                (string) ($event['error_message'] ?? 'presentment failed')
            );
            // Failed debit — reset flag so next trigger day can retry.
            mysqli_query($db, "UPDATE loan SET enach_request=0 WHERE lid=$lid AND enach_request=1 LIMIT 1");
        } else {
            $summary['pending']++;
            creditlab_enach_mark_presentment_settled(
                $merchant_ref,
                'pending',
                '',
                'handler=' . $action
            );
        }

        $summary['details'][] = [
            'lid' => $lid,
            'merchant_ref' => $merchant_ref,
            'action' => $action !== '' ? $action : $outcome,
            'message' => $result['message'] ?? '',
            'status' => $event['raw_status'] ?? '',
        ];
    }

    return $summary;
}

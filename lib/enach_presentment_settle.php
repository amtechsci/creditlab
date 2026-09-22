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
 * Persist recovered merchant_ref onto the presentment_run row.
 */
function creditlab_enach_save_run_merchant_ref(int $run_id, string $ref): string
{
    global $db;
    $ref = trim($ref);
    if ($ref === '' || !isset($db)) {
        return $ref;
    }
    if ($run_id > 0) {
        $esc = mysqli_real_escape_string($db, $ref);
        mysqli_query($db, "UPDATE enach_presentment_run SET merchant_ref='$esc' WHERE id=$run_id LIMIT 1");
    }
    return $ref;
}

/**
 * Extract CLL_AUTO_{lid}_{ts} from free text / JSON.
 */
function creditlab_enach_extract_merchant_ref_from_text(string $text, int $lid): string
{
    if ($text === '' || $lid <= 0) {
        return '';
    }
    if (preg_match('/CLL_AUTO_' . $lid . '_\d+/', $text, $m)) {
        return $m[0];
    }
    return '';
}

/**
 * Recover merchant_request_number for older presentment_run rows (pre merchant_ref column).
 * Sources: event log meta_json, webhook inbox, cron/autocollect log files.
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
    $uid = (int) ($run['uid'] ?? 0);
    $run_id = (int) ($run['id'] ?? 0);
    $presented_on = trim((string) ($run['presented_on'] ?? ''));
    if ($lid <= 0) {
        return '';
    }

    $like = mysqli_real_escape_string($db, '%CLL_AUTO_' . $lid . '_%');

    // 1) easebuzz_enach_event_log.meta_json (column is meta_json, not meta)
    $eventSql = "SELECT meta_json FROM easebuzz_enach_event_log
         WHERE stage IN ('presentment','presentment_webhook')
           AND meta_json LIKE '$like'
         ORDER BY id DESC LIMIT 20";
    $q = mysqli_query($db, $eventSql);
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $rawMeta = (string) ($row['meta_json'] ?? '');
            $meta = json_decode($rawMeta, true);
            if (is_array($meta)) {
                foreach (['merchant_request_number', 'merchant_debit_id', 'merchant_ref'] as $key) {
                    $ref = trim((string) ($meta[$key] ?? ''));
                    if ($ref !== '' && strpos($ref, 'CLL_AUTO_' . $lid) === 0) {
                        return creditlab_enach_save_run_merchant_ref($run_id, $ref);
                    }
                }
                $ref = creditlab_enach_extract_merchant_ref_from_text(json_encode($meta) ?: '', $lid);
            } else {
                $ref = creditlab_enach_extract_merchant_ref_from_text($rawMeta, $lid);
            }
            if ($ref !== '') {
                return creditlab_enach_save_run_merchant_ref($run_id, $ref);
            }
        }
    }

    // 1b) Same table by uid + date window (meta may omit CLL_AUTO when stage=presentment used only mandate txn id)
    if ($uid > 0) {
        $dateFilter = '';
        if ($presented_on !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $presented_on)) {
            $from = mysqli_real_escape_string($db, $presented_on . ' 00:00:00');
            $to = mysqli_real_escape_string($db, $presented_on . ' 23:59:59');
            $dateFilter = " AND created_at BETWEEN '$from' AND '$to'";
        }
        $q2 = mysqli_query(
            $db,
            "SELECT meta_json, message FROM easebuzz_enach_event_log
             WHERE uid=$uid AND stage='presentment' AND outcome='success'
             $dateFilter
             ORDER BY id DESC LIMIT 10"
        );
        if ($q2) {
            while ($row = mysqli_fetch_assoc($q2)) {
                $blob = (string) ($row['meta_json'] ?? '') . ' ' . (string) ($row['message'] ?? '');
                $ref = creditlab_enach_extract_merchant_ref_from_text($blob, $lid);
                if ($ref === '') {
                    $meta = json_decode((string) ($row['meta_json'] ?? ''), true);
                    if (is_array($meta)) {
                        foreach (['merchant_request_number', 'merchant_debit_id', 'merchant_ref'] as $key) {
                            $cand = trim((string) ($meta[$key] ?? ''));
                            if ($cand !== '' && (strpos($cand, 'CLL_AUTO_' . $lid) === 0 || strpos($cand, 'CLL_AUTO_') === 0)) {
                                // Accept CLL_AUTO_{lid}_… only
                                if (strpos($cand, 'CLL_AUTO_' . $lid) === 0) {
                                    $ref = $cand;
                                    break;
                                }
                            }
                        }
                    }
                }
                if ($ref !== '') {
                    return creditlab_enach_save_run_merchant_ref($run_id, $ref);
                }
            }
        }
    }

    // 2) enach_webhook_inbox
    $tbl = mysqli_query($db, "SHOW TABLES LIKE 'enach_webhook_inbox'");
    if ($tbl && mysqli_num_rows($tbl) > 0) {
        $lidEsc = mysqli_real_escape_string($db, (string) $lid);
        $qi = mysqli_query(
            $db,
            "SELECT merchant_ref, payload_json FROM enach_webhook_inbox
             WHERE loan_lid='$lidEsc' OR merchant_ref LIKE 'CLL_AUTO_{$lid}_%'
             ORDER BY id DESC LIMIT 20"
        );
        if ($qi) {
            while ($row = mysqli_fetch_assoc($qi)) {
                $ref = trim((string) ($row['merchant_ref'] ?? ''));
                if ($ref === '' || strpos($ref, 'CLL_AUTO_' . $lid) !== 0) {
                    $ref = creditlab_enach_extract_merchant_ref_from_text((string) ($row['payload_json'] ?? ''), $lid);
                }
                if ($ref !== '' && strpos($ref, 'CLL_AUTO_' . $lid) === 0) {
                    return creditlab_enach_save_run_merchant_ref($run_id, $ref);
                }
            }
        }
    }

    // 3) Cron / Autocollect log files around presented_on
    $dates = [];
    if ($presented_on !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $presented_on)) {
        $dates[] = $presented_on;
        $dates[] = date('Y-m-d', strtotime($presented_on . ' +1 day'));
        $dates[] = date('Y-m-d', strtotime($presented_on . ' -1 day'));
    }
    $dates[] = date('Y-m-d');
    $dates = array_values(array_unique($dates));

    $logRoots = [
        dirname(__DIR__) . '/logs',
        dirname(__DIR__),
    ];
    foreach ($dates as $ymd) {
        $candidates = [
            "enach_cron_{$ymd}.log",
            "easebuzz_autocollect_{$ymd}.log",
            "autocollect_api_web.log",
            "autocollect_webhook_{$ymd}.log",
        ];
        foreach ($logRoots as $root) {
            foreach ($candidates as $name) {
                $path = $root . '/' . $name;
                if (!is_readable($path)) {
                    continue;
                }
                // Prefer grep via shell for large logs; fallback to PHP scan of last 2MB.
                $ref = '';
                $pattern = 'CLL_AUTO_' . $lid . '_';
                if (function_exists('shell_exec')) {
                    $cmd = 'grep -o ' . escapeshellarg($pattern . '[0-9]*') . ' ' . escapeshellarg($path) . ' 2>/dev/null | tail -n 1';
                    $out = @shell_exec($cmd);
                    $ref = trim((string) $out);
                }
                if ($ref === '') {
                    $fh = @fopen($path, 'rb');
                    if ($fh) {
                        $size = @filesize($path);
                        if ($size !== false && $size > 2 * 1024 * 1024) {
                            fseek($fh, -2 * 1024 * 1024, SEEK_END);
                        }
                        $chunk = stream_get_contents($fh);
                        fclose($fh);
                        $ref = creditlab_enach_extract_merchant_ref_from_text((string) $chunk, $lid);
                    }
                }
                if ($ref !== '' && strpos($ref, 'CLL_AUTO_' . $lid) === 0) {
                    return creditlab_enach_save_run_merchant_ref($run_id, $ref);
                }
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
            // Real runs: stop re-polling unrecoverable legacy rows (no merchant_ref anywhere).
            // New presentments store merchant_ref at initiate time.
            if (!$dry_run && $run_id > 0) {
                $now = date('Y-m-d H:i:s');
                mysqli_query(
                    $db,
                    "UPDATE enach_presentment_run SET
                        settlement_status='untracked',
                        last_poll_at='$now',
                        poll_error='missing merchant_ref (legacy row; cannot poll status)'
                     WHERE id=$run_id LIMIT 1"
                );
            }
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

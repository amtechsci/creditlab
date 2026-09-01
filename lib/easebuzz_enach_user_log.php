<?php
/**
 * Per-user e-NACH / Autocollect event log (DB) — mandate signup, callback, presentment.
 */

/**
 * Cron scripts (auto_enach) define towquery($db, $sql). Web uses towquery($sql).
 * Always query via mysqli so presentment logging cannot fatal the debit batch.
 */
function creditlab_easebuzz_enach_db_query(string $sql)
{
    global $db;
    if (!isset($db) || !@mysqli_ping($db)) {
        return false;
    }

    return mysqli_query($db, $sql);
}

function creditlab_ensure_easebuzz_enach_event_log_table(): bool
{
    global $db;
    if (!isset($db) || !@mysqli_ping($db)) {
        return false;
    }

    mysqli_query($db, "CREATE TABLE IF NOT EXISTS `easebuzz_enach_event_log` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `uid` int(11) NOT NULL DEFAULT 0,
        `mobile` varchar(15) NOT NULL DEFAULT '',
        `transaction_id` varchar(64) NOT NULL DEFAULT '',
        `stage` varchar(32) NOT NULL DEFAULT '',
        `outcome` varchar(16) NOT NULL DEFAULT 'pending',
        `api` varchar(16) NOT NULL DEFAULT '',
        `auth_mode` varchar(32) NOT NULL DEFAULT '',
        `amount` decimal(12,2) DEFAULT NULL,
        `message` varchar(512) NOT NULL DEFAULT '',
        `meta_json` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_uid` (`uid`),
        KEY `idx_mobile` (`mobile`),
        KEY `idx_transaction_id` (`transaction_id`),
        KEY `idx_outcome` (`outcome`),
        KEY `idx_stage` (`stage`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

/**
 * @param array{uid?:int,mobile?:string,transaction_id?:string,stage:string,outcome:string,api?:string,auth_mode?:string,amount?:float|string,message?:string,meta?:array} $event
 */
function creditlab_easebuzz_log_user_event(array $event): bool
{
    global $db;

    try {
    if (!function_exists('creditlab_easebuzz_normalize_phone')) {
        require_once __DIR__ . '/easebuzz_enach.php';
    }

    if (!creditlab_ensure_easebuzz_enach_event_log_table()) {
        return false;
    }

    $uid = (int) ($event['uid'] ?? 0);
    $mobile = creditlab_easebuzz_normalize_phone($event['mobile'] ?? '');
    if ($mobile === '' && $uid > 0) {
        $uq = creditlab_easebuzz_enach_db_query("SELECT mobile FROM user WHERE id=$uid LIMIT 1");
        if ($uq && mysqli_num_rows($uq) > 0) {
            $ur = mysqli_fetch_assoc($uq);
            $mobile = creditlab_easebuzz_normalize_phone($ur['mobile'] ?? '');
        }
    }

    $transaction_id = trim((string) ($event['transaction_id'] ?? ''));
    $stage = trim((string) ($event['stage'] ?? ''));
    $outcome = strtolower(trim((string) ($event['outcome'] ?? 'pending')));
    if (!in_array($outcome, ['success', 'failure', 'pending'], true)) {
        $outcome = 'pending';
    }

    $api = trim((string) ($event['api'] ?? ''));
    $auth_mode = trim((string) ($event['auth_mode'] ?? ''));
    $message = trim((string) ($event['message'] ?? ''));
    if (strlen($message) > 512) {
        $message = substr($message, 0, 509) . '...';
    }

    $amount_sql = 'NULL';
    if (isset($event['amount']) && $event['amount'] !== '' && $event['amount'] !== null) {
        $amount_sql = number_format((float) $event['amount'], 2, '.', '');
    }

    $meta_json = '';
    if (!empty($event['meta']) && is_array($event['meta'])) {
        $meta_json = json_encode($event['meta'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $uid_sql = (int) $uid;
    $mobile_sql = mysqli_real_escape_string($db, $mobile);
    $txn_sql = mysqli_real_escape_string($db, $transaction_id);
    $stage_sql = mysqli_real_escape_string($db, $stage);
    $outcome_sql = mysqli_real_escape_string($db, $outcome);
    $api_sql = mysqli_real_escape_string($db, $api);
    $auth_sql = mysqli_real_escape_string($db, $auth_mode);
    $msg_sql = mysqli_real_escape_string($db, $message);
    $meta_sql = mysqli_real_escape_string($db, $meta_json);

    $amount_part = $amount_sql === 'NULL' ? 'NULL' : "'" . mysqli_real_escape_string($db, $amount_sql) . "'";

    return (bool) creditlab_easebuzz_enach_db_query("INSERT INTO easebuzz_enach_event_log
        (uid, mobile, transaction_id, stage, outcome, api, auth_mode, amount, message, meta_json)
        VALUES ($uid_sql, '$mobile_sql', '$txn_sql', '$stage_sql', '$outcome_sql', '$api_sql', '$auth_sql', $amount_part, '$msg_sql', '$meta_sql')");
    } catch (Throwable $e) {
        error_log('easebuzz_enach_event_log failed: ' . $e->getMessage());
        return false;
    }
}

function creditlab_easebuzz_outcome_from_user_easebuzz($user_easebuzz): string
{
    $user_easebuzz = (int) $user_easebuzz;
    if ($user_easebuzz === 1) {
        return 'success';
    }
    if ($user_easebuzz === 2) {
        return 'failure';
    }

    return 'pending';
}

if (!function_exists('creditlab_easebuzz_normalize_phone')) {
    $enachLib = __DIR__ . '/easebuzz_enach.php';
    if (is_file($enachLib)) {
        require_once $enachLib;
    }
}
if (!function_exists('creditlab_easebuzz_normalize_phone')) {
    function creditlab_easebuzz_normalize_phone($phone) {
        $phone = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }
        return $phone;
    }
}

/**
 * @return array{rows:array,totals:array{success:int,failure:int,pending:int,all:int}}
 */
function creditlab_easebuzz_query_user_events(array $filters = []): array
{
    global $db;

    if (!creditlab_ensure_easebuzz_enach_event_log_table()) {
        return ['rows' => [], 'totals' => ['success' => 0, 'failure' => 0, 'pending' => 0, 'all' => 0]];
    }

    $where = ['1=1'];
    $uid = (int) ($filters['uid'] ?? 0);
    if ($uid > 0) {
        $where[] = 'uid=' . $uid;
    }

    $mobile = creditlab_easebuzz_normalize_phone($filters['mobile'] ?? '');
    if ($mobile !== '') {
        $where[] = "mobile='" . mysqli_real_escape_string($db, $mobile) . "'";
    }

    $transaction_id = trim((string) ($filters['transaction_id'] ?? ''));
    if ($transaction_id !== '') {
        $where[] = "transaction_id='" . mysqli_real_escape_string($db, $transaction_id) . "'";
    }

    $outcome = strtolower(trim((string) ($filters['outcome'] ?? '')));
    if ($outcome !== '' && $outcome !== 'all') {
        $where[] = "outcome='" . mysqli_real_escape_string($db, $outcome) . "'";
    }

    $stage = trim((string) ($filters['stage'] ?? ''));
    if ($stage !== '' && $stage !== 'all') {
        $where[] = "stage='" . mysqli_real_escape_string($db, $stage) . "'";
    }

    $date_from = trim((string) ($filters['date_from'] ?? ''));
    if ($date_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
        $where[] = "created_at >= '" . mysqli_real_escape_string($db, $date_from) . " 00:00:00'";
    }

    $date_to = trim((string) ($filters['date_to'] ?? ''));
    if ($date_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
        $where[] = "created_at <= '" . mysqli_real_escape_string($db, $date_to) . " 23:59:59'";
    }

    $search = trim((string) ($filters['search'] ?? ''));
    if ($search !== '') {
        $search_sql = mysqli_real_escape_string($db, $search);
        $where[] = "(message LIKE '%$search_sql%' OR transaction_id LIKE '%$search_sql%' OR meta_json LIKE '%$search_sql%')";
    }

    $where_sql = implode(' AND ', $where);
    $limit = max(1, min(2000, (int) ($filters['limit'] ?? 500)));

    $rows = [];
    $q = creditlab_easebuzz_enach_db_query("SELECT * FROM easebuzz_enach_event_log WHERE $where_sql ORDER BY id DESC LIMIT $limit");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $rows[] = $row;
        }
    }

    $totals = ['success' => 0, 'failure' => 0, 'pending' => 0, 'all' => 0];
    $tq = creditlab_easebuzz_enach_db_query("SELECT outcome, COUNT(*) AS cnt FROM easebuzz_enach_event_log WHERE $where_sql GROUP BY outcome");
    if ($tq) {
        while ($tr = mysqli_fetch_assoc($tq)) {
            $o = (string) ($tr['outcome'] ?? '');
            $cnt = (int) ($tr['cnt'] ?? 0);
            if (isset($totals[$o])) {
                $totals[$o] = $cnt;
            }
            $totals['all'] += $cnt;
        }
    }

    return ['rows' => $rows, 'totals' => $totals];
}

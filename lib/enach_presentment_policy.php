<?php
/**
 * eNACH cron presentment policy: triggers, 3/month cap, 4-day gap.
 */
require_once __DIR__ . '/working_calendar.php';
require_once __DIR__ . '/loan_charge_calc.php';

const CREDITLAB_ENACH_MAX_PRESENTMENTS_PER_MONTH = 3;
const CREDITLAB_ENACH_MIN_GAP_DAYS = 4;

function creditlab_enach_ensure_presentment_run_table(): void
{
    global $db;
    if (!isset($db) || !@mysqli_ping($db)) {
        return;
    }
    mysqli_query($db, "CREATE TABLE IF NOT EXISTS `enach_presentment_run` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `lid` int(11) NOT NULL DEFAULT 0,
        `uid` int(11) NOT NULL DEFAULT 0,
        `mandate_id` varchar(64) NOT NULL DEFAULT '',
        `trigger_reason` varchar(32) NOT NULL DEFAULT '',
        `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
        `outcome` varchar(16) NOT NULL DEFAULT 'success',
        `merchant_ref` varchar(128) NOT NULL DEFAULT '',
        `api` varchar(32) NOT NULL DEFAULT '',
        `settlement_status` varchar(16) NOT NULL DEFAULT 'pending',
        `bank_ref` varchar(128) NOT NULL DEFAULT '',
        `poll_error` text,
        `last_poll_at` datetime DEFAULT NULL,
        `settled_at` datetime DEFAULT NULL,
        `presented_on` date NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_mandate_on` (`mandate_id`, `presented_on`),
        KEY `idx_lid_on` (`lid`, `presented_on`),
        KEY `idx_merchant_ref` (`merchant_ref`),
        KEY `idx_settlement` (`settlement_status`, `presented_on`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    creditlab_enach_ensure_presentment_run_columns();
}

/**
 * Add settlement/poll columns on older installs that already have the table.
 */
function creditlab_enach_ensure_presentment_run_columns(): void
{
    global $db;
    if (!isset($db)) {
        return;
    }
    $alters = [
        'merchant_ref' => "ALTER TABLE `enach_presentment_run` ADD COLUMN `merchant_ref` varchar(128) NOT NULL DEFAULT '' AFTER `outcome`",
        'api' => "ALTER TABLE `enach_presentment_run` ADD COLUMN `api` varchar(32) NOT NULL DEFAULT '' AFTER `merchant_ref`",
        'settlement_status' => "ALTER TABLE `enach_presentment_run` ADD COLUMN `settlement_status` varchar(16) NOT NULL DEFAULT 'pending' AFTER `api`",
        'bank_ref' => "ALTER TABLE `enach_presentment_run` ADD COLUMN `bank_ref` varchar(128) NOT NULL DEFAULT '' AFTER `settlement_status`",
        'poll_error' => "ALTER TABLE `enach_presentment_run` ADD COLUMN `poll_error` text AFTER `bank_ref`",
        'last_poll_at' => "ALTER TABLE `enach_presentment_run` ADD COLUMN `last_poll_at` datetime DEFAULT NULL AFTER `poll_error`",
        'settled_at' => "ALTER TABLE `enach_presentment_run` ADD COLUMN `settled_at` datetime DEFAULT NULL AFTER `last_poll_at`",
    ];
    foreach ($alters as $col => $sql) {
        $q = mysqli_query($db, "SHOW COLUMNS FROM `enach_presentment_run` LIKE '" . mysqli_real_escape_string($db, $col) . "'");
        if ($q && mysqli_num_rows($q) === 0) {
            mysqli_query($db, $sql);
        }
    }
    $idx = mysqli_query($db, "SHOW INDEX FROM `enach_presentment_run` WHERE Key_name='idx_merchant_ref'");
    if ($idx && mysqli_num_rows($idx) === 0) {
        mysqli_query($db, "ALTER TABLE `enach_presentment_run` ADD KEY `idx_merchant_ref` (`merchant_ref`)");
    }
    $idx2 = mysqli_query($db, "SHOW INDEX FROM `enach_presentment_run` WHERE Key_name='idx_settlement'");
    if ($idx2 && mysqli_num_rows($idx2) === 0) {
        mysqli_query($db, "ALTER TABLE `enach_presentment_run` ADD KEY `idx_settlement` (`settlement_status`, `presented_on`)");
    }
}

function creditlab_enach_mandate_key(array $easebuzz_row, int $lid): string
{
    $id = trim((string) ($easebuzz_row['customer_authentication_id'] ?? ''));
    if ($id !== '') {
        return $id;
    }
    return 'lid:' . $lid;
}

function creditlab_enach_month_presentment_count(string $mandateId, string $ymd): int
{
    global $db;
    if (!isset($db) || $mandateId === '') {
        return 0;
    }
    $start = date('Y-m-01', strtotime($ymd));
    $end = date('Y-m-t', strtotime($ymd));
    $mid = mysqli_real_escape_string($db, $mandateId);
    $q = mysqli_query(
        $db,
        "SELECT COUNT(*) AS c FROM enach_presentment_run
         WHERE mandate_id='$mid' AND outcome='success'
           AND presented_on BETWEEN '$start' AND '$end'"
    );
    if (!$q) {
        return 0;
    }
    $row = mysqli_fetch_assoc($q);
    return (int) ($row['c'] ?? 0);
}

function creditlab_enach_last_success_presentment_date(string $mandateId): ?string
{
    global $db;
    if (!isset($db) || $mandateId === '') {
        return null;
    }
    $mid = mysqli_real_escape_string($db, $mandateId);
    $q = mysqli_query(
        $db,
        "SELECT MAX(presented_on) AS d FROM enach_presentment_run
         WHERE mandate_id='$mid' AND outcome='success'"
    );
    if (!$q) {
        return null;
    }
    $row = mysqli_fetch_assoc($q);
    $d = $row['d'] ?? null;
    return $d ? (string) $d : null;
}

/**
 * @return array{ok:bool,reason:string}
 */
function creditlab_enach_mandate_may_present(string $mandateId, string $ymd): array
{
    $count = creditlab_enach_month_presentment_count($mandateId, $ymd);
    if ($count >= CREDITLAB_ENACH_MAX_PRESENTMENTS_PER_MONTH) {
        return ['ok' => false, 'reason' => 'max_3_per_month'];
    }
    $last = creditlab_enach_last_success_presentment_date($mandateId);
    if ($last !== null) {
        $gap = (int) ((strtotime($ymd . ' 12:00:00') - strtotime($last . ' 12:00:00')) / 86400);
        if ($gap < CREDITLAB_ENACH_MIN_GAP_DAYS) {
            return ['ok' => false, 'reason' => 'gap_lt_' . CREDITLAB_ENACH_MIN_GAP_DAYS . '_days'];
        }
    }
    return ['ok' => true, 'reason' => ''];
}

/**
 * @param array{merchant_ref?:string,api?:string,settlement_status?:string} $extra
 */
function creditlab_enach_record_presentment(
    int $lid,
    int $uid,
    string $mandateId,
    string $trigger,
    $amount,
    string $outcome,
    string $ymd,
    array $extra = []
): void {
    global $db;
    if (!isset($db)) {
        return;
    }
    creditlab_enach_ensure_presentment_run_table();
    $mid = mysqli_real_escape_string($db, $mandateId);
    $tr = mysqli_real_escape_string($db, $trigger);
    $out = mysqli_real_escape_string($db, $outcome);
    $amt = number_format((float) $amount, 2, '.', '');
    $merchant_ref = mysqli_real_escape_string($db, trim((string) ($extra['merchant_ref'] ?? '')));
    $api = mysqli_real_escape_string($db, trim((string) ($extra['api'] ?? '')));
    $settlement = strtolower(trim((string) ($extra['settlement_status'] ?? '')));
    if ($settlement === '') {
        // API accepted presentment → await bank result; failed initiate is terminal.
        $settlement = ($outcome === 'success') ? 'pending' : 'skipped';
    }
    $settlement = mysqli_real_escape_string($db, $settlement);
    mysqli_query(
        $db,
        "INSERT INTO enach_presentment_run
            (`lid`, `uid`, `mandate_id`, `trigger_reason`, `amount`, `outcome`,
             `merchant_ref`, `api`, `settlement_status`, `presented_on`)
         VALUES ($lid, $uid, '$mid', '$tr', '$amt', '$out',
             '$merchant_ref', '$api', '$settlement', '$ymd')"
    );
}

/**
 * Mark presentment settlement outcome (webhook or status-poll cron).
 */
function creditlab_enach_mark_presentment_settled(
    string $merchant_ref,
    string $settlement_status,
    string $bank_ref = '',
    string $poll_error = ''
): void {
    global $db;
    $merchant_ref = trim($merchant_ref);
    if (!isset($db) || $merchant_ref === '') {
        return;
    }
    creditlab_enach_ensure_presentment_run_table();
    $ref = mysqli_real_escape_string($db, $merchant_ref);
    $st = mysqli_real_escape_string($db, strtolower(trim($settlement_status)));
    $br = mysqli_real_escape_string($db, trim($bank_ref));
    $err = mysqli_real_escape_string($db, substr(trim($poll_error), 0, 1000));
    $now = date('Y-m-d H:i:s');
    $settled_sql = in_array($st, ['success', 'failure'], true)
        ? ", `settled_at`='$now'"
        : '';
    mysqli_query(
        $db,
        "UPDATE enach_presentment_run SET
            `settlement_status`='$st',
            `bank_ref`=IF('$br'='', `bank_ref`, '$br'),
            `poll_error`='$err',
            `last_poll_at`='$now'
            $settled_sql
         WHERE `merchant_ref`='$ref'
         ORDER BY id DESC
         LIMIT 1"
    );
}

function creditlab_enach_salary_day($salaryDate): int
{
    $raw = trim((string) $salaryDate);
    if ($raw === '' || $raw === '0000-00-00') {
        return 0;
    }
    if (preg_match('/^\d{1,2}$/', $raw)) {
        $day = (int) $raw;
        return ($day >= 1 && $day <= 31) ? $day : 0;
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return 0;
    }
    return (int) date('j', $ts);
}

/**
 * Priority: dpd1 > salary_date > last_working_day > seventh
 *
 * @return string|null trigger key
 */
function creditlab_enach_trigger_for_loan(
    array $loan,
    array $loan_apply,
    $salaryDate,
    string $ymd,
    bool $allOverdue = false
): ?string {
    $breakdown = creditlab_enach_presentment_breakdown($loan, $loan_apply);
    if ($breakdown['total'] <= 0) {
        return null;
    }
    $dpd = (int) $breakdown['calendar_dpd'];
    if ($dpd < 1) {
        return null;
    }

    $day = (int) date('j', strtotime($ymd . ' 12:00:00'));
    $isLastWorking = ($ymd === creditlab_enach_last_working_day_of_month($ymd));
    $salaryDay = creditlab_enach_salary_day($salaryDate);
    $isSalary = ($salaryDay > 0 && $salaryDay === $day);

    if ($dpd === 1) {
        return 'dpd1';
    }
    if ($isSalary) {
        return 'salary_date';
    }
    if ($isLastWorking) {
        return 'last_working_day';
    }
    if ($day === 7) {
        return 'seventh';
    }
    if ($allOverdue) {
        return 'catch_up';
    }
    return null;
}

function creditlab_enach_loan_is_skipped(array $loan, string $ymd): bool
{
    if ((int) ($loan['enach_request'] ?? 0) !== 2) {
        return false;
    }
    $type = (string) ($loan['enach_skip_type'] ?? '');
    $until = (string) ($loan['enach_skip_until_date'] ?? '');
    if ($type === 'temporary' && $until !== '' && $until <= $ymd) {
        return false;
    }
    return true;
}

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
        `presented_on` date NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_mandate_on` (`mandate_id`, `presented_on`),
        KEY `idx_lid_on` (`lid`, `presented_on`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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

function creditlab_enach_record_presentment(
    int $lid,
    int $uid,
    string $mandateId,
    string $trigger,
    $amount,
    string $outcome,
    string $ymd
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
    mysqli_query(
        $db,
        "INSERT INTO enach_presentment_run
            (`lid`, `uid`, `mandate_id`, `trigger_reason`, `amount`, `outcome`, `presented_on`)
         VALUES ($lid, $uid, '$mid', '$tr', '$amt', '$out', '$ymd')"
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

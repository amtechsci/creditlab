<?php
/**
 * Working days for eNACH last-working-day presentment.
 * Non-working: Saturday, Sunday, and dates in enach_holidays.
 */

function creditlab_enach_ensure_holiday_table(): void
{
    global $db;
    if (!isset($db) || !@mysqli_ping($db)) {
        return;
    }
    mysqli_query($db, "CREATE TABLE IF NOT EXISTS `enach_holidays` (
        `holiday_date` date NOT NULL,
        `name` varchar(128) NOT NULL DEFAULT '',
        PRIMARY KEY (`holiday_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach (creditlab_enach_default_holidays() as $date => $name) {
        $d = mysqli_real_escape_string($db, $date);
        $n = mysqli_real_escape_string($db, $name);
        mysqli_query($db, "INSERT IGNORE INTO `enach_holidays` (`holiday_date`, `name`) VALUES ('$d', '$n')");
    }
}

/** Seed Indian bank / public holidays (edit enach_holidays in DB as needed). */
function creditlab_enach_default_holidays(): array
{
    return [
        '2026-01-26' => 'Republic Day',
        '2026-03-03' => 'Holi',
        '2026-03-31' => 'Ramzan Id',
        '2026-04-03' => 'Good Friday',
        '2026-04-14' => 'Dr Ambedkar Jayanti',
        '2026-05-01' => 'Maharashtra Day',
        '2026-08-15' => 'Independence Day',
        '2026-10-02' => 'Gandhi Jayanti',
        '2026-10-20' => 'Dussehra',
        '2026-11-08' => 'Diwali',
        '2026-12-25' => 'Christmas',
        '2027-01-26' => 'Republic Day',
        '2027-03-22' => 'Holi',
        '2027-03-21' => 'Ramzan Id',
        '2027-03-26' => 'Good Friday',
        '2027-04-14' => 'Dr Ambedkar Jayanti',
        '2027-05-01' => 'Maharashtra Day',
        '2027-08-15' => 'Independence Day',
        '2027-10-02' => 'Gandhi Jayanti',
        '2027-10-09' => 'Dussehra',
        '2027-10-29' => 'Diwali',
        '2027-12-25' => 'Christmas',
    ];
}

function creditlab_enach_holiday_dates(): array
{
    static $dates = null;
    if ($dates !== null) {
        return $dates;
    }
    $dates = [];
    global $db;
    if (isset($db) && @mysqli_ping($db)) {
        $q = mysqli_query($db, 'SELECT holiday_date FROM enach_holidays');
        if ($q) {
            while ($row = mysqli_fetch_assoc($q)) {
                $dates[$row['holiday_date']] = true;
            }
        }
    }
    if ($dates === []) {
        foreach (array_keys(creditlab_enach_default_holidays()) as $d) {
            $dates[$d] = true;
        }
    }
    return $dates;
}

function creditlab_is_enach_working_day(string $ymd): bool
{
    $ts = strtotime($ymd . ' 12:00:00');
    if ($ts === false) {
        return false;
    }
    $dow = (int) date('N', $ts);
    if ($dow >= 6) {
        return false;
    }
    $holidays = creditlab_enach_holiday_dates();
    return empty($holidays[$ymd]);
}

/** Last Sat/Sun/holiday-excluded date in the month of $ymd. */
function creditlab_enach_last_working_day_of_month(string $ymd): string
{
    $last = date('Y-m-t', strtotime($ymd . ' 12:00:00'));
    $guard = 0;
    while (!creditlab_is_enach_working_day($last) && $guard < 31) {
        $last = date('Y-m-d', strtotime($last . ' -1 day'));
        $guard++;
    }
    return $last;
}

<?php

/**
 * zzautoloanamountcalculator.php
 *
 * This script calculates and updates loan service charges and penalties.
 * It is designed to be run as a frequent cron job.
 *
 * @version 2.1
 * @author Fixed for cron execution
 *
 * CHANGES:
 * - Use existing db.php configuration
 * - Fixed function conflicts
 * - Added proper error logging
 * - Added cron-specific error handling
 */

// --- Configuration and Setup ---

// Enable error reporting for debugging cron job issues.

// Set the correct timezone for date calculations.
date_default_timezone_set('Asia/Kolkata');

if (php_sapi_name() === 'cli' && !defined('CREDITLAB_SKIP_SESSION')) {
    define('CREDITLAB_SKIP_SESSION', true);
}

$autocalc_log_file = __DIR__ . '/logs/autocalculator_cron.log';
function autocalc_log(string $message): void
{
    global $autocalc_log_file;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    error_log($line);
    $dir = dirname($autocalc_log_file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($autocalc_log_file, $line . "\n", FILE_APPEND | LOCK_EX);
}

// Include the existing database configuration
require_once 'db.php';
require_once __DIR__ . '/lib/loan_charge_calc.php';

// --- Database Connection ---

// Use the existing database connection from db.php
if (!isset($db) || !$db) {
    autocalc_log('FATAL: Database connection failed (check .env readable by cron user www-data)');
    exit(1);
}


// --- Helper Functions (Refactored) ---

/**
 * Executes a database query using the existing connection.
 * @param string $query The SQL query string.
 * @return mysqli_result|bool The result object or false on failure.
 */
function cron_query($query)
{
    global $db;
    $result = mysqli_query($db, $query);
    if (!$result) {
        error_log("Cron Job Query Error: " . mysqli_error($db) . " - Query: " . $query);
    }
    return $result;
}

/**
 * Fetches a result row as a numeric array.
 * @param mysqli_result $query_result The result object from mysqli_query.
 * @return array|null The fetched row or null.
 */
function cron_fetch($query_result)
{
    return mysqli_fetch_array($query_result);
}


// --- Main Script Logic ---

$date = date('Y-m-d');

// OPTIMIZATION: Combined two queries into one using an INNER JOIN.
// This fetches all required loan and user data in a single, efficient database call,
// eliminating the "N+1 query problem" from the original script.
$loan_data_query_template = "
    SELECT
        loan.*,
        loan_apply.interest_percentage,
        loan_apply.days,
        user.approvenew,
        user.star_member
    FROM
        loan
    INNER JOIN loan_apply ON loan_apply.id = loan.lid
    INNER JOIN `user` ON `user`.id = loan.uid
    WHERE
        (loan.status_log = 'account manager' OR loan.status_log = 'recovery officer')
    AND
        (loan.last_cal_date IS NULL OR loan.last_cal_date < '$date')
    ORDER BY
        COALESCE(loan.last_cal_date, '1970-01-01') ASC,
        loan.id ASC
    LIMIT 500";

autocalc_log('Starting loan calculation');

$processed_count = 0;

// Process all loans due today (batched); oldest last_cal_date first so overdue loans are not starved.
do {
    $batch_count = 0;
    $loan_data = cron_query($loan_data_query_template);
    if (!$loan_data) {
        autocalc_log('FATAL: Main loan query failed');
        exit(1);
    }

    while ($loan_fetch = cron_fetch($loan_data)) {
        extract($loan_fetch, EXTR_PREFIX_ALL, 'users');

        $loan_apply_days = isset($users_days) && $users_days > 0 ? (int) $users_days : 30;
        $loan_row = [
            'is_emi' => isset($users_is_emi) ? $users_is_emi : 0,
            'total_time' => isset($users_total_time) ? $users_total_time : 0,
        ];

        $calc = creditlab_calculate_loan_charges(
            (string) $users_processed_date,
            (float) $users_processed_amount,
            (float) $users_p_fee,
            $users_interest_percentage,
            $loan_apply_days,
            $loan_row
        );

        $day = $calc['exhausted_period'];
        $service_charge = $calc['service_charge'];
        $penality = $calc['penality_charge'];
        $users_id = (int) $users_id;

        $update_query = "UPDATE `loan` SET
                        `exhausted_period` = '$day',
                        `service_charge` = '$service_charge',
                        `penality_charge` = '$penality',
                        `last_cal_date` = '$date'
                    WHERE `id` = '$users_id'";

        $update_result = cron_query($update_query);
        if ($update_result) {
            $processed_count++;
            $batch_count++;
            if ($processed_count <= 3 || $calc['dpd'] > 0) {
                autocalc_log("Updated loan id=$users_id CLL$users_lid DPD={$calc['dpd']} penalty=$penality");
            }
        } else {
            autocalc_log("ERROR: Failed to update loan id=$users_id");
        }
    }
} while ($batch_count >= 500);

// --- Cleanup ---

autocalc_log("Completed: $processed_count rows updated for date=$date");

echo "$processed_count rows updated\n";

// Close the single database connection at the end of the script.
// mysqli_close($db);
?>

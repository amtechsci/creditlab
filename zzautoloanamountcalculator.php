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
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

// Set the correct timezone for date calculations.
date_default_timezone_set('Asia/Kolkata');

// Include the existing database configuration
require_once 'db.php';

// --- Database Connection ---

// Use the existing database connection from db.php
if (!isset($db) || !$db) {
    error_log("Cron Job Error: Database connection failed at " . date('Y-m-d H:i:s'));
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
$loan_data_query = "
    SELECT
        loan.*,
        loan_apply.interest_percentage,
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
        loan.id DESC
    LIMIT 5";

// Execute the main query using the single database connection.
$loan_data = cron_query($loan_data_query);

if (!$loan_data) {
    error_log("Cron Job Error: Main loan query failed at " . date('Y-m-d H:i:s'));
    exit(1);
}

// Log start of processing
error_log("Cron Job: Starting loan calculation at " . date('Y-m-d H:i:s'));

$processed_count = 0;

// Loop through each loan to perform calculations.
while ($loan_fetch = cron_fetch($loan_data)) {
    // Extract variables with a 'users_' prefix (e.g., $users_id, $users_uid, etc.)
    // This now includes $users_approvenew and $users_star_member directly from the JOIN.
    extract($loan_fetch, EXTR_PREFIX_ALL, "users");

    // --- Core Calculation Logic (Copied from original script) ---
    $stop_date = date_create($users_processed_date);
    $sa = date_create(date('Y-m-d 23:59:59'));
    $aa = date_diff($stop_date, $sa);
    $days = $aa->format("%a");
    $t = $users_processed_amount + $users_p_fee + ($users_p_fee * 0.18);
    $days++;
    $day =  $days;
    $service_charge = 0;

    if ($users_interest_percentage == 1) {
        if ($days >= 3) {
            $fee = $t * 3 / 100 * 0;
            $interest = "0%";
            $days = $days - 3;
            $service_charge += $fee;
        } else {
            $fee = $t * $days / 100 * 0;
            $interest = "0%";
            $days = 0;
            $service_charge += $fee;
        }
        if (($days) >= 7) {
            $fee = $t * 7 / 100 * 0.1;
            $interest = "0.1%";
            $days = $days - 7;
            $service_charge += $fee;
        } else {
            $fee = $t * $days / 100 * 0.1;
            $interest = "0.1%";
            $days = 0;
            $service_charge += $fee;
        }
        if (($days) >= 20) {
            $fee = $t * 20 / 100 * 0.115;
            $interest = "0.115%";
            $days = $days - 20;
            $service_charge += $fee;
        } else {
            $fee = $t * $days / 100 * 0.115;
            $interest = "0.115%";
            $days = 0;
            $service_charge += $fee;
        }
        if (($days) >= 1) {
            $fee = $t * $days / 100 * 0.1;
            $interest = "0.1%";
            $service_charge += $fee;
            $days = 0;
        }
    } else {
        $fee = $t * $day / 100 * $users_interest_percentage;
        $interest = "$users_interest_percentage%";
        $service_charge += $fee;
    }

    if ($day > 30) {
        $penalitydays = $day - 30;
        $penalitydays--;
        $penality = (($t) / 100) * 4;
        $atnp = ((($t) / 100) * 0.2) * $penalitydays;
        $penality = $penality + $atnp;
    } else {
        $penality = 0;
    }
    $penality = ($penality + ($penality * 0.18));

    // --- Update the loan record in the database ---
    $update_query = "UPDATE `loan` SET
                        `exhausted_period` = '$day',
                        `service_charge` = '$service_charge',
                        `penality_charge` = '$penality',
                        `last_cal_date` = '$date'
                    WHERE `id` = '$users_id'";

    // Execute the update query and check for success.
    $update_result = cron_query($update_query);
    if ($update_result) {
        $processed_count++;
        error_log("Cron Job: Updated loan ID $users_id - Service Charge: $service_charge, Penalty: $penality");
    } else {
        error_log("Cron Job Error: Failed to update loan ID $users_id");
    }
}

// --- Cleanup ---

// Log completion
error_log("Cron Job: Completed processing $processed_count loans at " . date('Y-m-d H:i:s'));

// Print result for cron output
echo "$processed_count rows updated\n";

// Close the single database connection at the end of the script.
mysqli_close($db);

?>

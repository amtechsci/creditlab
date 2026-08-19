<?php
/**
 * Loan clearance after successful e-NACH presentment (shared by easebuzz_webhook + autocollect_webhook).
 */

function creditlab_enach_settlement_query($db, $query)
{
    $result = mysqli_query($db, $query);
    if (!$result) {
        error_log('eNACH settlement query failed: ' . mysqli_error($db) . ' — ' . $query);
        return false;
    }
    return $result;
}

function creditlab_enach_settlement_num($query_result)
{
    return mysqli_num_rows($query_result);
}

function creditlab_enach_settlement_fetch($query_result)
{
    return mysqli_fetch_array($query_result);
}

function creditlab_enach_calculate_credit_score_points($dpd)
{
    if ($dpd > 0) {
        if ($dpd > 30) {
            return -50;
        }
        if ($dpd > 10) {
            return -8;
        }
        return 2;
    }
    return 8;
}

/**
 * Clear loan after successful auto-debit presentment.
 */
function creditlab_enach_process_loan_clearance($db, $loan_lid, $uid, $amount, $bank_ref_num, $transaction_flow = 'full')
{
    mysqli_autocommit($db, false);

    try {
        $loan_lid_escaped = mysqli_real_escape_string($db, (string) $loan_lid);
        $loan_data = creditlab_enach_settlement_query($db, "SELECT * FROM loan WHERE lid='$loan_lid_escaped'");
        if (!$loan_data || creditlab_enach_settlement_num($loan_data) === 0) {
            throw new Exception("Loan not found: $loan_lid");
        }
        $loan_details = creditlab_enach_settlement_fetch($loan_data);

        $loan_apply_data = creditlab_enach_settlement_fetch(
            creditlab_enach_settlement_query($db, "SELECT days FROM loan_apply WHERE id='$loan_lid_escaped'")
        );
        $loan_days = isset($loan_apply_data['days']) && (int) $loan_apply_data['days'] > 0
            ? (int) $loan_apply_data['days']
            : 30;

        $dpd = (int) $loan_details['exhausted_period'] - $loan_days;
        $point = creditlab_enach_calculate_credit_score_points($dpd);

        $chf_data = creditlab_enach_settlement_query($db, "SELECT * FROM pay_ref WHERE loan_id='$loan_lid_escaped'");
        if ($chf_data && creditlab_enach_settlement_num($chf_data) > 0) {
            $chf = creditlab_enach_settlement_fetch($chf_data);
            if ($chf && isset($chf['is_emi']) && (int) $chf['is_emi'] === 1) {
                if (!creditlab_enach_settlement_query($db, "UPDATE `loan` SET `semi`=1,`femi`=1 WHERE lid='$loan_lid_escaped'")) {
                    throw new Exception('Failed to update EMI status');
                }
            }
        }

        $uid_escaped = mysqli_real_escape_string($db, (string) $uid);
        $point_escaped = mysqli_real_escape_string($db, (string) $point);
        if (!creditlab_enach_settlement_query($db, "UPDATE `user` SET `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point_escaped WHERE id='$uid_escaped'")) {
            throw new Exception('Failed to update user credit score');
        }

        $current_date_escaped = mysqli_real_escape_string($db, date('Y-m-d'));
        if (!creditlab_enach_settlement_query($db, "UPDATE `loan` SET `action`='cleared',`status_log`='cleared',`cleard_date`='$current_date_escaped' WHERE lid='$loan_lid_escaped'")) {
            throw new Exception('Failed to clear loan');
        }
        if (!creditlab_enach_settlement_query($db, "UPDATE `user` SET `status`='cleared' WHERE id='$uid_escaped'")) {
            throw new Exception('Failed to clear user status');
        }
        if (!creditlab_enach_settlement_query($db, "UPDATE `loan_apply` SET `status`='cleared' WHERE id='$loan_lid_escaped'")) {
            throw new Exception('Failed to clear loan application');
        }
        if (!creditlab_enach_settlement_query($db, "DELETE FROM `pay_ref` WHERE `loan_id`='$loan_lid_escaped'")) {
            throw new Exception('Failed to delete payment references');
        }

        $bank_ref_num_escaped = mysqli_real_escape_string($db, (string) $bank_ref_num);
        $amount_escaped = mysqli_real_escape_string($db, (string) $amount);
        $transaction_flow_escaped = mysqli_real_escape_string($db, (string) $transaction_flow);
        $current_datetime_escaped = mysqli_real_escape_string($db, date('Y-m-d H:i:s'));
        if (!creditlab_enach_settlement_query($db, "INSERT INTO `transaction_details`(`uid`, `cllid`, `transaction_number`, `transaction_date`, `transaction_amount`, `transaction_flow`) VALUES ('$uid_escaped', '$loan_lid_escaped', '$bank_ref_num_escaped', '$current_datetime_escaped', '$amount_escaped', '$transaction_flow_escaped')")) {
            throw new Exception('Failed to insert transaction details');
        }

        mysqli_commit($db);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($db);
        error_log("eNACH loan clearance failed for CLL$loan_lid: " . $e->getMessage());
        return false;
    } finally {
        mysqli_autocommit($db, true);
    }
}

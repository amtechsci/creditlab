<?php
require_once __DIR__ . '/lib/guard_cli.php';
/**
 * Replay missed Easebuzz webhooks from webhook_data.txt (CLI only).
 */
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/lib/database.php';
$db = creditlab_db_connect();
if (!$db) {
	fwrite(STDERR, "Database connection failed\n");
	exit(1);
}

echo "Database connection successful.\n\n";

$logFilename = __DIR__ . '/webhook_data.txt';
$logContent = file_get_contents($logFilename);

if ($logContent === false) {
	fwrite(STDERR, "Could not read log file\n");
	exit(1);
}

$requests = explode('=== New Request ===', $logContent);
$requestCount = 0;

foreach ($requests as $requestBlock) {
	if (trim($requestBlock) === '') {
		continue;
	}

	$rawBodyPos = strpos($requestBlock, 'Raw Body:');
	if ($rawBodyPos === false) {
		continue;
	}

	$rawBodyString = trim(substr($requestBlock, $rawBodyPos + strlen('Raw Body:')));
	parse_str($rawBodyString, $data);

	if (empty($data) || !isset($data['txnid'])) {
		echo "Skipping a log entry with no transaction ID.\n";
		continue;
	}

	$requestCount++;
	$txnid = $data['txnid'];
	echo "--------------------------------------------------\n";
	echo "Processing Transaction ID: $txnid\n";

	if (isset($data['authorization_status'])) {
		$status = $data['status'];
		$update_status = strtolower($data['authorization_status']);
		$user_easebuzz_status = ($update_status === 'accepted') ? 1 : 2;
		if ($status === 'failure') {
			$user_easebuzz_status = 0;
		}

		$stmt_check = mysqli_prepare($db, "SELECT uid FROM easebuzz_adtd WHERE txnid = ? AND authorization_status IS NOT NULL AND authorization_status != ''");
		mysqli_stmt_bind_param($stmt_check, 's', $txnid);
		mysqli_stmt_execute($stmt_check);
		$result_check = mysqli_stmt_get_result($stmt_check);

		if (mysqli_num_rows($result_check) > 0) {
			echo "Skipping: Authorization status already set.\n";
			continue;
		}

		$stmt1 = mysqli_prepare($db, "UPDATE easebuzz_adtd SET authorization_status = ?, net_amount_debit = ?, bank_ref_num = ?, easepayid = ?, status = ?, error_message = ?, auto_debit_access_key = ? WHERE txnid = ?");
		mysqli_stmt_bind_param(
			$stmt1,
			'ssssssss',
			$update_status,
			$data['net_amount_debit'],
			$data['bank_ref_num'],
			$data['easepayid'],
			$data['status'],
			$data['error_Message'],
			$data['auto_debit_access_key'],
			$txnid
		);

		if (mysqli_stmt_execute($stmt1)) {
			echo "Updated authorization status in easebuzz_adtd.\n";
		} else {
			echo 'Error updating easebuzz_adtd: ' . mysqli_error($db) . "\n";
		}
	} elseif (isset($data['furl']) && strpos($data['furl'], 'payeasebuzz/response.php') !== false) {
		$status = $data['status'];

		if ($status == 'success') {
			$stmt_check = mysqli_prepare($db, "SELECT loan_id FROM pg_transaction WHERE txnid = ? AND status != 'success'");
			mysqli_stmt_bind_param($stmt_check, 's', $txnid);
			mysqli_stmt_execute($stmt_check);
			$pg_transaction = mysqli_stmt_get_result($stmt_check);

			if (mysqli_num_rows($pg_transaction) > 0) {
				$pg_data = mysqli_fetch_assoc($pg_transaction);
				$cllid = $pg_data['loan_id'];

				$loan_data = mysqli_query($db, "SELECT * FROM loan WHERE id='$cllid'");
				$loan_details = mysqli_fetch_assoc($loan_data);
				$uid = $loan_details['uid'];

				if (isset($loan_details['action']) && $loan_details['action'] === 'cleared') {
					echo "Loan (ID: $cllid) already cleared. Skipping loan/user updates.\n";
				} else {
					echo "Loan (ID: $cllid) not cleared. Proceeding with updates.\n";

					$dpd = $loan_details['exhausted_period'] - 30;
					$point = ($dpd > 0) ? (($dpd > 30) ? -50 : (($dpd > 10) ? -8 : 2)) : 8;

					mysqli_query($db, "UPDATE `user` SET `sloan`=`sloan`+1, `credit_score`=`credit_score`+$point WHERE id=" . (int) $uid);
					mysqli_query($db, "UPDATE `loan` SET `action`='cleared', `status_log`='cleared', `cleard_date`='" . date('Y-m-d') . "' WHERE id=" . (int) $loan_details['id']);
				}

				$payment_method = isset($data['mode']) ? $data['mode'] : 'N/A';
				$amount = mysqli_real_escape_string($db, $data['amount']);
				$bank_ref_num = mysqli_real_escape_string($db, $data['bank_ref_num']);

				mysqli_query($db, "UPDATE `pg_transaction` SET `status`='success', `amount`='$amount', `payment_method`='$payment_method', `bank_reference_number`='$bank_ref_num' WHERE txnid='$txnid'");

				echo "Updated pg_transaction for $txnid.\n";
			} else {
				echo "Skipping: Transaction already successful.\n";
			}
		} else {
			$error_msg = mysqli_real_escape_string($db, $data['error_Message']);
			mysqli_query($db, "UPDATE `pg_transaction` SET `status`='failure', `error_message`='$error_msg' WHERE txnid='$txnid'");
			echo "Marked transaction as failed.\n";
		}
	} else {
		echo "Skipping: Unrecognized callback type.\n";
	}
}

echo "--------------------------------------------------\n";
echo "Processing complete. Processed $requestCount log entries.\n";

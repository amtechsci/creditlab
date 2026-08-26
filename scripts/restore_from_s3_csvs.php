<?php
require_once __DIR__ . '/../lib/guard_cli.php';
/**
 * Overlay S3 daily/half-month report CSVs onto a restored Sep 2025 dump.
 *
 * Usage:
 *   DB_NAME=credit_restore php scripts/restore_from_s3_csvs.php [csv_dir]
 *
 * Default csv_dir: project/zzre
 *
 * Processes (idempotent):
 *   1. applied_*     → upsert user + pending loan_apply
 *   2. bs_disbursal_* → upsert user + loan_apply + loan (disbursed)
 *   3. bs_repayment_* → insert transaction_details (settlement/part/full/…)
 *   4. cleared_* / settlement_* → mark loans cleared/settled (by lid)
 *   5. default_*     → mark open loans overdue (prefer latest default file)
 *   6. Account_manager_data_* → backfill mobile/email by loan id
 *      (S3 CIBIL/bs_* reports often ship with empty Telephone/Mobile columns)
 */
date_default_timezone_set('Asia/Kolkata');

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/lib/env.php';
require_once $projectRoot . '/lib/database.php';
require_once $projectRoot . '/lib/auth.php';

$csvDir = $argv[1] ?? ($projectRoot . '/zzre');
$csvDir = rtrim($csvDir, '/');
if (!is_dir($csvDir)) {
    fwrite(STDERR, "CSV directory not found: {$csvDir}\n");
    exit(1);
}

// Prefer restore DB unless caller already set DB_NAME
if (getenv('DB_NAME') === false || getenv('DB_NAME') === '') {
    putenv('DB_NAME=credit_restore');
    $_ENV['DB_NAME'] = 'credit_restore';
}
// Homebrew PHP often lacks the XAMPP socket; TCP works with XAMPP MariaDB
if (getenv('DB_HOST') === false || getenv('DB_HOST') === '' || getenv('DB_HOST') === 'localhost') {
    putenv('DB_HOST=127.0.0.1');
    $_ENV['DB_HOST'] = '127.0.0.1';
}

$db = creditlab_db_connect();
if (!$db) {
    fwrite(STDERR, "Database connection failed. Check DB_* / .env (target DB_NAME=" . env('DB_NAME', '') . ")\n");
    exit(1);
}
$GLOBALS['db'] = $db;
mysqli_query($db, "SET NAMES utf8mb4");
mysqli_query($db, "SET sql_mode = 'NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

$stats = [
    'files' => 0,
    'users_inserted' => 0,
    'users_updated' => 0,
    'loan_apply_inserted' => 0,
    'loan_apply_updated' => 0,
    'loan_inserted' => 0,
    'loan_updated' => 0,
    'tx_inserted' => 0,
    'tx_skipped' => 0,
    'cleared' => 0,
    'settled' => 0,
    'defaulted' => 0,
    'pending_apply' => 0,
    'mobile_backfilled' => 0,
    'errors' => 0,
];

$amCache = [];
$res = mysqli_query($db, "SELECT id, name FROM account_manager");
while ($row = mysqli_fetch_assoc($res)) {
    $amCache[strtolower(trim($row['name']))] = (int) $row['id'];
}

$placeholderPassword = creditlab_hash_password('RestoreTemp!' . bin2hex(random_bytes(4)));

function restore_log(string $msg): void
{
    echo '[' . date('H:i:s') . "] {$msg}\n";
}

function restore_esc(mysqli $db, $v): string
{
    return mysqli_real_escape_string($db, (string) $v);
}

function restore_read_csv(string $path): array
{
    $fh = fopen($path, 'r');
    if (!$fh) {
        throw new RuntimeException("Cannot open {$path}");
    }
    $header = fgetcsv($fh, 0, ',', '"', '\\');
    if (!$header) {
        fclose($fh);
        return [[], []];
    }
    $header = array_map(static function ($h) {
        return trim(str_replace("\xEF\xBB\xBF", '', (string) $h));
    }, $header);
    $rows = [];
    while (($row = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
        if (count($row) === 1 && trim((string) $row[0]) === '') {
            continue;
        }
        if (!array_filter($row, static fn($c) => trim((string) $c) !== '')) {
            continue;
        }
        // Pad short rows
        while (count($row) < count($header)) {
            $row[] = '';
        }
        $assoc = [];
        foreach ($header as $i => $key) {
            $assoc[$key] = isset($row[$i]) ? trim((string) $row[$i], " \t\n\r\0\x0B\"") : '';
            $assoc[$key] = ltrim($assoc[$key], "\t");
        }
        $rows[] = $assoc;
    }
    fclose($fh);
    return [$header, $rows];
}

function restore_parse_cll(?string $voucher): ?int
{
    if ($voucher === null || $voucher === '') {
        return null;
    }
    if (preg_match('/CLL\s*(\d+)/i', $voucher, $m)) {
        return (int) $m[1];
    }
    if (preg_match('/^\d+$/', $voucher)) {
        return (int) $voucher;
    }
    return null;
}

/** Normalize Indian mobiles to 10 digits when possible. */
function restore_normalize_mobile(?string $raw): string
{
    $m = preg_replace('/\D+/', '', (string) $raw);
    if ($m === null || $m === '') {
        return '';
    }
    if (strlen($m) >= 12 && str_starts_with($m, '91')) {
        $m = substr($m, -10);
    } elseif (strlen($m) === 11 && str_starts_with($m, '0')) {
        $m = substr($m, 1);
    }
    return $m;
}

/**
 * Set contact fields on an existing user without clobbering good data.
 * Respects UNIQUE(mobile): skips if another user already owns that number.
 */
function restore_backfill_user_contact(
    mysqli $db,
    array &$stats,
    int $uid,
    string $mobile,
    string $altmobile = '',
    string $email = '',
    string $altemail = '',
    string $name = ''
): void {
    $mobile = restore_normalize_mobile($mobile);
    $altmobile = restore_normalize_mobile($altmobile);
    $email = trim($email);
    $altemail = trim($altemail);
    $name = trim($name);

    $q = mysqli_query($db, "SELECT id, name, mobile, altmobile, email, altemail FROM user WHERE id = {$uid} LIMIT 1");
    $u = $q ? mysqli_fetch_assoc($q) : null;
    if (!$u) {
        return;
    }

    $sets = [];
    if ($mobile !== '') {
        $cur = trim((string) ($u['mobile'] ?? ''));
        if ($cur === '') {
            $mEsc = restore_esc($db, $mobile);
            $dup = mysqli_query($db, "SELECT id FROM user WHERE mobile = '{$mEsc}' AND id != {$uid} LIMIT 1");
            if (!$dup || mysqli_num_rows($dup) === 0) {
                $sets[] = "mobile = '{$mEsc}'";
            }
        }
    }
    if ($altmobile !== '' && trim((string) ($u['altmobile'] ?? '')) === '') {
        $sets[] = "altmobile = '" . restore_esc($db, $altmobile) . "'";
    }
    if ($email !== '' && trim((string) ($u['email'] ?? '')) === '') {
        $sets[] = "email = '" . restore_esc($db, $email) . "'";
    }
    if ($altemail !== '' && trim((string) ($u['altemail'] ?? '')) === '') {
        $sets[] = "altemail = '" . restore_esc($db, $altemail) . "'";
    }
    if ($name !== '' && trim((string) ($u['name'] ?? '')) === '') {
        $sets[] = "name = '" . restore_esc($db, $name) . "'";
        $sets[] = "pan_name = '" . restore_esc($db, $name) . "'";
    }
    if (!$sets) {
        return;
    }
    if (!mysqli_query($db, 'UPDATE user SET ' . implode(', ', $sets) . " WHERE id = {$uid}")) {
        $stats['errors']++;
        restore_log("CONTACT UPDATE FAIL uid={$uid}: " . mysqli_error($db));
        return;
    }
    $stats['mobile_backfilled']++;
    $stats['users_updated']++;
}

function restore_parse_date(?string $raw): ?string
{
    $raw = trim((string) $raw);
    $raw = ltrim($raw, "\t");
    if ($raw === '' || $raw === '0000-00-00') {
        return null;
    }
    // DD-MM-YYYY or DD/MM/YYYY
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $raw, $m)) {
        return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
    }
    // DDMMYYYY (CIBIL)
    if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $raw, $m)) {
        return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
    }
    $ts = strtotime($raw);
    if ($ts) {
        return date('Y-m-d', $ts);
    }
    return null;
}

function restore_find_user_by_rcid(mysqli $db, string $rcid): ?array
{
    $rcid = restore_esc($db, $rcid);
    $q = mysqli_query($db, "SELECT id, rcid, name, mobile, email, assign_account_manager FROM user WHERE rcid = '{$rcid}' LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    return $row ?: null;
}

function restore_find_user_by_pan(mysqli $db, string $pan): ?array
{
    $pan = restore_esc($db, strtoupper($pan));
    if ($pan === '') {
        return null;
    }
    $q = mysqli_query($db, "SELECT id, rcid FROM user WHERE UPPER(pan) = '{$pan}' LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    return $row ?: null;
}

function restore_am_id(array $amCache, string $officerName): int
{
    $key = strtolower(trim($officerName));
    if ($key === '' || $key === 'n/a') {
        return 0;
    }
    if (isset($amCache[$key])) {
        return $amCache[$key];
    }
    // fuzzy: first name match
    foreach ($amCache as $name => $id) {
        if (str_contains($name, $key) || str_contains($key, $name)) {
            return $id;
        }
    }
    return 0;
}

function restore_upsert_user(
    mysqli $db,
    array &$stats,
    array $amCache,
    string $placeholderPassword,
    string $rcid,
    string $name,
    string $mobile,
    string $altmobile,
    string $email,
    string $officerName = '',
    string $panName = '',
    string $pan = ''
): ?int {
    $rcid = trim($rcid);
    if ($rcid === '') {
        return null;
    }
    $existing = restore_find_user_by_rcid($db, $rcid);
    $amId = restore_am_id($amCache, $officerName);
    $displayName = $name !== '' ? $name : $panName;

    if ($existing) {
        $sets = [];
        if ($displayName !== '') {
            $sets[] = "name = '" . restore_esc($db, $displayName) . "'";
            if ($panName !== '' || $displayName !== '') {
                $sets[] = "pan_name = '" . restore_esc($db, $panName !== '' ? $panName : $displayName) . "'";
            }
        }
        if ($mobile !== '') {
            $sets[] = "mobile = '" . restore_esc($db, $mobile) . "'";
        }
        if ($altmobile !== '') {
            $sets[] = "altmobile = '" . restore_esc($db, $altmobile) . "'";
        }
        if ($email !== '') {
            $sets[] = "email = '" . restore_esc($db, $email) . "'";
        }
        if ($pan !== '') {
            $sets[] = "pan = '" . restore_esc($db, strtoupper($pan)) . "'";
        }
        if ($amId > 0 && (int) $existing['assign_account_manager'] <= 0) {
            $sets[] = "assign_account_manager = {$amId}";
        }
        if ($sets) {
            $sql = "UPDATE user SET " . implode(', ', $sets) . " WHERE id = " . (int) $existing['id'];
            if (!mysqli_query($db, $sql)) {
                $stats['errors']++;
                restore_log("USER UPDATE FAIL {$rcid}: " . mysqli_error($db));
                return (int) $existing['id'];
            }
            $stats['users_updated']++;
        }
        return (int) $existing['id'];
    }

    // Unique index on mobile: empty string collides; use NULL when unknown
    $mobileSql = $mobile !== '' ? ("'" . restore_esc($db, $mobile) . "'") : 'NULL';

    $now = date('Y-m-d H:i:s');
    $comment = restore_esc($db, 'RESTORE_FORCE_RESET from S3 CSV ' . date('Y-m-d'));
    $sql = "INSERT INTO user (
        rcid, name, father_name, pan_name, mobile, altmobile, email, altemail,
        state, state_code, dob, gender, pan, salary, salarystatus,
        present_address, permanent_address, graduation_year, marital_status,
        college_name, freq_app, experience, residence_type, credit_card, company, designation,
        office_number, department, annual_income, office_pincode, office_address_line1, office_address_line2,
        conpanydocument, personaldocument, salarydocument, bankdocument, bankdocument2, bankdocument3,
        companyidcard, addressdocument, bank_name, branch_name, ifsc, account_no, account_type, account_name,
        validation, password, active, verify, otp, reg_date, status, document_password, get_salary,
        loan, loan_limit, sloan, assign_account_manager, assign_recovery_officer,
        aadhar, old_document, company_url, fb_url, insta_id, comment, star_member, approvenew,
        work_from, average_salary, salary_date, total_emi, work_year, work_month, signature, pincode,
        credit_score, latlong, selfie, limit_inc, old_loan_limit, easebuzz, auto_limit, member
    ) VALUES (
        '" . restore_esc($db, $rcid) . "',
        '" . restore_esc($db, $displayName) . "',
        '',
        '" . restore_esc($db, $panName !== '' ? $panName : $displayName) . "',
        {$mobileSql},
        '" . restore_esc($db, $altmobile) . "',
        '" . restore_esc($db, $email) . "',
        '',
        '', 0, NULL, 2,
        '" . restore_esc($db, strtoupper($pan)) . "',
        '', '', '', '', '', '', '', '', '', '', '', '', '',
        NULL, '', '', '', '', '',
        NULL, NULL, 'no', 'no', 'no', 'no', 'no', 'no',
        NULL, '', NULL, NULL, NULL, NULL, NULL,
        '" . restore_esc($db, $placeholderPassword) . "',
        1, 1, NULL, '{$now}', 'applied',
        'pan no password pan', 'bank transfer',
        0, 10000, 0, {$amId}, 0,
        '', '', '', '', '', '{$comment}', 2, 0,
        '', '', 0, 0, 0, 0, NULL, NULL,
        650, NULL, NULL, 1, 10000, 0, 1, 0
    )";
    if (!mysqli_query($db, $sql)) {
        $stats['errors']++;
        restore_log("USER INSERT FAIL {$rcid}: " . mysqli_error($db));
        return null;
    }
    $stats['users_inserted']++;
    return (int) mysqli_insert_id($db);
}

function restore_upsert_disbursal(
    mysqli $db,
    array &$stats,
    int $uid,
    int $lid,
    float $sanctioned,
    float $disbursed,
    float $pfCollected,
    int $pfPct,
    int $tenure,
    ?string $loanDate,
    string $refNo
): void {
    $pf = $pfCollected > 0 ? $pfCollected : round($sanctioned * $pfPct / (100 + $pfPct * 1.18), 3);
    // Prefer explicit PF from CSV; principal approx sanctioned - PF - GST
    $gst = round($pf * 0.18, 3);
    $principal = $sanctioned > 0 ? max(0, round($sanctioned - $pf - $gst, 3)) : round($disbursed, 3);
    if ($principal <= 0 && $disbursed > 0) {
        $principal = round($disbursed, 3);
    }
    $processedDate = $loanDate ? ($loanDate . ' 12:00:00') : date('Y-m-d H:i:s');
    $days = $tenure > 0 ? $tenure : 30;

    $q = mysqli_query($db, "SELECT id, uid, status FROM loan_apply WHERE id = {$lid} LIMIT 1");
    $la = $q ? mysqli_fetch_assoc($q) : null;

    if ($la) {
        $sql = "UPDATE loan_apply SET
            uid = {$uid},
            amount = {$principal},
            processing_fees = {$pf},
            pro_fee_per = {$pfPct},
            days = {$days},
            status = 'account manager',
            status_date = '" . restore_esc($db, $processedDate) . "',
            last_update = '" . restore_esc($db, date('Y-m-d H:i:s')) . "'
            WHERE id = {$lid}";
        if (!mysqli_query($db, $sql)) {
            $stats['errors']++;
            restore_log("LA UPDATE FAIL CLL{$lid}: " . mysqli_error($db));
        } else {
            $stats['loan_apply_updated']++;
        }
    } else {
        $sql = "INSERT INTO loan_apply (
            id, uid, amount, processing_fees, pro_fee_per, origination_fee, account_management_fee,
            service_charge, days, apply_date, status, status_date, follow_up_date, created_by, reason,
            agreement, keyid, lat, longt, ubank_id, last_update, mail_status, interest_percentage
        ) VALUES (
            {$lid}, {$uid}, {$principal}, {$pf}, {$pfPct}, 0, NULL,
            '0', {$days}, '" . restore_esc($db, $processedDate) . "', 'account manager',
            '" . restore_esc($db, $processedDate) . "', '', 'restore_csv', 'Disbursed',
            1, 0, NULL, NULL, 0, '" . restore_esc($db, date('Y-m-d H:i:s')) . "', 0, 0.10
        )";
        if (!mysqli_query($db, $sql)) {
            $stats['errors']++;
            restore_log("LA INSERT FAIL CLL{$lid}: " . mysqli_error($db));
            return;
        }
        $stats['loan_apply_inserted']++;
    }

    $q2 = mysqli_query($db, "SELECT id FROM loan WHERE lid = {$lid} LIMIT 1");
    $loan = $q2 ? mysqli_fetch_assoc($q2) : null;
    $disb = $disbursed > 0 ? $disbursed : $principal;

    if ($loan) {
        $sql = "UPDATE loan SET
            uid = {$uid},
            processed_date = '" . restore_esc($db, $processedDate) . "',
            processed_amount = '" . restore_esc($db, (string) $disb) . "',
            p_fee = '" . restore_esc($db, (string) $pf) . "',
            exhausted_period = '1',
            service_charge = '0',
            penality_charge = '0',
            total_amount = '" . restore_esc($db, (string) $sanctioned) . "',
            status_log = 'account manager',
            action = 'disbursal',
            total_time = '" . restore_esc($db, (string) $days) . "'
            WHERE lid = {$lid}";
        if (!mysqli_query($db, $sql)) {
            $stats['errors']++;
            restore_log("LOAN UPDATE FAIL CLL{$lid}: " . mysqli_error($db));
        } else {
            $stats['loan_updated']++;
        }
    } else {
        $sql = "INSERT INTO loan (
            lid, uid, processed_date, processed_amount, exhausted_period, p_fee,
            origination_fee, account_management_fee, service_charge, penality_charge, total_amount,
            status_log, action, follow_up_mess, advance_amount, total_time,
            femi, semi, is_emi, cleard_date, limit_inc_prompt, last_cal_date, enach_request
        ) VALUES (
            {$lid}, {$uid}, '" . restore_esc($db, $processedDate) . "',
            '" . restore_esc($db, (string) $disb) . "', '1', '" . restore_esc($db, (string) $pf) . "',
            0, NULL, '0', '0', '" . restore_esc($db, (string) $sanctioned) . "',
            'account manager', 'disbursal', '', '', '" . restore_esc($db, (string) $days) . "',
            0, 0, 0, NULL, 0, NULL, 0
        )";
        if (!mysqli_query($db, $sql)) {
            $stats['errors']++;
            restore_log("LOAN INSERT FAIL CLL{$lid}: " . mysqli_error($db));
        } else {
            $stats['loan_inserted']++;
        }
    }

    // Disbursal creditleg transaction if missing
    $ref = restore_esc($db, $refNo !== '' ? $refNo : 'RESTORE');
    $chk = mysqli_query($db, "SELECT id FROM transaction_details WHERE cllid = '{$lid}' AND transaction_flow = 'creditlab To Customer' LIMIT 1");
    if ($chk && mysqli_num_rows($chk) === 0) {
        $sql = "INSERT INTO transaction_details (uid, cllid, transaction_number, transaction_date, transaction_amount, transaction_flow)
                VALUES ({$uid}, '{$lid}', '{$ref}', '" . restore_esc($db, $processedDate) . "',
                '" . restore_esc($db, (string) $disb) . "', 'creditlab To Customer')";
        if (mysqli_query($db, $sql)) {
            $stats['tx_inserted']++;
        } else {
            $stats['errors']++;
        }
    }

    mysqli_query($db, "UPDATE user SET loan = 1, status = 'account manager' WHERE id = {$uid}");
}

function restore_glob_sorted(string $dir, string $prefix): array
{
    $files = glob($dir . '/' . $prefix . '*.csv') ?: [];
    sort($files);
    return $files;
}

// ---------------------------------------------------------------------------
// 1) Users from applied_* (no pending yet — disbursals first)
// ---------------------------------------------------------------------------
foreach (restore_glob_sorted($csvDir, 'applied_') as $file) {
    $stats['files']++;
    restore_log('applied users: ' . basename($file));
    [, $rows] = restore_read_csv($file);
    foreach ($rows as $r) {
        restore_upsert_user(
            $db,
            $stats,
            $amCache,
            $placeholderPassword,
            $r['CID'] ?? '',
            $r['Name of user'] ?? '',
            $r['Primary Number'] ?? '',
            $r['Alt Number'] ?? '',
            $r['Primary Mail ID'] ?? '',
            $r['Loan Officer Name'] ?? ''
        );
    }
}

// ---------------------------------------------------------------------------
// 2) bs_disbursal_* → users + loan_apply + loan
// ---------------------------------------------------------------------------
foreach (restore_glob_sorted($csvDir, 'bs_disbursal_') as $file) {
    $stats['files']++;
    restore_log('bs_disbursal: ' . basename($file));
    [, $rows] = restore_read_csv($file);
    foreach ($rows as $r) {
        $rcid = $r['CLID (Account ID)'] ?? ($r['CLID'] ?? '');
        $lid = restore_parse_cll($r['Voucher No. (or CLLID)'] ?? '');
        if (!$lid) {
            continue;
        }
        $uid = restore_upsert_user(
            $db,
            $stats,
            $amCache,
            $placeholderPassword,
            $rcid,
            $r['Name'] ?? '',
            '',
            '',
            '',
            '',
            $r['Name'] ?? ''
        );
        if (!$uid) {
            continue;
        }
        restore_upsert_disbursal(
            $db,
            $stats,
            $uid,
            $lid,
            (float) ($r['Sanctioned Amount'] ?? 0),
            (float) ($r['Disbursal Amount'] ?? 0),
            (float) ($r['Processing Fees Collected'] ?? 0),
            (int) ($r['Processing fee(%)'] ?? 14),
            (int) ($r['Tenure'] ?? 30),
            restore_parse_date($r['LoanDate'] ?? ''),
            (string) ($r['Reference No. (or Payout ID)'] ?? '')
        );
    }
}

// ---------------------------------------------------------------------------
// 3) applied_* pending rows (only if not already disbursed)
// ---------------------------------------------------------------------------
foreach (restore_glob_sorted($csvDir, 'applied_') as $file) {
    restore_log('applied pending: ' . basename($file));
    [, $rows] = restore_read_csv($file);
    foreach ($rows as $r) {
        $rcid = $r['CID'] ?? '';
        $u = restore_find_user_by_rcid($db, $rcid);
        if (!$u) {
            continue;
        }
        $uid = (int) $u['id'];
        $applyDate = restore_parse_date($r['Loan Applied Date'] ?? '');
        if (!$applyDate) {
            continue;
        }
        // Skip if user already has a disbursed/open/cleared loan on/after this date
        $chk = mysqli_query($db, "SELECT id FROM loan_apply WHERE uid = {$uid} AND status IN ('account manager','cleared','recovery officer') AND DATE(apply_date) >= '{$applyDate}' LIMIT 1");
        if ($chk && mysqli_num_rows($chk) > 0) {
            continue;
        }
        // Also skip if any open loan row exists for uid
        $chkLoan = mysqli_query($db, "SELECT id FROM loan WHERE uid = {$uid} AND status_log IN ('account manager','recovery officer') LIMIT 1");
        if ($chkLoan && mysqli_num_rows($chkLoan) > 0) {
            continue;
        }
        // Avoid duplicate pending for same day
        $chk2 = mysqli_query($db, "SELECT id FROM loan_apply WHERE uid = {$uid} AND status = 'pending' AND DATE(apply_date) = '{$applyDate}' LIMIT 1");
        if ($chk2 && mysqli_num_rows($chk2) > 0) {
            continue;
        }
        $amId = restore_am_id($amCache, $r['Loan Officer Name'] ?? '');
        $sql = "INSERT INTO loan_apply (
            uid, amount, processing_fees, pro_fee_per, origination_fee, account_management_fee,
            service_charge, days, apply_date, status, status_date, follow_up_date, created_by, reason,
            agreement, keyid, lat, longt, ubank_id, last_update, mail_status, interest_percentage
        ) VALUES (
            {$uid}, 0, 0, 14, 0, NULL, '0', 30,
            '{$applyDate} 12:00:00', 'pending', '{$applyDate} 12:00:00', '', 'restore_csv', 'Applied',
            0, 0, NULL, NULL, 0, '" . date('Y-m-d H:i:s') . "', 0, 0.10
        )";
        if (mysqli_query($db, $sql)) {
            $stats['loan_apply_inserted']++;
            $stats['pending_apply']++;
            if ($amId > 0) {
                mysqli_query($db, "UPDATE user SET assign_account_manager = {$amId}, status = 'applied' WHERE id = {$uid}");
            } else {
                mysqli_query($db, "UPDATE user SET status = 'applied' WHERE id = {$uid}");
            }
        } else {
            $stats['errors']++;
            restore_log('pending apply fail: ' . mysqli_error($db));
        }
    }
}

// ---------------------------------------------------------------------------
// 4) bs_repayment_*
// ---------------------------------------------------------------------------
foreach (restore_glob_sorted($csvDir, 'bs_repayment_') as $file) {
    $stats['files']++;
    restore_log('bs_repayment: ' . basename($file));
    [, $rows] = restore_read_csv($file);
    foreach ($rows as $r) {
        $lid = restore_parse_cll($r['Voucher No. (or CLLID)'] ?? '');
        if (!$lid) {
            continue;
        }
        $flow = strtolower(trim($r['LOAN CLOSURE TYPE'] ?? ''));
        if ($flow === '') {
            $flow = 'full';
        }
        // Normalize
        if (!in_array($flow, ['settlement', 'part', 'renew', 'full', 'preclose'], true)) {
            $flow = 'full';
        }
        $txDate = restore_parse_date($r['LoanDate'] ?? '') ?: restore_parse_date($r['Loan Process Date'] ?? '');
        if (!$txDate) {
            $txDate = date('Y-m-d');
        }
        $amount = (float) ($r['REPAYMENT AMOUNT'] ?? 0);
        $ref = (string) ($r['Reference No. (or Payout ID)'] ?? 'RESTORE');
        $rcid = $r['CLID'] ?? '';

        $uid = 0;
        $q = mysqli_query($db, "SELECT uid FROM loan WHERE lid = {$lid} LIMIT 1");
        if ($q && ($row = mysqli_fetch_assoc($q))) {
            $uid = (int) $row['uid'];
        } elseif ($rcid !== '') {
            $u = restore_find_user_by_rcid($db, $rcid);
            $uid = $u ? (int) $u['id'] : 0;
        }
        if ($uid <= 0) {
            $uid = restore_upsert_user(
                $db,
                $stats,
                $amCache,
                $placeholderPassword,
                $rcid,
                $r['Name'] ?? '',
                '',
                '',
                '',
                '',
                $r['Name'] ?? ''
            ) ?: 0;
        }
        if ($uid <= 0) {
            continue;
        }

        // Ensure loan exists (many repayments are for loans disbursed in earlier half-months)
        $qLoan = mysqli_query($db, "SELECT id FROM loan WHERE lid = {$lid} LIMIT 1");
        if (!$qLoan || mysqli_num_rows($qLoan) === 0) {
            $processDate = restore_parse_date($r['Loan Process Date'] ?? '') ?: $txDate;
            restore_upsert_disbursal(
                $db,
                $stats,
                $uid,
                $lid,
                (float) ($r['Sanctioned Amount'] ?? 0),
                (float) ($r['Disbursal Amount'] ?? 0),
                (float) ($r['Processing Fees Collected'] ?? 0),
                (int) ($r['Processing fee(%)'] ?? 14),
                30,
                $processDate,
                (string) ($r['Reference No. (or Payout ID)'] ?? '')
            );
        }

        $refEsc = restore_esc($db, $ref);
        $flowEsc = restore_esc($db, $flow);
        $amtEsc = restore_esc($db, (string) $amount);
        $dt = $txDate . ' 12:00:00';
        $dup = mysqli_query(
            $db,
            "SELECT id FROM transaction_details
             WHERE cllid = '{$lid}' AND transaction_flow = '{$flowEsc}'
               AND DATE(transaction_date) = '{$txDate}'
               AND transaction_amount = '{$amtEsc}'
             LIMIT 1"
        );
        if ($dup && mysqli_num_rows($dup) > 0) {
            $stats['tx_skipped']++;
            continue;
        }
        $sql = "INSERT INTO transaction_details (uid, cllid, transaction_number, transaction_date, transaction_amount, transaction_flow)
                VALUES ({$uid}, '{$lid}', '{$refEsc}', '{$dt}', '{$amtEsc}', '{$flowEsc}')";
        if (mysqli_query($db, $sql)) {
            $stats['tx_inserted']++;
            $interest = (float) ($r['INTEREST COLLECTED'] ?? 0);
            $penalty = (float) ($r['PENALTY'] ?? 0);
            $exhausted = (int) ($r['Exhausted Days'] ?? 0);
            if ($flow === 'settlement' || $flow === 'full' || $flow === 'preclose') {
                mysqli_query($db, "UPDATE loan SET
                    status_log = 'cleared',
                    action = '" . restore_esc($db, $flow) . "',
                    cleard_date = '{$txDate}',
                    service_charge = '" . restore_esc($db, (string) $interest) . "',
                    penality_charge = '" . restore_esc($db, (string) $penalty) . "',
                    exhausted_period = '" . restore_esc($db, (string) $exhausted) . "'
                    WHERE lid = {$lid}");
                mysqli_query($db, "UPDATE loan_apply SET status = 'cleared', status_date = '{$dt}' WHERE id = {$lid}");
                mysqli_query($db, "UPDATE user SET loan = 0, status = 'cleared' WHERE id = {$uid}");
            } elseif ($flow === 'part') {
                mysqli_query($db, "UPDATE loan SET
                    service_charge = '" . restore_esc($db, (string) $interest) . "',
                    penality_charge = '" . restore_esc($db, (string) $penalty) . "',
                    exhausted_period = '" . restore_esc($db, (string) $exhausted) . "'
                    WHERE lid = {$lid}");
            }
        } else {
            $stats['errors']++;
            restore_log('tx insert fail CLL' . $lid . ': ' . mysqli_error($db));
        }
    }
}

function restore_ensure_loan_from_cibil(
    mysqli $db,
    array &$stats,
    array $amCache,
    string $placeholderPassword,
    array $r,
    string $statusLog,
    string $action
): ?int {
    $lid = restore_parse_cll($r['Curr/New Account No'] ?? '');
    if (!$lid) {
        return null;
    }
    $q = mysqli_query($db, "SELECT id, uid FROM loan WHERE lid = {$lid} LIMIT 1");
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (int) $row['uid'];
    }

    $pan = strtoupper(trim($r['Income Tax ID Number'] ?? ''));
    $name = trim($r['Consumer Name'] ?? '');
    $mobile = preg_replace('/\D+/', '', (string) ($r['Telephone No.Mobile'] ?? ''));
    if (strlen($mobile) > 10) {
        $mobile = substr($mobile, -10);
    }
    $email = trim($r['Email ID 1'] ?? '');
    $addr = trim($r['Address Line 1'] ?? '');
    $pincode = (int) preg_replace('/\D+/', '', (string) ($r['PIN Code 1'] ?? '0'));
    $sanctioned = (float) ($r['High Credit/Sanctioned Amt'] ?? 0);
    $opened = restore_parse_date($r['Date Opened/Disbursed'] ?? '');
    $dpd = (int) ($r['No of Days Past Due'] ?? 0);
    $balance = (float) ($r['Current Balance'] ?? 0);

    $uid = 0;
    if ($pan !== '') {
        $u = restore_find_user_by_pan($db, $pan);
        $uid = $u ? (int) $u['id'] : 0;
    }
    if ($uid <= 0 && $mobile !== '') {
        $mEsc = restore_esc($db, $mobile);
        $qm = mysqli_query($db, "SELECT id FROM user WHERE mobile = '{$mEsc}' LIMIT 1");
        if ($qm && ($row = mysqli_fetch_assoc($qm))) {
            $uid = (int) $row['id'];
        }
    }
    if ($uid <= 0) {
        $rcid = 'CLRESTORE' . $lid;
        $uid = restore_upsert_user(
            $db,
            $stats,
            $amCache,
            $placeholderPassword,
            $rcid,
            $name,
            $mobile,
            '',
            $email,
            '',
            $name,
            $pan
        ) ?: 0;
        if ($uid > 0 && ($addr !== '' || $pincode > 0)) {
            $sets = [];
            if ($addr !== '') {
                $sets[] = "present_address = '" . restore_esc($db, $addr) . "'";
            }
            if ($pincode > 0) {
                $sets[] = "pincode = {$pincode}";
            }
            mysqli_query($db, 'UPDATE user SET ' . implode(', ', $sets) . " WHERE id = {$uid}");
        }
    }
    if ($uid <= 0) {
        return null;
    }

    restore_upsert_disbursal(
        $db,
        $stats,
        $uid,
        $lid,
        $sanctioned > 0 ? $sanctioned : max($balance, 1),
        $sanctioned > 0 ? round($sanctioned * 0.83, 2) : max($balance, 1),
        0,
        14,
        30,
        $opened,
        'RESTORE_CIBIL'
    );

    $dpdSql = restore_esc($db, (string) max($dpd, 1));
    $actionEsc = restore_esc($db, $action);
    $statusEsc = restore_esc($db, $statusLog);
    mysqli_query($db, "UPDATE loan SET status_log = '{$statusEsc}', action = '{$actionEsc}', exhausted_period = '{$dpdSql}' WHERE lid = {$lid}");
    mysqli_query($db, "UPDATE loan_apply SET status = '{$statusEsc}' WHERE id = {$lid}");
    return $uid;
}

// ---------------------------------------------------------------------------
// 5) cleared_* / settlement_* (CIBIL: Curr/New Account No = lid)
// ---------------------------------------------------------------------------
foreach (array_merge(restore_glob_sorted($csvDir, 'cleared_'), restore_glob_sorted($csvDir, 'settlement_')) as $file) {
    $stats['files']++;
    $isSettlement = str_starts_with(basename($file), 'settlement_');
    restore_log(($isSettlement ? 'settlement' : 'cleared') . ': ' . basename($file));
    [, $rows] = restore_read_csv($file);
    foreach ($rows as $r) {
        $lid = restore_parse_cll($r['Curr/New Account No'] ?? '');
        if (!$lid) {
            continue;
        }
        $uid = restore_ensure_loan_from_cibil(
            $db,
            $stats,
            $amCache,
            $placeholderPassword,
            $r,
            'cleared',
            $isSettlement ? 'settlement' : 'cleared'
        );
        $closed = restore_parse_date($r['Date Closed'] ?? '') ?: restore_parse_date($r['Date of Last Payment'] ?? '');
        $closedSql = $closed ? "'{$closed}'" : 'CURDATE()';
        $ok = mysqli_query($db, "UPDATE loan SET status_log = 'cleared', action = '" . ($isSettlement ? 'settlement' : 'cleared') . "', cleard_date = {$closedSql} WHERE lid = {$lid}");
        mysqli_query($db, "UPDATE loan_apply SET status = 'cleared', status_date = CONCAT({$closedSql}, ' 12:00:00') WHERE id = {$lid}");
        if ($ok) {
            if ($isSettlement) {
                $stats['settled']++;
                $chk = mysqli_query($db, "SELECT id FROM transaction_details WHERE cllid = '{$lid}' AND transaction_flow = 'settlement' LIMIT 1");
                if ($chk && mysqli_num_rows($chk) === 0) {
                    $q = mysqli_query($db, "SELECT uid FROM loan WHERE lid = {$lid} LIMIT 1");
                    $loanUid = ($q && ($row = mysqli_fetch_assoc($q))) ? (int) $row['uid'] : (int) $uid;
                    $amt = (float) ($r['Settlement Amt'] ?? ($r['High Credit/Sanctioned Amt'] ?? 0));
                    mysqli_query($db, "INSERT INTO transaction_details (uid, cllid, transaction_number, transaction_date, transaction_amount, transaction_flow)
                        VALUES ({$loanUid}, '{$lid}', 'RESTORE_SETTLE', CONCAT({$closedSql}, ' 12:00:00'), '" . restore_esc($db, (string) $amt) . "', 'settlement')");
                    $stats['tx_inserted']++;
                }
            } else {
                $stats['cleared']++;
            }
            $q = mysqli_query($db, "SELECT uid FROM loan WHERE lid = {$lid} LIMIT 1");
            if ($q && ($row = mysqli_fetch_assoc($q))) {
                mysqli_query($db, "UPDATE user SET loan = 0, status = 'cleared' WHERE id = " . (int) $row['uid']);
            }
        }
    }
}

// ---------------------------------------------------------------------------
// 6) default_* — use latest file only (full overdue book snapshot)
// ---------------------------------------------------------------------------
$defaultFiles = restore_glob_sorted($csvDir, 'default_');
if ($defaultFiles) {
    $file = end($defaultFiles);
    $stats['files']++;
    restore_log('default (latest): ' . basename($file));
    [, $rows] = restore_read_csv($file);
    foreach ($rows as $r) {
        $lid = restore_parse_cll($r['Curr/New Account No'] ?? '');
        $pan = $r['Income Tax ID Number'] ?? '';
        $dpd = (int) ($r['No of Days Past Due'] ?? 0);
        $uid = restore_ensure_loan_from_cibil(
            $db,
            $stats,
            $amCache,
            $placeholderPassword,
            $r,
            'account manager',
            'default'
        );
        if ($uid <= 0 && $pan !== '') {
            $u = restore_find_user_by_pan($db, $pan);
            $uid = $u ? (int) $u['id'] : 0;
        }
        if ($uid <= 0 && !$lid) {
            continue;
        }
        if ($lid) {
            $affected = mysqli_query($db, "UPDATE loan SET
                status_log = 'account manager',
                action = 'default',
                exhausted_period = '" . restore_esc($db, (string) max($dpd, 1)) . "',
                cleard_date = NULL
                WHERE lid = {$lid} AND status_log != 'cleared'");
            mysqli_query($db, "UPDATE loan_apply SET status = 'account manager' WHERE id = {$lid} AND status != 'cleared'");
            if ($affected) {
                $stats['defaulted']++;
            }
        } elseif ($uid > 0) {
            mysqli_query($db, "UPDATE loan l
                INNER JOIN loan_apply la ON la.id = l.lid
                SET l.status_log = 'account manager', l.action = 'default',
                    l.exhausted_period = '" . restore_esc($db, (string) max($dpd, 1)) . "'
                WHERE l.uid = {$uid} AND l.status_log != 'cleared'");
            $stats['defaulted']++;
        }
        if ($uid > 0) {
            mysqli_query($db, "UPDATE user SET loan = 1, status = 'account manager' WHERE id = {$uid}");
        }
    }
}

// Fix AUTO_INCREMENT
foreach (['user', 'loan', 'loan_apply', 'transaction_details'] as $t) {
    $q = mysqli_query($db, "SELECT COALESCE(MAX(id), 0) + 1 AS n FROM `{$t}`");
    $n = (int) mysqli_fetch_assoc($q)['n'];
    mysqli_query($db, "ALTER TABLE `{$t}` AUTO_INCREMENT = {$n}");
}

restore_log('Done.');
echo "\n=== Summary ===\n";
foreach ($stats as $k => $v) {
    echo str_pad($k, 22) . $v . "\n";
}
echo "\nTarget DB: " . env('DB_NAME', 'credit_restore') . "\n";
echo "CSV dir: {$csvDir}\n";
echo "Note: New users need password reset (comment contains RESTORE_FORCE_RESET).\n";
echo "Add *_2026-08-15_*.csv to zzre and re-run this script for 1–15 Aug coverage.\n";

<?php

require_once __DIR__ . '/../config/easebuzz.php';
require_once __DIR__ . '/app_url.php';

function creditlab_easebuzz_enach_log_path() {
    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    return $log_dir . '/easebuzz_enach_' . date('Y-m-d') . '.log';
}

function creditlab_easebuzz_enach_log($title, array $payload = []) {
    $log_file = creditlab_easebuzz_enach_log_path();
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $title . PHP_EOL;
    if (!empty($payload)) {
        $entry .= json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    $entry .= str_repeat('-', 80) . PHP_EOL;
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
    error_log('Easebuzz ENACH: ' . $title);
    return basename($log_file);
}

function creditlab_easebuzz_normalize_phone($phone) {
    $phone = preg_replace('/\D+/', '', (string) $phone);
    if (strlen($phone) > 10) {
        $phone = substr($phone, -10);
    }
    return $phone;
}

/**
 * Max e-NACH mandate amount (Autocollect access-key `amount`, amount_rule MAX).
 *
 * Default: max(60% of salary, loan_limit), floor ₹10,000.
 * Prod test override: set EASEBUZZ_ENACH_TEST_MOBILES + EASEBUZZ_ENACH_TEST_MAX_AMOUNT in .env.
 */
function creditlab_easebuzz_max_debit_amount($salary, $loan_limit, $phone = '') {
    require_once __DIR__ . '/env.php';

    $phone = creditlab_easebuzz_normalize_phone($phone);
    $test_max = trim(env('EASEBUZZ_ENACH_TEST_MAX_AMOUNT', ''));
    $test_phones_raw = trim(env('EASEBUZZ_ENACH_TEST_MOBILES', ''));

    if ($phone !== '' && $test_max !== '' && $test_phones_raw !== '') {
        $test_phones = array_filter(array_map(function ($entry) {
            $normalized = creditlab_easebuzz_normalize_phone($entry);
            return $normalized !== '' ? $normalized : null;
        }, explode(',', $test_phones_raw)));

        if (in_array($phone, $test_phones, true)) {
            return max(1, (int) round((float) $test_max));
        }
    }

    $amount = round((float) $salary * 0.6);
    if ((float) $loan_limit > $amount) {
        $amount = (int) round((float) $loan_limit);
    }
    if ($amount < 10000) {
        $amount = 10000;
    }
    return $amount;
}

/** @deprecated Legacy PG 5-char codes — use creditlab_autocollect_resolve_bank_code() for Autocollect. */
function creditlab_resolve_easebuzz_bank_code($ifsc, $db_code) {
    require_once __DIR__ . '/easebuzz_autocollect.php';
    return creditlab_autocollect_resolve_bank_code($ifsc, $db_code);
}

function creditlab_resolve_easebuzz_account_type($ac_type) {
    $normalized = strtolower(trim((string)$ac_type));
    if (strpos($normalized, 'curr') !== false) {
        return 'CURRENT';
    }
    return 'SAVINGS';
}

function creditlab_is_valid_ifsc($ifsc) {
    return (bool)preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper(trim((string)$ifsc)));
}

function creditlab_easebuzz_clean_field($value) {
    return trim(strip_tags((string)$value));
}

/**
 * Start customer e-NACH via Easebuzz Autocollect (replaces legacy initiateLink flow).
 *
 * @return array{ok:bool, html?:string, error?:string, transaction_id?:string, log_file?:string}
 */
function creditlab_start_easebuzz_enach($user_id, array $post, array $context = []) {
    require_once __DIR__ . '/easebuzz_autocollect.php';
    return creditlab_autocollect_start_user_enach($user_id, $post, $context);
}

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

function creditlab_easebuzz_base_url() {
    return (strtolower(EASEBUZZ_ENV) === 'test') ? 'https://testpay.easebuzz.in' : 'https://pay.easebuzz.in';
}

function creditlab_easebuzz_max_debit_amount($salary, $loan_limit) {
    $amount = round((float)$salary * 0.6);
    if ((float)$loan_limit > $amount) {
        $amount = (int)round((float)$loan_limit);
    }
    if ($amount < 10000) {
        $amount = 10000;
    }
    return $amount;
}

function creditlab_resolve_easebuzz_bank_code($ifsc, $db_code) {
    $ifsc = strtoupper(trim((string)$ifsc));
    $prefix = strlen($ifsc) >= 4 ? substr($ifsc, 0, 4) : '';

    $overrides = [
        'HDFC' => 'HDFCB',
        'UTIB' => 'UTIB',
        'ICIC' => 'ICIC',
        'SBIN' => 'SBIN',
        'KKBK' => 'KKBK',
        'IDIB' => 'IDIB',
        'BARB' => 'BARB',
        'PUNB' => 'PUNB',
        'CBIN' => 'CBIN',
        'YESB' => 'YESB',
        'CNRB' => 'CNRB',
        'FDRL' => 'FDRL',
        'BKID' => 'BKID',
        'INDB' => 'INDB',
        'AUBL' => 'AUBL',
    ];

    if ($prefix !== '' && isset($overrides[$prefix])) {
        return $overrides[$prefix];
    }

    $db_code = strtoupper(trim((string)$db_code));
    if ($db_code !== '' && $db_code !== '0' && preg_match('/^[A-Z]{4,5}$/', $db_code)) {
        return $db_code;
    }

    return $prefix;
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

function creditlab_easebuzz_generate_hash(array $data, $salt) {
    $hashSequence = $data['key'] . '|' . $data['txnid'] . '|' . $data['amount'] . '|' . $data['productinfo'] . '|' . $data['firstname'] . '|' . $data['email'] . '|'
        . $data['udf1'] . '|' . $data['udf2'] . '|' . $data['udf3'] . '|' . $data['udf4'] . '|' . $data['udf5'] . '|'
        . $data['udf6'] . '|' . $data['udf7'] . '|' . $data['udf8'] . '|' . $data['udf9'] . '|' . $data['udf10'] . '|' . $salt;
    return strtolower(hash('sha512', $hashSequence));
}

function creditlab_easebuzz_post_json($url, array $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return [
            'status' => 0,
            'error_desc' => 'Unable to reach Easebuzz payment gateway.',
            '_meta' => [
                'url' => $url,
                'http_code' => $http_code,
                'curl_errno' => $errno,
                'curl_error' => $curl_error,
                'raw_body' => $body,
            ],
        ];
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        return [
            'status' => 0,
            'error_desc' => 'Invalid response from Easebuzz.',
            '_meta' => [
                'url' => $url,
                'http_code' => $http_code,
                'raw_body' => $body,
            ],
        ];
    }

    $decoded['_meta'] = [
        'url' => $url,
        'http_code' => $http_code,
        'raw_body' => $body,
    ];
    return $decoded;
}

function creditlab_easebuzz_clean_field($value) {
    return trim(strip_tags((string)$value));
}

function creditlab_easebuzz_build_seamless_form($easebuzz_base, $access_key, array $fields, $txnid = '') {
    $action = rtrim($easebuzz_base, '/') . '/initiate_seamless_payment/';
    $inputs = '';
    foreach ($fields as $name => $value) {
        $inputs .= '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES) . '" value="' . htmlspecialchars($value, ENT_QUOTES) . '">';
    }
    $txnid_comment = $txnid !== '' ? '<!-- easebuzz_txnid:' . htmlspecialchars($txnid, ENT_QUOTES) . ' -->' : '';
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Redirecting to Easebuzz</title>' . $txnid_comment . '</head><body>'
        . '<p>Redirecting to Easebuzz for e-NACH registration...</p>'
        . '<form id="easebuzz_enach_form" method="POST" action="' . htmlspecialchars($action, ENT_QUOTES) . '">'
        . '<input type="hidden" name="access_key" value="' . htmlspecialchars($access_key, ENT_QUOTES) . '">'
        . $inputs
        . '</form><script>document.getElementById("easebuzz_enach_form").submit();</script></body></html>';
}

function creditlab_start_easebuzz_enach($user_id, array $post, array $context = []) {
    global $db;

    $log_context = [
        'uid' => (int)$user_id,
        'rcid' => isset($context['rcid']) ? (string)$context['rcid'] : '',
        'mobile' => isset($context['mobile']) ? (string)$context['mobile'] : '',
        'env' => EASEBUZZ_ENV,
        'merchant_key' => EASEBUZZ_MERCHANT_KEY,
        'client_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];

    creditlab_easebuzz_enach_log('INCOMING USER POST (sanitized)', array_merge($log_context, [
        'post' => $post,
    ]));

    $required_fields = ['firstname', 'phone', 'email', 'account_no', 'account_type', 'ifsc', 'bank_code'];
    foreach ($required_fields as $field) {
        if (!isset($post[$field]) || trim((string)$post[$field]) === '') {
            $error = 'Missing required field: ' . $field;
            creditlab_easebuzz_enach_log('VALIDATION FAILED', array_merge($log_context, ['error' => $error]));
            return ['ok' => false, 'error' => $error, 'log_file' => creditlab_easebuzz_enach_log_path()];
        }
    }

    $firstname = creditlab_easebuzz_clean_field($post['firstname']);
    $phone = preg_replace('/\D+/', '', creditlab_easebuzz_clean_field($post['phone']));
    if (strlen($phone) > 10) {
        $phone = substr($phone, -10);
    }
    $email = creditlab_easebuzz_clean_field($post['email']);
    $bankCode = creditlab_easebuzz_clean_field($post['bank_code']);
    $accountNo = preg_replace('/\D+/', '', creditlab_easebuzz_clean_field($post['account_no']));
    $auth_mode = creditlab_easebuzz_clean_field($post['auth_mode'] ?? 'NetBanking');
    $accountType = creditlab_easebuzz_clean_field($post['account_type']);
    $ifsc = strtoupper(trim(creditlab_easebuzz_clean_field($post['ifsc'])));
    $accountName = creditlab_easebuzz_clean_field($post['account_name'] ?? '');

    if (strlen($phone) !== 10) {
        $error = 'Invalid mobile number on profile. Please contact support.';
        creditlab_easebuzz_enach_log('VALIDATION FAILED', array_merge($log_context, ['error' => $error, 'phone' => $phone]));
        return ['ok' => false, 'error' => $error, 'log_file' => creditlab_easebuzz_enach_log_path()];
    }
    if ($accountNo === '') {
        $error = 'Invalid bank account number. Please contact support.';
        creditlab_easebuzz_enach_log('VALIDATION FAILED', array_merge($log_context, ['error' => $error]));
        return ['ok' => false, 'error' => $error, 'log_file' => creditlab_easebuzz_enach_log_path()];
    }

    $bankCode = creditlab_resolve_easebuzz_bank_code($ifsc, $bankCode);
    $accountType = creditlab_resolve_easebuzz_account_type($accountType);

    if (!creditlab_is_valid_ifsc($ifsc)) {
        $error = 'Invalid IFSC code. Please ask support to update your bank IFSC.';
        creditlab_easebuzz_enach_log('VALIDATION FAILED', array_merge($log_context, ['error' => $error, 'ifsc' => $ifsc]));
        return ['ok' => false, 'error' => $error, 'log_file' => creditlab_easebuzz_enach_log_path()];
    }
    if ($bankCode === '' || $bankCode === '0') {
        $error = 'Unable to resolve Easebuzz bank code for this IFSC.';
        creditlab_easebuzz_enach_log('VALIDATION FAILED', array_merge($log_context, ['error' => $error, 'ifsc' => $ifsc]));
        return ['ok' => false, 'error' => $error, 'log_file' => creditlab_easebuzz_enach_log_path()];
    }
    if (!in_array($auth_mode, ['NetBanking', 'DebitCard'], true)) {
        $auth_mode = 'NetBanking';
    }

    $salary = isset($context['salary']) ? (float)$context['salary'] : 0;
    $loan_limit = isset($context['loan_limit']) ? (float)$context['loan_limit'] : 0;
    $udf5 = creditlab_easebuzz_max_debit_amount($salary, $loan_limit);

    $merchant_key = EASEBUZZ_MERCHANT_KEY;
    $salt = EASEBUZZ_SALT;
    if ($merchant_key === '' || $salt === '') {
        $error = 'Easebuzz credentials are not configured on the server.';
        creditlab_easebuzz_enach_log('CONFIG ERROR', array_merge($log_context, ['error' => $error]));
        return ['ok' => false, 'error' => $error, 'log_file' => creditlab_easebuzz_enach_log_path()];
    }

    $txnid = str_replace('.', '', uniqid('en', true));
    $customer_auth_id = str_replace('.', '', uniqid('cai', true));
    $log_context['txnid'] = $txnid;
    $log_context['customer_authentication_id'] = $customer_auth_id;
    $callback_base = creditlab_get_base_url();

    $authData = [
        'key' => $merchant_key,
        'txnid' => $txnid,
        'amount' => '1.0',
        'productinfo' => 'Loan Payment',
        'firstname' => $firstname,
        'phone' => $phone,
        'email' => $email,
        'surl' => $callback_base . '/easebuzz_callback.php',
        'furl' => $callback_base . '/easebuzz_callback.php',
        'udf1' => '', 'udf2' => '', 'udf3' => '', 'udf4' => '', 'udf5' => $udf5 . '.0',
        'udf6' => '', 'udf7' => '', 'udf8' => '', 'udf9' => '', 'udf10' => '',
        'request_flow' => 'SEAMLESS',
        'customer_authentication_id' => $customer_auth_id,
        'final_collection_date' => date('d/m/Y', strtotime('+3 year')),
    ];
    $authData['hash'] = creditlab_easebuzz_generate_hash($authData, $salt);

    $easebuzz_base = creditlab_easebuzz_base_url();
    $initiate_url = $easebuzz_base . '/payment/initiateLink';

    creditlab_easebuzz_enach_log('STEP 1 initiateLink REQUEST', array_merge($log_context, [
        'url' => $initiate_url,
        'request' => $authData,
        'resolved_bank_code' => $bankCode,
        'resolved_account_type' => $accountType,
        'auth_mode' => $auth_mode,
        'account_name' => $accountName,
    ]));

    $authResponse = creditlab_easebuzz_post_json($initiate_url, $authData);
    $auth_meta = $authResponse['_meta'] ?? [];
    unset($authResponse['_meta']);

    creditlab_easebuzz_enach_log('STEP 1 initiateLink RESPONSE', array_merge($log_context, [
        'url' => $initiate_url,
        'http_code' => $auth_meta['http_code'] ?? null,
        'response_json' => $authResponse,
        'response_raw' => $auth_meta['raw_body'] ?? null,
    ]));

    if (!$authResponse || !isset($authResponse['status']) || (int)$authResponse['status'] !== 1 || empty($authResponse['data'])) {
        $error_msg = $authResponse['error_desc'] ?? $authResponse['data'] ?? 'Easebuzz rejected the e-NACH request.';
        creditlab_easebuzz_enach_log('STEP 1 initiateLink FAILED', array_merge($log_context, [
            'error' => is_string($error_msg) ? $error_msg : json_encode($error_msg),
        ]));
        return [
            'ok' => false,
            'error' => is_string($error_msg) ? $error_msg : 'Easebuzz rejected the e-NACH request.',
            'txnid' => $txnid,
            'log_file' => creditlab_easebuzz_enach_log_path(),
        ];
    }

    $access_key = $authResponse['data'];
    $firstname_sql = mysqli_real_escape_string($db, $firstname);
    $phone_sql = mysqli_real_escape_string($db, $phone);
    $email_sql = mysqli_real_escape_string($db, $email);
    $access_key_sql = mysqli_real_escape_string($db, $access_key);
    $ifsc_sql = mysqli_real_escape_string($db, $ifsc);
    $accountType_sql = mysqli_real_escape_string($db, $accountType);
    $accountNo_sql = mysqli_real_escape_string($db, $accountNo);
    $auth_mode_sql = mysqli_real_escape_string($db, $auth_mode);
    $bankCode_sql = mysqli_real_escape_string($db, $bankCode);

    towquery("DELETE FROM `easebuzz_adtd` WHERE `uid` = " . (int)$user_id);
    $insert_query = "INSERT INTO `easebuzz_adtd` (`uid`, `txnid`, `firstname`, `phone`, `email`, `udf5`, `request_flow`, `customer_authentication_id`, `final_collection_date`, `hash`, `access_key`, `payment_mode`, `ifsc`, `account_type`, `account_no`, `auth_mode`, `bank_code`)
        VALUES (" . (int)$user_id . ", '$txnid', '$firstname_sql', '$phone_sql', '$email_sql', '{$authData['udf5']}', 'SEAMLESS', '$customer_auth_id', '{$authData['final_collection_date']}', '{$authData['hash']}', '$access_key_sql', 'EN', '$ifsc_sql', '$accountType_sql', '$accountNo_sql', '$auth_mode_sql', '$bankCode_sql')";

    if (!towquery($insert_query)) {
        creditlab_easebuzz_enach_log('DB INSERT FAILED', array_merge($log_context, [
            'sql_error' => mysqli_error($db),
        ]));
        return ['ok' => false, 'error' => 'Could not save e-NACH request. Please try again.', 'txnid' => $txnid, 'log_file' => creditlab_easebuzz_enach_log_path()];
    }

    $seamless_fields = [
        'payment_mode' => 'EN',
        'ifsc' => $ifsc,
        'account_type' => $accountType,
        'account_no' => $accountNo,
        'auth_mode' => $auth_mode,
        'bank_code' => $bankCode,
    ];
    $seamless_url = rtrim($easebuzz_base, '/') . '/initiate_seamless_payment/';

    creditlab_easebuzz_enach_log('STEP 2 initiate_seamless_payment REQUEST', array_merge($log_context, [
        'url' => $seamless_url,
        'request' => array_merge(['access_key' => $access_key], $seamless_fields),
        'note' => 'Browser form auto-submit follows this log entry. WC0E05 usually appears after this step on Easebuzz side.',
    ]));

    creditlab_easebuzz_enach_log('STEP 2 READY — redirecting browser to Easebuzz', array_merge($log_context, [
        'access_key' => $access_key,
        'log_file' => creditlab_easebuzz_enach_log_path(),
    ]));

    return [
        'ok' => true,
        'html' => creditlab_easebuzz_build_seamless_form($easebuzz_base, $access_key, $seamless_fields, $txnid),
        'txnid' => $txnid,
        'log_file' => creditlab_easebuzz_enach_log_path(),
        'debug' => [
            'txnid' => $txnid,
            'bank_code' => $bankCode,
            'auth_mode' => $auth_mode,
        ],
    ];
}

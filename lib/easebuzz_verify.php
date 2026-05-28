<?php
require_once __DIR__ . '/../config/easebuzz.php';

/**
 * Verify Easebuzz callback hash (reverse hash sequence).
 */
function creditlab_easebuzz_verify_hash(array $data): bool
{
    if (empty($data['hash']) || !isset($data['status'])) {
        return false;
    }

    $salt = EASEBUZZ_SALT;
    if ($salt === '') {
        return false;
    }

    $reverseHashSequence = 'udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key';
    $reverseHash = $salt . '|' . $data['status'];
    foreach (explode('|', $reverseHashSequence) as $field) {
        $reverseHash .= '|' . ($data[$field] ?? '');
    }

    $expected = strtolower(hash('sha512', $reverseHash));
    $received = strtolower((string) $data['hash']);

    return hash_equals($expected, $received);
}

/**
 * Validate webhook/callback POST before changing loan state.
 */
function creditlab_easebuzz_validate_callback(array $data): bool
{
    if (creditlab_easebuzz_verify_hash($data)) {
        return true;
    }

    $headerSecret = env('EASEBUZZ_WEBHOOK_SECRET');
    if ($headerSecret !== '') {
        $provided = $_SERVER['HTTP_X_EASEBUZZ_WEBHOOK_SECRET'] ?? ($data['webhook_secret'] ?? '');
        if ($provided !== '' && hash_equals($headerSecret, (string) $provided)) {
            return true;
        }
    }

    return false;
}

function creditlab_easebuzz_reject_invalid_callback(): void
{
    error_log('Easebuzz callback rejected: invalid or missing hash/secret');
    if (!headers_sent()) {
        http_response_code(403);
    }
    exit('Forbidden');
}

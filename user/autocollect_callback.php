<?php
/**
 * Autocollect eNACH mandate return URL — success/failure redirect from Easebuzz.
 * Polls mandate retrieve and updates easebuzz_adtd + user.easebuzz.
 */
include_once '../db.php';
require_once __DIR__ . '/../lib/easebuzz_autocollect.php';

$cb = strtolower(trim((string) ($_GET['cb'] ?? '')));
$transaction_id = trim((string) ($_GET['transaction_id'] ?? ''));

if ($transaction_id === '' && isset($_GET['txnid'])) {
    $transaction_id = trim((string) $_GET['txnid']);
}

if ($cb !== 'success' && $cb !== 'failure') {
    header('Location: index.php');
    exit;
}

$result = creditlab_autocollect_finalize_user_mandate($transaction_id);
$message = htmlspecialchars($result['message'], ENT_QUOTES);
$is_ok = !empty($result['ok']) && ($result['user_easebuzz'] ?? 0) === 1;
$is_pending = !empty($result['ok']) && ($result['user_easebuzz'] ?? 0) === 0 && ($result['status'] ?? '') === 'in_process';

if ($cb === 'failure' && empty($result['ok'])) {
    $message = 'e-NACH registration could not be completed. Please try again from your dashboard.';
}

$redirect_url = htmlspecialchars(creditlab_get_base_url() . '/user/index.php', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-NACH Registration</title>
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: Arial, sans-serif; background: #f4f4f9; margin: 0; padding: 20px; }
        .message { text-align: center; padding: 24px; border-radius: 10px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08); max-width: 480px; }
        .ok { border-left: 4px solid #28a745; }
        .warn { border-left: 4px solid #ffc107; }
        .err { border-left: 4px solid #dc3545; }
        p { color: #555; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="message <?= $is_ok ? 'ok' : ($is_pending ? 'warn' : 'err') ?>">
        <h2><?= $is_ok ? 'Registration successful' : ($is_pending ? 'Registration in progress' : 'Registration status') ?></h2>
        <p><?= $message ?></p>
        <p>Redirecting to dashboard…</p>
    </div>
    <script>
        setTimeout(function () {
            window.location.href = '<?= $redirect_url ?>';
        }, 3000);
    </script>
</body>
</html>

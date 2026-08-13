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

$result = creditlab_autocollect_finalize_user_mandate($transaction_id, ['cb' => $cb]);
$message = htmlspecialchars($result['message'], ENT_QUOTES);
$user_easebuzz = (int) ($result['user_easebuzz'] ?? 0);
$is_ok = !empty($result['ok']) && $user_easebuzz === 1;
$is_pending = !empty($result['ok']) && $user_easebuzz === 0;
$is_failed = !empty($result['ok']) && $user_easebuzz === 2;
$is_error = empty($result['ok']);

if ($is_error) {
    $message = htmlspecialchars($result['message'] ?? 'Could not verify e-NACH status.', ENT_QUOTES);
}

$redirect_url = htmlspecialchars(creditlab_get_base_url() . '/user/index.php', ENT_QUOTES);
$retry_url = htmlspecialchars(creditlab_get_base_url() . '/user/index.php#easebuzz', ENT_QUOTES);
$ref_id = htmlspecialchars($transaction_id, ENT_QUOTES);
$redirect_delay_ms = $is_ok ? 4000 : ($is_pending ? 8000 : 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-NACH Registration</title>
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: Arial, sans-serif; background: #f4f4f9; margin: 0; padding: 20px; }
        .message { text-align: center; padding: 24px; border-radius: 10px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08); max-width: 520px; }
        .ok { border-left: 4px solid #28a745; }
        .warn { border-left: 4px solid #ffc107; }
        .err { border-left: 4px solid #dc3545; }
        p { color: #555; line-height: 1.5; }
        .actions { margin-top: 18px; }
        .btn { display: inline-block; margin: 6px 8px; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-secondary { background: #e9ecef; color: #333; }
        .ref { font-size: 12px; color: #888; margin-top: 12px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="message <?= $is_ok ? 'ok' : ($is_pending ? 'warn' : 'err') ?>">
        <h2><?= $is_ok ? 'Registration successful' : ($is_pending ? 'Registration in progress' : ($is_failed ? 'Registration not completed' : 'Registration status')) ?></h2>
        <p><?= $message ?></p>
        <?php if ($is_pending): ?>
            <p style="font-size:14px;color:#666;">NPCI may take up to 5 minutes to confirm. You can close this page and check your dashboard.</p>
        <?php elseif ($is_failed && $cb === 'failure'): ?>
            <p style="font-size:14px;color:#666;">If you chose Debit Card or Aadhaar, your bank may only support Net Banking for e-NACH.</p>
        <?php endif; ?>
        <?php if ($ref_id !== ''): ?>
            <p class="ref">Reference: <?= $ref_id ?></p>
        <?php endif; ?>
        <div class="actions">
            <?php if ($is_failed || $is_error): ?>
                <a class="btn btn-primary" href="<?= $retry_url ?>">Try again</a>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= $redirect_url ?>">Go to dashboard</a>
        </div>
        <?php if ($redirect_delay_ms > 0): ?>
            <p style="font-size:13px;color:#888;margin-top:16px;">Redirecting to dashboard…</p>
        <?php endif; ?>
    </div>
    <?php if ($redirect_delay_ms > 0): ?>
    <script>
        setTimeout(function () {
            window.location.href = '<?= $redirect_url ?>';
        }, <?= (int) $redirect_delay_ms ?>);
    </script>
    <?php endif; ?>
</body>
</html>

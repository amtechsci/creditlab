<?php
/**
 * Shared Autocollect API log viewer. Entry points set CREDITLAB_AUTOCOLLECT_* constants.
 */

if (!defined('CREDITLAB_AUTOCOLLECT_PLAYGROUND_MODE')) {
    define('CREDITLAB_AUTOCOLLECT_PLAYGROUND_MODE', 'uat');
}
if (!defined('CREDITLAB_AUTOCOLLECT_WEB_LOG_CHANNEL')) {
    define('CREDITLAB_AUTOCOLLECT_WEB_LOG_CHANNEL', CREDITLAB_AUTOCOLLECT_PLAYGROUND_MODE);
}
if (!defined('CREDITLAB_AUTOCOLLECT_PLAYGROUND_SELF')) {
    define('CREDITLAB_AUTOCOLLECT_PLAYGROUND_SELF', 'autocollect_playground.php');
}
if (!defined('CREDITLAB_AUTOCOLLECT_LOGS_SELF')) {
    define('CREDITLAB_AUTOCOLLECT_LOGS_SELF', 'autocollect_logs.php');
}

$playground_is_uat = (CREDITLAB_AUTOCOLLECT_PLAYGROUND_MODE === 'uat');
$playground_is_prod = !$playground_is_uat;
$playground_self = CREDITLAB_AUTOCOLLECT_PLAYGROUND_SELF;
$playground_logs = CREDITLAB_AUTOCOLLECT_LOGS_SELF;
$log_file_basename = 'autocollect_api_web' . (CREDITLAB_AUTOCOLLECT_WEB_LOG_CHANNEL !== '' ? '_' . CREDITLAB_AUTOCOLLECT_WEB_LOG_CHANNEL : '') . '.log';

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/auth.php';

if ($playground_is_uat) {
    define('CREDITLAB_AUTOCOLLECT_FORCE_UAT', true);
}

require_once __DIR__ . '/../lib/easebuzz_autocollect.php';

if ($playground_is_prod) {
    if (!creditlab_autocollect_playground_prod_allowed()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die("Autocollect prod logs are disabled. Set EASEBUZZ_AUTOCOLLECT_PLAYGROUND=1 in .env.\n");
    }
} elseif (!creditlab_autocollect_playground_allowed()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    die("Autocollect playground is disabled.\n");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_logs') {
    creditlab_autocollect_clear_web_logs();
    header('Location: ' . $playground_logs . '?cleared=1');
    exit;
}

$cleared = isset($_GET['cleared']);
$log_count = count(creditlab_autocollect_read_web_logs(10000));
$env_label = creditlab_autocollect_env_label() . ($playground_is_uat ? ' (forced UAT)' : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autocollect API Logs<?= $playground_is_prod ? ' (PROD)' : '' ?></title>
    <style>
        body { font-family: sans-serif; margin: 2em; background: #f4f4f9; color: #333; }
        .container { max-width: 1100px; margin: auto; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 20px; }
        h1, h2 { color: #444; margin-top: 0; }
        button { padding: 8px 14px; font-size: 1rem; border-radius: 5px; border: 1px solid #dc3545; background: #dc3545; color: #fff; cursor: pointer; }
        button.secondary { background: #6c757d; border-color: #6c757d; }
        .hint { color: #666; font-size: 13px; margin: 6px 0 0; }
        .banner { background: #d4edda; border: 1px solid #28a745; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .uat-banner { background: #cfe2ff; border: 1px solid #0d6efd; }
        .prod-banner { background: #f8d7da; border: 1px solid #dc3545; color: #721c24; }
        code { background: #eee; padding: 1px 4px; border-radius: 3px; }
        .nav-links { margin-bottom: 16px; }
        .nav-links a { margin-right: 16px; }
        .log-entry { margin-bottom: 12px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; }
        .log-meta { padding: 8px 12px; background: #f8f9fa; border-bottom: 1px solid #eee; font-size: 13px; }
        .log-body { margin: 0; padding: 10px 12px; font-size: 12px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; }
        .toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="container">
    <div class="nav-links">
        <?php if ($playground_is_uat): ?>
        <a href="autocollect_playground.php"><strong>UAT playground</strong></a>
        <a href="autocollect_logs.php"><strong>UAT logs</strong></a>
        <a href="autocollect_playground_prod.php">Prod playground</a>
        <?php else: ?>
        <a href="autocollect_playground.php">UAT playground</a>
        <a href="autocollect_playground_prod.php"><strong>Prod playground</strong></a>
        <a href="autocollect_logs_prod.php"><strong>Prod logs</strong></a>
        <?php endif; ?>
    </div>

    <h1>Autocollect API Logs<?= $playground_is_prod ? ' <span style="color:#dc3545;">(PROD)</span>' : '' ?></h1>
    <p class="hint">
        <?= htmlspecialchars($env_label, ENT_QUOTES) ?>
        · API <code><?= htmlspecialchars(creditlab_autocollect_base_url(), ENT_QUOTES) ?></code>
        · Merchant key <code><?= htmlspecialchars(creditlab_autocollect_merchant_key(), ENT_QUOTES) ?></code>
        · <?= (int) $log_count ?> entries
    </p>

    <div class="banner <?= $playground_is_prod ? 'prod-banner' : 'uat-banner' ?>">
        Every Autocollect API call from the <?= $playground_is_prod ? 'prod' : 'UAT' ?> playground is stored here.
        File: <code>logs/<?= htmlspecialchars($log_file_basename, ENT_QUOTES) ?></code>
    </div>

    <?php if ($cleared): ?>
        <div class="banner">Log cleared.</div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <form method="POST" onsubmit="return confirm('Clear all API log entries?');">
                <input type="hidden" name="action" value="clear_logs">
                <button type="submit">Clear log</button>
            </form>
            <a href="<?= htmlspecialchars($playground_logs, ENT_QUOTES) ?>"><button type="button" class="secondary">Refresh</button></a>
        </div>

        <?= creditlab_autocollect_render_web_logs_html(500) ?>
    </div>
</div>
</body>
</html>

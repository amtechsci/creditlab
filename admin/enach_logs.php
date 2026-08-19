<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/easebuzz_enach_user_log.php';

creditlab_ensure_easebuzz_enach_event_log_table();

$filters = [
    'uid' => isset($_GET['uid']) ? (int) $_GET['uid'] : 0,
    'mobile' => isset($_GET['mobile']) ? trim((string) $_GET['mobile']) : '',
    'transaction_id' => isset($_GET['transaction_id']) ? trim((string) $_GET['transaction_id']) : '',
    'outcome' => isset($_GET['outcome']) ? trim((string) $_GET['outcome']) : 'all',
    'stage' => isset($_GET['stage']) ? trim((string) $_GET['stage']) : 'all',
    'date_from' => isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '',
    'date_to' => isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '',
    'search' => isset($_GET['search']) ? trim((string) $_GET['search']) : '',
    'limit' => 500,
];

$result = creditlab_easebuzz_query_user_events($filters);
$rows = $result['rows'];
$totals = $result['totals'];

function enach_logs_qs(array $extra = []): string
{
    global $filters;
    $params = [];
    if ($filters['uid'] > 0) {
        $params['uid'] = $filters['uid'];
    }
    if ($filters['mobile'] !== '') {
        $params['mobile'] = $filters['mobile'];
    }
    if ($filters['transaction_id'] !== '') {
        $params['transaction_id'] = $filters['transaction_id'];
    }
    if ($filters['outcome'] !== '' && $filters['outcome'] !== 'all') {
        $params['outcome'] = $filters['outcome'];
    }
    if ($filters['stage'] !== '' && $filters['stage'] !== 'all') {
        $params['stage'] = $filters['stage'];
    }
    if ($filters['date_from'] !== '') {
        $params['date_from'] = $filters['date_from'];
    }
    if ($filters['date_to'] !== '') {
        $params['date_to'] = $filters['date_to'];
    }
    if ($filters['search'] !== '') {
        $params['search'] = $filters['search'];
    }
    $params = array_merge($params, $extra);

    return $params === [] ? '' : '?' . http_build_query($params);
}

$outcome_class = [
    'success' => 'success',
    'failure' => 'danger',
    'pending' => 'warning',
];
?>
<body>
<?php
include_once 'Left_menu.php';
include_once 'welcome.php';
include_once 'm_menu.php';
?>
<div class="breadcome-area">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcome-list">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="breadcome-heading"><h4>e-NACH User Logs</h4></div>
                        </div>
                        <div class="col-lg-6">
                            <ul class="breadcome-menu">
                                <li><a href="index.php">Home</a> <span class="bread-slash">/</span></li>
                                <li><span class="bread-blod">e-NACH Logs</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="single-pro-review-area mt-t-30 mg-b-15">
<div class="container-fluid">
<div class="row">
<div class="col-lg-12">
<div class="product-payment-inner-st">

<p class="text-muted">Per-user mandate signup, callback, and presentment events (success / failure / pending). Table auto-creates on first event.</p>

<div class="row" style="margin-bottom:16px;">
    <div class="col-md-3"><div class="alert alert-success" style="margin:0;">Success: <strong><?= (int) $totals['success'] ?></strong></div></div>
    <div class="col-md-3"><div class="alert alert-danger" style="margin:0;">Failure: <strong><?= (int) $totals['failure'] ?></strong></div></div>
    <div class="col-md-3"><div class="alert alert-warning" style="margin:0;">Pending: <strong><?= (int) $totals['pending'] ?></strong></div></div>
    <div class="col-md-3"><div class="alert alert-info" style="margin:0;">Total: <strong><?= (int) $totals['all'] ?></strong></div></div>
</div>

<form method="get" class="form-inline" style="margin-bottom:20px;flex-wrap:wrap;gap:8px;">
    <input type="number" name="uid" class="form-control" placeholder="User ID" value="<?= $filters['uid'] > 0 ? (int) $filters['uid'] : '' ?>" style="width:100px;">
    <input type="text" name="mobile" class="form-control" placeholder="Mobile" value="<?= htmlspecialchars($filters['mobile'], ENT_QUOTES) ?>" style="width:130px;">
    <input type="text" name="transaction_id" class="form-control" placeholder="cai… / txn id" value="<?= htmlspecialchars($filters['transaction_id'], ENT_QUOTES) ?>" style="width:200px;">
    <select name="outcome" class="form-control">
        <option value="all"<?= $filters['outcome'] === 'all' ? ' selected' : '' ?>>All outcomes</option>
        <option value="success"<?= $filters['outcome'] === 'success' ? ' selected' : '' ?>>Success</option>
        <option value="failure"<?= $filters['outcome'] === 'failure' ? ' selected' : '' ?>>Failure</option>
        <option value="pending"<?= $filters['outcome'] === 'pending' ? ' selected' : '' ?>>Pending</option>
    </select>
    <select name="stage" class="form-control">
        <option value="all"<?= $filters['stage'] === 'all' ? ' selected' : '' ?>>All stages</option>
        <option value="mandate_start"<?= $filters['stage'] === 'mandate_start' ? ' selected' : '' ?>>Mandate start</option>
        <option value="mandate_callback"<?= $filters['stage'] === 'mandate_callback' ? ' selected' : '' ?>>Mandate callback</option>
        <option value="presentment"<?= $filters['stage'] === 'presentment' ? ' selected' : '' ?>>Presentment (initiated)</option>
        <option value="presentment_webhook"<?= $filters['stage'] === 'presentment_webhook' ? ' selected' : '' ?>>Presentment webhook</option>
    </select>
    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES) ?>">
    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES) ?>">
    <input type="text" name="search" class="form-control" placeholder="Search message…" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES) ?>" style="width:160px;">
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="enach_logs.php" class="btn btn-default">Reset</a>
</form>

<div class="table-responsive">
<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th>Time</th>
            <th>User</th>
            <th>Mobile</th>
            <th>Stage</th>
            <th>Outcome</th>
            <th>API</th>
            <th>Transaction ID</th>
            <th>Auth</th>
            <th>Amount</th>
            <th>Message</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="10" class="text-center text-muted">No log entries match your filters.</td></tr>
    <?php else: ?>
        <?php foreach ($rows as $log): ?>
            <?php
            $oc = (string) ($log['outcome'] ?? 'pending');
            $badge = $outcome_class[$oc] ?? 'default';
            ?>
            <tr>
                <td style="white-space:nowrap;font-size:12px;"><?= htmlspecialchars((string) $log['created_at'], ENT_QUOTES) ?></td>
                <td><?php if ((int) $log['uid'] > 0): ?><a href="profile.php?id=<?= (int) $log['uid'] ?>"><?= (int) $log['uid'] ?></a><?php else: ?>—<?php endif; ?></td>
                <td><?= htmlspecialchars((string) $log['mobile'], ENT_QUOTES) ?></td>
                <td><code><?= htmlspecialchars((string) $log['stage'], ENT_QUOTES) ?></code></td>
                <td><span class="label label-<?= $badge ?>"><?= htmlspecialchars($oc, ENT_QUOTES) ?></span></td>
                <td><?= htmlspecialchars((string) $log['api'], ENT_QUOTES) ?: '—' ?></td>
                <td style="font-size:11px;word-break:break-all;max-width:180px;"><?= htmlspecialchars((string) $log['transaction_id'], ENT_QUOTES) ?: '—' ?></td>
                <td><?= htmlspecialchars((string) $log['auth_mode'], ENT_QUOTES) ?: '—' ?></td>
                <td><?= $log['amount'] !== null && $log['amount'] !== '' ? '₹' . htmlspecialchars((string) $log['amount'], ENT_QUOTES) : '—' ?></td>
                <td style="font-size:12px;max-width:280px;"><?= htmlspecialchars((string) $log['message'], ENT_QUOTES) ?>
                    <?php if (!empty($log['meta_json'])): ?>
                        <details style="margin-top:4px;"><summary style="cursor:pointer;font-size:11px;color:#666;">meta</summary>
                        <pre style="font-size:10px;max-height:120px;overflow:auto;margin:4px 0 0;"><?= htmlspecialchars((string) $log['meta_json'], ENT_QUOTES) ?></pre></details>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>
<p class="text-muted" style="font-size:12px;">Showing up to <?= count($rows) ?> events (newest first).</p>

</div>
</div>
</div>
</div>
</div>

<?php include_once 'foot.php'; ?>
</body>
</html>

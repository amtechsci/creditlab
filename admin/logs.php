<?php
$allowed_tabs = ['files', 'enach'];
$active_tab = isset($_GET['tab']) ? strtolower(trim((string) $_GET['tab'])) : 'files';
if (!in_array($active_tab, $allowed_tabs, true)) {
    $active_tab = 'files';
}

if (isset($_GET['download'])) {
    include __DIR__ . '/../db.php';
    if (!isset($admin)) {
        header('location:/account/login.php');
        exit;
    }
    require_once __DIR__ . '/../lib/admin_log_files.php';
    $selectedRelativePath = isset($_GET['file']) ? trim((string) $_GET['file']) : '';
    if ($selectedRelativePath !== '') {
        $filePath = creditlab_admin_resolve_log_path($selectedRelativePath);
        if ($filePath !== null) {
            $downloadName = basename($filePath);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $downloadName . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
    http_response_code(404);
    exit('File not found');
}

include_once 'head.php';

$filterQueryBase = ['tab' => 'files'];
$allLogs = [];
$totals = ['cron' => 0, 'webhook' => 0, 'sms' => 0, 'other' => 0, 'all' => 0];
$filteredSize = 0;
$selectedLog = null;
$logContent = null;
$selectedRelativePath = '';
$filterDateFrom = '';
$filterDateTo = '';
$filterType = 'all';
$filterSearch = '';

$enachFilters = [];
$enachRows = [];
$enachTotals = ['success' => 0, 'failure' => 0, 'pending' => 0, 'all' => 0];

function log_viewer_build_query(array $extra = []): string
{
    global $filterQueryBase;
    $params = array_merge($filterQueryBase, $extra);
    $params['tab'] = 'files';
    return '?' . http_build_query($params);
}

function enach_logs_qs(array $extra = []): string
{
    global $enachFilters;
    $params = ['tab' => 'enach'];
    if (($enachFilters['uid'] ?? 0) > 0) {
        $params['uid'] = $enachFilters['uid'];
    }
    if (($enachFilters['mobile'] ?? '') !== '') {
        $params['mobile'] = $enachFilters['mobile'];
    }
    if (($enachFilters['transaction_id'] ?? '') !== '') {
        $params['transaction_id'] = $enachFilters['transaction_id'];
    }
    if (($enachFilters['outcome'] ?? '') !== '' && ($enachFilters['outcome'] ?? '') !== 'all') {
        $params['outcome'] = $enachFilters['outcome'];
    }
    if (($enachFilters['stage'] ?? '') !== '' && ($enachFilters['stage'] ?? '') !== 'all') {
        $params['stage'] = $enachFilters['stage'];
    }
    if (($enachFilters['date_from'] ?? '') !== '') {
        $params['date_from'] = $enachFilters['date_from'];
    }
    if (($enachFilters['date_to'] ?? '') !== '') {
        $params['date_to'] = $enachFilters['date_to'];
    }
    if (($enachFilters['search'] ?? '') !== '') {
        $params['search'] = $enachFilters['search'];
    }
    $params = array_merge($params, $extra);

    return '?' . http_build_query($params);
}

$typeBadgeClass = [
    'cron' => 'primary',
    'webhook' => 'success',
    'sms' => 'warning',
    'other' => 'default',
];
$outcome_class = [
    'success' => 'success',
    'failure' => 'danger',
    'pending' => 'warning',
];

if ($active_tab === 'files') {
    require_once __DIR__ . '/../lib/admin_log_files.php';

    $filterDateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
    $filterDateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';
    $filterType = isset($_GET['type']) ? trim((string) $_GET['type']) : 'all';
    $filterSearch = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

    $listResult = creditlab_admin_list_logs([
        'date_from' => $filterDateFrom,
        'date_to' => $filterDateTo,
        'type' => $filterType,
        'search' => $filterSearch,
    ]);
    $allLogs = $listResult['rows'];
    $totals = $listResult['totals'];

    if ($filterDateFrom !== '') {
        $filterQueryBase['date_from'] = $filterDateFrom;
    }
    if ($filterDateTo !== '') {
        $filterQueryBase['date_to'] = $filterDateTo;
    }
    if ($filterType !== '' && $filterType !== 'all') {
        $filterQueryBase['type'] = $filterType;
    }
    if ($filterSearch !== '') {
        $filterQueryBase['search'] = $filterSearch;
    }

    $selectedRelativePath = isset($_GET['file']) ? trim((string) $_GET['file']) : '';
    if (isset($_GET['view']) && $selectedRelativePath !== '') {
        $resolved = creditlab_admin_resolve_log_path($selectedRelativePath);
        if ($resolved !== null) {
            $selectedLog = $selectedRelativePath;
            $lines = isset($_GET['lines']) ? max(10, (int) $_GET['lines']) : 100;
            $logContent = creditlab_admin_read_log_tail($selectedRelativePath, $lines);
            if (isset($logContent['error'])) {
                $selectedLog = null;
                $logContent = null;
            }
        }
    }
    $filteredSize = array_sum(array_column($allLogs, 'size'));
} else {
    require_once __DIR__ . '/../lib/easebuzz_enach_user_log.php';
    creditlab_ensure_easebuzz_enach_event_log_table();

    $enachFilters = [
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

    $result = creditlab_easebuzz_query_user_events($enachFilters);
    $enachRows = $result['rows'];
    $enachTotals = $result['totals'];
}
?>
<body>
<?php include_once 'Left_menu.php'; include_once 'welcome.php'; include_once 'm_menu.php'; ?>

            <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <h2 style="margin:0 0 6px;"><i class="fa fa-file-text-o"></i> Logs</h2>
                                <p style="margin:0;color:#666;">File logs and e-NACH user events</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .logs-wrap { padding-bottom: 40px; }
            .settings-nav.nav-tabs {
                display: flex;
                flex-wrap: wrap;
                margin: 0 0 22px;
                padding: 0;
                list-style: none;
                border: 0;
                border-bottom: 2px solid #e8ecef;
                background: #fff;
            }
            .settings-nav > li { float: none; margin: 0; }
            .settings-nav > li > a {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 14px 22px;
                color: #6b7280;
                font-weight: 600;
                font-size: 15px;
                text-decoration: none;
                border: 0 !important;
                border-bottom: 3px solid transparent !important;
                margin-bottom: -2px;
                background: transparent !important;
                border-radius: 0;
            }
            .settings-nav > li > a:hover,
            .settings-nav > li > a:focus {
                background: transparent !important;
                color: #00a57d;
                text-decoration: none;
            }
            .settings-nav > li.active > a,
            .settings-nav > li.active > a:hover,
            .settings-nav > li.active > a:focus {
                color: #00a57d;
                background: transparent !important;
                border-bottom-color: #00c195 !important;
            }
            .log-manager-panel,
            .log-view-panel {
                background: #fff;
                padding: 20px;
                margin-bottom: 20px;
                border: 1px solid #e7e7e7;
                border-radius: 4px;
                overflow: visible;
            }
            .log-filter-form .form-group { margin-bottom: 12px; }
            .log-filter-form label { display: block; margin-bottom: 4px; font-weight: 600; }
            .log-filter-actions { margin-top: 4px; margin-bottom: 8px; }
            .log-filter-actions .btn { margin-right: 8px; }
            .log-bulk-title { margin-bottom: 10px; }
            .log-bulk-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
            .log-bulk-label { margin: 0; font-weight: 600; }
            .log-bulk-days { width: 120px; display: inline-block; }
            .log-table-wrap { max-height: 560px; overflow: auto; margin-top: 10px; border: 1px solid #e7e7e7; }
            #logFilesTable { margin-bottom: 0; font-size: 13px; }
            #logFilesTable thead th { background: #f5f5f5; white-space: nowrap; vertical-align: middle; }
            #logFilesTable .col-check { width: 40px; }
            #logFilesTable .col-actions { min-width: 200px; }
            .log-file-cell { min-width: 200px; max-width: 320px; }
            .log-file-path { font-size: 11px; color: #888; margin-top: 2px; word-break: break-all; }
            .log-modified-cell { white-space: nowrap; font-size: 12px; }
            .log-actions-cell .btn { margin-bottom: 2px; }
            .log-content {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 15px;
                max-height: 600px;
                overflow-y: auto;
                font-family: 'Courier New', monospace;
                font-size: 12px;
                line-height: 1.4;
            }
            .log-content pre {
                margin: 0;
                white-space: pre-wrap;
                word-wrap: break-word;
                background: transparent;
                border: none;
                padding: 0;
            }
            .contact-panel .panel-body { padding: 20px; }
            .social-media-inner { display: flex; justify-content: space-between; align-items: center; }
            .contact-left h4 { margin: 0 0 5px 0; font-size: 16px; }
            .contact-left p { margin: 0; color: #666; font-size: 14px; }
            .contact-right i { font-size: 24px; opacity: 0.7; }
            .enach-filters { margin-bottom: 20px; }
            .enach-filters .form-control { margin: 0 6px 8px 0; display: inline-block; width: auto; }
        </style>

        <div class="single-pro-review-area mt-t-30 mg-b-15 logs-wrap">
            <div class="container-fluid">
                <ul class="nav nav-tabs settings-nav">
                    <li class="<?= $active_tab === 'files' ? 'active' : '' ?>">
                        <a href="logs.php?tab=files"><i class="fa fa-file-text-o"></i> Log Files</a>
                    </li>
                    <li class="<?= $active_tab === 'enach' ? 'active' : '' ?>">
                        <a href="logs.php?tab=enach"><i class="fa fa-university"></i> e-NACH</a>
                    </li>
                </ul>

                <?php if ($active_tab === 'files'): ?>
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                <div class="hpanel hblue contact-panel">
                                    <div class="panel-body">
                                        <div class="social-media-inner">
                                            <div class="contact-left">
                                                <h4>Cron Logs</h4>
                                                <p><?= (int) $totals['cron'] ?> files</p>
                                            </div>
                                            <div class="contact-right"><i class="fa fa-clock-o"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                <div class="hpanel hgreen contact-panel">
                                    <div class="panel-body">
                                        <div class="social-media-inner">
                                            <div class="contact-left">
                                                <h4>Webhook Logs</h4>
                                                <p><?= (int) $totals['webhook'] ?> files</p>
                                            </div>
                                            <div class="contact-right"><i class="fa fa-exchange"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                <div class="hpanel hred contact-panel">
                                    <div class="panel-body">
                                        <div class="social-media-inner">
                                            <div class="contact-left">
                                                <h4>Showing</h4>
                                                <p><?= count($allLogs) ?> of <?= (int) $totals['all'] ?> files</p>
                                            </div>
                                            <div class="contact-right"><i class="fa fa-file-text-o"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                <div class="hpanel hyellow contact-panel">
                                    <div class="panel-body">
                                        <div class="social-media-inner">
                                            <div class="contact-left">
                                                <h4>Filtered Size</h4>
                                                <p><?= creditlab_admin_format_file_size($filteredSize) ?></p>
                                            </div>
                                            <div class="contact-right"><i class="fa fa-hdd-o"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="log-manager-panel">
                                    <h4>Search &amp; filters</h4>
                                    <form method="get" action="logs.php" class="log-filter-form">
                                        <input type="hidden" name="tab" value="files">
                                        <div class="row">
                                            <div class="col-md-3 col-sm-6">
                                                <div class="form-group">
                                                    <label for="filterDateFrom">Date from</label>
                                                    <input type="date" id="filterDateFrom" name="date_from" class="form-control" value="<?= htmlspecialchars($filterDateFrom) ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6">
                                                <div class="form-group">
                                                    <label for="filterDateTo">Date to</label>
                                                    <input type="date" id="filterDateTo" name="date_to" class="form-control" value="<?= htmlspecialchars($filterDateTo) ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-6">
                                                <div class="form-group">
                                                    <label for="filterType">Type</label>
                                                    <select id="filterType" name="type" class="form-control">
                                                        <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>All</option>
                                                        <option value="cron" <?= $filterType === 'cron' ? 'selected' : '' ?>>Cron</option>
                                                        <option value="webhook" <?= $filterType === 'webhook' ? 'selected' : '' ?>>Webhook</option>
                                                        <option value="sms" <?= $filterType === 'sms' ? 'selected' : '' ?>>SMS</option>
                                                        <option value="other" <?= $filterType === 'other' ? 'selected' : '' ?>>Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-group">
                                                    <label for="filterSearch">Search filename</label>
                                                    <input type="text" id="filterSearch" name="search" class="form-control" placeholder="webhook, enach..." value="<?= htmlspecialchars($filterSearch) ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="log-filter-actions">
                                            <button type="submit" class="btn btn-primary">Apply filters</button>
                                            <a href="logs.php?tab=files" class="btn btn-default">Reset</a>
                                        </div>
                                    </form>

                                    <hr>
                                    <div class="log-bulk-bar">
                                        <h4 class="log-bulk-title">Bulk delete <small class="text-muted">(admin only, permanent)</small></h4>
                                        <div class="log-bulk-actions">
                                            <button type="button" class="btn btn-danger" id="btnDeleteSelected" disabled onclick="confirmDeleteSelected()">
                                                <i class="fa fa-trash"></i> Delete selected
                                            </button>
                                            <label for="deleteOlderDays" class="log-bulk-label">Older than</label>
                                            <select id="deleteOlderDays" class="form-control log-bulk-days">
                                                <option value="7">7 days</option>
                                                <option value="30">30 days</option>
                                                <option value="90" selected>90 days</option>
                                                <option value="180">180 days</option>
                                            </select>
                                            <button type="button" class="btn btn-danger" onclick="confirmDeleteOlder()">
                                                <i class="fa fa-trash"></i> Delete by age
                                            </button>
                                            <span id="selectedSizeHint" class="text-muted small"></span>
                                        </div>
                                    </div>

                                    <h4 style="margin-top:20px;">Log files</h4>
                                    <div class="table-responsive log-table-wrap">
                                        <table class="table table-striped table-bordered table-hover" id="logFilesTable">
                                            <thead>
                                                <tr>
                                                    <th class="col-check">
                                                        <input type="checkbox" id="selectAllLogs" title="Select all">
                                                    </th>
                                                    <th>File</th>
                                                    <th>Type</th>
                                                    <th>Date</th>
                                                    <th>Size</th>
                                                    <th>Modified</th>
                                                    <th class="col-actions">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($allLogs as $log):
                                                    $rel = $log['relative_path'];
                                                    $viewQ = log_viewer_build_query(['view' => 1, 'file' => $rel]);
                                                    $badge = $typeBadgeClass[$log['type']] ?? 'default';
                                                ?>
                                                <tr class="log-row" data-path="<?= htmlspecialchars($rel) ?>" data-size="<?= (int) $log['size'] ?>">
                                                    <td>
                                                        <input type="checkbox" class="log-checkbox" value="<?= htmlspecialchars($rel) ?>">
                                                    </td>
                                                    <td class="log-file-cell">
                                                        <a href="logs.php<?= htmlspecialchars($viewQ) ?>"><?= htmlspecialchars($log['name']) ?></a>
                                                        <div class="log-file-path"><?= htmlspecialchars($rel) ?></div>
                                                    </td>
                                                    <td><span class="label label-<?= $badge ?>"><?= htmlspecialchars($log['type']) ?></span></td>
                                                    <td><?= htmlspecialchars($log['date']) ?></td>
                                                    <td><?= creditlab_admin_format_file_size((int) $log['size']) ?></td>
                                                    <td class="log-modified-cell"><?= date('Y-m-d H:i:s', $log['modified']) ?></td>
                                                    <td class="log-actions-cell">
                                                        <a href="logs.php<?= htmlspecialchars($viewQ) ?>" class="btn btn-primary btn-xs">View</a>
                                                        <a href="logs.php<?= htmlspecialchars(log_viewer_build_query(['download' => 1, 'file' => $rel])) ?>" class="btn btn-success btn-xs">Download</a>
                                                        <button type="button" class="btn btn-danger btn-xs" onclick="confirmDeleteSingle('<?= htmlspecialchars($rel, ENT_QUOTES) ?>', '<?= htmlspecialchars($log['name'], ENT_QUOTES) ?>')">Delete</button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if ($allLogs === []): ?>
                                                <tr><td colspan="7" class="text-center text-muted">No log files match your filters.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                        </div>

                                <?php if ($selectedLog && $logContent): ?>
                                <div class="log-view-panel">
                                    <h4>
                                        Viewing: <?= htmlspecialchars($selectedLog) ?>
                                        <div class="pull-right">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                    Lines: <?= (int) $logContent['showing_lines'] ?> <span class="caret"></span>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php foreach ([50, 100, 500, 1000] as $n): ?>
                                                    <li><a href="logs.php<?= htmlspecialchars(log_viewer_build_query(['view' => 1, 'file' => $selectedLog, 'lines' => $n])) ?>">Last <?= $n ?> lines</a></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <?php
                                            $ar = isset($_GET['autorefresh']) && $_GET['autorefresh'] === '1' ? '0' : '1';
                                            $arLabel = $ar === '1' ? 'Start Auto-Refresh' : 'Stop Auto-Refresh';
                                            $arIcon = $ar === '1' ? 'play' : 'pause';
                                            $arClass = $ar === '1' ? 'default' : 'warning';
                                            ?>
                                            <a href="logs.php<?= htmlspecialchars(log_viewer_build_query(['view' => 1, 'file' => $selectedLog, 'autorefresh' => $ar])) ?>" class="btn btn-<?= $arClass ?> btn-sm">
                                                <i class="fa fa-<?= $arIcon ?>"></i> <?= $arLabel ?>
                                            </a>
                                            <a href="logs.php<?= htmlspecialchars(log_viewer_build_query(['download' => 1, 'file' => $selectedLog])) ?>" class="btn btn-success btn-sm"><i class="fa fa-download"></i> Download</a>
                                        </div>
                                    </h4>
                                    <p class="text-muted">
                                        Showing last <?= (int) $logContent['showing_lines'] ?> lines of <?= (int) $logContent['total_lines'] ?> total lines
                                    </p>
                                    <div class="log-content">
                                        <pre><?= htmlspecialchars($logContent['content']) ?></pre>
                                    </div>
                                </div>
                                <?php elseif ($selectedRelativePath !== '' && !$selectedLog): ?>
                                <div class="alert alert-warning">Log file not found or was deleted. <a href="logs.php<?= htmlspecialchars(log_viewer_build_query()) ?>">Back to list</a></div>
                                <?php endif; ?>

                <?php else: ?>
                    <p class="text-muted">Per-user mandate signup, callback, and presentment events (success / failure / pending). Table auto-creates on first event.</p>

                    <div class="row" style="margin-bottom:16px;">
                        <div class="col-md-3"><div class="alert alert-success" style="margin:0;">Success: <strong><?= (int) $enachTotals['success'] ?></strong></div></div>
                        <div class="col-md-3"><div class="alert alert-danger" style="margin:0;">Failure: <strong><?= (int) $enachTotals['failure'] ?></strong></div></div>
                        <div class="col-md-3"><div class="alert alert-warning" style="margin:0;">Pending: <strong><?= (int) $enachTotals['pending'] ?></strong></div></div>
                        <div class="col-md-3"><div class="alert alert-info" style="margin:0;">Total: <strong><?= (int) $enachTotals['all'] ?></strong></div></div>
                    </div>

                    <form method="get" action="logs.php" class="enach-filters">
                        <input type="hidden" name="tab" value="enach">
                        <input type="number" name="uid" class="form-control" placeholder="User ID" value="<?= $enachFilters['uid'] > 0 ? (int) $enachFilters['uid'] : '' ?>" style="width:100px;">
                        <input type="text" name="mobile" class="form-control" placeholder="Mobile" value="<?= htmlspecialchars($enachFilters['mobile'], ENT_QUOTES) ?>" style="width:130px;">
                        <input type="text" name="transaction_id" class="form-control" placeholder="cai… / txn id" value="<?= htmlspecialchars($enachFilters['transaction_id'], ENT_QUOTES) ?>" style="width:200px;">
                        <select name="outcome" class="form-control">
                            <option value="all"<?= $enachFilters['outcome'] === 'all' ? ' selected' : '' ?>>All outcomes</option>
                            <option value="success"<?= $enachFilters['outcome'] === 'success' ? ' selected' : '' ?>>Success</option>
                            <option value="failure"<?= $enachFilters['outcome'] === 'failure' ? ' selected' : '' ?>>Failure</option>
                            <option value="pending"<?= $enachFilters['outcome'] === 'pending' ? ' selected' : '' ?>>Pending</option>
                        </select>
                        <select name="stage" class="form-control">
                            <option value="all"<?= $enachFilters['stage'] === 'all' ? ' selected' : '' ?>>All stages</option>
                            <option value="mandate_start"<?= $enachFilters['stage'] === 'mandate_start' ? ' selected' : '' ?>>Mandate start</option>
                            <option value="mandate_callback"<?= $enachFilters['stage'] === 'mandate_callback' ? ' selected' : '' ?>>Mandate callback</option>
                            <option value="presentment"<?= $enachFilters['stage'] === 'presentment' ? ' selected' : '' ?>>Presentment (initiated)</option>
                            <option value="presentment_webhook"<?= $enachFilters['stage'] === 'presentment_webhook' ? ' selected' : '' ?>>Presentment webhook</option>
                        </select>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($enachFilters['date_from'], ENT_QUOTES) ?>">
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($enachFilters['date_to'], ENT_QUOTES) ?>">
                        <input type="text" name="search" class="form-control" placeholder="Search message…" value="<?= htmlspecialchars($enachFilters['search'], ENT_QUOTES) ?>" style="width:160px;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="logs.php?tab=enach" class="btn btn-default">Reset</a>
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
                        <?php if (!$enachRows): ?>
                            <tr><td colspan="10" class="text-center text-muted">No log entries match your filters.</td></tr>
                        <?php else: ?>
                            <?php foreach ($enachRows as $log): ?>
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
                    <p class="text-muted" style="font-size:12px;">Showing up to <?= count($enachRows) ?> events (newest first).</p>
                <?php endif; ?>
            </div>
        </div>

    <?php if ($active_tab === 'files'): ?>
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Confirm delete</h4>
                </div>
                <div class="modal-body">
                    <p id="deleteConfirmMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deleteConfirmBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php include_once 'foot.php'; ?>

<?php if ($active_tab === 'files'): ?>
    <script>
    let pendingDeleteAction = null;

    function formatBytes(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' bytes';
    }

    function getSelectedPaths() {
        return Array.from(document.querySelectorAll('.log-checkbox:checked')).map(function(cb) { return cb.value; });
    }

    function updateSelectionUi() {
        const paths = getSelectedPaths();
        const btn = document.getElementById('btnDeleteSelected');
        const hint = document.getElementById('selectedSizeHint');
        btn.disabled = paths.length === 0;
        let totalSize = 0;
        document.querySelectorAll('.log-row').forEach(function(row) {
            const cb = row.querySelector('.log-checkbox');
            if (cb && cb.checked) {
                totalSize += parseInt(row.getAttribute('data-size') || '0', 10);
            }
        });
        if (paths.length > 0) {
            hint.textContent = paths.length + ' selected (' + formatBytes(totalSize) + ')';
        } else {
            hint.textContent = '';
        }
        const all = document.querySelectorAll('.log-checkbox');
        const selectAll = document.getElementById('selectAllLogs');
        if (selectAll) {
            selectAll.checked = all.length > 0 && paths.length === all.length;
        }
    }

    document.getElementById('selectAllLogs').addEventListener('change', function() {
        const checked = this.checked;
        document.querySelectorAll('.log-checkbox').forEach(function(cb) { cb.checked = checked; });
        updateSelectionUi();
    });

    document.querySelectorAll('.log-checkbox').forEach(function(cb) {
        cb.addEventListener('change', updateSelectionUi);
    });

    function showDeleteModal(message, action) {
        document.getElementById('deleteConfirmMessage').textContent = message;
        pendingDeleteAction = action;
        $('#deleteConfirmModal').modal('show');
    }

    document.getElementById('deleteConfirmBtn').addEventListener('click', function() {
        if (pendingDeleteAction) {
            pendingDeleteAction();
        }
        $('#deleteConfirmModal').modal('hide');
    });

    function postLogAction(payload) {
        const fd = new FormData();
        Object.keys(payload).forEach(function(k) {
            const v = payload[k];
            if (Array.isArray(v)) {
                v.forEach(function(item) { fd.append(k, item); });
            } else {
                fd.append(k, v);
            }
        });
        return fetch('/api/admin_logs.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json().then(function(j) { return { status: r.status, body: j }; }); });
    }

    function reloadAfterDelete() {
        window.location.href = 'logs.php<?= htmlspecialchars(log_viewer_build_query(), ENT_QUOTES) ?>';
    }

    function confirmDeleteSingle(path, name) {
        showDeleteModal('Delete log file "' + name + '"? This cannot be undone.', function() {
            postLogAction({ action: 'delete', files: [path] }).then(function(res) {
                if (res.body.ok || (res.body.deleted && res.body.deleted.length)) {
                    reloadAfterDelete();
                } else {
                    alert('Delete failed: ' + (res.body.error || JSON.stringify(res.body.failed)));
                }
            }).catch(function() { alert('Delete request failed'); });
        });
    }

    function confirmDeleteSelected() {
        const paths = getSelectedPaths();
        if (!paths.length) return;
        showDeleteModal('Delete ' + paths.length + ' selected log file(s)? This cannot be undone.', function() {
            postLogAction({ action: 'delete', files: paths }).then(function(res) {
                if (res.body.ok || (res.body.deleted && res.body.deleted.length)) {
                    reloadAfterDelete();
                } else {
                    alert('Delete failed: ' + (res.body.error || JSON.stringify(res.body.failed)));
                }
            }).catch(function() { alert('Delete request failed'); });
        });
    }

    function confirmDeleteOlder() {
        const days = document.getElementById('deleteOlderDays').value;
        showDeleteModal('Delete all log files older than ' + days + ' days? This cannot be undone.', function() {
            postLogAction({ action: 'delete_older_than_days', days: days }).then(function(res) {
                if (res.body.ok || (res.body.deleted && res.body.deleted.length)) {
                    alert('Deleted ' + (res.body.deleted ? res.body.deleted.length : 0) + ' file(s). Freed ' + formatBytes(res.body.freed_bytes || 0));
                    reloadAfterDelete();
                } else if (res.body.deleted && res.body.deleted.length === 0) {
                    alert('No files matched the age criteria.');
                } else {
                    alert('Delete failed: ' + (res.body.error || JSON.stringify(res.body.failed)));
                }
            }).catch(function() { alert('Delete request failed'); });
        });
    }

    <?php if ($selectedLog && isset($_GET['autorefresh']) && $_GET['autorefresh'] === '1'): ?>
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            location.reload();
        }
    }, 1800000);
    <?php endif; ?>
    </script>
<?php endif; ?>
</body>
</html>

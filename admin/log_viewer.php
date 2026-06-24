<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

include_once 'head.php';
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
$allRowsUnfiltered = $listResult['all_rows'];

$filterQueryBase = [];
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

function log_viewer_build_query(array $extra = []): string
{
    global $filterQueryBase;
    $params = array_merge($filterQueryBase, $extra);
    return $params === [] ? '' : '?' . http_build_query($params);
}

$selectedLog = null;
$logContent = null;
$selectedRelativePath = isset($_GET['file']) ? trim((string) $_GET['file']) : '';

if (isset($_GET['download']) && $selectedRelativePath !== '') {
    $filePath = creditlab_admin_resolve_log_path($selectedRelativePath);
    if ($filePath !== null) {
        $downloadName = basename($filePath);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    http_response_code(404);
    exit('File not found');
}

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

$logsByDate = [];
foreach ($allRowsUnfiltered as $log) {
    $date = $log['date'];
    if (!isset($logsByDate[$date])) {
        $logsByDate[$date] = ['cron' => 0, 'webhook' => 0, 'sms' => 0, 'other' => 0, 'size' => 0];
    }
    $t = $log['type'];
    if (isset($logsByDate[$date][$t])) {
        $logsByDate[$date][$t]++;
    } else {
        $logsByDate[$date]['other']++;
    }
    $logsByDate[$date]['size'] += $log['size'];
}
ksort($logsByDate);

$filteredSize = array_sum(array_column($allLogs, 'size'));
$typeBadgeClass = [
    'cron' => 'primary',
    'webhook' => 'success',
    'sms' => 'warning',
    'other' => 'default',
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
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="breadcome-list">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                <div class="breadcome-heading">
                                    <h4>Log Manager</h4>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                <ul class="breadcome-menu">
                                    <li><a href="index.php">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Log Manager</span></li>
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
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <h4><i class="fa fa-play-circle"></i> E-NACH Auto Debit Dry Run</h4>
                                    </div>
                                    <div class="panel-body">
                                        <p>Run a dry run test of the E-NACH auto debit cron job without making API calls.</p>
                                        <button type="button" class="btn btn-warning btn-lg" id="dryRunBtn" onclick="runDryRun()">
                                            <i class="fa fa-play"></i> Run Dry Run Test
                                        </button>
                                        <div id="dryRunLoading" style="display: none; margin-top: 15px;">
                                            <i class="fa fa-spinner fa-spin"></i> Running dry run test, please wait...
                                        </div>
                                        <div id="dryRunResults" style="display: none; margin-top: 20px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

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

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="review-content-section">
                                    <h4>Recent activity by date</h4>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-condensed">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Cron</th>
                                                    <th>Webhook</th>
                                                    <th>SMS</th>
                                                    <th>Other</th>
                                                    <th>Size</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $recentDates = array_slice(array_keys($logsByDate), -7, 7, true);
                                                foreach ($recentDates as $date):
                                                    $stats = $logsByDate[$date];
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($date) ?></td>
                                                    <td><span class="badge badge-primary"><?= (int) $stats['cron'] ?></span></td>
                                                    <td><span class="badge badge-success"><?= (int) $stats['webhook'] ?></span></td>
                                                    <td><span class="badge badge-warning"><?= (int) $stats['sms'] ?></span></td>
                                                    <td><?= (int) $stats['other'] ?></td>
                                                    <td><?= creditlab_admin_format_file_size((int) $stats['size']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if ($recentDates === []): ?>
                                                <tr><td colspan="6" class="text-center text-muted">No log files found</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                <div class="review-content-section">
                                    <h4>Filters</h4>
                                    <form method="get" action="log_viewer.php" class="form-horizontal">
                                        <div class="form-group">
                                            <label>Date from</label>
                                            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filterDateFrom) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Date to</label>
                                            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filterDateTo) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Type</label>
                                            <select name="type" class="form-control">
                                                <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>All</option>
                                                <option value="cron" <?= $filterType === 'cron' ? 'selected' : '' ?>>Cron</option>
                                                <option value="webhook" <?= $filterType === 'webhook' ? 'selected' : '' ?>>Webhook</option>
                                                <option value="sms" <?= $filterType === 'sms' ? 'selected' : '' ?>>SMS</option>
                                                <option value="other" <?= $filterType === 'other' ? 'selected' : '' ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Search filename</label>
                                            <input type="text" name="search" class="form-control" placeholder="webhook, enach..." value="<?= htmlspecialchars($filterSearch) ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-block">Apply filters</button>
                                        <a href="log_viewer.php" class="btn btn-default btn-block">Reset</a>
                                    </form>

                                    <hr>
                                    <h4>Bulk delete</h4>
                                    <p class="text-muted small">Permanent. Admin only.</p>
                                    <button type="button" class="btn btn-danger btn-block" id="btnDeleteSelected" disabled onclick="confirmDeleteSelected()">
                                        <i class="fa fa-trash"></i> Delete selected
                                    </button>
                                    <div class="form-group" style="margin-top:10px;">
                                        <label>Delete older than</label>
                                        <select id="deleteOlderDays" class="form-control">
                                            <option value="7">7 days</option>
                                            <option value="30">30 days</option>
                                            <option value="90" selected>90 days</option>
                                            <option value="180">180 days</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-block" onclick="confirmDeleteOlder()">
                                        <i class="fa fa-trash"></i> Delete by age
                                    </button>
                                    <p id="selectedSizeHint" class="text-muted small" style="margin-top:8px;"></p>
                                </div>
                            </div>

                            <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
                                <div class="review-content-section">
                                    <h4>Log files</h4>
                                    <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                                        <table class="table table-striped table-bordered table-hover" id="logFilesTable">
                                            <thead>
                                                <tr>
                                                    <th style="width:36px;">
                                                        <input type="checkbox" id="selectAllLogs" title="Select all">
                                                    </th>
                                                    <th>File</th>
                                                    <th>Type</th>
                                                    <th>Date</th>
                                                    <th>Size</th>
                                                    <th>Modified</th>
                                                    <th>Actions</th>
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
                                                    <td>
                                                        <a href="log_viewer.php<?= htmlspecialchars($viewQ) ?>"><?= htmlspecialchars($log['name']) ?></a>
                                                        <br><small class="text-muted"><?= htmlspecialchars($rel) ?></small>
                                                    </td>
                                                    <td><span class="label label-<?= $badge ?>"><?= htmlspecialchars($log['type']) ?></span></td>
                                                    <td><?= htmlspecialchars($log['date']) ?></td>
                                                    <td><?= creditlab_admin_format_file_size((int) $log['size']) ?></td>
                                                    <td><?= date('Y-m-d H:i:s', $log['modified']) ?></td>
                                                    <td>
                                                        <a href="log_viewer.php<?= htmlspecialchars($viewQ) ?>" class="btn btn-primary btn-xs">View</a>
                                                        <a href="log_viewer.php<?= htmlspecialchars(log_viewer_build_query(['download' => 1, 'file' => $rel])) ?>" class="btn btn-success btn-xs">Download</a>
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
                                <div class="review-content-section" style="margin-top:20px;">
                                    <h4>
                                        Viewing: <?= htmlspecialchars($selectedLog) ?>
                                        <div class="pull-right">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                    Lines: <?= (int) $logContent['showing_lines'] ?> <span class="caret"></span>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php foreach ([50, 100, 500, 1000] as $n): ?>
                                                    <li><a href="log_viewer.php<?= htmlspecialchars(log_viewer_build_query(['view' => 1, 'file' => $selectedLog, 'lines' => $n])) ?>">Last <?= $n ?> lines</a></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <?php
                                            $ar = isset($_GET['autorefresh']) && $_GET['autorefresh'] === '1' ? '0' : '1';
                                            $arLabel = $ar === '1' ? 'Start Auto-Refresh' : 'Stop Auto-Refresh';
                                            $arIcon = $ar === '1' ? 'play' : 'pause';
                                            $arClass = $ar === '1' ? 'default' : 'warning';
                                            ?>
                                            <a href="log_viewer.php<?= htmlspecialchars(log_viewer_build_query(['view' => 1, 'file' => $selectedLog, 'autorefresh' => $ar])) ?>" class="btn btn-<?= $arClass ?> btn-sm">
                                                <i class="fa fa-<?= $arIcon ?>"></i> <?= $arLabel ?>
                                            </a>
                                            <a href="log_viewer.php<?= htmlspecialchars(log_viewer_build_query(['download' => 1, 'file' => $selectedLog])) ?>" class="btn btn-success btn-sm"><i class="fa fa-download"></i> Download</a>
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
                                <div class="alert alert-warning">Log file not found or was deleted. <a href="log_viewer.php<?= htmlspecialchars(log_viewer_build_query()) ?>">Back to list</a></div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <?php include_once 'foot.php'; ?>

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
        window.location.href = 'log_viewer.php<?= htmlspecialchars(log_viewer_build_query(), ENT_QUOTES) ?>';
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

    function runDryRun() {
        const btn = document.getElementById('dryRunBtn');
        const loading = document.getElementById('dryRunLoading');
        const results = document.getElementById('dryRunResults');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Running...';
        loading.style.display = 'block';
        results.style.display = 'none';
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '../payment/dry_run_enach.php', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-play"></i> Run Dry Run Test';
                loading.style.display = 'none';
                if (xhr.status === 200) {
                    const response = xhr.responseText;
                    let summaryHtml = '<div class="alert alert-info"><h4><i class="fa fa-info-circle"></i> Dry Run Results</h4>';
                    const eligibleMatch = response.match(/Total eligible loans found: (\d+)/i);
                    const successMatch = response.match(/Would-be Success: (\d+)/i);
                    const failedMatch = response.match(/Would-be Failed: (\d+)/i);
                    const skippedMatch = response.match(/Skipped: (\d+)/i);
                    summaryHtml += '<div class="row" style="margin-top: 15px;">';
                    if (eligibleMatch) summaryHtml += '<div class="col-md-3"><div class="alert alert-primary"><strong>Eligible:</strong><br><h3>' + eligibleMatch[1] + '</h3></div></div>';
                    if (successMatch) summaryHtml += '<div class="col-md-3"><div class="alert alert-success"><strong>Would Process:</strong><br><h3>' + successMatch[1] + '</h3></div></div>';
                    if (failedMatch) summaryHtml += '<div class="col-md-3"><div class="alert alert-danger"><strong>Would Fail:</strong><br><h3>' + failedMatch[1] + '</h3></div></div>';
                    if (skippedMatch) summaryHtml += '<div class="col-md-3"><div class="alert alert-warning"><strong>Skipped:</strong><br><h3>' + skippedMatch[1] + '</h3></div></div>';
                    summaryHtml += '</div>';
                    summaryHtml += '<div style="margin-top: 15px;"><button class="btn btn-sm btn-default" type="button" data-toggle="collapse" data-target="#fullOutput">View Full Output</button>';
                    summaryHtml += '<div class="collapse" id="fullOutput" style="margin-top: 15px;"><div class="well log-content"><pre>' + htmlEscape(response) + '</pre></div></div></div></div>';
                    results.innerHTML = summaryHtml;
                    results.style.display = 'block';
                } else {
                    results.innerHTML = '<div class="alert alert-danger">Error running dry run. Status: ' + xhr.status + '</div>';
                    results.style.display = 'block';
                }
            }
        };
        xhr.send();
    }

    function htmlEscape(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>

    <style>
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
    #logFilesTable thead th { position: sticky; top: 0; background: #fff; z-index: 1; }
    </style>

</body>
</html>

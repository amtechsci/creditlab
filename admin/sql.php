<?php
$is_csv_export = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export']) && $_POST['export'] === 'csv');

if ($is_csv_export) {
    include __DIR__ . '/../db.php';
} else {
    include_once 'head.php';
}

if (!isset($admin)) {
    header('location:/account/login.php');
    exit;
}

$allowed_limits = [50, 100, 250, 500, 1000, 2000];
$max_history = 20;
$cell_preview_len = 200;

function sql_console_ident($name)
{
    return '`' . str_replace('`', '``', (string) $name) . '`';
}

function sql_console_load_tables(mysqli $db)
{
    $tables = [];
    $result = mysqli_query($db, 'SHOW TABLE STATUS');
    if (!$result) {
        return $tables;
    }
    while ($row = mysqli_fetch_assoc($result)) {
        $tables[] = [
            'name' => (string) ($row['Name'] ?? ''),
            'engine' => (string) ($row['Engine'] ?? ''),
            'rows' => (int) ($row['Rows'] ?? 0),
            'data_length' => (int) ($row['Data_length'] ?? 0),
        ];
    }
    mysqli_free_result($result);
    return $tables;
}

function sql_console_run(mysqli $db, $sql, $max_rows)
{
    $started = microtime(true);
    $sets = [];
    $error = '';

    $ok = mysqli_multi_query($db, $sql);
    if (!$ok) {
        return [
            'sets' => [],
            'error' => mysqli_error($db) ?: 'Query failed.',
            'ms' => (microtime(true) - $started) * 1000,
        ];
    }

    do {
        $result = mysqli_store_result($db);
        $set = [
            'type' => 'ok',
            'columns' => [],
            'rows' => [],
            'row_count' => 0,
            'truncated' => false,
            'affected' => 0,
            'insert_id' => 0,
        ];

        if ($result instanceof mysqli_result) {
            foreach (mysqli_fetch_fields($result) as $field) {
                $set['columns'][] = $field->name;
            }
            $count = 0;
            while ($count < $max_rows && ($row = mysqli_fetch_assoc($result))) {
                $set['rows'][] = $row;
                $count++;
            }
            $set['row_count'] = $count;
            $set['truncated'] = mysqli_fetch_assoc($result) !== null;
            mysqli_free_result($result);
            $set['type'] = 'result';
        } elseif (mysqli_errno($db)) {
            $error = mysqli_error($db);
            break;
        } else {
            $set['affected'] = mysqli_affected_rows($db);
            $set['insert_id'] = (int) mysqli_insert_id($db);
        }
        $sets[] = $set;
    } while (mysqli_more_results($db) && mysqli_next_result($db));

    if ($error === '' && mysqli_errno($db)) {
        $error = mysqli_error($db);
    }

    while (mysqli_more_results($db)) {
        if (!mysqli_next_result($db)) {
            break;
        }
        $leftover = mysqli_store_result($db);
        if ($leftover instanceof mysqli_result) {
            mysqli_free_result($leftover);
        }
    }

    return [
        'sets' => $sets,
        'error' => $error,
        'ms' => (microtime(true) - $started) * 1000,
    ];
}

function sql_console_remember($sql)
{
    global $max_history;
    $sql = trim((string) $sql);
    if ($sql === '') {
        return;
    }
    if (!isset($_SESSION['sql_console_history']) || !is_array($_SESSION['sql_console_history'])) {
        $_SESSION['sql_console_history'] = [];
    }
    $_SESSION['sql_console_history'] = array_values(array_filter(
        $_SESSION['sql_console_history'],
        function ($item) use ($sql) {
            return $item !== $sql;
        }
    ));
    array_unshift($_SESSION['sql_console_history'], $sql);
    $_SESSION['sql_console_history'] = array_slice($_SESSION['sql_console_history'], 0, $max_history);
}

function sql_console_format_bytes($bytes)
{
    $bytes = (int) $bytes;
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / 1048576, 1) . ' MB';
}

function sql_console_cell($value, $preview_len)
{
    if ($value === null) {
        return '<span class="sql-null">NULL</span>';
    }
    $text = (string) $value;
    if ($text === '') {
        return '<span class="sql-empty"></span>';
    }
    if (strlen($text) > $preview_len) {
        $short = substr($text, 0, $preview_len) . '…';
        return '<span title="' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($short, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

$db_name = creditlab_db_credentials()['name'];
$tables = sql_console_load_tables($db);
$table_names = array_column($tables, 'name');
$query = '';
$max_rows = 500;
$run_result = null;
$flash_error = '';
$did_run = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $flash_error = 'Invalid request token. Reload the page and try again.';
    } else {
        $query = trim((string) ($_POST['sql'] ?? ''));
        $max_rows = (int) ($_POST['max_rows'] ?? 500);
        if (!in_array($max_rows, $allowed_limits, true)) {
            $max_rows = 500;
        }
        if ($query === '') {
            $flash_error = 'Enter a SQL query.';
        } else {
            $did_run = true;
            sql_console_remember($query);
            error_log('[sql_console] admin=' . $admin . ' sql=' . substr(preg_replace('/\s+/', ' ', $query), 0, 2000));
            $run_result = sql_console_run($db, $query, $max_rows);
        }
    }
} elseif (isset($_GET['browse']) || isset($_GET['structure']) || isset($_GET['create'])) {
    $source = isset($_GET['browse']) ? 'browse' : (isset($_GET['structure']) ? 'structure' : 'create');
    $table = trim((string) $_GET[$source]);
    if ($table === '' || !in_array($table, $table_names, true)) {
        $flash_error = 'Unknown table.';
    } else {
        $ident = sql_console_ident($table);
        if ($source === 'browse') {
            $query = 'SELECT * FROM ' . $ident . ' LIMIT 50';
        } elseif ($source === 'structure') {
            $query = 'DESCRIBE ' . $ident;
        } else {
            $query = 'SHOW CREATE TABLE ' . $ident;
        }
        $did_run = true;
        sql_console_remember($query);
        $run_result = sql_console_run($db, $query, $max_rows);
    }
}

if ($did_run && $run_result && ($run_result['error'] ?? '') === '') {
    $tables = sql_console_load_tables($db);
    $table_names = array_column($tables, 'name');
}

if ($is_csv_export) {
    if ($flash_error !== '' || !$run_result || ($run_result['error'] ?? '') !== '') {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(400);
        echo $flash_error !== '' ? $flash_error : ($run_result['error'] ?? 'Nothing to export.');
        exit;
    }
    $export_set = null;
    foreach ($run_result['sets'] as $set) {
        if ($set['type'] === 'result') {
            $export_set = $set;
            break;
        }
    }
    if ($export_set === null) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(400);
        echo 'This query did not return a result set.';
        exit;
    }
    $filename = 'query_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    if (!empty($export_set['columns'])) {
        fputcsv($out, $export_set['columns']);
    }
    foreach ($export_set['rows'] as $row) {
        $line = [];
        foreach ($export_set['columns'] as $col) {
            $line[] = $row[$col] === null ? '' : $row[$col];
        }
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

$history = isset($_SESSION['sql_console_history']) && is_array($_SESSION['sql_console_history'])
    ? $_SESSION['sql_console_history']
    : [];
$has_result_set = false;
if ($run_result) {
    foreach ($run_result['sets'] as $set) {
        if ($set['type'] === 'result') {
            $has_result_set = true;
            break;
        }
    }
}
?>
<body>
<?php include_once 'Left_menu.php'; include_once 'welcome.php'; include_once 'm_menu.php'; ?>

            <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                <h2 style="margin:0 0 6px;"><i class="fa fa-database"></i> SQL</h2>
                                <p style="margin:0;color:#666;">Run queries on <strong><?= htmlspecialchars($db_name, ENT_QUOTES, 'UTF-8') ?></strong>. Changes apply immediately.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .sql-wrap { padding-bottom: 40px; }
            .sql-panel {
                background: #fff;
                border: 1px solid #e7e7e7;
                border-radius: 4px;
                margin-bottom: 16px;
            }
            .sql-panel-head {
                padding: 12px 16px;
                border-bottom: 1px solid #eee;
                font-weight: 600;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 8px;
            }
            .sql-panel-body { padding: 12px 16px; }
            .sql-table-search { width: 100%; margin-bottom: 10px; height: 34px; }
            .sql-table-list { max-height: 640px; overflow: auto; }
            .sql-table-item {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 8px;
                padding: 8px 0;
                border-bottom: 1px solid #f2f2f2;
            }
            .sql-table-item:last-child { border-bottom: 0; }
            .sql-table-name { font-weight: 600; word-break: break-all; }
            .sql-table-meta { font-size: 11px; color: #888; }
            .sql-table-actions a { margin-left: 6px; font-size: 12px; }
            #sqlEditor {
                width: 100%;
                min-height: 180px;
                font-family: Consolas, Monaco, "Courier New", monospace;
                font-size: 13px;
                line-height: 1.45;
                padding: 12px;
                border: 1px solid #d9d9d9;
                border-radius: 4px;
                resize: vertical;
                background: #1e1e1e;
                color: #dcdcdc;
            }
            .sql-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 10px; }
            .sql-toolbar .form-control { width: auto; display: inline-block; height: 34px; }
            .sql-status { margin-top: 12px; }
            .sql-result-wrap { max-height: 560px; overflow: auto; border: 1px solid #e7e7e7; }
            .sql-result-table { margin-bottom: 0; font-size: 12px; white-space: nowrap; }
            .sql-result-table thead th {
                background: #f5f5f5;
                position: sticky;
                top: 0;
                z-index: 1;
            }
            .sql-null { color: #999; font-style: italic; }
            .sql-empty:before { content: "''"; color: #bbb; }
            .sql-muted { color: #666; font-size: 12px; }
            .sql-history { width: 100%; margin-bottom: 10px; }
        </style>

        <div class="single-pro-review-area mt-t-30 mg-b-15 sql-wrap">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-3 col-md-4 col-sm-12 col-xs-12">
                        <div class="sql-panel">
                            <div class="sql-panel-head">
                                <span>Tables (<?= count($tables) ?>)</span>
                                <a href="sql.php" class="btn btn-xs btn-default">Refresh</a>
                            </div>
                            <div class="sql-panel-body">
                                <input type="text" id="tableFilter" class="form-control sql-table-search" placeholder="Filter tables...">
                                <div class="sql-table-list" id="tableList">
                                    <?php if (!$tables): ?>
                                        <p class="sql-muted">No tables found.</p>
                                    <?php endif; ?>
                                    <?php foreach ($tables as $table): ?>
                                        <div class="sql-table-item" data-name="<?= htmlspecialchars(strtolower($table['name']), ENT_QUOTES, 'UTF-8') ?>">
                                            <div>
                                                <div class="sql-table-name">
                                                    <a href="sql.php?browse=<?= urlencode($table['name']) ?>"><?= htmlspecialchars($table['name'], ENT_QUOTES, 'UTF-8') ?></a>
                                                </div>
                                                <div class="sql-table-meta">
                                                    ~<?= number_format($table['rows']) ?> rows
                                                    <?php if ($table['engine'] !== ''): ?>
                                                        · <?= htmlspecialchars($table['engine'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                    · <?= sql_console_format_bytes($table['data_length']) ?>
                                                </div>
                                            </div>
                                            <div class="sql-table-actions">
                                                <a href="sql.php?structure=<?= urlencode($table['name']) ?>">Struct</a>
                                                <a href="sql.php?create=<?= urlencode($table['name']) ?>">SQL</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9 col-md-8 col-sm-12 col-xs-12">
                        <div class="sql-panel">
                            <div class="sql-panel-head">SQL query</div>
                            <div class="sql-panel-body">
                                <?php if ($history): ?>
                                    <select id="sqlHistory" class="form-control sql-history">
                                        <option value="">Recent queries</option>
                                        <?php foreach ($history as $item): ?>
                                            <option value="<?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars(substr(preg_replace('/\s+/', ' ', $item), 0, 120), ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <form method="post" id="sqlForm">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <textarea name="sql" id="sqlEditor" spellcheck="false" placeholder="SELECT * FROM user LIMIT 50"><?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?></textarea>
                                    <div class="sql-toolbar">
                                        <button type="submit" class="btn btn-success" id="runSqlBtn"><i class="fa fa-play"></i> Run</button>
                                        <button type="button" class="btn btn-default" id="clearSqlBtn">Clear</button>
                                        <?php if ($has_result_set): ?>
                                            <button type="submit" name="export" value="csv" class="btn btn-default" formnovalidate>Download CSV</button>
                                        <?php endif; ?>
                                        <label for="maxRows" class="sql-muted" style="margin:0 0 0 6px;">Max rows</label>
                                        <select name="max_rows" id="maxRows" class="form-control">
                                            <?php foreach ($allowed_limits as $limit): ?>
                                                <option value="<?= $limit ?>" <?= $max_rows === $limit ? 'selected' : '' ?>><?= $limit ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="sql-muted">Ctrl/Cmd + Enter to run</span>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php if ($flash_error !== ''): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($flash_error, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>

                        <?php if ($did_run && $run_result): ?>
                            <div class="sql-status">
                                <?php if (($run_result['error'] ?? '') !== ''): ?>
                                    <div class="alert alert-danger">
                                        <strong>Error:</strong> <?= htmlspecialchars($run_result['error'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        Query finished in <?= number_format($run_result['ms'], 1) ?> ms
                                        · <?= count($run_result['sets']) ?> result<?= count($run_result['sets']) === 1 ? '' : 's' ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php foreach ($run_result['sets'] as $index => $set): ?>
                                <div class="sql-panel">
                                    <div class="sql-panel-head">
                                        <span>
                                            <?php if ($set['type'] === 'result'): ?>
                                                Result <?= $index + 1 ?>
                                                · <?= number_format($set['row_count']) ?> row<?= $set['row_count'] === 1 ? '' : 's' ?>
                                                <?php if ($set['truncated']): ?>
                                                    · truncated at <?= number_format($max_rows) ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Statement <?= $index + 1 ?>
                                                · <?= number_format($set['affected']) ?> affected
                                                <?php if (!empty($set['insert_id'])): ?>
                                                    · insert id <?= (int) $set['insert_id'] ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <?php if ($set['type'] === 'result'): ?>
                                        <div class="sql-result-wrap">
                                            <table class="table table-bordered table-striped sql-result-table">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <?php foreach ($set['columns'] as $col): ?>
                                                            <th><?= htmlspecialchars($col, ENT_QUOTES, 'UTF-8') ?></th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!$set['rows']): ?>
                                                        <tr>
                                                            <td colspan="<?= count($set['columns']) + 1 ?>" class="sql-muted">Empty result.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <?php foreach ($set['rows'] as $row_index => $row): ?>
                                                        <tr>
                                                            <td><?= $row_index + 1 ?></td>
                                                            <?php foreach ($set['columns'] as $col): ?>
                                                                <td><?= sql_console_cell($row[$col] ?? null, $cell_preview_len) ?></td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="sql-panel-body sql-muted">Statement executed successfully.</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
<?php include_once 'foot.php'; ?>
<script>
(function () {
    var editor = document.getElementById('sqlEditor');
    var form = document.getElementById('sqlForm');
    var filter = document.getElementById('tableFilter');
    var history = document.getElementById('sqlHistory');
    var clearBtn = document.getElementById('clearSqlBtn');

    function isDestructive(sql) {
        var stripped = (sql || '').replace(/^\s+/, '').replace(/^(\/\*[\s\S]*?\*\/\s*|--[^\n]*\n\s*|#.*\n\s*)+/, '');
        return /^(DROP|TRUNCATE|ALTER|DELETE|UPDATE|INSERT|REPLACE|RENAME|GRANT|REVOKE)\b/i.test(stripped);
    }

    if (form && editor) {
        form.addEventListener('submit', function (e) {
            if (e.submitter && e.submitter.name === 'export') {
                return;
            }
            if (isDestructive(editor.value) && !confirm('This query will change data. Continue?')) {
                e.preventDefault();
            }
        });
        editor.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
    }

    if (clearBtn && editor) {
        clearBtn.addEventListener('click', function () {
            editor.value = '';
            editor.focus();
        });
    }

    if (history && editor) {
        history.addEventListener('change', function () {
            if (this.value) {
                editor.value = this.value;
                editor.focus();
            }
        });
    }

    if (filter) {
        filter.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            document.querySelectorAll('#tableList .sql-table-item').forEach(function (item) {
                item.style.display = item.getAttribute('data-name').indexOf(q) === -1 ? 'none' : '';
            });
        });
    }
})();
</script>
</body>
</html>

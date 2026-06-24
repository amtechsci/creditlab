<?php
/**
 * Admin log file listing and safe deletion under logs/ and payment/logs/.
 */

function creditlab_admin_log_project_root(): string
{
    return dirname(__DIR__);
}

/**
 * @return array<string, string> relative prefix => absolute path
 */
function creditlab_admin_log_roots(): array
{
    $root = creditlab_admin_log_project_root();
    return [
        'logs' => $root . '/logs',
        'payment/logs' => $root . '/payment/logs',
    ];
}

function creditlab_admin_log_detect_type(string $filename): string
{
    if (strpos($filename, 'enach_cron_') === 0) {
        return 'cron';
    }
    if (strpos($filename, 'webhook_') === 0) {
        return 'webhook';
    }
    if (strpos($filename, 'sms_cron_') === 0) {
        return 'sms';
    }
    if (strpos($filename, 'zzenach_') === 0) {
        return 'cron';
    }
    return 'other';
}

function creditlab_admin_log_date_from_filename(string $filename): ?string
{
    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $filename, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * @return array{relative_path: string, name: string, type: string, modified: int, size: int, date: string}|null
 */
function creditlab_admin_log_row_from_file(string $relativePrefix, string $absolutePath): ?array
{
    if (!is_file($absolutePath)) {
        return null;
    }
    $name = basename($absolutePath);
    $modified = (int) filemtime($absolutePath);
    $parsedDate = creditlab_admin_log_date_from_filename($name);
    $date = $parsedDate ?? date('Y-m-d', $modified);

    return [
        'relative_path' => $relativePrefix . '/' . $name,
        'name' => $name,
        'type' => creditlab_admin_log_detect_type($name),
        'modified' => $modified,
        'size' => (int) filesize($absolutePath),
        'date' => $date,
    ];
}

/**
 * @param array{date_from?: string, date_to?: string, type?: string, search?: string} $filters
 * @return array{rows: array, totals: array}
 */
function creditlab_admin_list_logs(array $filters = []): array
{
    $seen = [];
    $rows = [];

    foreach (creditlab_admin_log_roots() as $relativePrefix => $absoluteRoot) {
        if (!is_dir($absoluteRoot)) {
            continue;
        }
        $entries = scandir($absoluteRoot);
        if ($entries === false) {
            continue;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $absolutePath = $absoluteRoot . '/' . $entry;
            if (!is_file($absolutePath)) {
                continue;
            }
            $row = creditlab_admin_log_row_from_file($relativePrefix, $absolutePath);
            if ($row === null) {
                continue;
            }
            if (isset($seen[$row['relative_path']])) {
                continue;
            }
            $seen[$row['relative_path']] = true;
            $rows[] = $row;
        }
    }

    usort($rows, static function (array $a, array $b): int {
        return $b['modified'] <=> $a['modified'];
    });

    $dateFrom = isset($filters['date_from']) && $filters['date_from'] !== '' ? $filters['date_from'] : null;
    $dateTo = isset($filters['date_to']) && $filters['date_to'] !== '' ? $filters['date_to'] : null;
    $typeFilter = isset($filters['type']) && $filters['type'] !== '' && $filters['type'] !== 'all' ? $filters['type'] : null;
    $search = isset($filters['search']) ? strtolower(trim($filters['search'])) : '';

    $filtered = [];
    foreach ($rows as $row) {
        if ($dateFrom !== null && $row['date'] < $dateFrom) {
            continue;
        }
        if ($dateTo !== null && $row['date'] > $dateTo) {
            continue;
        }
        if ($typeFilter !== null && $row['type'] !== $typeFilter) {
            continue;
        }
        if ($search !== '' && strpos(strtolower($row['name']), $search) === false) {
            continue;
        }
        $filtered[] = $row;
    }

    $totals = [
        'all' => count($rows),
        'cron' => 0,
        'webhook' => 0,
        'sms' => 0,
        'other' => 0,
        'size' => 0,
    ];
    foreach ($rows as $row) {
        if (isset($totals[$row['type']])) {
            $totals[$row['type']]++;
        }
        $totals['size'] += $row['size'];
    }

    return ['rows' => $filtered, 'totals' => $totals, 'all_rows' => $rows];
}

/**
 * Resolve a relative path (e.g. logs/webhook_2025-06-24.log) to absolute path.
 */
function creditlab_admin_resolve_log_path(string $relativePath): ?string
{
    $relativePath = str_replace('\\', '/', trim($relativePath));
    if ($relativePath === '' || strpos($relativePath, '..') !== false || $relativePath[0] === '/') {
        return null;
    }

    $roots = creditlab_admin_log_roots();
    foreach ($roots as $prefix => $absoluteRoot) {
        if ($relativePath === $prefix || strpos($relativePath, $prefix . '/') !== 0) {
            continue;
        }
        $suffix = substr($relativePath, strlen($prefix) + 1);
        if ($suffix === '' || strpos($suffix, '/') !== false || strpos($suffix, '..') !== false) {
            return null;
        }
        $candidate = $absoluteRoot . '/' . $suffix;
        $realRoot = realpath($absoluteRoot);
        $realFile = realpath($candidate);
        if ($realRoot === false || $realFile === false || !is_file($realFile)) {
            return null;
        }
        if (strpos($realFile, $realRoot . DIRECTORY_SEPARATOR) !== 0 && $realFile !== $realRoot) {
            return null;
        }
        return $realFile;
    }

    return null;
}

function creditlab_admin_format_file_size(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}

/**
 * @return array{deleted: array, failed: array, freed_bytes: int}
 */
function creditlab_admin_delete_logs(array $relativePaths): array
{
    $deleted = [];
    $failed = [];
    $freedBytes = 0;

    foreach ($relativePaths as $relativePath) {
        $relativePath = (string) $relativePath;
        $absolute = creditlab_admin_resolve_log_path($relativePath);
        if ($absolute === null) {
            $failed[] = ['path' => $relativePath, 'reason' => 'Invalid or not allowed'];
            continue;
        }
        $size = (int) filesize($absolute);
        if (!unlink($absolute)) {
            $failed[] = ['path' => $relativePath, 'reason' => 'Delete failed'];
            continue;
        }
        $deleted[] = $relativePath;
        $freedBytes += $size;
    }

    return ['deleted' => $deleted, 'failed' => $failed, 'freed_bytes' => $freedBytes];
}

/**
 * Delete logs with date strictly before $beforeDate (Y-m-d).
 *
 * @return array{deleted: array, failed: array, freed_bytes: int}
 */
function creditlab_admin_delete_logs_before(string $beforeDate): array
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $beforeDate)) {
        return ['deleted' => [], 'failed' => [['path' => '', 'reason' => 'Invalid date']], 'freed_bytes' => 0];
    }

    $list = creditlab_admin_list_logs();
    $paths = [];
    foreach ($list['all_rows'] as $row) {
        if ($row['date'] < $beforeDate) {
            $paths[] = $row['relative_path'];
        }
    }

    return creditlab_admin_delete_logs($paths);
}

/**
 * @return array{content: string, total_lines: int, showing_lines: int}|array{error: string}
 */
function creditlab_admin_read_log_tail(string $relativePath, int $lines = 100): array
{
    $absolute = creditlab_admin_resolve_log_path($relativePath);
    if ($absolute === null) {
        return ['error' => 'Log file not found'];
    }

    $content = file_get_contents($absolute);
    if ($content === false) {
        return ['error' => 'Could not read log file'];
    }

    $logLines = explode("\n", $content);
    $totalLines = count($logLines);
    $tail = array_slice($logLines, -max(1, $lines));

    return [
        'content' => implode("\n", $tail),
        'total_lines' => $totalLines,
        'showing_lines' => count($tail),
    ];
}

<?php
require_once __DIR__ . '/env.php';

function creditlab_db_credentials(): array
{
    return [
        'host' => env('DB_HOST', 'localhost'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASSWORD'),
        'name' => env('DB_NAME', 'credit'),
    ];
}

function creditlab_db_connect()
{
    $c = creditlab_db_credentials();
    mysqli_report(MYSQLI_REPORT_OFF);
    try {
        $db = mysqli_connect($c['host'], $c['user'], $c['pass'], $c['name']);
    } catch (Throwable $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        return null;
    }
    if (!$db) {
        error_log('Database connection failed: ' . mysqli_connect_error());
        return null;
    }
    mysqli_set_charset($db, 'utf8');
    mysqli_query(
        $db,
        "SET sql_mode = 'NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'"
    );
    return $db;
}

/**
 * End request with HTTP 500 when the database is unavailable.
 */
function creditlab_db_connection_failed(string $message = 'Database connection failed'): void
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit($message);
}

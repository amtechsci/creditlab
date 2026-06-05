<?php
require_once __DIR__ . '/env.php';

function creditlab_get_base_url(): string
{
    if (function_exists('getAppUrl')) {
        return rtrim((string) getAppUrl(), '/');
    }
    return rtrim((string) env('APP_BASE_URL', 'https://creditlab.in'), '/');
}

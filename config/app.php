<?php

declare(strict_types=1);

$env = static function (string $key, $default = null) {
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER)) {
        return $_SERVER[$key];
    }

    return $default;
};

return [
    'db' => [
        'host' => (string) $env('DB_HOST', '127.0.0.1'),
        'port' => (int) $env('DB_PORT', 3306),
        'name' => (string) $env('DB_NAME', 'suivi_contrat_pro'),
        'user' => (string) $env('DB_USER', 'root'),
        'pass' => (string) $env('DB_PASS', $env('DB_PASSWORD', '')),
        'charset' => (string) $env('DB_CHARSET', 'utf8mb4'),
    ],
    'app_name' => (string) $env('APP_NAME', 'Suivi Contrat Pro - PHP'),
    'app_url' => rtrim((string) $env('APP_URL', ''), '/'),
];

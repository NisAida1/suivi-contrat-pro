<?php

declare(strict_types=1);

function load_dotenv_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ($key === '') {
            continue;
        }

        // Remove optional surrounding quotes.
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        // Do not override values already defined by Apache or the OS.
        if (getenv($key) !== false) {
            continue;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

load_dotenv_file(__DIR__ . '/../.env');

$config = require __DIR__ . '/../config/app.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/mail_service.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = create_pdo($config['db']);
$currentUser = current_user($pdo);
$statuses = contract_statuses();

<?php

declare(strict_types=1);

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

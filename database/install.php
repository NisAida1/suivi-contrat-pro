<?php

declare(strict_types=1);

function starts_with_install(string $value, string $prefix): bool
{
    return substr($value, 0, strlen($prefix)) === $prefix;
}

function ends_with_install(string $value, string $suffix): bool
{
    if ($suffix === '') {
        return true;
    }

    return substr($value, -strlen($suffix)) === $suffix;
}

function can_use_putenv_install(): bool
{
    if (!function_exists('putenv')) {
        return false;
    }

    $disabled = (string) ini_get('disable_functions');
    if ($disabled === '') {
        return true;
    }

    $parts = array_map('trim', explode(',', $disabled));
    return !in_array('putenv', $parts, true);
}

function load_dotenv_for_install(string $path): void
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
        if ($line === '' || starts_with_install($line, '#')) {
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

        if ((starts_with_install($value, '"') && ends_with_install($value, '"')) || (starts_with_install($value, "'") && ends_with_install($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) !== false) {
            continue;
        }

        if (can_use_putenv_install()) {
            putenv($key . '=' . $value);
        }
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

load_dotenv_for_install(__DIR__ . '/../.env');

$config = require __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/helpers.php';

$serverDsn = sprintf('mysql:host=%s;port=%d;charset=%s', $config['db']['host'], $config['db']['port'], $config['db']['charset']);
$serverPdo = new PDO($serverDsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$schema = file_get_contents(__DIR__ . '/schema.sql');
if ($schema === false) {
    exit("Impossible de lire schema.sql\n");
}

$targetDbName = $config['db']['name'];
$schema = preg_replace('/^CREATE DATABASE IF NOT EXISTS\s+[^\s;]+/mi', 'CREATE DATABASE IF NOT EXISTS ' . $targetDbName, $schema) ?? $schema;
$schema = preg_replace('/^USE\s+[^\s;]+\s*;?/mi', 'USE ' . $targetDbName . ';', $schema) ?? $schema;

$serverPdo->exec($schema);

$pdo = create_pdo($config['db']);

$users = [
    ['Etudiant Demo', 'student@demo.com', 'etudiant', 'student123', '20260001'],
    ['Secretaire Demo', 'secretary@demo.com', 'secretaire', 'secretary123', null],
    ['Responsable Demo', 'responsable@demo.com', 'responsable', 'responsable123', null],
    ['Directeur Demo', 'director@demo.com', 'directeur', 'director123', null],
];

$insertUser = $pdo->prepare('INSERT INTO users (full_name, email, role, password_hash, student_number, created_at) VALUES (:full_name, :email, :role, :password_hash, :student_number, NOW()) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), role = VALUES(role), password_hash = VALUES(password_hash), student_number = VALUES(student_number)');
foreach ($users as [$fullName, $email, $role, $password, $studentNumber]) {
    $insertUser->execute([
        'full_name' => $fullName,
        'email' => $email,
        'role' => $role,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'student_number' => $studentNumber,
    ]);
}

$studentId = (int) $pdo->query("SELECT id FROM users WHERE email = 'student@demo.com'")->fetchColumn();
$responsableId = (int) $pdo->query("SELECT id FROM users WHERE email = 'responsable@demo.com'")->fetchColumn();

$exists = (int) $pdo->query("SELECT COUNT(*) FROM contracts WHERE dossier_number = 'INFO-" . date('Y') . "-000001'")->fetchColumn();
if ($exists === 0) {
    $dossierNumber = 'INFO-' . date('Y') . '-000001';
    $insertContract = $pdo->prepare('INSERT INTO contracts (dossier_number, student_user_id, company_name, formation, academic_year, opco, status, current_step, created_at, updated_at) VALUES (:dossier_number, :student_user_id, :company_name, :formation, :academic_year, :opco, :status, :current_step, NOW(), NOW())');
    $insertContract->execute([
        'dossier_number' => $dossierNumber,
        'student_user_id' => $studentId,
        'company_name' => 'Entreprise Demo SARL',
        'formation' => 'INFO',
        'academic_year' => date('Y') . '-' . (date('Y') + 1),
        'opco' => 'OPCO Demo',
        'status' => 'EN_COURS',
        'current_step' => default_steps()[0],
    ]);
    $contractId = (int) $pdo->lastInsertId();

    $insertStep = $pdo->prepare('INSERT INTO contract_steps (contract_id, step_order, step_name, state, done_at, done_by_id) VALUES (:contract_id, :step_order, :step_name, :state, :done_at, :done_by_id)');
    foreach (default_steps() as $index => $stepName) {
        $isDone = $index < 3;
        $insertStep->execute([
            'contract_id' => $contractId,
            'step_order' => $index,
            'step_name' => $stepName,
            'state' => $isDone ? 'done' : 'pending',
            'done_at' => $isDone ? date('Y-m-d H:i:s') : null,
            'done_by_id' => $isDone ? $responsableId : null,
        ]);
    }

    log_action($pdo, $contractId, $responsableId, 'Creation du dossier', 'Dossier ' . $dossierNumber . ' cree');
}

echo "Installation terminee.\n";

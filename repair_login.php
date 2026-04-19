<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

require __DIR__ . '/src/bootstrap.php';

function table_exists_login(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
    $stmt->execute(['table_name' => $table]);
    return (bool) $stmt->fetchColumn();
}

function column_exists_login(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE :column_name');
    $stmt->execute(['column_name' => $column]);
    return (bool) $stmt->fetchColumn();
}

function add_column_login(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!column_exists_login($pdo, $table, $column)) {
        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        echo "[OK] Added {$table}.{$column}\n";
    } else {
        echo "[SKIP] {$table}.{$column} already exists\n";
    }
}

function ensure_demo_user(PDO $pdo, string $name, string $email, string $role, string $password): void
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $id = $stmt->fetchColumn();

    if ($id !== false) {
        $update = $pdo->prepare('UPDATE users SET full_name = :full_name, role = :role, password_hash = :password_hash, must_change_password = 0 WHERE id = :id');
        $update->execute([
            'full_name' => $name,
            'role' => $role,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => (int) $id,
        ]);
        echo "[OK] Updated user {$email}\n";
        return;
    }

    $insert = $pdo->prepare('INSERT INTO users (full_name, email, role, password_hash, must_change_password, created_at) VALUES (:full_name, :email, :role, :password_hash, 0, NOW())');
    $insert->execute([
        'full_name' => $name,
        'email' => $email,
        'role' => $role,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    echo "[OK] Created user {$email}\n";
}

try {
    if (!table_exists_login($pdo, 'users')) {
        throw new RuntimeException('Table users introuvable. Importez database/schema.sql.');
    }

    add_column_login($pdo, 'users', 'full_name', 'VARCHAR(120) NOT NULL DEFAULT "Utilisateur"');
    add_column_login($pdo, 'users', 'email', 'VARCHAR(160) NOT NULL');
    add_column_login($pdo, 'users', 'role', 'VARCHAR(30) NOT NULL DEFAULT "etudiant"');
    add_column_login($pdo, 'users', 'password_hash', 'VARCHAR(255) NULL');
    add_column_login($pdo, 'users', 'must_change_password', 'TINYINT(1) NOT NULL DEFAULT 0');

    ensure_demo_user($pdo, 'Responsable Demo', 'responsable@demo.com', 'responsable', 'responsable123');
    ensure_demo_user($pdo, 'Secretaire Demo', 'secretary@demo.com', 'secretaire', 'secretary123');
    ensure_demo_user($pdo, 'Directeur Demo', 'director@demo.com', 'directeur', 'director123');
    ensure_demo_user($pdo, 'Etudiant Demo', 'student@demo.com', 'etudiant', 'student123');

    echo "Done. Login prerequisites repaired.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage() . "\n";
}

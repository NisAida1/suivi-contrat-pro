<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

require __DIR__ . '/src/bootstrap.php';

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
    $stmt->execute(['table_name' => $table]);
    return (bool) $stmt->fetchColumn();
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE :column_name');
    $stmt->execute(['column_name' => $column]);
    return (bool) $stmt->fetchColumn();
}

function add_column_if_missing(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!column_exists($pdo, $table, $column)) {
        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        echo "[OK] Added {$table}.{$column}\n";
    } else {
        echo "[SKIP] {$table}.{$column} already exists\n";
    }
}

try {
    if (!table_exists($pdo, 'users')) {
        throw new RuntimeException('Table users introuvable. Importez database/schema.sql d abord.');
    }

    if (!table_exists($pdo, 'contracts')) {
        throw new RuntimeException('Table contracts introuvable. Importez database/schema.sql d abord.');
    }

    if (!table_exists($pdo, 'contract_steps')) {
        throw new RuntimeException('Table contract_steps introuvable. Importez database/schema.sql d abord.');
    }

    if (!table_exists($pdo, 'activities')) {
        throw new RuntimeException('Table activities introuvable. Importez database/schema.sql d abord.');
    }

    add_column_if_missing($pdo, 'users', 'must_change_password', 'TINYINT(1) NOT NULL DEFAULT 0');

    add_column_if_missing($pdo, 'contracts', 'academic_year', 'VARCHAR(20) NULL');
    add_column_if_missing($pdo, 'contracts', 'is_eu_eea_swiss', 'TINYINT(1) NOT NULL DEFAULT 0');
    add_column_if_missing($pdo, 'contracts', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

    if (!table_exists($pdo, 'password_reset_tokens')) {
        $pdo->exec(
            'CREATE TABLE password_reset_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token_expires (token, expires_at, used)
            )'
        );
        echo "[OK] Created table password_reset_tokens\n";
    } else {
        echo "[SKIP] Table password_reset_tokens already exists\n";
    }

    echo "Done. Schema compatibility repair completed.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage() . "\n";
}

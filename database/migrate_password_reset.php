<?php

declare(strict_types=1);

/**
 * Migration : Ajouter le support de réinitialisation de mot de passe
 */

require_once __DIR__ . '/../src/bootstrap.php';

echo "Migration de la base de données pour le reset de mot de passe...\n";

try {
    // Ajouter la colonne must_change_password si elle n'existe pas
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'must_change_password'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0
        ");
        echo "✓ Colonne must_change_password ajoutée\n";
    } else {
        echo "✓ Colonne must_change_password existe déjà\n";
    }
    
    // Créer la table des tokens de réinitialisation
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token_expires (token, expires_at, used)
        )
    ");
    echo "✓ Table password_reset_tokens créée\n";
    
    echo "\nMigration terminée avec succès !\n";
} catch (PDOException $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}

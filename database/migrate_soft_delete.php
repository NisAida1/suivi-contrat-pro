<?php

declare(strict_types=1);

/**
 * Migration : Ajouter la suppression logique (soft delete) pour les contrats
 */

require_once __DIR__ . '/../src/bootstrap.php';

echo "Migration de la base de données pour la corbeille...\n";

try {
    // Ajouter la colonne deleted_at si elle n'existe pas
    $stmt = $pdo->query("SHOW COLUMNS FROM contracts LIKE 'deleted_at'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("
            ALTER TABLE contracts 
            ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at
        ");
        echo "✓ Colonne deleted_at ajoutée à la table contracts\n";
    } else {
        echo "✓ Colonne deleted_at existe déjà\n";
    }
    
    // Ajouter un index pour améliorer les performances
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_deleted_at ON contracts(deleted_at)
    ");
    echo "✓ Index sur deleted_at créé\n";
    
    echo "\nMigration terminée avec succès !\n";
    echo "Les dossiers peuvent maintenant être mis à la corbeille.\n";
} catch (PDOException $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}

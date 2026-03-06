<?php

declare(strict_types=1);

/**
 * Migration : Ajouter le champ is_eu_eea_swiss pour gérer l'autorisation de travail
 */

require_once __DIR__ . '/../src/bootstrap.php';

echo "Migration de la base de données pour la nationalité UE/EEE/Suisse...\n";

try {
    // Ajouter la colonne is_eu_eea_swiss si elle n'existe pas
    $stmt = $pdo->query("SHOW COLUMNS FROM contracts LIKE 'is_eu_eea_swiss'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("
            ALTER TABLE contracts 
            ADD COLUMN is_eu_eea_swiss TINYINT(1) NOT NULL DEFAULT 0 AFTER opco
        ");
        echo "✓ Colonne is_eu_eea_swiss ajoutée à la table contracts\n";
    } else {
        echo "✓ Colonne is_eu_eea_swiss existe déjà\n";
    }
    
    echo "\nMigration terminée avec succès !\n";
    echo "Les étudiants de l'UE/EEE/Suisse n'auront plus l'étape APT (autorisation de travail).\n";
} catch (PDOException $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}

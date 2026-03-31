<?php

declare(strict_types=1);

/**
 * Migration : Ajouter le champ academic_year pour l'annee universitaire
 */

require_once __DIR__ . '/../src/bootstrap.php';

echo "Migration de la base de donnees pour l'annee universitaire...\n";

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM contracts LIKE 'academic_year'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("
            ALTER TABLE contracts
            ADD COLUMN academic_year VARCHAR(20) NULL AFTER formation
        ");
        echo "Colonne academic_year ajoutee a la table contracts\n";
    } else {
        echo "Colonne academic_year existe deja\n";
    }

    echo "\nMigration terminee avec succes !\n";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}

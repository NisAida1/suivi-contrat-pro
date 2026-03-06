<?php

declare(strict_types=1);

/**
 * Script de test pour l'envoi d'emails
 * 
 * Usage : php test_email.php votre.email@example.com
 */

require_once __DIR__ . '/src/mail_service.php';

if ($argc < 2) {
    echo "Usage: php test_email.php votre.email@example.com\n";
    exit(1);
}

$testEmail = $argv[1];

if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo "Erreur: Adresse email invalide\n";
    exit(1);
}

echo "Test d'envoi d'email a : $testEmail\n";
echo "Configuration SMTP...\n";

$mailConfig = require __DIR__ . '/config/mail.php';
echo "Driver: {$mailConfig['driver']}\n";
echo "SMTP Host: {$mailConfig['smtp']['host']}\n";
echo "SMTP Port: {$mailConfig['smtp']['port']}\n";
echo "SMTP User: {$mailConfig['smtp']['username']}\n";
echo "\n";

echo "Envoi de l'email de test...\n";

$success = send_student_welcome_email(
    $testEmail,
    'Etudiant Test',
    'MotDePasseTest123',
    'TEST-2026-000001',
    'Entreprise Test SARL'
);

if ($success) {
    echo "\n✓ Email envoye avec succes !\n";
    echo "Verifiez la boite de reception (et le dossier spam) de $testEmail\n";
} else {
    echo "\n✗ Echec de l'envoi de l'email\n";
    echo "Verifiez la configuration SMTP dans config/mail.php\n";
    echo "Consultez EMAIL_CONFIG.md pour plus d'informations\n";
}

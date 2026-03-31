<?php

declare(strict_types=1);

$env = static function (string $key, mixed $default = null): mixed {
    $value = getenv($key);
    return $value !== false ? $value : $default;
};

/**
 * Configuration de l'envoi d'emails
 */
return [
    // Mode d'envoi : 'smtp' ou 'mail' (fonction mail() de PHP)
    'driver' => (string) $env('MAIL_DRIVER', 'smtp'),
    
    // Configuration SMTP
    'smtp' => [
        'host' => (string) $env('SMTP_HOST', 'smtp.gmail.com'),
        'port' => (int) $env('SMTP_PORT', 587),
        'username' => (string) $env('SMTP_USERNAME', ''),
        'password' => (string) $env('SMTP_PASSWORD', ''),
        'encryption' => (string) $env('SMTP_ENCRYPTION', 'tls'), // 'tls' ou 'ssl'
    ],
    
    // Adresse d'envoi par défaut
    'from' => [
        'address' => (string) $env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
        'name' => (string) $env('MAIL_FROM_NAME', 'Suivi Contrat Pro - EILCO'),
    ],
];

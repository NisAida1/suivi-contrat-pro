<?php

declare(strict_types=1);

$env = static function (string $key, $default = null) {
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER)) {
        return $_SERVER[$key];
    }

    return $default;
};

/**
 * Configuration de l'envoi d'emails
 */
return [
    // Mode d'envoi : 'smtp' ou 'mail' (fonction mail() de PHP)
    'driver' => (string) $env('MAIL_DRIVER', 'smtp'),
    
    // Configuration SMTP
    'smtp' => [
        'host' => (string) $env('SMTP_HOST', 'ssl0.ovh.net'),
        'port' => (int) $env('SMTP_PORT', 465),
        'username' => (string) $env('SMTP_USERNAME', ''),
        'password' => (string) $env('SMTP_PASSWORD', ''),
        'encryption' => (string) $env('SMTP_ENCRYPTION', 'ssl'), // 'tls' ou 'ssl'
    ],
    
    // Adresse d'envoi par défaut
    'from' => [
        'address' => (string) $env('MAIL_FROM_ADDRESS', 'projetsuivicp@eilco-ulco.fr'),
        'name' => (string) $env('MAIL_FROM_NAME', 'Suivi Contrat Pro - EILCO'),
    ],
];

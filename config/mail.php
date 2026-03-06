<?php

declare(strict_types=1);

/**
 * Configuration de l'envoi d'emails
 */
return [
    // Mode d'envoi : 'smtp' ou 'mail' (fonction mail() de PHP)
    'driver' => 'smtp',
    
    // Configuration SMTP
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'nisrineaida2@gmail.com',
        'password' => 'htlw uzvj rjcr hzpo',
        'encryption' => 'tls', // 'tls' ou 'ssl'
    ],
    
    // Adresse d'envoi par défaut
    'from' => [
        'address' => 'nisrineaida2@gmail.com',
        'name' => 'Suivi Contrat Pro - EILCO',
    ],
];

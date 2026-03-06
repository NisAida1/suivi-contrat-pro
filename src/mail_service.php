<?php

declare(strict_types=1);

/**
 * Service d'envoi d'emails
 */

/**
 * Envoyer un email simple
 */
function send_email(string $to, string $subject, string $body, bool $isHtml = true): bool
{
    $mailConfig = require __DIR__ . '/../config/mail.php';
    
    if ($mailConfig['driver'] === 'smtp') {
        return send_email_smtp($to, $subject, $body, $isHtml, $mailConfig);
    }
    
    return send_email_simple($to, $subject, $body, $isHtml, $mailConfig);
}

/**
 * Lire une réponse complète du serveur SMTP (gère les réponses multi-lignes)
 */
function smtp_read_response($socket): string
{
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        // Si la ligne ne contient pas de tiret après le code (250 au lieu de 250-), c'est la dernière ligne
        if (preg_match('/^\d{3} /', $line)) {
            break;
        }
    }
    return $response;
}

/**
 * Envoi via SMTP (nécessite extension sockets)
 */
function send_email_smtp(string $to, string $subject, string $body, bool $isHtml, array $config): bool
{
    $smtp = $config['smtp'];
    
    // Vérifier si fsockopen est disponible
    if (!function_exists('fsockopen')) {
        error_log('SMTP Error: fsockopen function not available');
        return false;
    }
    
    try {
        // Connexion au serveur SMTP
        $socket = fsockopen(
            ($smtp['encryption'] === 'ssl' ? 'ssl://' : '') . $smtp['host'],
            $smtp['port'],
            $errno,
            $errstr,
            30
        );
        
        if (!$socket) {
            error_log("SMTP Error: Cannot connect to {$smtp['host']}:{$smtp['port']} - $errstr ($errno)");
            return false;
        }
        
        // Lire la réponse du serveur
        $response = smtp_read_response($socket);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            error_log("SMTP Error: Server did not respond with 220: $response");
            return false;
        }
        
        // EHLO
        fputs($socket, "EHLO {$smtp['host']}\r\n");
        $response = smtp_read_response($socket);
        
        // STARTTLS si nécessaire
        if ($smtp['encryption'] === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = smtp_read_response($socket);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                error_log("SMTP Error: STARTTLS failed: $response");
                return false;
            }
            
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                error_log('SMTP Error: Cannot enable TLS');
                return false;
            }
            
            // EHLO après STARTTLS
            fputs($socket, "EHLO {$smtp['host']}\r\n");
            $response = smtp_read_response($socket);
        }
        
        // Authentification
        if ($smtp['username'] !== '' && $smtp['password'] !== '') {
            fputs($socket, "AUTH LOGIN\r\n");
            $response = smtp_read_response($socket);
            
            fputs($socket, base64_encode($smtp['username']) . "\r\n");
            $response = smtp_read_response($socket);
            
            fputs($socket, base64_encode($smtp['password']) . "\r\n");
            $response = smtp_read_response($socket);
            
            if (substr($response, 0, 3) !== '235') {
                fclose($socket);
                error_log("SMTP Error: Authentication failed: $response");
                return false;
            }
        }
        
        // MAIL FROM
        fputs($socket, "MAIL FROM:<{$config['from']['address']}>\r\n");
        $response = smtp_read_response($socket);
        
        // RCPT TO
        fputs($socket, "RCPT TO:<$to>\r\n");
        $response = smtp_read_response($socket);
        
        // DATA
        fputs($socket, "DATA\r\n");
        $response = smtp_read_response($socket);
        
        // En-têtes et corps
        $headers = "From: {$config['from']['name']} <{$config['from']['address']}>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        if ($isHtml) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        $headers .= "\r\n";
        
        fputs($socket, $headers . $body . "\r\n.\r\n");
        $response = smtp_read_response($socket);
        
        // QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
    } catch (Throwable $e) {
        error_log("SMTP Error: {$e->getMessage()}");
        return false;
    }
}

/**
 * Envoi via fonction mail() de PHP (simple)
 */
function send_email_simple(string $to, string $subject, string $body, bool $isHtml, array $config): bool
{
    $headers = "From: {$config['from']['name']} <{$config['from']['address']}>\r\n";
    $headers .= "Reply-To: {$config['from']['address']}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    if ($isHtml) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $body, $headers);
}

/**
 * Envoyer un email de bienvenue à un étudiant avec ses identifiants
 */
function send_student_welcome_email(string $email, string $fullName, string $password, string $dossierNumber, string $companyName): bool
{
    $subject = 'Votre dossier de contrat a ete cree - EILCO';
    
    $body = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f8f9fa; padding: 20px; margin-top: 20px; }
        .credentials { background-color: #fff; border-left: 4px solid #0d6efd; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #6c757d; }
        .btn { display: inline-block; background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Suivi Contrat Pro - EILCO</h1>
        </div>
        
        <div class="content">
            <h2>Bonjour {$fullName},</h2>
            
            <p>Votre dossier de contrat a ete cree avec succes par le secretariat de l'EILCO.</p>
            
            <p><strong>Informations du dossier :</strong></p>
            <ul>
                <li><strong>Numero de dossier :</strong> {$dossierNumber}</li>
                <li><strong>Entreprise :</strong> {$companyName}</li>
            </ul>
            
            <div class="credentials">
                <h3>Vos identifiants de connexion</h3>
                <p><strong>Email :</strong> {$email}</p>
                <p><strong>Mot de passe provisoire :</strong> <code style="background: #e9ecef; padding: 5px 10px; border-radius: 3px; font-size: 14px;">{$password}</code></p>
                <p style="color: #dc3545; margin-top: 10px;">
                    <strong>Important :</strong> Pour des raisons de securite, veuillez changer ce mot de passe lors de votre premiere connexion.
                </p>
            </div>
            
            <p>Vous pouvez maintenant vous connecter a la plateforme pour suivre l'avancement de votre dossier.</p>
            
            <div style="text-align: center;">
                <a href="http://localhost:8000/index.php?page=login" class="btn">Se connecter</a>
            </div>
        </div>
        
        <div class="footer">
            <p>Cet email a ete envoye automatiquement par la plateforme Suivi Contrat Pro.</p>
            <p>EILCO - Ecole d'Ingenieurs du Littoral Cote d'Opale</p>
        </div>
    </div>
</body>
</html>
HTML;

    $success = send_email($email, $subject, $body, true);
    
    if (!$success) {
        error_log("Failed to send welcome email to $email");
    }
    
    return $success;
}

/**
 * Envoyer un email de réinitialisation de mot de passe
 */
function send_password_reset_email(string $email, string $fullName, string $resetToken): bool
{
    $subject = 'Réinitialisation de votre mot de passe - EILCO';
    $resetLink = "http://localhost:8000/index.php?page=reset_password&token=" . urlencode($resetToken);
    
    $body = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f8f9fa; padding: 20px; margin-top: 20px; }
        .alert-box { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #6c757d; }
        .btn { display: inline-block; background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Suivi Contrat Pro - EILCO</h1>
        </div>
        
        <div class="content">
            <h2>Bonjour {$fullName},</h2>
            
            <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>
            
            <p>Pour réinitialiser votre mot de passe, cliquez sur le bouton ci-dessous :</p>
            
            <div style="text-align: center;">
                <a href="{$resetLink}" class="btn">Réinitialiser mon mot de passe</a>
            </div>
            
            <div class="alert-box">
                <strong>⚠️ Ce lien est valide pendant 1 heure.</strong><br>
                Si vous n'avez pas demandé cette réinitialisation, ignorez cet email. Votre mot de passe actuel reste inchangé.
            </div>
            
            <p style="font-size: 12px; color: #6c757d; margin-top: 20px;">
                Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
                <a href="{$resetLink}" style="color: #0d6efd; word-break: break-all;">{$resetLink}</a>
            </p>
        </div>
        
        <div class="footer">
            <p>Cet email a été envoyé automatiquement par la plateforme Suivi Contrat Pro.</p>
            <p>EILCO - Ecole d'Ingenieurs du Littoral Cote d'Opale</p>
        </div>
    </div>
</body>
</html>
HTML;

    $success = send_email($email, $subject, $body, true);
    
    if (!$success) {
        error_log("Failed to send password reset email to $email");
    }
    
    return $success;
}

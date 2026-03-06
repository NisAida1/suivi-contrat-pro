# Guide de configuration de l'envoi d'emails

## Configuration SMTP

Pour activer l'envoi d'emails aux étudiants lors de la création de leur dossier, vous devez configurer les paramètres SMTP.

### Option 1 : Variables d'environnement

Créez un fichier `.env` à la racine du projet (copier `.env.example`) et remplissez les valeurs :

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=votre.email@gmail.com
SMTP_PASSWORD=votre_mot_de_passe_app
MAIL_FROM_ADDRESS=noreply@eilco.fr
MAIL_FROM_NAME="Suivi Contrat Pro - EILCO"
```

### Option 2 : Modifier directement config/mail.php

Ouvrez `config/mail.php` et modifiez les valeurs par défaut :

```php
'smtp' => [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'votre.email@gmail.com',
    'password' => 'votre_mot_de_passe',
    'encryption' => 'tls',
],
```

### Configuration Gmail

Pour utiliser Gmail :

1. Activer la validation en 2 étapes sur votre compte Google
2. Générer un mot de passe d'application : https://myaccount.google.com/apppasswords
3. Utiliser ce mot de passe d'application dans la configuration SMTP

### Configuration serveur SMTP local

Si vous utilisez un serveur de développement local (ex: MailHog, Mailtrap), configurez :

```env
SMTP_HOST=localhost
SMTP_PORT=1025
SMTP_USERNAME=
SMTP_PASSWORD=
```

### Désactiver l'envoi SMTP

Si vous souhaitez utiliser la fonction `mail()` de PHP (moins fiable mais plus simple) :

Modifiez `config/mail.php` :

```php
'driver' => 'mail',  // au lieu de 'smtp'
```

### Test de l'envoi d'emails

Pour tester l'envoi d'emails, créez simplement un nouveau dossier étudiant via l'interface web en tant que secrétaire ou responsable. L'email sera envoyé automatiquement si un nouveau compte étudiant est créé.

### Résolution de problèmes

**Erreurs SMTP :** Vérifiez les logs PHP (`php.ini` : `error_log`) pour voir les détails des erreurs.

**Email non reçu :** Vérifiez le dossier spam de l'étudiant.

**Connexion refusée :** Vérifiez que le port SMTP (587 pour TLS, 465 pour SSL) est accessible et que les identifiants sont corrects.

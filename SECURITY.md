# Sécurité de l'Application

## Mesures de Sécurité Implémentées

### 1. Authentification & Autorisation
- ✅ Hachage des mots de passe avec `PASSWORD_DEFAULT` (bcrypt)
- ✅ Tokens CSRF sur tous les formulaires POST
- ✅ Régénération de session après connexion
- ✅ Validation stricte des rôles utilisateur (etudiant, secretaire, responsable, directeur)
- ✅ Destruction complète de session lors de la déconnexion
- ✅ Verrouillage après 5 tentatives échouées pendant 5 minutes

### 2. Rate Limiting
- ✅ Protection contre le brute force en session (5 tentatives = blocage 5 min)
- ✅ Rate limiting par IP (10 tentatives par heure) sur la page de login
- ✅ Logging des tentatives de connexion échouées

### 3. Protection contre les Injections
- ✅ Requêtes SQL préparées (prepared statements) partout
- ✅ Pas de concaténation directe de paramètres en SQL
- ✅ Échappement HTML systématique avec fonction `h()` dans les templates
- ✅ Validation stricte des entrées utilisateur

### 4. Validation des Données
- ✅ Validation d'email avec `filter_var(FILTER_VALIDATE_EMAIL)`
- ✅ Validation des formations contre liste autorisée
- ✅ Validation des formats (dates, numéros)
- ✅ Limites de longueur sur tous les champs
- ✅ Vérification des permissions pour chaque action

### 5. En-têtes HTTP de Sécurité
- ✅ `Content-Security-Policy`: Restriction stricte des sources de contenu (pas d'unsafe-inline)
- ✅ `X-Content-Type-Options: nosniff`: Prévention du MIME sniffing
- ✅ `X-Frame-Options: SAMEORIGIN`: Protection contre le clickjacking
- ✅ `Referrer-Policy: strict-origin-when-cross-origin`
- ✅ `X-XSS-Protection: 1; mode=block`: Protection XSS navigateur
- ✅ `Strict-Transport-Security`: Force HTTPS en production
- ✅ `Permissions-Policy`: Restriction des APIs du navigateur

### 6. Protection des Fichiers Sensibles
- ✅ `.htaccess` bloque l'accès direct à:
  - `config/`, `database/`, `src/`, `templates/`
  - `.env`, `.git`, `.gitignore`, `README.md`
  - Tous les fichiers `.php` sauf `index.php`
- ✅ Seul `index.php` est accessible comme routeur
- ✅ Assets (CSS, JS, images) librement accessibles

### 7. Gestion des Erreurs
- ✅ Exception handler global qui ne révèle pas de détails sensibles
- ✅ Logging des erreurs en console sans affichage frontend
- ✅ Messages d'erreur génériques pour l'utilisateur
- ✅ Paths serveur non exposés

### 8. Mots de Passe
- ✅ Exigence minimale:
  - 12 caractères minimum
  - Au moins 1 minuscule
  - Au moins 1 majuscule
  - Au moins 1 chiffre
- ✅ Mot de passe temporaire généré pour les nouveaux étudiants
- ✅ Possibilité de réinitialiser via email

### 9. Transactions & Intégrité
- ✅ Transactions SQL pour les opérations critiques (création de dossiers)
- ✅ Rollback automatique en cas d'erreur
- ✅ Numéros de dossier uniques et générés de manière sécurisée

### 10. Logging
- ✅ Tentatives de connexion échouées loggées
- ✅ Historique des actions sur les dossiers
- ✅ IP de l'utilisateur enregistrée pour audit

## Recommandations pour la Production

### Déploiement
1. **HTTPS obligatoire** - Configurez SSL/TLS
2. **Base de données** - Utilisez un mot de passe root fort, contrôlez les accès
3. **Backups** - Mise en place de sauvegardes régulières
4. **Logs** - Envoyez les logs vers un système centralisé

### Configuration Apache
```apache
# Forcer HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Ajouter des en-têtes supplémentaires
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
```

### Variables d'Environnement (`.env`)
```
DB_HOST=production-host
DB_PORT=3306
DB_NAME=prod_database
DB_USER=db_user_prod
DB_PASSWORD=STRONG_PASSWORD_HERE
DB_CHARSET=utf8mb4

APP_URL=https://suivi-contrat.exemple.fr

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
```

### Mise à Jour PHP
- Maintenez PHP à jour (8.2+)
- Activez les extensions de sécurité: `mbstring`, `json`, `openssl`, `pdo_mysql`

### Tests de Sécurité Réguliers
- Scannez avec OWASP ZAP
- Testez les injections SQL/XSS manuellement
- Auditez les logs mensuellement

## Vulnérabilités Connues Adressées

| Risque | Mitigation |
|--------|-----------|
| SQL Injection | Prepared statements obligatoires |
| XSS (Cross-Site Scripting) | Échappement HTML systématique + CSP strict |
| CSRF (Cross-Site Request Forgery) | Tokens CSRF sur tous les POST |
| Brute Force | Rate limiting IP + verrouillage session |
| Session Hijacking | Régénération ID + cookies HttpOnly |
| Information Disclosure | Messages d'erreur génériques + logs invisibles |
| Clickjacking | X-Frame-Options: SAMEORIGIN |
| MIME Sniffing | X-Content-Type-Options: nosniff |

## Contacts & Support

Pour signaler une faille de sécurité:
1. Ne pas la publier publiquement
2. Contacter l'équipe de développement
3. Attendre une correction avant divulgation


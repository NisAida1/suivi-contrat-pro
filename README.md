# Version PHP / MySQL

Cette version parallele reprend le projet Flask sous forme de projet PHP natif avec HTML/CSS et base MySQL.

## Contenu
- `index.php` : routeur principal
- `config/app.php` : configuration de connexion MySQL
- `src/` : helpers, acces base, logique metier
- `templates/` : vues PHP
- `assets/css/styles.css` : theme CSS
- `database/schema.sql` : schema MySQL
- `database/install.php` : installation + donnees de demo

## Prerequis
- PHP 8.1+
- MySQL 8+
- Un serveur local type XAMPP, WAMP, Laragon ou le serveur PHP integre

## Installation rapide
1. Creer la base et les tables:
   - Executer `database/schema.sql` dans MySQL
   - ou lancer `php database/install.php`
2. Ajuster la connexion dans `config/app.php` ou via les variables d'environnement `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
3. Lancer le projet:
   - `php -S localhost:8000`
4. Ouvrir `http://localhost:8000/index.php?page=login`

## Comptes de demo
- `student@demo.com` / `student123`
- `secretary@demo.com` / `secretary123`
- `responsable@demo.com` / `responsable123`
- `director@demo.com` / `director123`

## Fonctionnalites migrees
- Authentification par role
- Tableau de bord
- Liste et filtrage des contrats
- Creation d'un dossier + compte etudiant automatique
- Envoi d'email automatique a l'etudiant avec ses identifiants (voir EMAIL_CONFIG.md pour la configuration)
- Suivi des etapes
- Historique des actions
- Statistiques directeur

## Configuration de l'envoi d'emails
L'application envoie automatiquement un email a l'etudiant lors de la creation de son dossier.
Consultez le fichier `EMAIL_CONFIG.md` pour la configuration des parametres SMTP.

## Limites actuelles
- La recherche AJAX a ete remplacee par un filtrage serveur pour rester simple et robuste.

# Suivi Contrat Pro (PHP / MySQL)

Application web de suivi des dossiers de contrat pro EILCO.
Le projet est base sur PHP natif, MySQL, templates PHP et CSS.

## Objectif de l'application
- Centraliser le suivi des dossiers de contrat pro
- Gerer les etapes du dossier selon le role utilisateur
- Tracer toutes les actions (historique)
- Calculer automatiquement le statut global du dossier

## Outils et technologies
- PHP 8.1+
- MySQL 8+
- HTML/CSS (Bootstrap)
- Serveur local: XAMPP, WAMP, Laragon ou `php -S`
- Editeur recommande: VS Code

## Structure du projet
- `index.php`: routeur principal et traitement des actions
- `config/app.php`: configuration base de donnees
- `config/mail.php`: configuration email (SMTP ou mail())
- `src/helpers.php`: logique metier, statuts, permissions, utilitaires
- `src/repository.php`: acces aux donnees
- `src/mail_service.php`: envoi des emails
- `templates/`: pages de l'interface
- `assets/css/styles.css`: styles CSS
- `database/schema.sql`: schema de base
- `database/install.php`: installation + jeux de donnees demo

## Installation
1. Creer la base et les tables:
   - Soit executer `database/schema.sql` dans MySQL
   - Soit lancer `php database/install.php`
2. Configurer la connexion DB dans `config/app.php`
   - ou via les variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
3. Lancer l'application:
   - `php -S localhost:8000`
4. Ouvrir:
   - `http://localhost:8000/index.php?page=login`

## Comptes demo (4 comptes)
- Etudiant: `student@demo.com` / `student123`
- Secretaire: `secretary@demo.com` / `secretary123`
- Responsable: `responsable@demo.com` / `responsable123`
- Directeur: `director@demo.com` / `director123`

## Fonctionnement general

### 1. Authentification et roles
- `etudiant`
- `secretaire`
- `responsable`
- `directeur`

Chaque role dispose de permissions specifiques sur les dossiers et les etapes.

### 2. Creation de dossier
- Le secretaire ou responsable cree un dossier
- Les infos etudiant sont saisies manuellement (email inclus)
- Si le compte etudiant n'existe pas, il est cree automatiquement
- Un mot de passe provisoire est genere pour le nouvel etudiant

### 3. Suivi des etapes
- Les etapes sont affichees dans la page detail dossier
- Certaines etapes sont obligatoires (marquees avec `*`)
- Les etapes optionnelles ne bloquent pas le suivi
- Deux etapes sont affichees a droite dans la timeline:
  - `CERFA envoye a l OPCO par l etudiant`
  - `CERFA recu par l ecole`

### 4. Decision OPCO (cas metier)
- Etape unique de decision: `Decision OPCO`
- Choix disponibles:
  - `valide`
  - `refuse`
  - `demande-documents` (affiche en UI comme "Demande des docs supplementaire ou modifications")
- Si OPCO demande des documents:
  - une nouvelle etape est ajoutee automatiquement: `Decision OPCO 2e`, puis `Decision OPCO 3e`, etc.
  - ces etapes suivantes reutilisent exactement les memes choix que la premiere

### 5. Statut global automatique
Le statut global est gere automatiquement a partir des etapes (pas de mise a jour manuelle requise).

Regles principales:
- Decision OPCO = `valide` -> statut `VALIDE`
- Decision OPCO = `refuse` -> statut `CLOTURE`
- Decision OPCO = `demande-documents` -> statut `EN_ATTENTE_OPCO`

### 6. Historique et traçabilite
- Chaque mise a jour d'etape est tracee
- Affichage du modificateur + date/heure sur les etapes completees

## Configuration email
- Voir `EMAIL_CONFIG.md`
- Le service supporte SMTP ou `mail()` PHP
- Les envois automatiques dependent de la logique metier active dans l'application

## Notes
- L'application privilegie une logique serveur simple et robuste
- La progression et les statuts sont centralises dans la couche metier (`src/helpers.php`)

# Suivi Contrat Pro (PHP / MySQL)

Application web de suivi des dossiers de contrat pro EILCO.
Le projet est basé sur PHP natif, MySQL, des templates PHP et du CSS.

## Objectif de l'application
- Centraliser le suivi des dossiers de contrat pro
- Gérer les étapes du dossier selon le rôle utilisateur
- Tracer toutes les actions (historique)
- Calculer automatiquement le statut global du dossier

## Outils et technologies
- PHP 8.1+
- MySQL 8+
- HTML/CSS (Bootstrap)
- Serveur local: XAMPP, WAMP, Laragon ou `php -S`
- Éditeur recommandé: VS Code

## Structure du projet
- `index.php`: routeur principal et traitement des actions
- `config/app.php`: configuration base de données
- `config/mail.php`: configuration email (SMTP ou mail())
- `src/helpers.php`: logique métier, statuts, permissions, utilitaires
- `src/repository.php`: accès aux données
- `src/mail_service.php`: envoi des emails
- `templates/`: pages de l'interface
- `assets/css/styles.css`: styles CSS
- `database/schema.sql`: schéma de base
- `database/install.php`: installation + jeux de données démo

## Installation
1. Créer la base et les tables:
   - Soit exécuter `database/schema.sql` dans MySQL
   - Soit lancer `php database/install.php`
2. Configurer la connexion DB dans `config/app.php`
   - ou via les variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
3. Lancer l'application:
   - `php -S localhost:8000`
4. Ouvrir:
   - `http://localhost:8000/index.php?page=login`

## Comptes demo (4 comptes)
- Étudiant: `student@demo.com` / `student123`
- Secrétaire: `secretary@demo.com` / `secretary123`
- Responsable: `responsable@demo.com` / `responsable123`
- Directeur: `director@demo.com` / `director123`

## Fonctionnement général

### 1. Authentification et rôles
- `etudiant`
- `secretaire`
- `responsable`
- `directeur`

Chaque rôle dispose de permissions spécifiques sur les dossiers et les étapes.

### 2. Création de dossier
- Le secrétaire ou responsable crée un dossier
- Les informations de l'étudiant sont saisies manuellement (email inclus)
- Si le compte étudiant n'existe pas, il est créé automatiquement
- Un mot de passe provisoire est généré pour le nouvel étudiant

### 3. Suivi des étapes
- Les étapes sont affichées dans la page de détail du dossier
- Certaines étapes sont obligatoires (marquées avec `*`)
- Les étapes optionnelles ne bloquent pas le suivi
- Deux étapes sont affichées à droite dans la timeline:
  - `CERFA envoye a l OPCO par l etudiant`
  - `CERFA recu par l ecole`

### 4. Décision OPCO (cas métier)
- Étape unique de décision: `Decision OPCO`
- Choix disponibles:
  - `valide`
  - `refuse`
   - `demande-documents` (affiché dans l'interface comme "Demande de documents supplémentaires ou de modifications")
- Si OPCO demande des documents:
   - une nouvelle étape est ajoutée automatiquement: `Decision OPCO 2e`, puis `Decision OPCO 3e`, etc.
   - ces étapes suivantes réutilisent exactement les mêmes choix que la première

### 5. Statut global automatique
Le statut global est géré automatiquement à partir des étapes (pas de mise à jour manuelle requise).

Règles principales:
- Décision OPCO = `valide` -> statut `VALIDE`
- Décision OPCO = `refuse` -> statut `CLOTURE`
- Décision OPCO = `demande-documents` -> statut `EN_ATTENTE_OPCO`

### 6. Historique et traçabilité
- Chaque mise à jour d'étape est tracée
- Affichage du modificateur + date/heure sur les étapes complétées

## Configuration email
- Voir `EMAIL_CONFIG.md`
- Le service supporte SMTP ou `mail()` PHP
- Les envois automatiques dépendent de la logique métier active dans l'application

## Notes
- L'application privilégie une logique serveur simple et robuste
- La progression et les statuts sont centralisés dans la couche métier (`src/helpers.php`)

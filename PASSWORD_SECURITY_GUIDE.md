# Guide de sécurité des mots de passe

## Fonctionnalités implémentées

### 1. Changement forcé de mot de passe à la première connexion

Lorsqu'un étudiant est créé via le formulaire de création de dossier :
- Un mot de passe temporaire est généré automatiquement
- L'étudiant reçoit un email avec ses identifiants (email + mot de passe)
- Le flag `must_change_password` est défini à 1 dans la base de données
- À la première connexion, l'étudiant est automatiquement redirigé vers la page de changement de mot de passe
- L'étudiant doit entrer son mot de passe actuel et définir un nouveau mot de passe
- Une fois le mot de passe changé, le flag `must_change_password` est mis à 0

### 2. Mot de passe oublié avec réinitialisation par email

**Pour tous les utilisateurs** (étudiants, secrétaires, responsables, directeurs) :
- Un lien "Mot de passe oublié ?" est disponible sur la page de connexion
- L'utilisateur entre son adresse email
- Un token de réinitialisation unique est généré (64 caractères hexadécimaux)
- Un email est envoyé avec un lien contenant le token
- Le lien est valide pendant **1 heure**
- L'utilisateur clique sur le lien et définit un nouveau mot de passe
- Le token est marqué comme "utilisé" et ne peut plus être réutilisé

## Tests à effectuer

### Test 1 : Changement forcé de mot de passe (étudiant)

1. Connectez-vous avec un compte secrétaire ou responsable
2. Créez un nouveau dossier étudiant :
   - Cochez "Nouvel étudiant"
   - Remplissez les informations de l'étudiant
   - Notez l'email de l'étudiant créé
3. Vérifiez que l'email de bienvenue a été reçu par l'étudiant (vérifiez votre boîte mail)
4. Déconnectez-vous
5. Connectez-vous avec l'email et le mot de passe temporaire de l'étudiant
6. **Résultat attendu** : Vous êtes automatiquement redirigé vers la page "Changer le mot de passe"
7. Entrez le mot de passe actuel (mot de passe temporaire)
8. Entrez deux fois le nouveau mot de passe (minimum 6 caractères)
9. Cliquez sur "Changer le mot de passe"
10. **Résultat attendu** : Message de succès et redirection vers le tableau de bord étudiant
11. Déconnectez-vous et reconnectez-vous avec le nouveau mot de passe
12. **Résultat attendu** : Connexion réussie sans redirection vers changement de mot de passe

### Test 2 : Mot de passe oublié

1. Sur la page de connexion, cliquez sur "Mot de passe oublié ?"
2. Entrez une adresse email existante dans la base de données (ex: student@demo.com)
3. Cliquez sur "Envoyer le lien de réinitialisation"
4. **Résultat attendu** : Message "Si cette adresse email existe dans notre système, un lien de réinitialisation vous a été envoyé."
5. Vérifiez votre boîte mail pour l'email de réinitialisation
6. Cliquez sur le lien dans l'email
7. **Résultat attendu** : Vous êtes redirigé vers la page de réinitialisation avec le token dans l'URL
8. Entrez deux fois le nouveau mot de passe (minimum 6 caractères)
9. Cliquez sur "Réinitialiser le mot de passe"
10. **Résultat attendu** : Message de succès et redirection vers la page de connexion
11. Connectez-vous avec le nouveau mot de passe
12. **Résultat attendu** : Connexion réussie

### Test 3 : Expiration du token

1. Demandez un lien de réinitialisation pour un compte
2. **Attendez plus d'1 heure** (ou modifiez temporairement l'expiration dans le code pour tester)
3. Cliquez sur le lien
4. **Résultat attendu** : Message d'erreur "Ce lien de réinitialisation est invalide ou a expiré."

### Test 4 : Réutilisation d'un token

1. Demandez un lien de réinitialisation
2. Utilisez le lien pour réinitialiser le mot de passe avec succès
3. Essayez de réutiliser le même lien
4. **Résultat attendu** : Message d'erreur "Ce lien de réinitialisation est invalide ou a expiré."

### Test 5 : Email inexistant

1. Sur la page "Mot de passe oublié", entrez une adresse email qui n'existe pas
2. **Résultat attendu** : Même message que pour un email existant (pour éviter l'énumération des emails)
3. Aucun email n'est envoyé

## Structure de la base de données

### Table `users` (modifiée)
- Nouvelle colonne : `must_change_password TINYINT(1) NOT NULL DEFAULT 0`
- Valeur 1 = l'utilisateur doit changer son mot de passe à la prochaine connexion
- Valeur 0 = connexion normale

### Table `password_reset_tokens` (nouvelle)
```sql
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_expires (token, expires_at, used)
)
```

## Configuration email

Les emails sont envoyés via **Gmail SMTP** :
- Serveur : smtp.gmail.com:587
- Sécurité : TLS
- Email : nisrineaida2@gmail.com
- Mot de passe d'application : htlw uzvj rjcr hzpo

Configuration dans : `config/mail.php`

## Sécurité

- Les mots de passe sont hachés avec `password_hash()` (bcrypt)
- Validation de 6 caractères minimum
- Les tokens de réinitialisation sont générés avec `random_bytes(32)` (sécurisés cryptographiquement)
- Les tokens expirent après 1 heure
- Les tokens ne peuvent être utilisés qu'une seule fois
- Protection contre l'énumération d'emails (même message succès/erreur)
- Les liens de réinitialisation sont uniques et non prévisibles (64 caractères hexadécimaux)

## Flux de fonctionnement

### Création d'étudiant avec changement forcé
```
1. Secrétaire/Responsable crée un dossier étudiant
   ↓
2. Étudiant créé avec must_change_password=1
   ↓
3. Email envoyé à l'étudiant avec identifiants
   ↓
4. Étudiant se connecte avec mot de passe temporaire
   ↓
5. Redirection automatique vers change_password
   ↓
6. Étudiant entre nouveau mot de passe
   ↓
7. must_change_password mis à 0
   ↓
8. Redirection vers tableau de bord
```

### Réinitialisation de mot de passe
```
1. Utilisateur clique "Mot de passe oublié ?"
   ↓
2. Entre son email
   ↓
3. Token généré et stocké en base (expire dans 1h)
   ↓
4. Email envoyé avec lien contenant le token
   ↓
5. Utilisateur clique sur le lien
   ↓
6. Validation du token (non expiré, non utilisé)
   ↓
7. Formulaire de nouveau mot de passe
   ↓
8. Mot de passe mis à jour, token marqué "utilisé"
   ↓
9. Redirection vers connexion
```

## Fichiers modifiés/créés

### Nouveaux fichiers
- `templates/change_password.php` - Formulaire changement de mot de passe
- `templates/forgot_password.php` - Formulaire mot de passe oublié
- `templates/reset_password.php` - Formulaire réinitialisation avec token
- `database/migrate_password_reset.php` - Script de migration
- `PASSWORD_SECURITY_GUIDE.md` - Ce fichier

### Fichiers modifiés
- `index.php` - Routes POST/GET pour password management
- `templates/login.php` - Lien "Mot de passe oublié ?"
- `src/repository.php` - Fonctions create_password_reset_token(), validate_reset_token(), mark_token_as_used(), update_user_password()
- `src/mail_service.php` - Fonction send_password_reset_email()
- `database/schema.sql` - Colonnes/tables pour password management

## Migration de la base de données

La migration a déjà été exécutée avec succès. Si vous devez la réexécuter :

```bash
php database/migrate_password_reset.php
```

**Résultat attendu** :
```
✓ Colonne must_change_password ajoutée (ou existe déjà)
✓ Table password_reset_tokens créée
Migration terminée avec succès !
```

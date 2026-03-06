# Système de Corbeille - Guide Utilisateur

## Vue d'ensemble

Le système de corbeille permet de supprimer des dossiers de manière réversible. Les dossiers supprimés peuvent être restaurés ou supprimés définitivement.

## Fonctionnement

### Suppression logique (Soft Delete)
- Les dossiers ne sont **pas supprimés immédiatement** de la base de données
- Un champ `deleted_at` est défini avec la date/heure de suppression
- Les dossiers supprimés n'apparaissent plus dans les listes normales
- Les dossiers peuvent être restaurés à tout moment

### Suppression définitive (Hard Delete)
- Supprime définitivement un dossier de la base de données
- **Action irréversible** - toutes les données sont perdues :
  - Le contrat lui-même
  - Toutes les étapes du contrat
  - Tout l'historique des activités
- Accessible uniquement depuis la corbeille

## Droits d'accès

**Qui peut supprimer des dossiers ?**
- Secrétaire
- Responsable
- Directeur

**Les étudiants ne peuvent pas supprimer de dossiers.**

## Utilisation

### 1. Supprimer un dossier

**Depuis la page de détail du contrat :**
1. Ouvrez un dossier (cliquez sur son numéro dans la liste)
2. Dans l'en-tête du dossier, cliquez sur le bouton **"Supprimer"** (rouge)
3. Confirmez la suppression dans la boîte de dialogue
4. Le dossier est déplacé vers la corbeille

**Message affiché :**
> "Voulez-vous supprimer ce dossier ?  
> Le dossier sera déplacé dans la corbeille et pourra être restauré."

### 2. Accéder à la corbeille

**Depuis le menu de navigation :**
- Cliquez sur **"🗑️ Corbeille"** dans la barre de navigation
- La page affiche tous les dossiers supprimés avec :
  - Numéro de dossier
  - Nom de l'étudiant et email
  - Nom de l'entreprise
  - Formation
  - Date et heure de suppression

### 3. Restaurer un dossier

**Depuis la corbeille :**
1. Trouvez le dossier à restaurer
2. Cliquez sur le bouton **"Restaurer"** (vert)
3. Confirmez la restauration
4. Le dossier réapparaît dans la liste normale des contrats
5. Toutes les données sont préservées (étapes, historique, etc.)

**Message de confirmation :**
> "Voulez-vous restaurer ce dossier ?"

### 4. Supprimer définitivement un dossier

**⚠️ ATTENTION : Cette action est IRRÉVERSIBLE !**

**Depuis la corbeille :**
1. Trouvez le dossier à supprimer définitivement
2. Cliquez sur le bouton **"Supprimer définitivement"** (rouge)
3. Lisez attentivement le message de confirmation :

> ⚠️ ATTENTION : Cette action est irréversible !
>
> Voulez-vous supprimer définitivement ce dossier ?  
> Toutes les données (étapes, activités, historique) seront perdues.

4. Confirmez si vous êtes sûr
5. Le dossier est définitivement supprimé de la base de données

## Historique et traçabilité

### Actions enregistrées dans l'historique

1. **Suppression (mise à la corbeille) :**
   - Action : "Suppression"
   - Détails : "Dossier deplace vers la corbeille"
   - Utilisateur : Nom de la personne qui a supprimé le dossier
   - Date/heure : Moment de la suppression

2. **Restauration :**
   - Action : "Restauration"
   - Détails : "Dossier restaure depuis la corbeille"
   - Utilisateur : Nom de la personne qui a restauré le dossier
   - Date/heure : Moment de la restauration

**Note :** La suppression définitive ne laisse aucune trace car le dossier est complètement supprimé.

## Structure de la base de données

### Colonne ajoutée : `deleted_at`

```sql
ALTER TABLE contracts 
ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at;

CREATE INDEX idx_deleted_at ON contracts(deleted_at);
```

**Valeurs :**
- `NULL` = Le dossier est actif (non supprimé)
- `2026-03-06 14:30:00` = Le dossier a été supprimé le 6 mars 2026 à 14h30

### Performances

Un **index** a été créé sur la colonne `deleted_at` pour améliorer les performances des requêtes :
- Filtrage rapide des dossiers actifs (`deleted_at IS NULL`)
- Récupération rapide des dossiers dans la corbeille (`deleted_at IS NOT NULL`)

## Fichiers modifiés/créés

### Nouveaux fichiers
- `database/migrate_soft_delete.php` - Script de migration
- `templates/trash.php` - Page de la corbeille
- `TRASH_GUIDE.md` - Ce guide

### Fichiers modifiés
- `database/schema.sql` - Ajout de la colonne `deleted_at`
- `src/repository.php` - Nouvelles fonctions :
  - `soft_delete_contract()` - Mettre à la corbeille
  - `restore_contract()` - Restaurer
  - `hard_delete_contract()` - Supprimer définitivement
  - `fetch_deleted_contracts()` - Récupérer les dossiers supprimés
  - `fetch_contracts()` - Modifié pour exclure les dossiers supprimés
- `index.php` - Routes pour :
  - `contract_delete` (POST) - Suppression
  - `trash` (GET) - Afficher la corbeille
  - `trash` (POST) - Restaurer/Supprimer définitivement
- `templates/contract_detail.php` - Bouton "Supprimer" ajouté
- `templates/layout.php` - Lien "Corbeille" dans le menu

## Migration

La migration a été exécutée avec succès :

```bash
php database/migrate_soft_delete.php
```

**Résultat :**
```
✓ Colonne deleted_at ajoutée à la table contracts
✓ Index sur deleted_at créé
Migration terminée avec succès !
```

## Cas d'usage

### Scénario 1 : Erreur de saisie
**Problème :** Un dossier a été créé par erreur avec les mauvaises informations.

**Solution :**
1. Supprimer le dossier (bouton "Supprimer" dans le détail)
2. Le dossier va à la corbeille
3. Si besoin de récupérer des informations, aller dans la corbeille
4. Sinon, supprimer définitivement après vérification

### Scénario 2 : Dossier obsolète
**Problème :** Un étudiant a abandonné son contrat en alternance.

**Solution :**
1. Supprimer le dossier pour nettoyer la liste active
2. Le dossier reste dans la corbeille pour archives
3. Option : Supprimer définitivement si pas besoin de traces (selon politique de l'école)

### Scénario 3 : Suppression accidentelle
**Problème :** Un utilisateur a supprimé un dossier par erreur.

**Solution :**
1. Aller dans la corbeille
2. Trouver le dossier (trié par date de suppression, le plus récent en haut)
3. Cliquer sur "Restaurer"
4. Le dossier revient dans la liste normale avec toutes ses données

### Scénario 4 : Nettoyage annuel
**Problème :** Besoin de nettoyer les anciens dossiers définitivement.

**Solution :**
1. Supprimer les dossiers obsolètes (ils vont à la corbeille)
2. Aller dans la corbeille
3. Vérifier les dossiers à supprimer définitivement
4. Supprimer définitivement (attention : irréversible !)

## Bonnes pratiques

### ✅ À faire
- Vérifier deux fois avant de supprimer définitivement
- Utiliser la corbeille comme période de "réflexion" avant suppression définitive
- Restaurer rapidement si suppression accidentelle
- Nettoyer régulièrement la corbeille des vieux dossiers

### ❌ À éviter
- Supprimer définitivement sans vérifier
- Laisser des centaines de dossiers dans la corbeille indéfiniment
- Supprimer un dossier actif avec des étapes en cours sans raison valable

## Messages et notifications

### Messages de succès
- **Suppression :** "Le dossier a ete deplace vers la corbeille."
- **Restauration :** "Le dossier a ete restaure avec succes."
- **Suppression définitive :** "Le dossier a ete supprime definitivement."

### Confirmations
Tous les formulaires de la corbeille incluent des confirmations JavaScript pour éviter les erreurs :
- Restauration : Confirmation simple
- Suppression définitive : Avertissement détaillé sur le caractère irréversible

## Statistiques corbeille

La page de la corbeille affiche :
- **Nombre total de dossiers supprimés** dans le sous-titre
- **Liste complète** des dossiers avec toutes leurs informations
- **Date de suppression** pour chaque dossier
- **Alerte de sécurité** en bas de page

## FAQ

**Q : Est-ce que les étudiants voient que leur dossier a été supprimé ?**  
R : Non, les étudiants n'ont pas accès à la liste des contrats ni à la corbeille. Seuls les secrétaires, responsables et directeurs peuvent voir et gérer la corbeille.

**Q : Que se passe-t-il si je supprime définitivement un dossier par erreur ?**  
R : Il n'y a aucun moyen de le récupérer. Toutes les données sont perdues. C'est pourquoi une confirmation importante est demandée.

**Q : Les étapes et l'historique sont-ils conservés dans la corbeille ?**  
R : Oui, tant que le dossier est dans la corbeille, toutes les données associées (étapes, activités, historique) sont préservées. Elles sont restaurées intégralement si vous restaurez le dossier.

**Q : Combien de temps un dossier peut-il rester dans la corbeille ?**  
R : Indéfiniment. Il n'y a pas de suppression automatique. C'est à l'administrateur de gérer la corbeille.

**Q : Peut-on rechercher dans la corbeille ?**  
R : Actuellement non. La corbeille affiche tous les dossiers supprimés triés par date de suppression (les plus récents en premier).

**Q : Les statistiques incluent-elles les dossiers supprimés ?**  
R : Non, les dossiers dans la corbeille sont exclus de toutes les statistiques et listes normales.

## Support technique

En cas de problème avec la corbeille :
1. Vérifier que la migration a bien été exécutée
2. Vérifier les logs d'erreur PHP
3. Vérifier que l'utilisateur a les droits appropriés (secrétaire/responsable/directeur)
4. Consulter l'historique du dossier pour voir les actions effectuées

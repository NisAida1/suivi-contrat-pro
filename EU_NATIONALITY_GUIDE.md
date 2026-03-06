# Gestion de la nationalité UE/EEE/Suisse et Autorisation de Travail (APT)

## Vue d'ensemble

Le système gère maintenant différemment les dossiers selon la nationalité de l'étudiant :

- **Étudiants de l'UE/EEE/Suisse** : N'ont PAS besoin d'autorisation provisoire de travail (APT)
- **Étudiants hors UE/EEE/Suisse** : Doivent obtenir une autorisation provisoire de travail (APT)

## Pays concernés

### Union Européenne (UE - 27 pays)
Allemagne, Autriche, Belgique, Bulgarie, Chypre, Croatie, Danemark, Espagne, Estonie, Finlande, France, Grèce, Hongrie, Irlande, Italie, Lettonie, Lituanie, Luxembourg, Malte, Pays-Bas, Pologne, Portugal, République Tchèque, Roumanie, Slovaquie, Slovénie, Suède

### Espace Économique Européen (EEE)
Les 27 pays de l'UE + Islande, Liechtenstein, Norvège

### Suisse
La Suisse a des accords bilatéraux avec l'UE

## Fonctionnement

### Lors de la création d'un dossier

1. Le secrétaire ou responsable remplit le formulaire de création
2. Une nouvelle case à cocher est disponible : **"L'étudiant est ressortissant de l'UE, l'EEE ou de la Suisse"**
3. Si la case est cochée :
   - Le dossier est créé SANS les étapes liées à l'APT
   - Les étapes "Demande APT deposée", "APT obtenue", "APT refusée" ne sont PAS créées
4. Si la case n'est PAS cochée :
   - Le dossier est créé AVEC les étapes liées à l'APT
   - Les 3 étapes APT sont ajoutées dans le workflow

### Étapes du contrat - Étudiants UE/EEE/Suisse

```
1. Dossier ouvert
2. Fiche envoyée à l'entreprise
3. Fiche complétée reçue de l'entreprise
4. Mail d'acceptation étudiant reçu
5. Fiche renvoyée pour correction
6. Fiche corrigée reçue
7. CERFA et convention envoyés à l'entreprise
8. Dates modifiées et nouvelle version envoyée
9. CERFA signé avec l'entreprise
10. Convention signée avec l'entreprise
11. CERFA envoyé à l'école
12. CERFA envoyé à l'OPCO
13. OPCO validé
14. OPCO refusé
```

### Étapes du contrat - Étudiants hors UE/EEE/Suisse

```
1. Dossier ouvert
2. Fiche envoyée à l'entreprise
3. Fiche complétée reçue de l'entreprise
4. Mail d'acceptation étudiant reçu
5. Fiche renvoyée pour correction
6. Fiche corrigée reçue
7. CERFA et convention envoyés à l'entreprise
8. Dates modifiées et nouvelle version envoyée
9. CERFA signé avec l'entreprise
10. Convention signée avec l'entreprise
11. Demande APT déposée ⭐
12. APT obtenue ⭐
13. APT refusée ⭐
14. CERFA envoyé à l'école
15. CERFA envoyé à l'OPCO
16. OPCO validé
17. OPCO refusé
```

Les étapes marquées ⭐ sont uniquement pour les étudiants hors UE/EEE/Suisse.

## Structure de la base de données

### Table `contracts` (modifiée)

Nouvelle colonne ajoutée :
```sql
is_eu_eea_swiss TINYINT(1) NOT NULL DEFAULT 0
```

- **0** = Étudiant hors UE/EEE/Suisse → Étapes APT incluses
- **1** = Étudiant de l'UE/EEE/Suisse → Étapes APT exclues

## Fichiers modifiés

1. **database/schema.sql**
   - Ajout de la colonne `is_eu_eea_swiss` dans la table `contracts`

2. **database/migrate_eu_nationality.php** (nouveau)
   - Script de migration pour ajouter la colonne aux bases existantes

3. **templates/contract_form.php**
   - Ajout du checkbox "L'étudiant est ressortissant de l'UE, l'EEE ou de la Suisse"
   - Aide contextuelle expliquant l'impact sur les étapes APT

4. **src/helpers.php**
   - Fonction `default_steps()` modifiée pour accepter un paramètre `$isEuEeaSwiss`
   - Les étapes APT sont conditionnées selon ce paramètre

5. **index.php**
   - Récupération du paramètre `is_eu_eea_swiss` depuis le formulaire
   - Passage du paramètre à `default_steps()` lors de la création des étapes
   - Stockage de la valeur dans la base de données

## Migration

La migration a été exécutée avec succès :

```bash
php database/migrate_eu_nationality.php
```

**Résultat :**
```
✓ Colonne is_eu_eea_swiss ajoutée à la table contracts
Migration terminée avec succès !
```

## Tests à effectuer

### Test 1 : Création d'un dossier étudiant UE/EEE/Suisse

1. Connectez-vous avec un compte secrétaire ou responsable
2. Cliquez sur "Créer un nouveau dossier"
3. Remplissez les informations de l'étudiant
4. **Cochez** la case "L'étudiant est ressortissant de l'UE, l'EEE ou de la Suisse"
5. Remplissez les informations de l'entreprise
6. Cliquez sur "Créer le dossier"
7. **Résultat attendu** : Le dossier est créé sans les étapes APT
8. Vérifiez dans le détail du dossier que les étapes suivantes sont ABSENTES :
   - "Demande APT déposée"
   - "APT obtenue"
   - "APT refusée"

### Test 2 : Création d'un dossier étudiant hors UE/EEE/Suisse

1. Connectez-vous avec un compte secrétaire ou responsable
2. Cliquez sur "Créer un nouveau dossier"
3. Remplissez les informations de l'étudiant
4. **NE COCHEZ PAS** la case "L'étudiant est ressortissant de l'UE, l'EEE ou de la Suisse"
5. Remplissez les informations de l'entreprise
6. Cliquez sur "Créer le dossier"
7. **Résultat attendu** : Le dossier est créé avec les étapes APT
8. Vérifiez dans le détail du dossier que les étapes suivantes sont PRÉSENTES :
   - "Demande APT déposée"
   - "APT obtenue"
   - "APT refusée"

### Test 3 : Vérification de l'ordre des étapes

Pour un étudiant hors UE/EEE/Suisse, vérifiez que les étapes APT apparaissent :
- **Après** "Convention signée avec l'entreprise"
- **Avant** "CERFA envoyé à l'école"

## APT - Autorisation Provisoire de Travail

### Définition
L'APT (Autorisation Provisoire de Travail) est une autorisation administrative nécessaire pour qu'un étudiant étranger hors UE/EEE/Suisse puisse travailler en France dans le cadre d'un contrat en alternance.

### Processus
1. **Demande APT déposée** : La demande est déposée auprès de la préfecture
2. **APT obtenue** : L'autorisation est accordée → le contrat peut continuer
3. **APT refusée** : L'autorisation est refusée → nécessite des actions correctives

### Pourquoi les étudiants UE/EEE/Suisse sont exemptés ?
Les étudiants ressortissants de l'UE, l'EEE ou de la Suisse bénéficient de la libre circulation et du droit au travail automatique dans l'Union Européenne. Ils n'ont donc pas besoin d'autorisation de travail spécifique.

## Données existantes

Pour les dossiers créés avant cette mise à jour :
- La colonne `is_eu_eea_swiss` est à 0 par défaut
- Tous les anciens dossiers sont considérés comme "hors UE/EEE/Suisse"
- Cela ne modifie pas les étapes déjà créées pour ces dossiers
- Seuls les nouveaux dossiers bénéficient de cette fonctionnalité

## Notes techniques

- Le champ est stocké au niveau du **contrat** et non de l'utilisateur, car la situation peut évoluer
- Le calcul des étapes se fait à la création du dossier uniquement
- Une fois le dossier créé, les étapes ne sont pas recalculées automatiquement
- La fonction `default_steps()` retourne un tableau dynamique selon le paramètre `$isEuEeaSwiss`

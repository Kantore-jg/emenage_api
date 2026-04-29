# Menage - Plateforme de Communication Citoyen-Autorité

## 1. Contexte et Objectif

**Menage** est une plateforme web développée pour digitaliser la communication entre les citoyens et les autorités à Bujumbura, au Burundi. Le projet vise à :

- Digitaliser le **Carnet de Ménage** traditionnel
- Sécuriser le **recensement des invités** pour éviter les amendes/corruptions lors des contrôles
- Centraliser les **communiqués officiels** pour lutter contre la désinformation
- Fournir un système de **signalement d'incidents** géolocalisés
- Gérer les **paiements** et reçus des ménages
- Permettre des **campagnes de recensement** dynamiques (type Google Forms)

---

## 2. Stack Technique

| Composant | Technologie |
|-----------|-------------|
| Backend | Laravel (API REST) |
| Authentification | Laravel Sanctum (token-based) |
| Base de données | MySQL |
| Documentation API | L5-Swagger (`/api/documentation`) |

---

## 3. Rôles Utilisateurs

Le système implémente une hiérarchie de rôles avec un contrôle d'accès basé sur les zones géographiques.

| Rôle | Description | Portée géographique |
|------|-------------|---------------------|
| `admin` | Administrateur global | Tout le pays |
| `ministere` | Ministère | Tout le pays |
| `provincial` | Autorité provinciale | Province assignée |
| `communal` | Autorité communale | Commune assignée |
| `zonal` | Autorité zonale | Zone assignée |
| `collinaire` | Chef de colline (ex-chef de quartier) | Colline assignée |
| `citoyen` | Chef de famille | Son ménage uniquement |
| `police` | Forces de sécurité | Zone assignée |
| `agent_recensement` | Agent de terrain pour les campagnes de recensement | Zones assignées par campagne |

Chaque utilisateur (sauf admin/ministère) est lié à une **zone géographique** et ne voit que les données de sa zone et ses descendants.

---

## 4. Architecture Géographique Multi-niveaux

Le système utilise une hiérarchie géographique configurable :

```
Pays (Burundi)
  └── Province
        └── Commune
              └── Zone
                    └── Colline
```

### Tables associées

- **`geographic_levels`** : Définit les niveaux (Province, Commune, Zone, Colline) avec un `level_order`
- **`geographic_areas`** : Arbre hiérarchique des zones (`parent_id` auto-référencé)

### Zone Scoping (Trait `ZoneScope`)

Le trait `ZoneScope` est utilisé dans les controllers pour filtrer automatiquement les données selon la zone de l'utilisateur connecté :
- `getZoneIds()` : Récupère les IDs de la zone de l'utilisateur et tous ses descendants
- `applyHouseholdZoneFilter()` : Filtre les ménages par zone
- `getAccessibleHouseholdIds()` : Retourne les IDs des ménages accessibles
- `getAccessibleUserIds()` : Retourne les IDs des utilisateurs accessibles

---

## 5. Fonctionnalités Implémentées

### 5.1 Authentification et Profil

**Authentification**
- Connexion par email ou téléphone + mot de passe
- Token Sanctum pour les requêtes API
- Déconnexion (révocation du token)

**Gestion du profil**
- Consultation et modification : nom, téléphone, email, photo de profil
- Changement de mot de passe (avec validation de l'ancien)
- Mise à jour du quartier et de l'adresse pour les citoyens
- Remplacement automatique de l'ancienne photo de profil

### 5.2 Gestion des Utilisateurs (Admin / Autorités)

- **CRUD complet** des utilisateurs par les admins et autorités hiérarchiques
- Lors de la création, l'admin assigne un **rôle** et une **zone géographique** correspondante
- L'utilisateur créé ne voit que les activités de sa zone
- Réinitialisation du mot de passe par l'admin ou le créateur
- Mapping rôle/niveau géographique (ex: `provincial` → province, `collinaire` → colline)
- Traçabilité du créateur (`created_by`)

### 5.3 Carnet de Ménage Digital

**Compte Famille Unique**
- Un seul ménage par chef de famille
- Lié à une zone géographique (`geographic_area_id`)
- Adresse et quartier enregistrés

**Gestion des Membres Permanents**
- Ajout avec nom, âge, téléphone
- Photo CNI obligatoire si âge > 18 ans
- Statut de validation : `en_attente` → `valide` / `rejete`
- Notification automatique aux autorités de la zone lors de l'ajout

**Gestion des Invités**
- Enregistrement rapide avec nom, âge, téléphone
- Statut en temps réel : `present` / `parti`
- Mise à jour du statut par le chef de famille
- Suppression possible
- Notification automatique aux autorités de la zone

### 5.4 Système de Validation

- Les autorités (collinaire et au-dessus) voient les enregistrements en attente de leur zone
- Actions : **Valider** ou **Rejeter** un membre/invité
- Notification au citoyen lors de la validation/rejet
- File d'attente de validation dans le dashboard gouvernement
- Validation des paiements avec le même workflow

### 5.5 Communiqués Officiels

- **Lecture publique** : accessible sans authentification (liste et détail)
- **Publication** : réservée aux comptes autorités (collinaire → admin)
- Chaque communiqué affiche l'**autorité émettrice** (sceau d'authenticité)
- Modification et suppression par l'auteur ou l'admin
- Champs : titre, contenu, autorité, date

### 5.6 Signalement d'Incidents (Sécurité)

- Les citoyens créent un signalement avec description + coordonnées GPS
- Géolocalisation automatique via l'API Geolocation du navigateur
- Gestion des statuts : `en_attente` → `en_cours` → `resolu`
- Mise à jour du statut par la police et les autorités
- Liste des signalements filtrée par zone
- Carte interactive (Leaflet.js côté frontend)

### 5.7 Paiements et Reçus

- Les citoyens enregistrent leurs paiements : motif, montant, date, photo du reçu
- Motifs prédéfinis (poubelles, Regideso, eau, etc.) + motif personnalisé (`motif_autre`)
- Notification automatique aux autorités de la zone
- Validation par les autorités (même workflow que les membres)
- Notification au citoyen lors de la validation
- Consultation des paiements par ménage (filtrage par mois possible)
- Les autorités voient les paiements des ménages de leur zone

### 5.8 Campagnes de Recensement (Type Google Forms)

**Gestion des campagnes (Admin/Autorités)**
- CRUD campagnes avec titre, description, dates, périmètre géographique
- Statuts : `brouillon` → `actif` → `termine` → `archive`
- Définition dynamique des champs à collecter :
  - Types supportés : `text`, `number`, `date`, `select`, `multi_select`, `boolean`, `textarea`
  - Options JSON pour les champs select/multi_select
  - Champs obligatoires ou optionnels
  - Ordre personnalisable

**Gestion des agents**
- L'admin enregistre les agents de recensement dans le système
- Assignation d'agents à une campagne avec une zone géographique spécifique
- Les agents reçoivent des identifiants pour se connecter
- Accès restreint aux campagnes et zones assignées

**Collecte de données (Agents)**
- Formulaire dynamique généré à partir des champs de la campagne
- Soumission des réponses avec nom/téléphone du répondant + géolocalisation
- Consultation des réponses soumises par l'agent

**Export et statistiques**
- Export CSV des résultats d'une campagne
- Vue tabulaire JSON des réponses
- Statistiques par agent, par zone, par jour, distribution par champ
- Comparaison entre plusieurs campagnes de recensement

### 5.9 Notifications In-App

- Système de notifications personnalisé (table `notifications_custom`)
- Types de notifications :
  - `nouveau_membre` : nouveau membre ajouté (vers autorités)
  - `nouvel_invite` : nouvel invité enregistré (vers autorités)
  - `validation` : résultat de validation (vers citoyen)
  - `nouveau_paiement` : paiement enregistré (vers autorités)
  - `validation_paiement` : paiement validé (vers citoyen)
- Suppression par le propriétaire
- Affichées dans les dashboards respectifs

### 5.10 Tableaux de Bord par Rôle

**Dashboard Citoyen**
- Informations du ménage
- Liste des membres permanents et invités
- Notifications personnelles

**Dashboard Gouvernement (Autorités)**
- File d'attente de validation (membres et paiements en attente)
- Notifications
- Filtrage par zone géographique

**Dashboard Sécurité (Police)**
- Liste des signalements d'incidents
- Filtrage par zone

### 5.11 Statistiques

- Utilisateurs par mois (graphique)
- Paiements par type (graphique)
- Membres par ménage (graphique)
- Résumé global : nombre d'utilisateurs, citoyens, ménages, paiements, membres
- Toutes les statistiques sont filtrées par zone géographique

### 5.12 Gestion des Ménages par les Autorités

- Liste de tous les ménages de la zone avec filtres
- Détails complets de chaque ménage (membres, invités, paiements)
- Statistiques par ménage
- Filtrage hiérarchique par zone géographique

---

## 6. Structure de la Base de Données

### Table `users`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| nom | string | Nom complet |
| role | enum | admin, ministere, provincial, communal, zonal, collinaire, citoyen, police, agent_recensement |
| telephone | string | Numéro de téléphone |
| email | string | Adresse email |
| password | string | Mot de passe hashé |
| photo_profil | string | Chemin de la photo de profil |
| geographic_area_id | bigint | Zone géographique assignée |
| created_by | bigint | ID de l'utilisateur créateur |
| timestamps | | created_at, updated_at |

### Table `households`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| chef_id | bigint | FK vers users |
| quartier | string | Nom du quartier |
| adresse | string | Adresse complète |
| geographic_area_id | bigint | Zone géographique |
| timestamps | | created_at, updated_at |

### Table `members`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| household_id | bigint | FK vers households |
| nom | string | Nom du membre |
| type | enum | permanent, invite |
| statut | enum | present, parti |
| statut_validation | enum | en_attente, valide, rejete |
| photo_cni | string | Photo de la CNI |
| age | integer | Âge |
| telephone | string | Numéro de téléphone |
| timestamps | | created_at, updated_at |

### Table `announcements`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| author_id | bigint | FK vers users |
| titre | string | Titre du communiqué |
| contenu | text | Contenu complet |
| autorite | string | Autorité émettrice |
| date | date | Date de publication |
| timestamps | | created_at, updated_at |

### Table `reports`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| citizen_id | bigint | FK vers users |
| description | text | Description de l'incident |
| latitude | decimal | Coordonnée GPS |
| longitude | decimal | Coordonnée GPS |
| statut | enum | en_attente, en_cours, resolu |
| timestamps | | created_at, updated_at |

### Table `payments`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| household_id | bigint | FK vers households |
| motif | string | Motif du paiement |
| motif_autre | string | Motif personnalisé |
| montant | decimal | Montant payé |
| date_paiement | date | Date du paiement |
| recu_photo | string | Photo du reçu |
| statut_validation | enum | en_attente, valide, rejete |
| timestamps | | created_at, updated_at |

### Table `notifications_custom`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| user_id | bigint | FK vers users |
| type | string | Type de notification |
| titre | string | Titre |
| message | text | Message |
| lu | boolean | Lu ou non |
| created_at | timestamp | Date de création |

### Table `geographic_levels`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| name | string | Nom du niveau (Province, Commune, Zone, Colline) |
| slug | string | Slug |
| level_order | integer | Ordre hiérarchique |
| timestamps | | created_at, updated_at |

### Table `geographic_areas`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| name | string | Nom de la zone |
| level_id | bigint | FK vers geographic_levels |
| parent_id | bigint | FK auto-référencée |
| timestamps | | created_at, updated_at |

### Table `censuses`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| titre | string | Titre de la campagne |
| description | text | Description |
| statut | enum | brouillon, actif, termine, archive |
| date_debut | date | Date de début |
| date_fin | date | Date de fin |
| geographic_area_id | bigint | Périmètre géographique |
| created_by | bigint | FK vers users |
| timestamps | | created_at, updated_at |

### Table `census_fields`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| census_id | bigint | FK vers censuses |
| label | string | Libellé du champ |
| type | enum | text, number, date, select, multi_select, boolean, textarea |
| options | json | Options pour select/multi_select |
| required | boolean | Champ obligatoire |
| field_order | integer | Ordre d'affichage |
| timestamps | | created_at, updated_at |

### Table `census_agents`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| census_id | bigint | FK vers censuses |
| user_id | bigint | FK vers users |
| geographic_area_id | bigint | Zone assignée à l'agent |
| timestamps | | created_at, updated_at |

### Table `census_responses`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| census_id | bigint | FK vers censuses |
| agent_id | bigint | FK vers users |
| geographic_area_id | bigint | Zone de collecte |
| respondent_name | string | Nom du répondant |
| respondent_phone | string | Téléphone du répondant |
| latitude | decimal | Coordonnée GPS |
| longitude | decimal | Coordonnée GPS |
| timestamps | | created_at, updated_at |

### Table `census_response_values`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| response_id | bigint | FK vers census_responses |
| field_id | bigint | FK vers census_fields |
| value | text | Valeur de la réponse |

---

## 7. Middleware et Sécurité

| Alias | Classe | Rôle |
|-------|--------|------|
| `role` | `CheckRole` | Restreint l'accès par rôle utilisateur |
| `chef_famille` | `CheckChefFamille` | Vérifie que l'utilisateur a un ménage associé |
| `census_agent` | `CheckCensusAgent` | Autorise les autorités ou les agents assignés à au moins une campagne |

L'API utilise `statefulApi()` de Sanctum pour l'authentification.

---

## 8. Routes API

### Routes Publiques

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/auth/login` | Connexion |
| GET | `/api/announcements` | Liste des communiqués |
| GET | `/api/announcements/{id}` | Détail d'un communiqué |
| GET | `/api/geographic/levels` | Niveaux géographiques |
| GET | `/api/geographic/areas` | Zones géographiques |
| GET | `/api/geographic/areas/{id}` | Détail d'une zone |
| GET | `/api/geographic/tree` | Arbre géographique |
| GET | `/api/geographic/search` | Recherche de zones |

### Routes Authentifiées

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/auth/logout` | Déconnexion |
| GET | `/api/auth/user` | Utilisateur connecté |
| GET | `/api/stats` | Statistiques |
| GET | `/api/dashboard/citoyen` | Dashboard citoyen |
| GET | `/api/dashboard/gouvernement` | Dashboard gouvernement |
| GET | `/api/dashboard/securite` | Dashboard sécurité |
| DELETE | `/api/dashboard/notifications/{id}` | Supprimer notification |
| GET | `/api/payments` | Liste paiements |
| POST | `/api/payments` | Créer paiement |
| GET | `/api/profile` | Voir profil |
| POST | `/api/profile` | Modifier profil |
| POST | `/api/reports` | Créer signalement |
| PUT | `/api/announcements/{id}` | Modifier communiqué |
| DELETE | `/api/announcements/{id}` | Supprimer communiqué |

### Routes Gestion Utilisateurs (admin, ministere, provincial, communal, zonal, collinaire)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/users` | Liste utilisateurs |
| POST | `/api/users` | Créer utilisateur |
| GET | `/api/users/{id}` | Détail utilisateur |
| PUT | `/api/users/{id}` | Modifier utilisateur |
| POST | `/api/users/{id}/reset-password` | Reset mot de passe |
| DELETE | `/api/users/{id}` | Supprimer utilisateur |

### Routes Chef de Famille

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/household/members` | Ajouter membre permanent |
| POST | `/api/household/invites` | Ajouter invité |
| PUT | `/api/household/invites/{id}` | Modifier statut invité |
| DELETE | `/api/household/members/{id}` | Supprimer membre |

### Routes Ménages (Autorités)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/households` | Liste ménages |
| GET | `/api/households/{id}` | Détail ménage |
| GET | `/api/households-stats` | Statistiques ménages |

### Routes Validation (Autorités)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| PUT | `/api/validation/members/{id}` | Valider/rejeter membre |
| GET | `/api/validation/pending` | Éléments en attente |
| PUT | `/api/payments/{id}/validate` | Valider paiement |

### Routes Signalements (Police / Autorités)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| PUT | `/api/reports/{id}/statut` | Modifier statut incident |
| GET | `/api/reports/all` | Tous les signalements |

### Routes Communiqués (Autorités)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/announcements` | Publier communiqué |

### Routes Recensement (Autorités)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/censuses` | Liste campagnes |
| POST | `/api/censuses` | Créer campagne |
| GET | `/api/censuses/{id}` | Détail campagne |
| PUT | `/api/censuses/{id}` | Modifier campagne |
| PUT | `/api/censuses/{id}/fields` | Modifier champs |
| DELETE | `/api/censuses/{id}` | Supprimer campagne |
| GET | `/api/censuses/{id}/agents` | Liste agents |
| POST | `/api/censuses/{id}/agents` | Ajouter agent |
| POST | `/api/censuses/{id}/agents/assign` | Assigner agent |
| DELETE | `/api/censuses/{id}/agents/{agentId}` | Retirer agent |
| GET | `/api/censuses/{id}/export/csv` | Export CSV |
| GET | `/api/censuses/{id}/table` | Vue tabulaire |
| GET | `/api/censuses/{id}/stats` | Statistiques |
| POST | `/api/censuses/compare` | Comparer campagnes |

### Routes Agent de Recensement

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/census/my-campaigns` | Mes campagnes |
| GET | `/api/census/{id}/form` | Formulaire de collecte |
| POST | `/api/census/{id}/responses` | Soumettre réponse |
| GET | `/api/census/{id}/my-responses` | Mes réponses |

---

## 9. Modèles et Relations

| Modèle | Relations |
|--------|----------|
| **User** | `household` (hasOne), `notifications` (hasMany), `announcements` (hasMany), `reports` (hasMany), `createdBy` (belongsTo User), `createdUsers` (hasMany User), `geographicArea` (belongsTo), `censusAssignments` (hasMany CensusAgent) |
| **Household** | `chef` (belongsTo User), `geographicArea` (belongsTo), `members` (hasMany), `payments` (hasMany) + scopes : `permanentMembers`, `invites`, `presentInvites` |
| **Member** | `household` (belongsTo) |
| **Announcement** | `author` (belongsTo User) |
| **Report** | `citizen` (belongsTo User) |
| **Payment** | `household` (belongsTo) |
| **Notification** | `user` (belongsTo) |
| **GeographicArea** | `level` (belongsTo), `parent` (belongsTo self), `children` (hasMany self), `children_recursive`, `households` (hasMany), `users` (hasMany) |
| **GeographicLevel** | `areas` (hasMany) |
| **Census** | `creator` (belongsTo User), `geographicArea` (belongsTo), `fields` (hasMany), `agents` (hasMany), `responses` (hasMany) |
| **CensusField** | `census` (belongsTo), `values` (hasMany CensusResponseValue) |
| **CensusAgent** | `census` (belongsTo), `user` (belongsTo), `geographicArea` (belongsTo) |
| **CensusResponse** | `census` (belongsTo), `agent` (belongsTo User), `geographicArea` (belongsTo), `values` (hasMany) |
| **CensusResponseValue** | `response` (belongsTo), `field` (belongsTo) |

---

## 10. Seeders

| Seeder | Description |
|--------|-------------|
| **DatabaseSeeder** | Appelle GeographicSeeder puis TestUserSeeder |
| **GeographicSeeder** | Peuple les niveaux (Province, Commune, Zone, Colline) et les zones depuis `data.json` |
| **TestUserSeeder** | Crée des utilisateurs de test : Admin, Ministère, Provincial, Communal, Zonal, Collinaire, 2 Citoyens, Police — avec hiérarchie et liens géographiques |

---

## 11. Résumé des Modules

| Module | Fonctionnalités clés |
|--------|---------------------|
| **Auth** | Login email/téléphone, Sanctum tokens, logout |
| **Géographie** | Hiérarchie Province > Commune > Zone > Colline, arbre, recherche |
| **Utilisateurs** | CRUD hiérarchique, assignation rôle + zone, reset mot de passe |
| **Ménages** | Création par citoyen, membres permanents/invités, zone scoping |
| **Validation** | Workflow en_attente → validé/rejeté, notifications bidirectionnelles |
| **Communiqués** | Publication par autorités, lecture publique, sceau d'authenticité |
| **Signalements** | Création avec GPS, gestion des statuts, filtrage par zone |
| **Paiements** | Enregistrement avec reçu photo, validation par autorités |
| **Recensement** | Campagnes dynamiques, champs personnalisables, agents par zone, export CSV, statistiques, comparaison |
| **Notifications** | In-app, types multiples, création automatique |
| **Dashboards** | Vue par rôle (citoyen, gouvernement, sécurité) |
| **Statistiques** | Graphiques par mois/type, résumé global, zone scoping |
| **Profil** | Consultation et modification des informations personnelles |

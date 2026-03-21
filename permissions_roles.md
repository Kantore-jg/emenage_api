# Matrice des Permissions — Ubuzima Hub

## Légende

| Symbole | Signification |
|---------|---------------|
| ✅ | Accès complet (sans restriction géographique) |
| 🟡 | Limité à sa zone géographique (et ses descendants) |
| 👁️ | Lecture seule (propres données uniquement) |
| ❌ | Aucun accès |

## Portée géographique par rôle

| Rôle | Portée |
|------|--------|
| `admin` | National (tout le pays) |
| `ministere` | National (tout le pays) |
| `provincial` | Province assignée |
| `communal` | Commune assignée |
| `zonal` | Zone assignée |
| `collinaire` | Colline assignée |
| `citoyen` | Son ménage uniquement |
| `police` | Zone assignée |
| `agent_recensement` | Campagnes assignées uniquement |

---

## 1. Authentification & Profil

| Permission / Action | Admin | Ministère | Provincial | Communal | Zonal | Collinaire | Citoyen | Police | Agent recens. |
|---------------------|:-----:|:---------:|:----------:|:--------:|:-----:|:----------:|:-------:|:------:|:-------------:|
| Connexion (email / tél.) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Voir son profil | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Modifier son profil / photo | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Changer son mot de passe | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 2. Gestion des Utilisateurs

| Permission / Action | Admin | Ministère | Provincial | Communal | Zonal | Collinaire | Citoyen | Police | Agent recens. |
|---------------------|:-----:|:---------:|:----------:|:--------:|:-----:|:----------:|:-------:|:------:|:-------------:|
| Créer un utilisateur | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Voir liste utilisateurs | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Modifier un utilisateur | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Supprimer un utilisateur | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Reset mot de passe utilisateur | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Assigner rôle + zone géographique | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |

> **Règle hiérarchique** : Un `provincial` ne peut créer que des comptes `communal`, `zonal` ou `collinaire` dans sa province. Il ne peut pas créer un autre `provincial`, ni un `admin` ou `ministere`.

---

## 3. Carnet de Ménage

| Permission / Action | Admin | Ministère | Provincial | Communal | Zonal | Collinaire | Citoyen | Police | Agent recens. |
|---------------------|:-----:|:---------:|:----------:|:--------:|:-----:|:----------:|:-------:|:------:|:-------------:|
| Voir ses propres membres | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Ajouter un membre permanent | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Supprimer un membre | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Enregistrer un invité | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Mettre à jour statut invité | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Voir liste ménages (zone) | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | 🟡 | ❌ |
| Détail d'un ménage (zone) | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | 🟡 | ❌ |

---

## 4. Validation

| Permission / Action | Admin | Ministère | Provincial | Communal | Zonal | Collinaire | Citoyen | Police | Agent recens. |
|---------------------|:-----:|:---------:|:----------:|:--------:|:-----:|:----------:|:-------:|:------:|:-------------:|
| Valider / rejeter un membre | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Voir file d'attente validation | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Valider un paiement | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |

---

## 5. Communiqués Officiels

| Permission / Action | Admin | Ministère | Provincial | Communal | Zonal | Collinaire | Citoyen | Police | Agent recens. |
|---------------------|:-----:|:---------:|:----------:|:--------:|:-----:|:----------:|:-------:|:------:|:-------------:|
| Lire les communiqués (public) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Publier un communiqué | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Modifier / supprimer un communiqué | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |

> **Note** : Chaque autorité ne peut modifier/supprimer que les communiqués qu'elle a elle-même publiés. L'admin peut tout modifier.

---

## 6. Signalements d'Incidents

| Permission / Action | Admin | Ministère | Provincial | Communal | Zonal | Collinaire | Citoyen | Police | Agent recens. |
|---------------------|:-----:|:---------:|:----------:|:--------:|:-----:|:----------:|:-------:|:------:|:-------------:|
| Créer un signalement (GPS) | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Voir tous les signalements (zone) | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | 🟡 | ❌ |
| Changer le statut d'un incident | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | 🟡 | ❌ |

> **Statuts** : `en_attente` → `en_cours` → `resolu`

---

## 7. Paiements

| Permission / Action | Admin | Ministère | Provincial | Communal | Zonal | Collinaire | Citoyen | Police | Agent recens. |
|---------------------|:-----:|:---------:|:----------:|:--------:|:-----:|:----------:|:-------:|:------:|:-------------:|
| Enregistrer un paiement | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Voir ses propres paiements | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Voir paiements de la zone | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |

---

## 8. Campagnes de Recensement

| Permission / Action | Admin | Ministère | Provincial | Communal | Zonal | Collinaire | Citoyen | Police | Agent recens. |
|---------------------|:-----:|:---------:|:----------:|:--------:|:-----:|:----------:|:-------:|:------:|:-------------:|
| Créer / modifier une campagne | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Supprimer une campagne | ✅ | ✅ | 🟡 | 🟡 | ❌ | ❌ | ❌ | ❌ | ❌ |
| Assigner des agents à une campagne | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Voir résultats / stats d'une campagne | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | 👁️ |
| Export CSV d'une campagne | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Comparer plusieurs campagnes | ✅ | ✅ | 🟡 | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Accéder au formulaire de collecte | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Soumettre une réponse (terrain) | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Voir ses propres réponses | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

> **Statuts campagne** : `brouillon` → `actif` → `termine` → `archive`

---

## 9. Dashboards & Statistiques

| Permission / Action | Admin | Ministère | Provincial | Communal | Zonal | Collinaire | Citoyen | Police | Agent recens. |
|---------------------|:-----:|:---------:|:----------:|:--------:|:-----:|:----------:|:-------:|:------:|:-------------:|
| Dashboard citoyen | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Dashboard gouvernement | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Dashboard sécurité | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Statistiques globales | ✅ | ✅ | 🟡 | 🟡 | 🟡 | 🟡 | ❌ | ❌ | ❌ |
| Notifications in-app | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |

---

## Résumé des règles clés

### Hiérarchie de création d'utilisateurs

Chaque autorité ne peut créer que des utilisateurs de niveau inférieur dans sa propre zone :

```
Admin          → peut créer tous les rôles
Ministère      → peut créer tous les rôles sauf admin
Provincial     → peut créer : communal, zonal, collinaire, citoyen, police, agent_recensement (dans sa province)
Communal       → peut créer : zonal, collinaire, citoyen, police, agent_recensement (dans sa commune)
Zonal          → peut créer : collinaire, citoyen, agent_recensement (dans sa zone)
Collinaire     → peut créer : citoyen, agent_recensement (dans sa colline)
```

### Zone Scoping (trait `ZoneScope`)

Toutes les permissions marquées 🟡 sont filtrées automatiquement via le trait `ZoneScope` :
- `getZoneIds()` — récupère l'ID de la zone de l'utilisateur et tous ses descendants
- `applyHouseholdZoneFilter()` — filtre les ménages par zone
- `getAccessibleHouseholdIds()` — retourne les IDs des ménages accessibles
- `getAccessibleUserIds()` — retourne les IDs des utilisateurs accessibles

### Middlewares Laravel

| Alias | Usage |
|-------|-------|
| `role:admin,ministere` | Réserve l'accès aux admins et ministères |
| `role:admin,ministere,provincial,communal,zonal,collinaire` | Toutes les autorités |
| `chef_famille` | Vérifie que l'utilisateur a un ménage associé (citoyen) |
| `census_agent` | Autorise les autorités **ou** les agents assignés à au moins une campagne |

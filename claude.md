Fonctionnalités supplémentaires suggérées
1. Gestion administrative avancée
Recensement & statistiques démographiques — pyramide des âges, répartition par genre, taille moyenne des ménages par quartier
Historique des déménagements — traçabilité quand un citoyen change de ménage/quartier
Gestion des naissances et décès — enregistrement d'état civil simplifié
Gestion des mariages — lier deux citoyens et fusionner/créer des ménages
2. Finances & fiscalité locale
Taxes et redevances récurrentes — planification automatique des paiements (mensuels, annuels)
Paiement mobile intégré (Mobile Money, Stripe, etc.)
Génération automatique de reçus PDF
Tableau de bord financier — revenus par quartier, taux de recouvrement
3. Sécurité & incidents
Suivi avancé des signalements — assignation à un agent, priorité, catégories (vol, incendie, conflit...)
Chat/messagerie entre citoyen et police sur un signalement
Carte interactive des incidents (heatmap)
Alertes d'urgence — notifications push en temps réel
4. Communication
Notifications SMS (Twilio, Africa's Talking)
Notifications push (Firebase Cloud Messaging)
Newsletters par email aux citoyens
Forum communautaire par quartier
5. Documents & services
Demandes de documents administratifs (attestation de résidence, certificat de composition familiale)
Génération automatique de documents PDF signés numériquement
Rendez-vous en ligne avec l'administration
6. Audit & conformité
Journal d'audit — traçabilité de toutes les actions (qui a fait quoi, quand)
Export de données (CSV, Excel, PDF)
RGPD/Protection des données — consentement, droit à l'oubli

Internationalisation — Faire fonctionner le système dans tous les pays
C'est un chantier important. Voici les modifications nécessaires :
Architecture multi-pays requise
1. Base de données — ajouter des entités géographiques
countries → provinces/regions → cities/communes → districts → neighborhoods/quartiers
Il faut remplacer le champ texte quartier par une vraie hiérarchie géographique configurable par pays.2. Internationalisation (i18n) de l'interface
Côté Vue : utiliser vue-i18n avec des fichiers de traduction (fr, en, sw, rn, etc.)
Côté Laravel : utiliser les fichiers lang/ pour les messages d'erreur et les emails
3. Multi-devise — stocker la devise avec chaque paiement4. Multi-fuseau horaire — stocker les dates en UTC, afficher en local5. Formats locaux — numéros de téléphone (libphonenumber), dates, montants

il y'a des cas ou le pays a le besoin de recencement par exemple de quelques choses, je veux integrer ca, donc l'administration va inscrire dans le systemes les personnes qui va faire le recencement, et donne-les les identifiants pour se connecter, et ils vont voir les informations qui concernent ce campagnent de recencement seulement, et puis les informations qu'il vont collecter dans les citoyens vont continuer a s'enregistrer dans le systemes, pour que ca se voit dans l'administration et qu'il puisse exporter l'excel ou le pdf des informations concernant telle recencement oubiens comparer les statistiques des informations venant dans les recencements differents.
ce sont les administrateurs qui vont enregistrer le recencement en ajoutant les champs a repondres,etc.. ca va etre comme celles de google forms.


Phase 1 - Architecture géographique multi-pays  TERMINÉE
Migration: table geographic_levels (name, slug, level_order)
Migration: table geographic_areas (name, level_id, parent_id) — arbre auto-référencé
Migration: update users — ajouter geographic_area_id (FK)
Migration: update households — ajouter geographic_area_id (FK)
Modèles: GeographicLevel, GeographicArea + relations (parent, children, ancestors, descendants)
Update modèle User — relation geographicArea + méthodes isAdmin(), getAccessibleAreaIds()
Update modèle Household — relation geographicArea + scope forUserZone()
Trait ZoneScope — logique réutilisable de filtrage par zone (getZoneIds, applyHouseholdZoneFilter, getAccessibleHouseholdIds, getAccessibleUserIds)
Seeder GeographicSeeder: importe data.json (Burundi: 18 provinces → communes → zones → collines)
Controller GeographicController — API endpoints:
  GET /api/geographic/levels — niveaux (province, commune, zone, colline)
  GET /api/geographic/areas?parent_id= — dropdown cascadé
  GET /api/geographic/areas/{id} — détail + enfants
  GET /api/geographic/tree?depth= — arbre complet
  GET /api/geographic/search?q= — recherche par nom

Phase 1b - Hiérarchie des rôles et contrôle d'accès par zone  TERMINÉE
Chaîne d'inscription hiérarchique:
  admin → ministere → provincial → communal → zonal → collinaire → citoyen
  (+ police et agent_recensement créés par admin)
Rôles: admin, ministere, provincial, communal, zonal, collinaire, citoyen, police, agent_recensement
Chaque rôle ne peut créer que le rôle directement en dessous, dans sa propre zone.
Principe: chaque utilisateur est assigné à une zone géographique lors de son enregistrement.
  - Admin (geographic_area_id = NULL) → voit TOUT le pays
  - Ministère → niveau national, voit tout
  - Provincial assigné à province X → voit province X et descendants
  - Communal assigné à commune Y → voit commune Y et descendants
  - Zonal assigné à zone Z → voit zone Z et descendants
  - Collinaire assigné à colline W → voit colline W uniquement
  - Citoyen → voit ses propres données
Vérifications dans UserManagementController:
  - Le rôle créé doit correspondre au niveau inférieur exact
  - La zone assignée doit être du bon type géographique (provincial=province, etc.)
  - La zone doit être dans le périmètre du créateur

Controllers mis à jour avec filtrage par zone:
  UserManagementController — endpoint unifié /users avec logique hiérarchique
  HouseholdController — index/show/stats filtrés par zone
  DashboardController — gouvernement/securite filtrés par zone
  StatsController — toutes les stats filtrées par zone
  ValidationController — pending/validate filtrés par zone
  ReportController — all/updateStatut filtrés par zone
  PaymentController — validate filtrés par zone, notifications ciblées par zone
  MemberController — notifications envoyées aux autorités de la zone
  AuthController — retourne les infos de zone au login

Hiérarchie géographique du Burundi (data.json):
  Niveau 1: Province (ex: BUJUMBURA, GITEGA, NGOZI...)
  Niveau 2: Commune (ex: MUKAZA, NTAHANGWA...)
  Niveau 3: Zone (ex: Buyenzi, Bwiza, Nyakabiga...)
  Niveau 4: Colline (ex: Quartier Nyakabiga, Quartier Mugoboka...)

Phase 2 - Système de recensement (type Google Forms)  TERMINÉE
Migration: table censuses (titre, description, statut, dates, scope géographique)
Migration: table census_fields (census_id, label, type, options JSON, required, order)
  Types supportés: text, number, date, select, multi_select, boolean, textarea
Migration: table census_agents (census_id, user_id, zone assignée)
Migration: table census_responses + census_response_values (EAV)
Rôle agent_recensement ajouté à l'enum users
Modèles: Census, CensusField, CensusAgent, CensusResponse, CensusResponseValue

CensusController — CRUD campagnes (autorités):
  GET    /api/censuses — liste des campagnes (filtrée par zone)
  POST   /api/censuses — créer campagne + champs (comme Google Forms)
  GET    /api/censuses/{id} — détail campagne
  PUT    /api/censuses/{id} — modifier (titre, statut, dates)
  PUT    /api/censuses/{id}/fields — modifier les champs (brouillon seulement)
  DELETE /api/censuses/{id} — supprimer

CensusAgentController — gestion des agents:
  GET    /api/censuses/{id}/agents — lister agents
  POST   /api/censuses/{id}/agents — créer agent + l'assigner
  POST   /api/censuses/{id}/agents/assign — assigner un utilisateur existant
  DELETE /api/censuses/{id}/agents/{agentId} — retirer agent

CensusCollectionController — collecte terrain (agents):
  GET    /api/census/my-campaigns — mes campagnes actives
  GET    /api/census/{id}/form — voir le formulaire
  POST   /api/census/{id}/responses — soumettre une réponse
  GET    /api/census/{id}/my-responses — mes réponses collectées

CensusExportController — export:
  GET    /api/censuses/{id}/export/csv — export CSV (séparateur ;, UTF-8 BOM)
  GET    /api/censuses/{id}/table — données tabulaires JSON

CensusStatsController — statistiques:
  GET    /api/censuses/{id}/stats — stats complètes (par agent, zone, jour, champ)
  POST   /api/censuses/compare — comparer N recensements

Middleware CheckCensusAgent — vérifie que l'utilisateur est agent ou autorité

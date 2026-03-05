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


Phase 1 - Architecture géographique multi-pays
Migration: table countries (name, code ISO, phone_code, currency, locale)
Migration: table geographic_levels (country_id, name, level_order)
Migration: table geographic_areas (country_id, parent_id, level_id, name)
Migration: update users - ajouter country_id
Migration: update households - remplacer quartier par geographic_area_id
Modèles: Country, GeographicLevel, GeographicArea + relations
Update modèles existants (User, Household) avec nouvelles relations
Seeder: importer data.json dans les tables géographiques (Burundi)
Controller + routes API pour données géographiques (pays, niveaux, zones)
Phase 2 - Système de recensement (type Google Forms)
Migration: table censuses (titre, description, statut, dates, scope géographique)
Migration: table census_fields (census_id, label, type, options JSON, required, order)
Migration: table census_agents (census_id, user_id, zone assignée)
Migration: table census_responses + census_response_values (EAV)
Ajouter rôle agent_recensement au enum users
Modèles: Census, CensusField, CensusAgent, CensusResponse, CensusResponseValue
CensusController - CRUD campagnes de recensement (admin)
CensusAgentController - gestion des agents (admin)
CensusCollectionController - collecte données terrain (agents)
CensusExportController - export Excel/PDF des résultats
CensusStatsController - statistiques et comparaison entre recensements
Middleware CheckCensusAgent - accès restreint aux agents
Routes API complètes pour le module recensement


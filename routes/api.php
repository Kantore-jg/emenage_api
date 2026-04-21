<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CensusAgentController;
use App\Http\Controllers\CensusCollectionController;
use App\Http\Controllers\CensusController;
use App\Http\Controllers\CensusExportController;
use App\Http\Controllers\CensusStatsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeographicController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\IdentityCardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ValidationController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);

// Données géographiques (public pour les formulaires)
Route::prefix('geographic')->group(function () {
    Route::get('/levels', [GeographicController::class, 'levels']);
    Route::get('/areas', [GeographicController::class, 'areas']);
    Route::get('/areas/{id}', [GeographicController::class, 'show']);
    Route::get('/tree', [GeographicController::class, 'tree']);
    Route::get('/search', [GeographicController::class, 'search']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Gestion des utilisateurs (tous les niveaux d'autorité)
    // La logique hiérarchique est dans le controller:
    // admin→ministere, ministere→provincial, provincial→communal,
    // communal→zonal, zonal→collinaire, collinaire→citoyen
    Route::middleware('role:admin,ministere,provincial,communal,zonal,collinaire')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']);
        Route::get('/users/{id}', [UserManagementController::class, 'show']);
        Route::put('/users/{id}', [UserManagementController::class, 'update']);
        Route::post('/users/{id}/reset-password', [UserManagementController::class, 'resetPassword']);
        Route::delete('/users/{id}', [UserManagementController::class, 'destroy']);
    });

    // Recherche globale
    Route::get('/search', [SearchController::class, 'search']);

    // Stats (accueil)
    Route::get('/stats', [StatsController::class, 'index']);

    // Dashboard
    Route::get('/dashboard/citoyen', [DashboardController::class, 'citoyen']);
    Route::get('/dashboard/gouvernement', [DashboardController::class, 'gouvernement']);
    Route::get('/dashboard/securite', [DashboardController::class, 'securite']);

    // Notifications
    Route::get('/dashboard/notifications', [NotificationController::class, 'index']);
    Route::delete('/dashboard/notifications/{id}', [NotificationController::class, 'destroy']);

    // Household members management (chef de famille + autorités)
    Route::middleware('role:citoyen,collinaire,zonal,communal,provincial,ministere,admin')->group(function () {
        Route::post('/household/members', [MemberController::class, 'storeMember']);
        Route::post('/household/invites', [MemberController::class, 'storeInvite']);
        Route::put('/household/invites/{id}', [MemberController::class, 'updateInvite']);
        Route::delete('/household/members/{id}', [MemberController::class, 'destroy']);
    });

    // Households (toutes les autorités)
    Route::middleware('role:collinaire,zonal,communal,provincial,ministere,admin')->group(function () {
        Route::get('/households', [HouseholdController::class, 'index']);
        Route::get('/households/{id}', [HouseholdController::class, 'show']);
        Route::get('/households-stats', [HouseholdController::class, 'stats']);
    });

    // Announcements (toutes les autorités)
    Route::middleware('role:collinaire,zonal,communal,provincial,ministere,admin')->group(function () {
        Route::post('/announcements', [AnnouncementController::class, 'store']);
    });
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

    // Reports
    Route::post('/reports', [ReportController::class, 'store']);
    Route::post('/reports/{reportId}/feedback', [FeedbackController::class, 'store']);
    Route::get('/feedbacks/mine', [FeedbackController::class, 'myFeedbacks']);
    Route::middleware('role:police,collinaire,zonal,communal,provincial,ministere,admin')->group(function () {
        Route::put('/reports/{id}/statut', [ReportController::class, 'updateStatut']);
        Route::get('/reports/all', [ReportController::class, 'all']);
    });

    // Validation (toutes les autorités)
    Route::middleware('role:collinaire,zonal,communal,provincial,ministere,admin')->group(function () {
        Route::put('/validation/members/{id}', [ValidationController::class, 'validateMember']);
        Route::get('/validation/pending', [ValidationController::class, 'pending']);
    });

    // Identity card
    Route::get('/identity-card', [IdentityCardController::class, 'show']);

    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::middleware('role:collinaire,zonal,communal,provincial,ministere,admin')->group(function () {
        Route::put('/payments/{id}/validate', [PaymentController::class, 'validate_payment']);
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);

    // ==========================================
    // Module Recensement
    // ==========================================

    // Gestion des campagnes (autorités uniquement)
    Route::middleware('role:collinaire,zonal,communal,provincial,ministere,admin')->group(function () {
        Route::get('/censuses', [CensusController::class, 'index']);
        Route::post('/censuses', [CensusController::class, 'store']);
        Route::get('/censuses/{id}', [CensusController::class, 'show']);
        Route::put('/censuses/{id}', [CensusController::class, 'update']);
        Route::put('/censuses/{id}/fields', [CensusController::class, 'updateFields']);
        Route::delete('/censuses/{id}', [CensusController::class, 'destroy']);

        // Gestion des agents
        Route::get('/censuses/{censusId}/agents', [CensusAgentController::class, 'index']);
        Route::post('/censuses/{censusId}/agents', [CensusAgentController::class, 'store']);
        Route::post('/censuses/{censusId}/agents/assign', [CensusAgentController::class, 'assign']);
        Route::delete('/censuses/{censusId}/agents/{agentId}', [CensusAgentController::class, 'destroy']);

        // Export et statistiques
        Route::get('/censuses/{censusId}/export/csv', [CensusExportController::class, 'exportCsv']);
        Route::get('/censuses/{censusId}/table', [CensusExportController::class, 'table']);
        Route::get('/censuses/{censusId}/stats', [CensusStatsController::class, 'show']);
        Route::post('/censuses/compare', [CensusStatsController::class, 'compare']);
    });

    // Export PDF
    Route::middleware('role:collinaire,zonal,communal,provincial,ministere,admin')->group(function () {
        Route::get('/export/zone-report', [ReportExportController::class, 'zoneReport']);
    });

    // Calendrier communautaire
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::middleware('role:collinaire,zonal,communal,provincial,ministere,admin')->group(function () {
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);
    });

    // Collecte terrain (agents de recensement + autorités)
    Route::middleware('census_agent')->group(function () {
        Route::get('/census/my-campaigns', [CensusCollectionController::class, 'myCensuses']);
        Route::get('/census/{censusId}/form', [CensusCollectionController::class, 'form']);
        Route::post('/census/{censusId}/responses', [CensusCollectionController::class, 'submit']);
        Route::get('/census/{censusId}/my-responses', [CensusCollectionController::class, 'myResponses']);
    });
});

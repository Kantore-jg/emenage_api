<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\IdentityCardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ValidationController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Admin: gestion des utilisateurs (police, chef_quartier, ministere)
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', [UserManagementController::class, 'index']);
        Route::post('/admin/users', [UserManagementController::class, 'storeByAdmin']);
        Route::get('/admin/users/{id}', [UserManagementController::class, 'show']);
        Route::put('/admin/users/{id}', [UserManagementController::class, 'update']);
        Route::post('/admin/users/{id}/reset-password', [UserManagementController::class, 'resetPassword']);
        Route::delete('/admin/users/{id}', [UserManagementController::class, 'destroy']);
    });

    // Chef de quartier: inscrire des citoyens
    Route::middleware('role:chef_quartier,admin')->group(function () {
        Route::post('/chef/citoyens', [UserManagementController::class, 'storeCitoyen']);
    });

    // Stats (accueil)
    Route::get('/stats', [StatsController::class, 'index']);

    // Dashboard
    Route::get('/dashboard/citoyen', [DashboardController::class, 'citoyen']);
    Route::get('/dashboard/gouvernement', [DashboardController::class, 'gouvernement']);
    Route::get('/dashboard/securite', [DashboardController::class, 'securite']);

    // Notifications
    Route::delete('/dashboard/notifications/{id}', [NotificationController::class, 'destroy']);

    // Household management (chef de famille)
    Route::middleware('chef_famille')->group(function () {
        Route::post('/household/members', [MemberController::class, 'storeMember']);
        Route::post('/household/invites', [MemberController::class, 'storeInvite']);
        Route::put('/household/invites/{id}', [MemberController::class, 'updateInvite']);
        Route::delete('/household/members/{id}', [MemberController::class, 'destroy']);
    });

    // Households (authorities)
    Route::middleware('role:chef_quartier,ministere,admin')->group(function () {
        Route::get('/households', [HouseholdController::class, 'index']);
        Route::get('/households/{id}', [HouseholdController::class, 'show']);
        Route::get('/households-stats', [HouseholdController::class, 'stats']);
    });

    // Announcements (authorities)
    Route::middleware('role:chef_quartier,ministere,admin')->group(function () {
        Route::post('/announcements', [AnnouncementController::class, 'store']);
    });
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

    // Reports
    Route::post('/reports', [ReportController::class, 'store']);
    Route::middleware('role:police,admin')->group(function () {
        Route::put('/reports/{id}/statut', [ReportController::class, 'updateStatut']);
        Route::get('/reports/all', [ReportController::class, 'all']);
    });

    // Validation (authorities)
    Route::middleware('role:chef_quartier,ministere,admin')->group(function () {
        Route::put('/validation/members/{id}', [ValidationController::class, 'validateMember']);
        Route::get('/validation/pending', [ValidationController::class, 'pending']);
    });

    // Identity card
    Route::get('/identity-card', [IdentityCardController::class, 'show']);
    Route::get('/identity-card/qrcode', [IdentityCardController::class, 'qrcode']);

    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::middleware('role:chef_quartier,admin')->group(function () {
        Route::put('/payments/{id}/validate', [PaymentController::class, 'validate_payment']);
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);
});

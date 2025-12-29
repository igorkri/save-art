<?php

use Illuminate\Support\Facades\Route;

// Подключение API-маршрутов
require __DIR__.'/api-auth.php';

use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\ArtistBoardController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\V1\ArtistController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\DonationController;
use App\Http\Controllers\Api\V1\LikeController;
use App\Http\Controllers\Api\V1\MyProjectController;
use App\Http\Controllers\Api\V1\ProjectBonusController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectStageController;
use App\Http\Controllers\ProfileApiController;

// ============================================
// API v1 - Auth routes (public)
// ============================================

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);
});

Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);
});

// ============================================
// API v1 - Public routes
// ============================================

Route::prefix('v1')->group(function () {
    // Проєкти (публічні)
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::get('/{slug}', [ProjectController::class, 'show']);
        Route::get('/{slug}/donors', [ProjectController::class, 'donors']);
    });

    // Категорії мистецтва
    Route::get('/categories', function () {
        return response()->json([
            'data' => collect(\App\Enums\ArtCategory::cases())->map(fn ($cat) => [
                'value' => $cat->value,
                'label' => $cat->getLabel(),
                'subcategories' => collect($cat->getSubcategories())->map(fn ($label, $value) => [
                    'value' => $value,
                    'label' => $label,
                ])->values(),
            ]),
        ]);
    });

    // Митці (публічний профіль)
    Route::prefix('artists')->group(function () {
        Route::get('/', [ArtistController::class, 'index']);
        Route::get('/{slug}', [ArtistController::class, 'show']);
        Route::get('/{slug}/projects', [ArtistController::class, 'projects']);
    });

    // Донати (публічні для ініціалізації)
    Route::post('/projects/{project}/donate', [DonationController::class, 'store']);

    // Webhook від платіжної системи (без auth)
    Route::post('/payments/webhook', [DonationController::class, 'webhook']);
});

// ============================================
// API v1 - Authenticated routes
// ============================================

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Мої проєкти
    Route::prefix('my/projects')->group(function () {
        Route::get('/', [MyProjectController::class, 'index']);
        Route::post('/', [MyProjectController::class, 'store']);
        Route::get('/{project}', [MyProjectController::class, 'show']);
        Route::put('/{project}', [MyProjectController::class, 'update']);
        Route::delete('/{project}', [MyProjectController::class, 'destroy']);
        Route::post('/{project}/submit', [MyProjectController::class, 'submit']);
        Route::post('/{project}/complete', [MyProjectController::class, 'complete']);
    });

    // Лайки
    Route::post('/projects/{project}/like', [LikeController::class, 'store']);
    Route::delete('/projects/{project}/like', [LikeController::class, 'destroy']);

    // Мої донати
    Route::get('/my/donations', [DonationController::class, 'myDonations']);
    Route::get('/my/donations/{donation}', [DonationController::class, 'show']);

    // Етапи проєкту
    Route::prefix('my/projects/{project}/stages')->group(function () {
        Route::get('/', [ProjectStageController::class, 'index']);
        Route::post('/', [ProjectStageController::class, 'store']);
        Route::put('/{stage}', [ProjectStageController::class, 'update']);
        Route::delete('/{stage}', [ProjectStageController::class, 'destroy']);
        Route::post('/{stage}/start', [ProjectStageController::class, 'start']);
        Route::post('/{stage}/complete', [ProjectStageController::class, 'complete']);
    });

    // Бонуси проєкту
    Route::prefix('my/projects/{project}/bonuses')->group(function () {
        Route::get('/', [ProjectBonusController::class, 'index']);
        Route::post('/', [ProjectBonusController::class, 'store']);
        Route::put('/{bonus}', [ProjectBonusController::class, 'update']);
        Route::delete('/{bonus}', [ProjectBonusController::class, 'destroy']);
    });
});

// ============================================
// Legacy public routes
// ============================================

// Public routes for About (не требуют аутентификации)
Route::prefix('about')->group(function () {
    Route::get('/', [AboutController::class, 'index']);
    Route::get('/language/{language}', [AboutController::class, 'getByLanguage']);
    Route::get('/{id}', [AboutController::class, 'show']);
});

// Public routes for ArtistBoard (не требуют аутентификации)
Route::prefix('artist-board')->group(function () {
    Route::get('/', [ArtistBoardController::class, 'index']);
    Route::get('/language/{language}', [ArtistBoardController::class, 'getByLanguage']);
    Route::get('/{id}', [ArtistBoardController::class, 'show']);
});

// Public routes for Content
Route::prefix('content')->group(function () {
    Route::get('/', [ContentController::class, 'index']);
    Route::get('/{slug}/{language}', [ContentController::class, 'showByLanguage']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileApiController::class, 'getProfile']);
    Route::put('/profile/personal', [ProfileApiController::class, 'updatePersonal']);
    Route::post('/profile/personal', [ProfileApiController::class, 'createPersonal']);
    Route::put('/profile/legal', [ProfileApiController::class, 'updateLegal']);
    Route::post('/profile/legal', [ProfileApiController::class, 'createLegal']);
    Route::put('/profile/social', [ProfileApiController::class, 'updateSocial']);
    Route::post('/profile/social', [ProfileApiController::class, 'createSocial']);

    // Документы профиля
    Route::prefix('profile/documents')->group(function () {
        Route::get('/', [ProfileApiController::class, 'getDocuments']);
        Route::post('/', [ProfileApiController::class, 'uploadDocument']);
        Route::get('/{documentId}', [ProfileApiController::class, 'getDocument']);
        Route::delete('/{documentId}', [ProfileApiController::class, 'deleteDocument']);
        Route::get('/{documentId}/download', [ProfileApiController::class, 'downloadDocument']);
    });
});

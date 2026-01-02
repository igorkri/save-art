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
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\DonationController;
use App\Http\Controllers\Api\V1\DraftController;
use App\Http\Controllers\Api\V1\FaqController;
use App\Http\Controllers\Api\V1\LikeController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\MyProjectController;
use App\Http\Controllers\Api\V1\ProjectBonusController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectStageController;
use App\Http\Controllers\Api\V1\PublicUserController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\StatisticsController;
use App\Http\Controllers\ProfileApiController;

// ============================================
// API v1 - Auth routes (public)
// ============================================

Route::prefix('v1/auth')->middleware('throttle:auth')->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

    // Google OAuth
    Route::get('/google/redirect', [SocialAuthController::class, 'googleRedirect']);
    Route::post('/google/callback', [SocialAuthController::class, 'googleCallback']);
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

    // Регіони
    Route::get('/regions', function () {
        return response()->json([
            'data' => collect(\App\Enums\Region::cases())->map(fn ($region) => [
                'value' => $region->value,
                'label' => $region->getLabel(),
            ]),
        ]);
    });

    // Статистика платформи
    Route::prefix('statistics')->group(function () {
        Route::get('/', [StatisticsController::class, 'index']);
        Route::get('/projects', [StatisticsController::class, 'projects']);
        Route::get('/donations', [StatisticsController::class, 'donations']);
    });

    // Звіти (публічні)
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index']);
        Route::get('/{id}', [ReportController::class, 'show'])->where('id', '[0-9]+');
    });

    // Звіти по проєкту
    Route::get('/projects/{slug}/reports', [ReportController::class, 'byProject']);

    // FAQ (публічний)
    Route::prefix('faq')->group(function () {
        Route::get('/', [FaqController::class, 'index']);
        Route::get('/language/{language}', [FaqController::class, 'byLanguage']);
        Route::get('/category/{slug}', [FaqController::class, 'category']);
    });

    // Митці (публічний профіль)
    Route::prefix('artists')->group(function () {
        Route::get('/', [ArtistController::class, 'index']);
        Route::get('/{slug}', [ArtistController::class, 'show']);
        Route::get('/{slug}/projects', [ArtistController::class, 'projects']);
    });

    // Публічні профілі користувачів
    Route::prefix('users')->group(function () {
        Route::get('/{id}', [PublicUserController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/{id}/projects', [PublicUserController::class, 'projects'])->where('id', '[0-9]+');
    });

    // Донати (публічні для ініціалізації)
    Route::post('/projects/{project}/donate', [DonationController::class, 'store'])
        ->middleware('throttle:donations');

    // Webhook від платіжної системи (без auth)
    Route::post('/payments/webhook', [DonationController::class, 'webhook'])->name('api.v1.payments.webhook');
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

    // Чернетки проєктів
    Route::prefix('my/drafts')->group(function () {
        Route::get('/', [DraftController::class, 'index']);
        Route::post('/', [DraftController::class, 'store']);
        Route::get('/{id}', [DraftController::class, 'show']);
        Route::put('/{id}', [DraftController::class, 'update']);
        Route::delete('/{id}', [DraftController::class, 'destroy']);
        Route::post('/sync', [DraftController::class, 'sync']);
    });

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

    // Повідомлення (чат з адміністрацією)
    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index']);
        Route::post('/', [MessageController::class, 'store']);
        Route::get('/unread-count', [MessageController::class, 'unreadCount']);
        Route::post('/mark-all-read', [MessageController::class, 'markAllAsRead']);
        Route::get('/{message}', [MessageController::class, 'show']);
    });

    // Написати автору проєкту (через адміністрацію)
    Route::post('/projects/{project}/contact-author', [MessageController::class, 'contactProjectAuthor']);
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
    Route::put('/profile/password', [ProfileApiController::class, 'updatePassword']);
    Route::post('/profile/avatar', [ProfileApiController::class, 'uploadAvatar']);
    Route::delete('/profile', [ProfileApiController::class, 'requestDeletion']);

    // Документы профиля
    Route::prefix('profile/documents')->group(function () {
        Route::get('/', [ProfileApiController::class, 'getDocuments']);
        Route::post('/', [ProfileApiController::class, 'uploadDocument']);
        Route::get('/{documentId}', [ProfileApiController::class, 'getDocument']);
        Route::delete('/{documentId}', [ProfileApiController::class, 'deleteDocument']);
        Route::get('/{documentId}/download', [ProfileApiController::class, 'downloadDocument']);
    });
});

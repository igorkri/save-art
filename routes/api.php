<?php

use Illuminate\Support\Facades\Route;

// Подключение API-маршрутов
require __DIR__.'/api-auth.php';

use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\ArtistBoardController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\HomePageController;
use App\Http\Controllers\Api\SiteSettingsController;
use App\Http\Controllers\Api\V1\ArtistController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ContractController;
use App\Http\Controllers\Api\V1\DonationController;
use App\Http\Controllers\Api\V1\DraftController;
use App\Http\Controllers\Api\V1\FaqController;
use App\Http\Controllers\Api\V1\LikeController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\MyProjectController;
use App\Http\Controllers\Api\V1\NotificationController;
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

Route::prefix('v1/auth')->middleware(['api.key', 'throttle:auth'])->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

    // Google OAuth
    Route::get('/google/redirect', [SocialAuthController::class, 'googleRedirect']);
    Route::post('/google/callback', [SocialAuthController::class, 'googleCallback']);
});

Route::prefix('v1/auth')->middleware(['api.key', 'auth:sanctum'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);
    Route::put('/change-password', \App\Http\Controllers\Api\V1\Auth\ChangePasswordController::class);
});

// ============================================
// API v1 - Public routes
// ============================================

Route::prefix('v1')->middleware('api.key')->group(function () {
    // Проєкти (публічні)
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::get('/{slug}', [ProjectController::class, 'show']);
        Route::get('/{slug}/donors', [ProjectController::class, 'donors']);
    });

    // Категорії мистецтва
    Route::get('/categories', [CatalogController::class, 'categories']);

    // Регіони
    Route::get('/regions', [CatalogController::class, 'regions']);

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

    // Донат на платформу (без прив'язки до проекту)
    Route::post('/donations/platform', [DonationController::class, 'storePlatformDonation'])
        ->middleware('throttle:donations');
});

// Webhook від платіжної системи (без API key - зовнішній сервіс)
Route::prefix('v1')->group(function () {
    Route::post('/payments/webhook', [DonationController::class, 'webhook'])->name('api.v1.payments.webhook');
});

// ============================================
// API v1 - Authenticated routes
// ============================================

Route::prefix('v1')->middleware(['api.key', 'auth:sanctum'])->group(function () {
    // Профіль користувача
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileApiController::class, 'getProfile']);
        Route::put('/personal', [ProfileApiController::class, 'updatePersonal']);
        Route::post('/personal', [ProfileApiController::class, 'createPersonal']);
        Route::put('/legal', [ProfileApiController::class, 'updateLegal']);
        Route::post('/legal', [ProfileApiController::class, 'createLegal']);
        Route::put('/social', [ProfileApiController::class, 'updateSocial']);
        Route::post('/social', [ProfileApiController::class, 'createSocial']);
        Route::put('/password', [ProfileApiController::class, 'updatePassword']);
        Route::post('/avatar', [ProfileApiController::class, 'uploadAvatar']);
        Route::delete('/', [ProfileApiController::class, 'requestDeletion']);

        // Документи профілю
        Route::prefix('documents')->group(function () {
            Route::get('/', [ProfileApiController::class, 'getDocuments']);
            Route::post('/', [ProfileApiController::class, 'uploadDocument']);
            Route::get('/{documentId}', [ProfileApiController::class, 'getDocument']);
            Route::delete('/{documentId}', [ProfileApiController::class, 'deleteDocument']);
            Route::get('/{documentId}/download', [ProfileApiController::class, 'downloadDocument']);
        });
    });

    // Мої проєкти
    Route::prefix('my/projects')->group(function () {
        Route::get('/', [MyProjectController::class, 'index']);
        Route::post('/', [MyProjectController::class, 'store']);
        Route::get('/{project}', [MyProjectController::class, 'show']);
        Route::put('/{project}', [MyProjectController::class, 'update']);
        Route::patch('/{project}', [MyProjectController::class, 'updatePartial']);
        Route::delete('/{project}', [MyProjectController::class, 'destroy']);
        Route::post('/{project}/submit', [MyProjectController::class, 'submit']);
        Route::post('/{project}/final-result/upload', [MyProjectController::class, 'uploadFinalResult']);
        Route::post('/{project}/complete', [MyProjectController::class, 'complete']);
    });

    // Чернетки проєктів
    Route::prefix('my/drafts')->group(function () {
        Route::get('/', [DraftController::class, 'index']);
        Route::post('/', [DraftController::class, 'store']);
        Route::get('/{id}', [DraftController::class, 'show']);
        Route::put('/{id}', [DraftController::class, 'update']);
        Route::delete('/{id}', [DraftController::class, 'destroy']);
        Route::post('/sync', [DraftController::class, 'sync']);
    });

    // Лайки
    Route::post('/projects/{project}/like', [LikeController::class, 'store']);
    Route::delete('/projects/{project}/like', [LikeController::class, 'destroy']);

    // Мої донати
    Route::get('/my/donations', [DonationController::class, 'myDonations']);
    Route::get('/my/donations/{donation}', [DonationController::class, 'show']);

    // Мої сповіщення (03.2.4)
    Route::prefix('my/notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::get('/{source}/{id}', [NotificationController::class, 'show'])
            ->where(['source' => 'notification|message', 'id' => '[0-9]+']);
        Route::post('/{source}/{id}/read', [NotificationController::class, 'markAsRead'])
            ->where(['source' => 'notification|message', 'id' => '[0-9]+']);
        Route::delete('/{source}/{id}', [NotificationController::class, 'destroy'])
            ->where(['source' => 'notification|message', 'id' => '[0-9]+']);
    });

    // Етапи проєкту
    Route::prefix('my/projects/{project}/stages')->group(function () {
        Route::get('/', [ProjectStageController::class, 'index']);
        Route::post('/', [ProjectStageController::class, 'store']);
        Route::put('/{stage}', [ProjectStageController::class, 'update']);
        Route::delete('/{stage}', [ProjectStageController::class, 'destroy']);
        Route::post('/{stage}/start', [ProjectStageController::class, 'start']);
        Route::post('/{stage}/complete', [ProjectStageController::class, 'complete']);
        Route::post('/{stage}/documents', [ProjectStageController::class, 'uploadDocuments']);
        Route::delete('/{stage}/documents/{documentIndex}', [ProjectStageController::class, 'deleteDocument']);
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

    // Контракти (03.7.5, 03.7.5.1)
    Route::prefix('contracts')->group(function () {
        Route::get('/template', [ContractController::class, 'template']);
        Route::get('/template/download', [ContractController::class, 'downloadTemplate']);
        Route::get('/active', [ContractController::class, 'active']);
        Route::get('/', [ContractController::class, 'index']);
        Route::post('/', [ContractController::class, 'store']);
        Route::get('/{contract}', [ContractController::class, 'show']);
        Route::post('/{contract}/sign', [ContractController::class, 'sign']);
        Route::get('/{contract}/download', [ContractController::class, 'download']);
        Route::delete('/{contract}', [ContractController::class, 'destroy']);
    });
});

// ============================================
// Legacy public routes
// ============================================

// Site global settings (глобальні налаштування для всіх сторінок)
Route::prefix('site')->middleware('api.key')->group(function () {
    Route::get('/settings', [SiteSettingsController::class, 'index']);
    Route::get('/footer', [SiteSettingsController::class, 'footer']);
    Route::get('/header', [SiteSettingsController::class, 'header']);
});

// Home page data (главная страница для React фронтенда)
Route::prefix('home')->middleware('api.key')->group(function () {
    Route::get('/', [HomePageController::class, 'index']);
    Route::get('/statistics', [HomePageController::class, 'statistics']);
    Route::get('/chart', [HomePageController::class, 'chart']);
});

// Public routes for About (не требуют аутентификации)
Route::get('/about', [AboutController::class, 'index'])->middleware('api.key');

// Public routes for ArtistBoard (не требуют аутентификации)
Route::get('/artist-board', [ArtistBoardController::class, 'index'])->middleware('api.key');

// Public routes for Content
Route::prefix('content')->middleware('api.key')->group(function () {
    Route::get('/', [ContentController::class, 'index']);
    Route::get('/language/{language}', [ContentController::class, 'getByLanguage']);
    Route::get('/{slug}', [ContentController::class, 'show']);
    Route::get('/{slug}/{language}', [ContentController::class, 'showByLanguage']);
});

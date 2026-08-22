<?php

use App\Http\Controllers\Api\V1\ArtCatalogController;
use App\Http\Controllers\Api\V1\ArtUaInfo\ArtistController;
use App\Http\Controllers\Api\V1\ArtUaInfo\CatalogController;
use App\Http\Controllers\Api\V1\ArtUaInfo\FaqController;
use App\Http\Controllers\Api\V1\ArtUaInfo\HomePageController;
use App\Http\Controllers\Api\V1\ArtUaInfo\LikeController;
use App\Http\Controllers\Api\V1\ArtUaInfo\OrganizationController;
use App\Http\Controllers\Api\V1\ArtUaInfo\ProfileApiController;
use App\Http\Controllers\Api\V1\ArtUaInfo\PublicProjectController;
use App\Http\Controllers\Api\V1\ArtUaInfo\RegisterController;
use App\Http\Controllers\Api\V1\ArtUaInfo\SocialAuthController;
use App\Http\Controllers\Api\V1\ArtUaInfo\TeamController;
use App\Http\Controllers\Api\V1\ArtUaInfo\TermsController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmailController;
use App\Http\Controllers\Api\V1\LikeController as SharedLikeController;
use App\Http\Controllers\Api\V1\MyServiceController;
use App\Http\Controllers\Api\V1\MyTeamController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\ArtInfo\InfoController;
use Illuminate\Support\Facades\Route;

// ============================================
// API v1/art-ua-info — окремий неймспейс маршрутів для art-ua-info,
// фізично ізольований від routes/api.php (save-art). Спільна БД, різні роути:
// правки тут не можуть випадково зачепити save-art і навпаки.
// ============================================

Route::prefix('v1/art-ua-info/auth')->middleware(['api.key', 'throttle:auth'])->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);
    Route::get('/google/redirect', [SocialAuthController::class, 'googleRedirect']);
    Route::post('/google/callback', [SocialAuthController::class, 'googleCallback']);
});

Route::prefix('v1/art-ua-info/auth')->middleware(['api.key', 'auth:sanctum'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);
    Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:6,1');
});

Route::prefix('v1/art-ua-info')->middleware('api.key')->group(function () {
    Route::get('/info', [InfoController::class, 'index']);
    Route::get('/home', [HomePageController::class, 'index']);

    Route::prefix('projects')->group(function () {
        Route::get('/', [PublicProjectController::class, 'index']);
        Route::get('/{slug}', [PublicProjectController::class, 'show']);
    });

    // Аліас "works" — фронтенд art-ua-info рендерить сторінку проєктів на
    // /works (URL змінили на прохання клієнта), бек лишається на "projects".
    Route::prefix('works')->group(function () {
        Route::get('/', [PublicProjectController::class, 'index']);
        Route::get('/{slug}', [PublicProjectController::class, 'show']);
    });

    Route::get('/categories', [CatalogController::class, 'categories']);

    Route::prefix('catalogs')->group(function () {
        Route::get('/', [ArtCatalogController::class, 'index']);
        Route::get('/{id}', [ArtCatalogController::class, 'show'])->where('id', '[0-9]+');
    });

    Route::prefix('faq')->group(function () {
        Route::get('/', [FaqController::class, 'index']);
        Route::get('/category/{slug}', [FaqController::class, 'category']);
    });

    Route::prefix('terms')->group(function () {
        Route::get('/', [TermsController::class, 'index']);
    });

    Route::prefix('news')->group(function () {
        Route::get('/', [NewsController::class, 'index']);
        Route::get('/{slug}', [NewsController::class, 'show']);
    });

    Route::prefix('artists')->group(function () {
        Route::get('/', [ArtistController::class, 'index']);
        Route::get('/{slug}', [ArtistController::class, 'show']);
        Route::get('/{slug}/projects', [ArtistController::class, 'projects']);
    });

    Route::prefix('organizations')->group(function () {
        Route::get('/', [OrganizationController::class, 'index']);
        Route::get('/{slug}', [OrganizationController::class, 'show']);
        Route::get('/{slug}/projects', [OrganizationController::class, 'projects']);
    });

    Route::prefix('teams')->group(function () {
        Route::get('/', [TeamController::class, 'index']);
        Route::get('/{slug}', [TeamController::class, 'show']);
        Route::get('/{slug}/projects', [TeamController::class, 'projects']);
    });
});

// ============================================
// API v1/art-ua-info — авторизовані маршрути
// ============================================

Route::prefix('v1/art-ua-info')->middleware(['api.key', 'auth:sanctum'])->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileApiController::class, 'getProfile']);
    });

    Route::post('/projects/{project}/like', [LikeController::class, 'store']);
    Route::delete('/projects/{project}/like', [LikeController::class, 'destroy']);

    // ArtCatalog — спільна модель для save-art і art-ua-info, тож лайки теж
    // через спільний LikeController (як і ArtCatalogController вище).
    Route::post('/catalogs/{artCatalog}/like', [SharedLikeController::class, 'likeCatalog']);
    Route::delete('/catalogs/{artCatalog}/like', [SharedLikeController::class, 'unlikeCatalog']);

    // my/catalogs (art-ua-info) видалено — керування каталогами переїхало у
    // Filament-панель "profile" (App\Filament\Profile\Resources\Catalogs).

    Route::prefix('my/services')->group(function () {
        Route::get('/', [MyServiceController::class, 'index']);
        Route::get('/{service}', [MyServiceController::class, 'show']);

        Route::middleware('not.blocked')->group(function () {
            Route::post('/', [MyServiceController::class, 'store']);
            Route::put('/{service}', [MyServiceController::class, 'update']);
            Route::delete('/{service}', [MyServiceController::class, 'destroy']);
        });
    });

    Route::prefix('my/teams')->group(function () {
        Route::get('/', [MyTeamController::class, 'index']);

        Route::middleware('not.blocked')->group(function () {
            Route::post('/', [MyTeamController::class, 'store']);
            Route::put('/{team}', [MyTeamController::class, 'update']);
            Route::delete('/{team}', [MyTeamController::class, 'destroy']);
            Route::post('/{team}/leave', [MyTeamController::class, 'leave']);
        });

        Route::get('/{team}/services', [MyServiceController::class, 'teamIndex']);
        Route::middleware('not.blocked')->group(function () {
            Route::post('/{team}/services', [MyServiceController::class, 'teamStore']);
        });
    });

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
});

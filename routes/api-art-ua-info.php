<?php

use App\Http\Controllers\Api\V1\ArtCatalogController;
use App\Http\Controllers\Api\V1\ArtUaInfo\ArtistController;
use App\Http\Controllers\Api\V1\ArtUaInfo\CatalogController;
use App\Http\Controllers\Api\V1\ArtUaInfo\FaqController;
use App\Http\Controllers\Api\V1\ArtUaInfo\HomePageController;
use App\Http\Controllers\Api\V1\ArtUaInfo\LikeController;
use App\Http\Controllers\Api\V1\ArtUaInfo\MyProjectController;
use App\Http\Controllers\Api\V1\ArtUaInfo\ProfileApiController;
use App\Http\Controllers\Api\V1\ArtUaInfo\ProjectController as ArtUaInfoWizardProjectController;
use App\Http\Controllers\Api\V1\ArtUaInfo\PublicProjectController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmailController;
use App\Http\Controllers\Api\V1\LikeController as SharedLikeController;
use App\Http\Controllers\Api\V1\MyArtCatalogController;
use App\Http\Controllers\Api\V1\MyServiceController;
use App\Http\Controllers\Api\V1\MyTeamController;
use App\Http\Controllers\Api\V1\NewsController;
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
        Route::get('/{slug}/donors', [PublicProjectController::class, 'donors']);
    });

    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/regions', [CatalogController::class, 'regions']);
    Route::get('/parameters', [CatalogController::class, 'parameters']);

    Route::prefix('catalogs')->group(function () {
        Route::get('/', [ArtCatalogController::class, 'index']);
        Route::get('/{id}', [ArtCatalogController::class, 'show'])->where('id', '[0-9]+');
    });

    Route::prefix('faq')->group(function () {
        Route::get('/', [FaqController::class, 'index']);
        Route::get('/category/{slug}', [FaqController::class, 'category']);
    });

    Route::prefix('news')->group(function () {
        Route::get('/', [NewsController::class, 'index']);
        Route::get('/{slug}', [NewsController::class, 'show']);
    });

    Route::prefix('artists')->group(function () {
        Route::get('/', [ArtistController::class, 'index']);
        Route::get('/{slug}', [ArtistController::class, 'show']);
        Route::get('/{slug}/projects', [ArtistController::class, 'projects']);
        Route::get('/{slug}/photos', [ArtistController::class, 'photos']);
    });
});

// ============================================
// API v1/art-ua-info — авторизовані маршрути
// ============================================

Route::prefix('v1/art-ua-info')->middleware(['api.key', 'auth:sanctum'])->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileApiController::class, 'getProfile']);
        Route::put('/personal', [ProfileApiController::class, 'updatePersonal']);
        Route::put('/social', [ProfileApiController::class, 'updateSocial']);
    });

    Route::prefix('my/projects')->group(function () {
        Route::get('/', [MyProjectController::class, 'index']);
        Route::get('/completed', [MyProjectController::class, 'completed']);
        Route::get('/{project}', [MyProjectController::class, 'show']);

        Route::middleware('not.blocked')->group(function () {
            Route::delete('/{project}', [MyProjectController::class, 'destroy']);
        });
    });

    // Створення та редагування проєкту з візарда art-ua-info (без бюджету/етапів/бонусів)
    Route::prefix('projects')->middleware('not.blocked')->group(function () {
        Route::post('/', [ArtUaInfoWizardProjectController::class, 'store']);
        Route::put('/{project}', [ArtUaInfoWizardProjectController::class, 'update']);
    });

    Route::post('/projects/{project}/like', [LikeController::class, 'store']);
    Route::delete('/projects/{project}/like', [LikeController::class, 'destroy']);

    // ArtCatalog — спільна модель для save-art і art-ua-info, тож лайки теж
    // через спільний LikeController (як і ArtCatalogController вище).
    Route::post('/catalogs/{artCatalog}/like', [SharedLikeController::class, 'likeCatalog']);
    Route::delete('/catalogs/{artCatalog}/like', [SharedLikeController::class, 'unlikeCatalog']);

    Route::prefix('my/catalogs')->group(function () {
        Route::get('/', [MyArtCatalogController::class, 'index']);

        Route::middleware('not.blocked')->group(function () {
            Route::post('/', [MyArtCatalogController::class, 'store']);
            Route::put('/{catalog}', [MyArtCatalogController::class, 'update']);
            Route::delete('/{catalog}', [MyArtCatalogController::class, 'destroy']);
            Route::post('/{catalog}/primary', [MyArtCatalogController::class, 'setPrimary']);
        });
    });

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
    });
});

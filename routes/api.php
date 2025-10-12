<?php

use Illuminate\Support\Facades\Route;

// Подключение API-маршрутов
require __DIR__.'/api-auth.php';

use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\ArtistBoardController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\ProfileApiController;

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
});

<?php

use App\Http\Controllers\Profile\GoogleAuthController;
use App\Http\Controllers\Profile\SsoLoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaveArt\HomeController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

// Редирект с корня на админ-панель
Route::get('/', function () {
    return redirect('/admin');
});

// Google OAuth для панелі авторів (profile) — окремий callback від API/SPA,
// бо тут потрібна сесія Filament-панелі, а не Sanctum-токен.
Route::middleware('web')->prefix('profile/auth/google')->group(function () {
    Route::get('/redirect', [GoogleAuthController::class, 'redirect'])->name('profile.auth.google.redirect');
    Route::get('/callback', [GoogleAuthController::class, 'callback'])->name('profile.auth.google.callback');
});

// SSO-міст: обмін одноразового гранту (виданого SPA по Bearer-токену через
// POST /v1/profile/sso-grant) на сесію Filament-панелі "profile", щоб перехід
// з кабінету SPA не вимагав повторного вводу логіна/пароля.
Route::middleware('web')->get('/profile-sso/{token}', [SsoLoginController::class, 'consume'])
    ->name('profile.sso.consume');

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
], function () {
    // Публичная часть закрыта
    // Route::get('/', [HomeController::class, 'index']);

    Route::get('/profile/new', [ProfileController::class, 'new'])->name('profile.new');
    Route::post('/profile/new', [ProfileController::class, 'store'])->name('profile.new.store');
    Route::get('/profile/legal', [ProfileController::class, 'legal'])->name('profile.legal');

    Route::get('/profile/personal', function () {
        return view('profile.personal');
    })->name('profile.personal');
    Route::get('/profile/social', function () {
        return view('profile.social');
    })->name('profile.social');
    Route::get('/profile/safety', function () {
        return view('profile.safety');
    })->name('profile.safety');
    // logout
    Route::get('/logout', function () {
        auth()->logout();
        session()->forget('api_token');

        return redirect('/admin/login');
    })->name('logout');

    require __DIR__.'/api-auth.php';
});

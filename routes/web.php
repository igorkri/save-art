<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;

// EN routes (default)
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);

Route::post('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('home');
})->name('logout');

// UA routes (with /ua prefix)
Route::prefix('ua')->group(function () {
    Route::get('/', function (Request $request) {
        return app(HomeController::class)->index($request->merge(['lang' => 'ua']));
    })->name('home.ua');

    Route::get('/register', [RegisterController::class, 'create'])->name('register.ua');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('home.ua');
    })->name('logout.ua');
});

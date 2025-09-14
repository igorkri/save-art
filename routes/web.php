<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;

// EN routes (default)
Route::get('/', function () {
    return view('home');
})->name('home');

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
    Route::get('/', function () {
        return view('home');
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

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;

// Головна сторінка
Route::get('/', function () {
    return view('home');
})->name('home');

// Маршрути аутентифікації (якщо потрібно для класичних контролерів)
Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);

// Маршрут для виходу
Route::post('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('home');
})->name('logout');

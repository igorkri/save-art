<?php

use App\Http\Controllers\SaveArt\HomeController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;



Route::group([
    'prefix' => LaravelLocalization::setLocale(),
], function() {
    Route::get('/', [HomeController::class, 'index']);
    Route::get('/profile/new', [App\Http\Controllers\ProfileController::class, 'new'])->name('profile.new');
    Route::post('/profile/new', [App\Http\Controllers\ProfileController::class, 'store'])->name('profile.new.store');
    Route::get('/profile/legal', [App\Http\Controllers\ProfileController::class, 'legal'])->name('profile.legal');


    Route::get('/profile/personal', function() {
        return view('profile.personal');
    })->name('profile.personal');
    Route::get('/profile/social', function() {
        return view('profile.social');
    })->name('profile.social');
    Route::get('/profile/safety', function() {
        return view('profile.safety');
    })->name('profile.safety');
    // logout
    Route::get('/logout', function () {
        auth()->logout();
        session()->forget('api_token');
        return redirect('/');
    })->name('logout');

    require __DIR__.'/api-auth.php';
});

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

    // logout
    Route::get('/logout', function () {
        auth()->logout();
        session()->forget('api_token');
        return redirect('/');
    })->name('logout');

    require __DIR__.'/api-auth.php';
});

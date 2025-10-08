<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Редирект с /uk/... на /... если основной язык
        \Illuminate\Support\Facades\Route::matched(function ($event) {
            $request = request();
            if ($request->segment(1) === 'uk') {
                $url = $request->fullUrl();
                $newUrl = preg_replace('#/uk($|/)#', '/', $url, 1);
                if ($newUrl !== $url) {
                    header('Location: ' . $newUrl, true, 301);
                    exit;
                }
            }
        });
    }
}

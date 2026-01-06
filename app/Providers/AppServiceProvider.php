<?php

namespace App\Providers;

use App\Models\Donation;
use App\Models\Project;
use App\Observers\DonationObserver;
use App\Observers\ProjectObserver;
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
        // Реєструємо observers для автоматичних сповіщень
        Donation::observe(DonationObserver::class);
        Project::observe(ProjectObserver::class);

        // Редирект з /uk/... на /... якщо основна мова
        \Illuminate\Support\Facades\Route::matched(function ($event) {
            $request = request();
            if ($request->segment(1) === 'uk') {
                $url = $request->fullUrl();
                $newUrl = preg_replace('#/uk($|/)#', '/', $url, 1);
                if ($newUrl !== $url) {
                    header('Location: '.$newUrl, true, 301);
                    exit;
                }
            }
        });
    }
}

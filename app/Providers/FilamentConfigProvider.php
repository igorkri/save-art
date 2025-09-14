<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Forms\Components\FileUpload;

class FilamentConfigProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Налаштовуємо глобальні ліміти для FileUpload компонентів
        FileUpload::configureUsing(function (FileUpload $component): void {
            $component
                ->maxSize(73728) // 72 MB за замовчуванням
                ->uploadingMessage('Завантаження файлу...')
                ->loadingIndicatorPosition('center');
        });

        // Збільшуємо ліміти PHP під час виконання
        ini_set('upload_max_filesize', '80M');
        ini_set('post_max_size', '80M');
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');
    }
}

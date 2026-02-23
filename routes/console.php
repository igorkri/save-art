<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Розклад завдань для крона.
| Запуск: * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Збір даних графіка донатів кожну годину
Schedule::command('donations:collect-chart-data')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/donations-chart.log'));

// Генерація звітів з проєктів щодня о 2:00
Schedule::command('reports:generate --all')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/reports-generate.log'));

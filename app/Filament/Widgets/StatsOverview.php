<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalProjects = Project::count();
        $pendingModeration = Project::where('status', ProjectStatus::Moderation)->count();
        $activeProjects = Project::whereIn('status', [
            ProjectStatus::Announced,
            ProjectStatus::InProgress,
        ])->count();

        $totalDonations = Donation::where('status', 'paid')->sum('amount');
        $donationsCount = Donation::where('status', 'paid')->count();

        $totalUsers = User::count();

        return [
            Stat::make('Всього проєктів', $totalProjects)
                ->description('Загальна кількість')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('primary'),

            Stat::make('На модерації', $pendingModeration)
                ->description('Очікують схвалення')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingModeration > 0 ? 'warning' : 'success'),

            Stat::make('Активні проєкти', $activeProjects)
                ->description('Оголошені + В роботі')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Зібрано донатів', number_format($totalDonations, 0, ',', ' ').' ₴')
                ->description($donationsCount.' транзакцій')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger'),

            Stat::make('Користувачів', $totalUsers)
                ->description('Зареєстровано')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),
        ];
    }
}

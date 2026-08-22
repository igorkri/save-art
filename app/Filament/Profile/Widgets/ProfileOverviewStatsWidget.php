<?php

namespace App\Filament\Profile\Widgets;

use App\Enums\ProjectSource;
use App\Filament\Profile\Resources\Donations\DonationResource;
use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Filament\Profile\Resources\Services\ServiceResource;
use App\Filament\Profile\Resources\Teams\TeamResource;
use App\Filament\Profile\Resources\Works\WorkResource;
use App\Models\Donation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfileOverviewStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $userId = auth()->id();

        $projectsCount = Project::query()
            ->where('user_id', $userId)
            ->where('source', ProjectSource::SaveArt->value)
            ->count();

        $worksCount = Project::query()
            ->where('user_id', $userId)
            ->where('source', ProjectSource::ArtUaInfo->value)
            ->count();

        $teamIds = Team::query()
            ->whereHas('teamMembers', fn ($query) => $query->where('user_id', $userId))
            ->pluck('id');

        $teamsCount = $teamIds->count();

        $servicesCount = Service::query()
            ->where(function ($query) use ($userId, $teamIds) {
                $query->where(function ($query) use ($userId) {
                    $query->where('serviceable_type', User::class)
                        ->where('serviceable_id', $userId);
                })->orWhere(function ($query) use ($teamIds) {
                    $query->where('serviceable_type', Team::class)
                        ->whereIn('serviceable_id', $teamIds);
                });
            })
            ->count();

        $collectedAmount = (float) Donation::query()
            ->where('status', 'paid')
            ->whereHas('project', fn ($query) => $query->where('user_id', $userId))
            ->sum('amount');

        $unreadMessagesCount = Message::query()
            ->where('user_id', $userId)
            ->fromAdmin()
            ->unread()
            ->count();

        $unreadNotificationsCount = Notification::query()
            ->where('user_id', $userId)
            ->unread()
            ->count();

        $unreadTotal = $unreadMessagesCount + $unreadNotificationsCount;

        return [
            Stat::make(__('profile_dashboard.stats.projects'), $projectsCount)
                ->icon('heroicon-o-rectangle-stack')
                ->url(ProjectResource::getUrl(panel: 'profile'))
                ->color('gray'),
            Stat::make(__('profile_dashboard.stats.works'), $worksCount)
                ->icon('heroicon-o-paint-brush')
                ->url(WorkResource::getUrl(panel: 'profile'))
                ->color('gray'),
            Stat::make(__('profile_dashboard.stats.teams'), $teamsCount)
                ->icon('heroicon-o-user-group')
                ->url(TeamResource::getUrl(panel: 'profile'))
                ->color('gray'),
            Stat::make(__('profile_dashboard.stats.services'), $servicesCount)
                ->icon('heroicon-o-wrench-screwdriver')
                ->url(ServiceResource::getUrl(panel: 'profile'))
                ->color('gray'),
            Stat::make(__('profile_dashboard.stats.collected'), number_format($collectedAmount, 0, ',', ' ').' ₴')
                ->icon('heroicon-o-banknotes')
                ->url(DonationResource::getUrl(panel: 'profile'))
                ->color('success'),
            Stat::make(__('profile_dashboard.stats.unread'), $unreadTotal)
                ->description(__('profile_dashboard.stats.unread_description', [
                    'messages' => $unreadMessagesCount,
                    'notifications' => $unreadNotificationsCount,
                ]))
                ->icon('heroicon-o-bell-alert')
                ->color($unreadTotal > 0 ? 'danger' : 'gray'),
        ];
    }
}

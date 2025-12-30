<?php

namespace App\Filament\Widgets;

use App\Models\Message;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MessagesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $unreadCount = Message::where('direction', 'user_to_admin')
            ->whereNull('read_at')
            ->count();

        $todayCount = Message::whereDate('created_at', today())->count();

        $totalCount = Message::count();

        return [
            Stat::make('Непрочитані повідомлення', $unreadCount)
                ->description('Від користувачів')
                ->descriptionIcon('heroicon-m-envelope')
                ->color($unreadCount > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.messages.index', ['tableFilters[read_status][value]' => 'unread'])),

            Stat::make('Сьогодні', $todayCount)
                ->description('Нових повідомлень')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info'),

            Stat::make('Всього', $totalCount)
                ->description('Повідомлень')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('gray'),
        ];
    }
}

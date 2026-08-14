<?php

namespace App\Filament\Profile\Resources\Notifications;

use App\Filament\Profile\Resources\Notifications\Pages\ListNotifications;
use App\Filament\Profile\Resources\Notifications\Tables\NotificationsTable;
use App\Models\Notification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('profile_panel.nav_groups.notifications');
    }

    public static function getModelLabel(): string
    {
        return __('profile_notifications.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('profile_notifications.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('profile_notifications.model.plural');
    }

    // Сповіщення відображаються через дзвіночок у шапці (NotificationsBell),
    // тож окремий пункт у бічному меню не потрібен — сторінка лишається
    // доступною за прямим посиланням із випадаючого списку.
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return NotificationsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotifications::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}

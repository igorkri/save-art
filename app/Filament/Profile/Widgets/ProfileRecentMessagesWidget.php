<?php

namespace App\Filament\Profile\Widgets;

use App\Models\Message;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ProfileRecentMessagesWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function getTableHeading(): string
    {
        return __('profile_dashboard.recent_messages.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Message::query()
                ->where('user_id', auth()->id())
                ->latest('created_at')
                ->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('direction')
                    ->label(__('profile_messages.table.direction'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Message::DIRECTION_USER_TO_ADMIN => __('profile_messages.direction.you'),
                        Message::DIRECTION_ADMIN_TO_USER => __('profile_messages.direction.admin'),
                        Message::DIRECTION_SYSTEM_TO_USER => __('profile_messages.direction.system'),
                        default => $state,
                    }),
                TextColumn::make('content')
                    ->label(__('profile_messages.table.content'))
                    ->limit(40),
                IconColumn::make('is_read')
                    ->label(__('profile_dashboard.recent_messages.read'))
                    ->boolean()
                    ->state(fn (Message $record): bool => $record->read_at !== null),
                TextColumn::make('created_at')
                    ->label(__('profile_messages.table.created_at'))
                    ->since()
                    ->sortable(false),
            ])
            ->recordUrl(null)
            ->emptyStateHeading(__('profile_dashboard.recent_messages.empty'));
    }
}

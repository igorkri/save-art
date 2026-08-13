<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Donations\DonationResource;
use App\Models\Donation;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentDonations extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Останні донати';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Donation::query()
                ->with(['project', 'user'])
                ->latest()
                ->limit(10)
            )
            ->columns([
                TextColumn::make('project.title')
                    ->label('Проєкт')
                    ->limit(30),

                TextColumn::make('amount')
                    ->label('Сума')
                    ->money(fn ($record) => $record->currency?->value ?? 'UAH'),

                TextColumn::make('user.name')
                    ->label('Донатер')
                    ->default(fn ($record) => $record->is_anonymous ? 'Анонімний' : ($record->donor_name ?? '-')),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Очікує',
                        'paid' => 'Оплачено',
                        'failed' => 'Помилка',
                        'refunded' => 'Повернено',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->recordUrl(fn (Donation $record): string => DonationResource::getUrl('view', ['record' => $record]))
            ->paginated(false);
    }
}

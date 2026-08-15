<?php

namespace App\Filament\Profile\Resources\Donations\Tables;

use App\Enums\DonationStatus;
use App\Models\Donation;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DonationsTable
{
    private static function isReceived(Donation $record): bool
    {
        return $record->project !== null && $record->project->user_id === auth()->id();
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('direction')
                    ->label(__('profile_donations.table.direction'))
                    ->badge()
                    ->getStateUsing(fn (Donation $record) => self::isReceived($record)
                        ? __('profile_donations.direction.received')
                        : __('profile_donations.direction.made'))
                    ->color(fn (Donation $record) => self::isReceived($record) ? 'success' : 'info'),

                TextColumn::make('project.title')
                    ->label(__('profile_donations.table.project'))
                    ->placeholder(__('profile_donations.table.platform'))
                    ->limit(30),

                TextColumn::make('counterparty')
                    ->label(__('profile_donations.table.counterparty'))
                    ->getStateUsing(function (Donation $record) {
                        if (self::isReceived($record)) {
                            return $record->getDisplayName();
                        }

                        return $record->project?->user?->display_name
                            ?? $record->project?->user?->full_name
                            ?? __('profile_donations.table.platform');
                    }),

                TextColumn::make('amount')
                    ->label(__('profile_donations.table.amount'))
                    ->money(fn (Donation $record) => $record->currency?->value ?? 'UAH')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('profile_donations.table.status'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        DonationStatus::Pending->value => 'warning',
                        DonationStatus::Paid->value => 'success',
                        DonationStatus::Failed->value => 'danger',
                        DonationStatus::Refunded->value => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => DonationStatus::tryFrom($state)?->getLabel() ?? $state),

                TextColumn::make('is_anonymous')
                    ->label(__('profile_donations.table.anonymous'))
                    ->formatStateUsing(fn (bool $state) => $state
                        ? __('profile_donations.anonymous.yes')
                        : __('profile_donations.anonymous.no'))
                    ->icon(fn (bool $state) => $state ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->iconPosition('before')
                    ->color(fn (bool $state) => $state ? 'gray' : 'success')
                    ->toggleable(),

                TextColumn::make('paid_at')
                    ->label(__('profile_donations.table.paid_at'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('profile_donations.table.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('profile_donations.table.status'))
                    ->options(DonationStatus::getOptions()),
            ]);
    }
}

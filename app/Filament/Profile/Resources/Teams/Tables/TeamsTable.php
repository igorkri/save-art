<?php

namespace App\Filament\Profile\Resources\Teams\Tables;

use App\Filament\Profile\Resources\Teams\TeamResource;
use App\Models\Team;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label(__('profile_teams.table.avatar'))
                    ->disk('public')
                    ->circular()
                    ->size(48),

                TextColumn::make('name')
                    ->label(__('profile_teams.table.name')),

                TextColumn::make('teamMembers_count')
                    ->label(__('profile_teams.table.members_count'))
                    ->counts('teamMembers')
                    ->numeric(),

                TextColumn::make('role')
                    ->label(__('profile_teams.table.role'))
                    ->state(fn (Team $record): string => $record->isOwnedBy(auth()->user())
                        ? __('profile_teams.roles.owner')
                        : __('profile_teams.roles.member'))
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Team $record): bool => TeamResource::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (Team $record): bool => TeamResource::canDelete($record)),
                Action::make('leave')
                    ->label(__('profile_teams.actions.leave'))
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Team $record): bool => ! $record->isOwnedBy(auth()->user()))
                    ->action(function (Team $record): void {
                        $record->teamMembers()->where('user_id', auth()->id())->delete();

                        Notification::make()
                            ->title(__('profile_teams.notifications.left'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}

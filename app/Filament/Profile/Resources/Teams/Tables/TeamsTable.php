<?php

namespace App\Filament\Profile\Resources\Teams\Tables;

use App\Filament\Profile\Resources\Teams\TeamResource;
use App\Models\Team;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1,
                'md' => 2,
            ])
            ->recordClasses('profile-team-card-record')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('teamMembers.user'))
            ->columns([
                View::make('filament.profile.teams.team-card')
                    ->schema([
                        TextColumn::make('name')
                            ->searchable()
                            ->hidden(),
                        TextColumn::make('description')
                            ->searchable()
                            ->hidden(),
                        TextColumn::make('specialization')
                            ->searchable()
                            ->hidden(),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn (Team $record): bool => TeamResource::canEdit($record)),
                    DeleteAction::make()
                        ->visible(fn (Team $record): bool => TeamResource::canDelete($record)),
                    Action::make('leave')
                        ->label(__('profile_teams.actions.leave'))
                        ->icon('heroicon-o-arrow-right-start-on-rectangle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Team $record): bool => self::canLeave($record))
                        ->action(function (Team $record): void {
                            $membership = $record->teamMembers()
                                ->where('user_id', auth()->id())
                                ->where('role', 'member')
                                ->first();

                            if (! $membership) {
                                return;
                            }

                            $membership->delete();

                            Notification::make()
                                ->title(__('profile_teams.notifications.left'))
                                ->success()
                                ->send();
                        }),
                ])
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->color('primary')
                    ->tooltip(__('filament-actions::group.trigger.label')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function canLeave(Team $team): bool
    {
        return $team->teamMembers()
            ->where('user_id', auth()->id())
            ->where('role', 'member')
            ->exists();
    }
}

<?php

namespace App\Filament\Profile\Widgets;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ProfileRecentProjectsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getTableHeading(): string
    {
        return __('profile_dashboard.recent_projects.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Project::query()
                ->where('user_id', auth()->id())
                ->latest('updated_at')
                ->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label(__('profile_projects.table.title'))
                    ->searchable(false)
                    ->limit(30),
                TextColumn::make('status')
                    ->label(__('profile_projects.table.status'))
                    ->badge()
                    ->formatStateUsing(fn (ProjectStatus $state): string => $state->getLabel())
                    ->color(fn (ProjectStatus $state): string => $state->getColor()),
                TextColumn::make('updated_at')
                    ->label(__('profile_projects.table.created_at'))
                    ->since()
                    ->sortable(false),
            ])
            ->recordUrl(null)
            ->emptyStateHeading(__('profile_dashboard.recent_projects.empty'));
    }
}

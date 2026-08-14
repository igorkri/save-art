<?php

namespace App\Filament\Profile\Resources\Donations;

use App\Filament\Profile\Resources\Donations\Pages\ListDonations;
use App\Filament\Profile\Resources\Donations\Tables\DonationsTable;
use App\Models\Donation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('profile_panel.nav_groups.projects');
    }

    public static function getModelLabel(): string
    {
        return __('profile_donations.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('profile_donations.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('profile_donations.model.plural');
    }

    public static function table(Table $table): Table
    {
        return DonationsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDonations::route('/'),
        ];
    }

    /**
     * Донати, де користувач — донатер, або донати, зроблені на його проєкти.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query): void {
                $query->where('user_id', auth()->id())
                    ->orWhereHas('project', fn (Builder $projectQuery) => $projectQuery->where('user_id', auth()->id()));
            });
    }
}

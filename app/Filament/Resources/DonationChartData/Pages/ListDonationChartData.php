<?php

namespace App\Filament\Resources\DonationChartData\Pages;

use App\Filament\Resources\DonationChartData\DonationChartDataResource;
use App\Filament\Widgets\DonationChartWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDonationChartData extends ListRecords
{
    protected static string $resource = DonationChartDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DonationChartWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}

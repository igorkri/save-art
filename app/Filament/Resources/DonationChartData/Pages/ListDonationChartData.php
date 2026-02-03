<?php

namespace App\Filament\Resources\DonationChartData\Pages;

use App\Filament\Resources\DonationChartData\DonationChartDataResource;
use App\Filament\Widgets\DonationChartWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDonationChartData extends ListRecords
{
    protected static string $resource = DonationChartDataResource::class;

    //title сторінки списку
    protected static ?string $title = 'Дані для графіків донатів';

    // breadcrumb
    public function getBreadcrumb(): ?string
    {
        return 'Перелік';
    }

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

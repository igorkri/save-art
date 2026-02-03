<?php

namespace App\Filament\Resources\DonationChartData\Widgets;

use App\Models\DonationChartData;
use Filament\Widgets\ChartWidget;

class DonationChartWidget extends ChartWidget
{
    protected ?string $heading = 'Статистика донатів';

    protected static ?int $sort = 1;

    public ?string $filter = 'day';

    protected function getData(): array
    {
        $chartData = DonationChartData::where('period_type', $this->filter)->first();
        if (! $chartData) {
            return [
                'datasets' => [
                    [
                        'label' => 'Донати',
                        'data' => [],
                    ],
                ],
                'labels' => [],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Донати (₴)',
                    'data' => $chartData->values ?? [],
                    'fill' => 'origin',
                ],
            ],
            'labels' => $chartData->labels ?? [],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => 'День (24 години)',
            'week' => 'Тиждень (7 днів)',
            'month' => 'Місяць (31 день)',
            'year' => 'Рік (12 місяців)',
            'all' => 'Весь час',
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\DonationChartData;
use Filament\Widgets\ChartWidget;

class DonationChartWidget extends ChartWidget
{
    protected ?string $heading = 'Динаміка донатів';

    protected ?string $maxHeight = '350px';

    protected static ?int $sort = 2;

    public ?string $filter = 'week';

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        $chartData = $this->getChartData();

        if (! $chartData) {
            return 'Немає даних за обраний період';
        }

        $total = number_format($chartData->total ?? 0, 0, ',', ' ');
        $count = count(array_filter($chartData->values ?? [], fn ($v) => $v > 0));
        $periodLabel = $this->getFilters()[$this->filter] ?? $this->filter;

        return "Загалом: {$total} ₴ • {$count} активних точок • Період: {$periodLabel}";
    }

    protected function getChartData(): ?DonationChartData
    {
        return DonationChartData::where('period_type', $this->filter)->first();
    }

    protected function getData(): array
    {
        $chartData = $this->getChartData();

        $values = $chartData?->values ?? [];
        $labels = $chartData?->labels ?? [];

        if (empty($values)) {
            return [
                'datasets' => [
                    [
                        'label' => 'Сума донатів (₴)',
                        'data' => [],
                        'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                        'borderColor' => 'rgb(16, 185, 129)',
                        'fill' => 'origin',
                        'tension' => 0.3,
                        'pointRadius' => 0,
                    ],
                ],
                'labels' => [],
            ];
        }

        // Calculate average for the trend line
        $avg = count($values) > 0 ? array_sum($values) / count($values) : 0;
        $avgLine = array_fill(0, count($values), round($avg, 2));

        return [
            'datasets' => [
                [
                    'label' => 'Сума донатів (₴)',
                    'data' => $values,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'fill' => 'origin',
                    'tension' => 0.3,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 7,
                    'pointBackgroundColor' => 'rgb(16, 185, 129)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointHoverBackgroundColor' => '#fff',
                    'pointHoverBorderColor' => 'rgb(16, 185, 129)',
                    'pointHoverBorderWidth' => 3,
                    'order' => 1,
                ],
                [
                    'label' => 'Середнє значення',
                    'data' => $avgLine,
                    'borderColor' => 'rgba(234, 88, 12, 0.7)',
                    'borderDash' => [5, 5],
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 0,
                    'order' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Сьогодні',
            'week' => 'Тиждень',
            'month' => 'Місяць',
            'year' => 'Рік',
            'all' => 'Весь час',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'aspectRatio' => 4,
            'responsive' => true,
            'animation' => [
                'duration' => 750,
                'easing' => 'easeInOutQuart',
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'align' => 'end',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 20,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(17, 24, 39, 0.95)',
                    'titleColor' => '#fff',
                    'titleFont' => [
                        'size' => 14,
                        'weight' => 'bold',
                    ],
                    'bodyColor' => '#e5e7eb',
                    'bodyFont' => [
                        'size' => 13,
                    ],
                    'borderColor' => 'rgba(16, 185, 129, 0.6)',
                    'borderWidth' => 2,
                    'cornerRadius' => 10,
                    'padding' => 14,
                    'displayColors' => true,
                    'boxWidth' => 12,
                    'boxHeight' => 12,
                    'boxPadding' => 6,
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'maxRotation' => 0,
                        'autoSkipPadding' => 20,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
                'y' => [
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.15)',
                        'drawBorder' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Сума (₴)',
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                        'padding' => [
                            'bottom' => 10,
                        ],
                    ],
                    'ticks' => [
                        'padding' => 10,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
            'elements' => [
                'line' => [
                    'borderWidth' => 3,
                ],
            ],
        ];
    }
}

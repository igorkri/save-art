<?php

namespace App\Filament\Widgets;

use App\Models\Donation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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

        $total = number_format($chartData['total'], 0, ',', ' ');
        $count = count(array_filter($chartData['values'], fn ($v) => $v > 0));
        $periodLabel = $this->getFilters()[$this->filter] ?? $this->filter;

        return "Загалом: {$total} ₴ • {$count} активних точок • Період: {$periodLabel}";
    }

    /**
     * Дані рахуються напряму з donations (без кешу/крону)
     *
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    protected function getChartData(): array
    {
        return match ($this->filter) {
            'day' => $this->getDataByHours(),
            'week' => $this->getDataByDays(7),
            'month' => $this->getDataByCalendarMonth(),
            'year' => $this->getDataByMonths(12),
            'all' => $this->getDataAllTime(),
            default => $this->getDataByCalendarMonth(),
        };
    }

    /**
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getDataByHours(): array
    {
        $today = Carbon::today();
        $labels = [];
        $values = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $labels[] = sprintf('%02d:00', $hour);

            $values[] = (float) Donation::where('status', 'paid')
                ->whereDate('paid_at', $today)
                ->whereRaw('HOUR(paid_at) = ?', [$hour])
                ->sum('amount');
        }

        return ['total' => array_sum($values), 'labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getDataByDays(int $days): array
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        return $this->getDataForDateRange($startDate, $endDate, 'j');
    }

    /**
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getDataByCalendarMonth(): array
    {
        return $this->getDataForDateRange(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'd');
    }

    /**
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getDataForDateRange(Carbon $startDate, Carbon $endDate, string $labelFormat): array
    {
        $period = CarbonPeriod::create($startDate, $endDate);

        $donations = Donation::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $values = [];

        foreach ($period as $date) {
            $labels[] = $date->format($labelFormat);
            $values[] = (float) ($donations[$date->format('Y-m-d')] ?? 0);
        }

        return ['total' => array_sum($values), 'labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getDataByMonths(int $months): array
    {
        $donations = Donation::where('status', 'paid')
            ->where('paid_at', '>=', Carbon::now()->subMonths($months)->startOfMonth())
            ->selectRaw('YEAR(paid_at) as year, MONTH(paid_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT))
            ->map(fn ($item) => (float) $item->total)
            ->toArray();

        $labels = [];
        $values = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->translatedFormat('M');
            $values[] = $donations[$date->format('Y-m')] ?? 0;
        }

        return ['total' => array_sum($values), 'labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getDataAllTime(): array
    {
        $donations = Donation::where('status', 'paid')
            ->selectRaw('YEAR(paid_at) as year, SUM(amount) as total')
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('total', 'year')
            ->toArray();

        return [
            'total' => array_sum($donations),
            'labels' => array_map('strval', array_keys($donations)),
            'values' => array_map('floatval', array_values($donations)),
        ];
    }

    protected function getData(): array
    {
        $chartData = $this->getChartData();

        $values = $chartData['values'];
        $labels = $chartData['labels'];

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

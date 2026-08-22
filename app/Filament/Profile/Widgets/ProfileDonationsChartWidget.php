<?php

namespace App\Filament\Profile\Widgets;

use App\Models\Donation;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class ProfileDonationsChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('profile_dashboard.donations_chart.heading');
    }

    protected function getData(): array
    {
        $months = 6;
        $userId = auth()->id();

        $donations = Donation::query()
            ->where('status', 'paid')
            ->whereHas('project', fn (Builder $query) => $query->where('user_id', $userId))
            ->where('paid_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->selectRaw('YEAR(paid_at) as year, MONTH(paid_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.str_pad((string) $item->month, 2, '0', STR_PAD_LEFT))
            ->map(fn ($item) => (float) $item->total);

        $labels = [];
        $values = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->translatedFormat('M');
            $values[] = (float) ($donations[$date->format('Y-m')] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('profile_dashboard.donations_chart.dataset_label'),
                    'data' => $values,
                    'backgroundColor' => 'rgba(254, 204, 57, 0.15)',
                    'borderColor' => 'rgb(249, 186, 1)',
                    'fill' => 'origin',
                    'tension' => 0.3,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => 'rgb(249, 186, 1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

<?php

namespace App\Filament\Profile\Widgets;

use App\Models\Donation;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProfileDonationsChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    public function getHeading(): string
    {
        return __('profile_dashboard.donations_chart.heading');
    }

    protected function getData(): array
    {
        $months = 6;
        $userId = auth()->id();

        $madeDonations = $this->monthlyTotals(
            Donation::query()->where('user_id', $userId),
            $months,
        );

        $receivedDonations = $this->monthlyTotals(
            Donation::query()
                ->whereHas('project', fn (Builder $query) => $query->where('user_id', $userId)),
            $months,
        );

        $labels = [];
        $madeValues = [];
        $receivedValues = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->translatedFormat('M');
            $madeValues[] = (float) ($madeDonations[$date->format('Y-m')] ?? 0);
            $receivedValues[] = (float) ($receivedDonations[$date->format('Y-m')] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('profile_dashboard.donations_chart.dataset_made'),
                    'data' => $madeValues,
                    'backgroundColor' => 'rgba(247, 244, 236, 0.08)',
                    'borderColor' => 'rgb(199, 199, 199)',
                    'fill' => false,
                    'tension' => 0.3,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => 'rgb(199, 199, 199)',
                ],
                [
                    'label' => __('profile_dashboard.donations_chart.dataset_received'),
                    'data' => $receivedValues,
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

    /**
     * @return Collection<string, float>
     */
    private function monthlyTotals(Builder $query, int $months): Collection
    {
        $query
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths($months - 1)->startOfMonth());

        $monthExpression = $query->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', paid_at)"
            : "DATE_FORMAT(paid_at, '%Y-%m')";

        return $query
            ->selectRaw("{$monthExpression} as month, SUM(amount) as total")
            ->groupByRaw($monthExpression)
            ->pluck('total', 'month')
            ->map(fn ($total): float => (float) $total);
    }

    protected function getType(): string
    {
        return 'line';
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Donation;
use App\Models\DonationChartData;
use App\Models\HomePage;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

class CollectDonationChartData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donations:collect-chart-data
                            {--period= : Конкретний період (day, week, month, year, all). Якщо не вказано - всі періоди}
                            {--force : Примусово оновити навіть якщо is_manual=true}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Збирає дані для графіка донатів по всіх періодах';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Збір даних графіка донатів...');

        // Перевірка чи дозволено автозбір
        $homePage = HomePage::getActive();
        if ($homePage && ! $homePage->chart_auto_collect && ! $this->option('force')) {
            $this->warn('⚠️  Автозбір вимкнено в налаштуваннях HomePage. Використайте --force для примусового оновлення.');

            return self::SUCCESS;
        }

        $specificPeriod = $this->option('period');
        $force = $this->option('force');

        $periods = $specificPeriod
            ? [$specificPeriod]
            : DonationChartData::getPeriodTypes();

        $bar = $this->output->createProgressBar(count($periods));
        $bar->start();

        foreach ($periods as $periodType) {
            $this->collectPeriod($periodType, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Дані графіка успішно оновлено!');

        return self::SUCCESS;
    }

    /**
     * Збір даних для конкретного періоду
     */
    private function collectPeriod(string $periodType, bool $force): void
    {
        // Перевірка чи не введено вручну
        $existing = DonationChartData::getByPeriod($periodType);
        if ($existing && $existing->is_manual && ! $force) {
            $this->line(" Пропускаю {$periodType} (введено вручну)");

            return;
        }

        $data = match ($periodType) {
            DonationChartData::PERIOD_DAY => $this->getChartDataByHours(),
            DonationChartData::PERIOD_WEEK => $this->getChartDataByDays(7),
            DonationChartData::PERIOD_MONTH => $this->getChartDataByDays(31),
            DonationChartData::PERIOD_YEAR => $this->getChartDataByMonths(12),
            DonationChartData::PERIOD_ALL => $this->getChartDataAllTime(),
            default => ['total' => 0, 'labels' => [], 'values' => []],
        };

        DonationChartData::upsertPeriod($periodType, $data, false);
    }

    /**
     * Дані графіка по годинах (за сьогодні)
     *
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getChartDataByHours(): array
    {
        $today = Carbon::today();
        $labels = [];
        $values = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $labels[] = sprintf('%02d:00', $hour);

            $amount = Donation::where('status', 'completed')
                ->whereDate('paid_at', $today)
                ->whereRaw('HOUR(paid_at) = ?', [$hour])
                ->sum('amount');

            $values[] = (float) $amount;
        }

        return [
            'total' => array_sum($values),
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Дані графіка по днях
     *
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getChartDataByDays(int $days): array
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $period = CarbonPeriod::create($startDate, $endDate);

        $labels = [];
        $values = [];

        $donations = Donation::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        foreach ($period as $date) {
            $dateKey = $date->format('Y-m-d');
            $labels[] = $date->format('j');
            $values[] = (float) ($donations[$dateKey] ?? 0);
        }

        return [
            'total' => array_sum($values),
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Дані графіка по місяцях
     *
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getChartDataByMonths(int $months): array
    {
        $labels = [];
        $values = [];

        $donations = Donation::where('status', 'completed')
            ->where('paid_at', '>=', Carbon::now()->subMonths($months)->startOfMonth())
            ->selectRaw('YEAR(paid_at) as year, MONTH(paid_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT))
            ->map(fn ($item) => (float) $item->total)
            ->toArray();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $labels[] = $date->translatedFormat('M');
            $values[] = $donations[$key] ?? 0;
        }

        return [
            'total' => array_sum($values),
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Дані графіка за весь час (по роках)
     *
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getChartDataAllTime(): array
    {
        $donations = Donation::where('status', 'completed')
            ->selectRaw('YEAR(paid_at) as year, SUM(amount) as total')
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('total', 'year')
            ->toArray();

        $labels = array_map('strval', array_keys($donations));
        $values = array_map('floatval', array_values($donations));

        return [
            'total' => array_sum($values),
            'labels' => $labels,
            'values' => $values,
        ];
    }
}

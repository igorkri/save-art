<?php

namespace Database\Seeders;

use App\Models\DonationChartData;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DonationChartDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // День (24 години)
        $this->seedDayData();

        // Тиждень (7 днів)
        $this->seedWeekData();

        // Місяць (31 день)
        $this->seedMonthData();

        // Рік (12 місяців)
        $this->seedYearData();

        // Весь час
        $this->seedAllTimeData();
    }

    /**
     * Дані за день (24 години)
     */
    private function seedDayData(): void
    {
        $labels = [];
        $values = [];
        $total = 0;

        for ($i = 0; $i < 24; $i++) {
            $hour = Carbon::now()->subHours(23 - $i);
            $labels[] = $hour->format('H:00');
            $value = fake()->randomFloat(2, 0, 5000);
            $values[] = $value;
            $total += $value;
        }

        DonationChartData::upsertPeriod(DonationChartData::PERIOD_DAY, [
            'total' => $total,
            'labels' => $labels,
            'values' => $values,
        ], true);
    }

    /**
     * Дані за тиждень (7 днів)
     */
    private function seedWeekData(): void
    {
        $labels = [];
        $values = [];
        $total = 0;

        $dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Нд'];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $dayNames[$date->dayOfWeekIso - 1].' '.$date->format('d.m');
            $value = fake()->randomFloat(2, 1000, 50000);
            $values[] = $value;
            $total += $value;
        }

        DonationChartData::upsertPeriod(DonationChartData::PERIOD_WEEK, [
            'total' => $total,
            'labels' => $labels,
            'values' => $values,
        ], true);
    }

    /**
     * Дані за місяць (31 день)
     */
    private function seedMonthData(): void
    {
        $labels = [];
        $values = [];
        $total = 0;

        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d.m');
            $value = fake()->randomFloat(2, 500, 30000);
            $values[] = $value;
            $total += $value;
        }

        DonationChartData::upsertPeriod(DonationChartData::PERIOD_MONTH, [
            'total' => $total,
            'labels' => $labels,
            'values' => $values,
        ], true);
    }

    /**
     * Дані за рік (12 місяців)
     */
    private function seedYearData(): void
    {
        $labels = [];
        $values = [];
        $total = 0;

        $monthNames = [
            'Січ', 'Лют', 'Бер', 'Кві', 'Тра', 'Чер',
            'Лип', 'Сер', 'Вер', 'Жов', 'Лис', 'Гру',
        ];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $monthNames[$date->month - 1].' '.$date->format('Y');
            $value = fake()->randomFloat(2, 50000, 500000);
            $values[] = $value;
            $total += $value;
        }

        DonationChartData::upsertPeriod(DonationChartData::PERIOD_YEAR, [
            'total' => $total,
            'labels' => $labels,
            'values' => $values,
        ], true);
    }

    /**
     * Дані за весь час
     */
    private function seedAllTimeData(): void
    {
        $labels = [];
        $values = [];
        $total = 0;

        // Генеруємо дані за останні 5 років по кварталах
        for ($year = Carbon::now()->year - 4; $year <= Carbon::now()->year; $year++) {
            $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];

            foreach ($quarters as $q => $quarter) {
                // Пропускаємо майбутні квартали
                if ($year === Carbon::now()->year) {
                    $currentQuarter = ceil(Carbon::now()->month / 3);
                    if ($q + 1 > $currentQuarter) {
                        continue;
                    }
                }

                $labels[] = $quarter.' '.$year;
                $value = fake()->randomFloat(2, 100000, 1500000);
                $values[] = $value;
                $total += $value;
            }
        }

        DonationChartData::upsertPeriod(DonationChartData::PERIOD_ALL, [
            'total' => $total,
            'labels' => $labels,
            'values' => $values,
        ], true);
    }
}

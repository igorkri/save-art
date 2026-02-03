<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationChartData extends Model
{
    protected $table = 'donation_chart_data';

    protected $fillable = [
        'period_type',
        'total',
        'labels',
        'values',
        'data_collected_at',
        'is_manual',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'values' => 'array',
            'total' => 'decimal:2',
            'data_collected_at' => 'datetime',
            'is_manual' => 'boolean',
        ];
    }

    /**
     * Типи періодів
     */
    public const PERIOD_DAY = 'day';

    public const PERIOD_WEEK = 'week';

    public const PERIOD_MONTH = 'month';

    public const PERIOD_YEAR = 'year';

    public const PERIOD_ALL = 'all';

    /**
     * Отримати всі доступні типи періодів
     *
     * @return array<string>
     */
    public static function getPeriodTypes(): array
    {
        return [
            self::PERIOD_DAY,
            self::PERIOD_WEEK,
            self::PERIOD_MONTH,
            self::PERIOD_YEAR,
            self::PERIOD_ALL,
        ];
    }

    /**
     * Отримати дані для конкретного періоду
     */
    public static function getByPeriod(string $periodType): ?self
    {
        return static::where('period_type', $periodType)->first();
    }

    /**
     * Отримати дані для API
     *
     * @return array{period: string, total: float, labels: array, values: array, updated_at: string|null}
     */
    public function toApiArray(): array
    {
        return [
            'period' => $this->period_type,
            'total' => (float) $this->total,
            'labels' => $this->labels ?? [],
            'values' => $this->values ?? [],
            'updated_at' => $this->data_collected_at?->toIso8601String(),
        ];
    }

    /**
     * Оновити або створити дані для періоду
     *
     * @param  array{total: float, labels: array, values: array}  $data
     */
    public static function upsertPeriod(string $periodType, array $data, bool $isManual = false): self
    {
        return static::updateOrCreate(
            ['period_type' => $periodType],
            [
                'total' => $data['total'] ?? 0,
                'labels' => $data['labels'] ?? [],
                'values' => $data['values'] ?? [],
                'data_collected_at' => now(),
                'is_manual' => $isManual,
            ]
        );
    }
}

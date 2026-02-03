<?php

namespace App\Filament\Resources\DonationChartData\Pages;

use App\Filament\Resources\DonationChartData\DonationChartDataResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDonationChartData extends CreateRecord
{
    protected static string $resource = DonationChartDataResource::class;

    //title сторінки створення
    protected static ?string $title = 'Створити дані для графіків донатів';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Обробка chart_data з Repeater
        if (! empty($data['chart_data']) && is_array($data['chart_data'])) {
            $data['labels'] = array_column($data['chart_data'], 'label');
            $data['values'] = array_map(fn ($v) => (float) $v, array_column($data['chart_data'], 'value'));

            // Розраховуємо total
            $data['total'] = array_sum($data['values']);
        } else {
            $data['labels'] = [];
            $data['values'] = [];
            $data['total'] = 0;
        }
        unset($data['chart_data']);

        // Встановлюємо час оновлення та is_manual за замовчуванням
        $data['data_collected_at'] = now();
        $data['is_manual'] = $data['is_manual'] ?? true;

        return $data;
    }
}

<?php

namespace App\Filament\Resources\DonationChartData\Pages;

use App\Filament\Resources\DonationChartData\DonationChartDataResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDonationChartData extends EditRecord
{
    protected static string $resource = DonationChartDataResource::class;

    //title сторінки редагування
    protected static ?string $title = 'Редагувати дані для графіків донатів';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Обробка chart_data з Repeater
        if (! empty($data['chart_data']) && is_array($data['chart_data'])) {
            $data['labels'] = array_column($data['chart_data'], 'label');
            $data['values'] = array_map(fn ($v) => (float) $v, array_column($data['chart_data'], 'value'));

            // Розраховуємо total
            $data['total'] = array_sum($data['values']);
        }
        unset($data['chart_data']);

        // Встановлюємо час оновлення
        $data['data_collected_at'] = now();

        return $data;
    }
}

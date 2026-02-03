<?php

namespace App\Filament\Resources\DonationChartData\Schemas;

use App\Models\DonationChartData;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DonationChartDataForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основні дані')
                    ->columns(2)
                    ->schema([
                        Select::make('period_type')
                            ->label('Тип періоду')
                            ->options([
                                DonationChartData::PERIOD_DAY => 'День (по годинах)',
                                DonationChartData::PERIOD_WEEK => 'Тиждень (7 днів)',
                                DonationChartData::PERIOD_MONTH => 'Місяць (31 день)',
                                DonationChartData::PERIOD_YEAR => 'Рік (12 місяців)',
                                DonationChartData::PERIOD_ALL => 'Весь час (по роках)',
                            ])
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($record) => $record !== null)
                            ->live(),

                        TextInput::make('total')
                            ->label('Загальна сума')
                            ->numeric()
                            ->prefix('₴')
                            ->required()
                            ->readOnly()
                            ->helperText('Розраховується автоматично з суми всіх точок'),

                        Toggle::make('is_manual')
                            ->label('Введено вручну')
                            ->helperText('Якщо увімкнено, крон не перезапише ці дані')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Дані графіка')
                    ->description('Додавайте точки графіка: мітка (label) та значення (value)')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('chart_data')
                            ->label('Точки графіка')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Мітка (Label)')
                                    ->placeholder('Приклад: 00:00 або Січень')
                                    ->required()
                                    ->maxLength(50)
                                    ->live(onBlur: true),

                                TextInput::make('value')
                                    ->label('Значення (Value)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->suffix('₴')
                                    ->live(onBlur: true),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Додати точку')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => isset($state['label'], $state['value'])
                                    ? "{$state['label']}: {$state['value']} ₴"
                                    : null
                            )
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && is_array($record->labels) && is_array($record->values)) {
                                    $chartData = [];
                                    $count = min(count($record->labels), count($record->values));
                                    for ($i = 0; $i < $count; $i++) {
                                        $chartData[] = [
                                            'label' => $record->labels[$i],
                                            'value' => $record->values[$i],
                                        ];
                                    }
                                    $component->state($chartData);
                                }
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Автоматично рахуємо загальну суму
                                $total = 0;
                                if (is_array($state)) {
                                    foreach ($state as $item) {
                                        if (isset($item['value']) && is_numeric($item['value'])) {
                                            $total += (float) $item['value'];
                                        }
                                    }
                                }
                                $set('total', $total);
                            })
                            ->columnSpanFull(),

                        Placeholder::make('data_collected_at_display')
                            ->label('Останнє оновлення')
                            ->content(fn ($record) => $record?->data_collected_at?->format('d.m.Y H:i:s') ?? 'Ніколи'),
                    ]),
            ]);
    }
}

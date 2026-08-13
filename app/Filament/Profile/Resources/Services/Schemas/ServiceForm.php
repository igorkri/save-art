<?php

namespace App\Filament\Profile\Resources\Services\Schemas;

use App\Enums\Currency;
use App\Models\ArtCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна інформація')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Назва послуги')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('description')
                            ->label('Опис')
                            ->columnSpanFull(),
                        TextInput::make('location')
                            ->label('Локація')
                            ->columnSpanFull(),

                        Select::make('art_category_id')
                            ->label('Галузь мистецтва')
                            ->options(function () {
                                $options = [];
                                foreach (ArtCategory::with('children')->whereNull('parent_id')->orderBy('sort_order')->get() as $root) {
                                    $options[$root->getLabel('uk')] = [
                                        (string) $root->id => $root->getLabel('uk'),
                                    ];
                                    foreach ($root->children as $child) {
                                        $options[$root->getLabel('uk')][(string) $child->id] = '  '.$child->getLabel('uk');
                                    }
                                }

                                return $options;
                            })
                            ->searchable(),

                        FileUpload::make('image')
                            ->label('Зображення')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('services')
                            ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file)),
                    ]),

                Section::make('Ціна')
                    ->columns(3)
                    ->schema([
                        Select::make('currency')
                            ->label('Валюта')
                            ->options([
                                Currency::UAH->value => '₴ UAH',
                                Currency::USD->value => '$ USD',
                                Currency::EUR->value => '€ EUR',
                            ])
                            ->default(Currency::UAH->value)
                            ->required(),

                        TextInput::make('price')
                            ->label('Ціна')
                            ->numeric(),

                        Toggle::make('price_from')
                            ->label('«Від» (орієнтовна ціна)'),
                    ]),
            ]);
    }
}

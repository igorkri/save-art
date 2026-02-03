<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Налаштування')
                    ->schema([
                        Select::make('faq_category_id')
                            ->label('Категорія')
                            ->relationship('category')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name['uk'] ?? $record->name['en'] ?? 'Без назви')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('order')
                            ->label('Порядок сортування')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('Активне')
                            ->default(true),
                    ]),

                Section::make('Контент')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('question')
                                ->label('Питання')
                                ->required()
                                ->maxLength(500),
                            Textarea::make('answer')
                                ->label('Відповідь')
                                ->required(),
                        ])->columnSpanFull(),
                    ]),
            ]);
    }
}

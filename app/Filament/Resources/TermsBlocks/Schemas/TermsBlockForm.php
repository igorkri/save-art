<?php

namespace App\Filament\Resources\TermsBlocks\Schemas;

use App\Models\TermsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class TermsBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Налаштування')
                    ->schema([
                        Select::make('terms_section_id')
                            ->label('Розділ')
                            ->relationship('section')
                            ->getOptionLabelFromRecordUsing(fn (TermsSection $record) => $record->heading['uk'] ?? $record->heading['en'] ?? "Розділ #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('order')
                            ->label('Порядок сортування')
                            ->numeric()
                            ->default(0),
                    ]),

                Section::make('Контент')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('heading')
                                ->label('Заголовок блоку')
                                ->maxLength(500),
                            Textarea::make('paragraphs')
                                ->label('Абзаци (кожен абзац з нового рядка)')
                                ->rows(6),
                        ])->columnSpanFull(),
                    ]),

                Section::make('Список (необов’язково)')
                    ->schema([
                        Select::make('list_type')
                            ->label('Тип списку')
                            ->options([
                                'ordered' => 'Нумерований',
                                'unordered' => 'Маркований',
                            ])
                            ->native(false),

                        LanguageTabs::make([
                            Textarea::make('list_items')
                                ->label('Пункти списку (кожен пункт з нового рядка)')
                                ->rows(6),
                        ])->columnSpanFull(),
                    ]),
            ]);
    }
}

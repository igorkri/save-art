<?php

namespace App\Filament\Resources\News\Schemas;

use App\Enums\NewsCategory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Налаштування')
                    ->schema([
                        Select::make('category')
                            ->label('Категорія')
                            ->options(NewsCategory::getOptions())
                            ->required()
                            ->default(NewsCategory::News->value),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Використовується в URL. Генерується автоматично з назви, якщо залишити порожнім.'),

                        DateTimePicker::make('published_at')
                            ->label('Дата публікації')
                            ->seconds(false)
                            ->default(now()),

                        Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Контент')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('title')
                                ->label('Заголовок')
                                ->required()
                                ->maxLength(255),
                        ])->columnSpanFull(),

                        FileUpload::make('main_image')
                            ->label('Головне зображення')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('news/main')
                            ->columnSpanFull(),
                    ]),

                Section::make('Текстові блоки')
                    ->schema([
                        Repeater::make('text_blocks')
                            ->label('')
                            ->schema([
                                LanguageTabs::make([
                                    Textarea::make('paragraphs')
                                        ->label('Абзаци (кожен абзац з нового рядка)')
                                        ->rows(5)
                                        ->dehydrateStateUsing(fn (?string $state): array => array_values(array_filter(
                                            array_map('trim', explode("\n", (string) $state)),
                                            fn (string $paragraph): bool => $paragraph !== ''
                                        )))
                                        ->afterStateHydrated(function (TextInput|Textarea $component, mixed $state) {
                                            $component->state(is_array($state) ? implode("\n", $state) : (string) $state);
                                        }),
                                ])->columnSpanFull(),

                                FileUpload::make('image')
                                    ->label('Зображення блоку (опційно)')
                                    ->image()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory('news/blocks')
                                    ->columnSpanFull(),
                            ])
                            ->itemLabel(fn (array $state): ?string => Str::limit((string) ($state['paragraphs']['uk'] ?? $state['paragraphs']['en'] ?? ''), 50) ?: 'Блок')
                            ->collapsible()
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

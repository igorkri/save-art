<?php

namespace App\Filament\Resources\Abouts\Schemas;

use App\Rules\MaxFileSizeRule;
use App\Traits\ConvertsImages;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class AboutForm
{
    use ConvertsImages;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                Section::make('Основна інформація')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('title')
                                ->label('Заголовок')
                                ->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Особливості')
                    ->schema([
                        FileUpload::make('feats.feat_image')
                            ->label('Зображення')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '12:5',
                            ])
                            ->directory('about')
                            ->disk('public')
                            ->rules(['nullable', new MaxFileSizeRule(5 * 1024)]) // 5 МБ
                            ->deleteUploadedFileUsing(function ($file) {
                                Storage::disk('public')->delete($file);
                            })
                            ->saveUploadedFileUsing(function ($file, $state) {
                                if ($file) {
                                    return AboutForm::convertImageToWebp($file, [
                                        'width' => 1440,
                                        'height' => 600,
                                        'quality' => 85,
                                        'method' => 'cover',
                                        'directory' => 'about',
                                        'disk' => 'public',
                                    ]);
                                }

                                return $state;
                            })
                            ->helperText('Максимальний розмір файлу: 5 МБ. Рекомендований формат: 1440х600 пікселів або співвідношення 12:5.'),

                        LanguageTabs::make([
                            Repeater::make('feats')
                                ->label('Список особливостей')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Назва')
                                        ->required(),
                                    TextInput::make('title')
                                        ->label('Заголовок')
                                        ->required(),
                                    Textarea::make('description')
                                        ->label('Опис')
                                        ->required(),
                                ])
                                ->columns(1)
                                ->minItems(1)
                                ->maxItems(10)
                                ->defaultItems(1)
                                ->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Опис')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('description.icon')
                                    ->label('Іконка')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '1:1',
                                    ])
                                    ->disk('public')
                                    ->rules(['nullable', new MaxFileSizeRule(2 * 1024)]) // 2 МБ
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    })
                                    ->saveUploadedFileUsing(function ($file, $state) {
                                        if ($file) {
                                            return AboutForm::convertImageToWebp($file, [
                                                'width' => 100,
                                                'height' => 100,
                                                'quality' => 85,
                                                'method' => 'cover',
                                                'directory' => 'about',
                                                'disk' => 'public',
                                            ]);
                                        }

                                        return $state;
                                    })->columns(1),

                                FileUpload::make('description.image')
                                    ->label('Зображення в описі (bg)')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                    ])
                                    ->disk('public')
                                    ->rules(['nullable', new MaxFileSizeRule(5 * 1024)]) // 5 МБ
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    })
                                    ->saveUploadedFileUsing(function ($file, $state) {
                                        if ($file) {
                                            return AboutForm::convertImageToWebp($file, [
                                                'width' => 1600,
                                                'height' => 900,
                                                'quality' => 85,
                                                'method' => 'fit',
                                                'directory' => 'about',
                                                'disk' => 'public',
                                            ]);
                                        }

                                        return $state;
                                    })
                                    ->helperText('Максимальний розмір файлу: 5 МБ. Рекомендований формат: 16:9 або розмір 1600x862 пікселів.'),
                            ]),

                        LanguageTabs::make([
                            TextInput::make('description.title_date')
                                ->label('Дата')
                                ->required(),

                            Textarea::make('description.description_date')
                                ->label('Опис дати')
                                ->required(),

                            RichEditor::make('description.text')
                                ->label('Опис')
                                ->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Цілі та завдання')
                    ->schema([
                        FileUpload::make('goals.image')
                            ->label('Зображення (bg)')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                            ])
                            ->disk('public')
                            ->rules(['nullable', new MaxFileSizeRule(5 * 1024)]) // 5 МБ
                            ->deleteUploadedFileUsing(function ($file) {
                                Storage::disk('public')->delete($file);
                            })
                            ->saveUploadedFileUsing(function ($file, $state) {
                                if ($file) {
                                    return AboutForm::convertImageToWebp($file, [
                                        'width' => 1600,
                                        'height' => 900,
                                        'quality' => 85,
                                        'method' => 'fit',
                                        'directory' => 'about',
                                        'disk' => 'public',
                                    ]);
                                }

                                return $state;
                            })
                            ->helperText('Максимальний розмір файлу: 5 МБ. Рекомендований формат: 16:9 або розмір 1600x900 пікселів.'),

                        LanguageTabs::make([
                            TextInput::make('goals.title')
                                ->label('Заголовок')
                                ->required(),
                            TextInput::make('goals.task')
                                ->label('Завдання')
                                ->required(),
                            RichEditor::make('goals.description')
                                ->label('Опис')
                                ->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Завдання')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('title')
                                ->label('Заголовок')
                                ->required(),
                            Textarea::make('description')
                                ->label('Опис')
                                ->required(),
                            Repeater::make('tasks')
                                ->label('Список завдань')
                                ->schema([
                                    TextInput::make('task')
                                        ->label('Завдання')
                                        ->required(),
                                ])
                                ->columns(1)
                                ->minItems(1)
                                ->maxItems(20)
                                ->defaultItems(1)
                                ->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Реалізація')
                    ->schema([
                        FileUpload::make('implementation.image')
                            ->label('Зображення (bg)')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '800:507',
                            ])
                            ->disk('public')
                            ->rules(['nullable', new MaxFileSizeRule(5 * 1024)]) // 5 МБ
                            ->deleteUploadedFileUsing(function ($file) {
                                Storage::disk('public')->delete($file);
                            })
                            ->saveUploadedFileUsing(function ($file, $state) {
                                if ($file) {
                                    return AboutForm::convertImageToWebp($file, [
                                        'width' => 1600,
                                        'height' => 1014,
                                        'quality' => 85,
                                        'method' => 'fit',
                                        'directory' => 'about',
                                        'disk' => 'public',
                                    ]);
                                }

                                return $state;
                            })
                            ->helperText('Максимальний розмір файлу: 5 МБ. Рекомендований формат: 800:507 або розмір 1600x1014 пікселів.'),

                        LanguageTabs::make([
                            TextInput::make('implementation.title')
                                ->label('Реалізація')
                                ->required(),
                            Repeater::make('implementation.items')
                                ->label('Пункти реалізації')
                                ->schema([
                                    TextInput::make('item.title')
                                        ->label('Заголовок')
                                        ->required(),
                                    Textarea::make('item.description')
                                        ->label('Опис')
                                        ->required(),
                                ])
                                ->columns(1)
                                ->minItems(1)
                                ->maxItems(20)
                                ->defaultItems(1)
                                ->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Результат')
                    ->schema([
                        FileUpload::make('implementation.image')
                            ->label('Зображення (bg)')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '47:20',
                            ])
                            ->disk('public')
                            ->rules(['nullable', new MaxFileSizeRule(5 * 1024)]) // 5 МБ
                            ->deleteUploadedFileUsing(function ($file) {
                                Storage::disk('public')->delete($file);
                            })
                            ->saveUploadedFileUsing(function ($file, $state) {
                                if ($file) {
                                    return AboutForm::convertImageToWebp($file, [
                                        'width' => 940,
                                        'height' => 400,
                                        'quality' => 85,
                                        'method' => 'fit',
                                        'directory' => 'about',
                                        'disk' => 'public',
                                    ]);
                                }

                                return $state;
                            })
                            ->helperText('Максимальний розмір файлу: 5 МБ. Рекомендований формат: 47:20 або розмір 940х400 пікселів.'),

                        LanguageTabs::make([
                            TextInput::make('results.title')
                                ->label('Заголовок')
                                ->required(),
                            RichEditor::make('results.description')
                                ->label('Опис')
                                ->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('ID Art UA')
                    ->schema([
                        FileUpload::make('id_art.image')
                            ->label('Зображення (bg)')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                            ])
                            ->disk('public')
                            ->rules(['nullable', new MaxFileSizeRule(5 * 1024)]) // 5 МБ
                            ->deleteUploadedFileUsing(function ($file) {
                                Storage::disk('public')->delete($file);
                            })
                            ->saveUploadedFileUsing(function ($file, $state) {
                                if ($file) {
                                    return AboutForm::convertImageToWebp($file, [
                                        'width' => 1600,
                                        'height' => 900,
                                        'quality' => 85,
                                        'method' => 'fit',
                                        'directory' => 'about',
                                        'disk' => 'public',
                                    ]);
                                }

                                return $state;
                            })
                            ->helperText('Максимальний розмір файлу: 5 МБ. Рекомендований формат: 16:9 або розмір 1600x900 пікселів.'),

                        LanguageTabs::make([
                            TextInput::make('id_art.title')
                                ->label('ID мистецтва')
                                ->required(),
                            RichEditor::make('id_art.description')
                                ->label('Опис')
                                ->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Події')
                    ->schema([
                        LanguageTabs::make([
                            TextInput::make('events.title')
                                ->label('Заголовок')
                                ->required(),
                            RichEditor::make('events.h2')
                                ->label('Опис')
                                ->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Проект')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('project.image_bg')
                                    ->label('Зображення (фон)')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '160:790',
                                    ])
                                    ->disk('public')
                                    ->rules(['nullable', new MaxFileSizeRule(5 * 1024)]) // 2 МБ
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    })
                                    ->saveUploadedFileUsing(function ($file, $state) {
                                        if ($file) {
                                            return AboutForm::convertImageToWebp($file, [
                                                'width' => 1600,
                                                'height' => 790,
                                                'quality' => 85,
                                                'method' => 'cover',
                                                'directory' => 'about/project',
                                                'disk' => 'public',
                                            ]);
                                        }

                                        return $state;
                                    })
                                    ->helperText('Максимальний розмір файлу: 5 МБ. Рекомендований формат: 160x790 пікселів або співвідношення 160:790.'),

                                FileUpload::make('project.image')
                                    ->label('Зображення (прямокутне)')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '11:15',
                                    ])
                                    ->disk('public')
                                    ->rules(['nullable', new MaxFileSizeRule(5 * 1024)]) // 5 МБ
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    })
                                    ->saveUploadedFileUsing(function ($file, $state) {
                                        if ($file) {
                                            return AboutForm::convertImageToWebp($file, [
                                                'width' => 440,
                                                'height' => 600,
                                                'quality' => 85,
                                                'method' => 'cover',
                                                'directory' => 'about/project',
                                                'disk' => 'public',
                                            ]);
                                        }

                                        return $state;
                                    })
                                    ->helperText('Максимальний розмір файлу: 5 МБ. Рекомендований формат: 440x600 пікселів або співвідношення 11:15.'),
                            ]),

                        LanguageTabs::make([
                            RichEditor::make('project.description')
                                ->label('Опис')
                                ->required(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('10 художників в 10 національних музеях')
                    ->schema([
                        Toggle::make('is_active_artist')
                            ->label('Активність художників')
                            ->default(true)
                            ->inline(false)
                            ->onIcon('heroicon-s-eye')
                            ->offIcon('heroicon-s-eye-slash')
                            ->onColor('success')
                            ->offColor('danger')
                            ->helperText('Вимкніть, щоб приховати розділ з художниками на фронтенді. Будуть відображатися дані зі сторінки "10 художників в 10 національних музеях"'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}

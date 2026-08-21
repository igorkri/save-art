<?php

namespace App\Filament\Profile\Resources\Catalogs\Schemas;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class CatalogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('profile_catalogs.sections.main'))
                    ->extraAttributes(['class' => 'profile-catalog-form-section'])
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('profile_catalogs.fields.title_uk'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        SelectTree::make('art_category_id')
                            ->label(__('profile_catalogs.fields.art_category'))
                            ->relationship('artCategory', 'name', 'parent_id')
                            ->searchable()
                            ->extraFieldWrapperAttributes(['class' => 'profile-project-art-category-field profile-catalog-art-category-field'])
                            ->columnSpanFull(),

                        DatePicker::make('published_at')
                            ->label(__('profile_catalogs.fields.published_at')),

                        Checkbox::make('is_primary')
                            ->label(__('profile_catalogs.fields.is_primary'))
                            ->extraFieldWrapperAttributes(['class' => 'profile-catalog-primary-toggle']),

                        FileUpload::make('image')
                            ->label(__('profile_catalogs.fields.image'))
                            ->image()
                            ->imageEditor()
                            ->imagePreviewHeight('400')
                            ->panelAspectRatio('1:1')
                            ->imageEditorAspectRatioOptions([null, '4:3'])
                            ->panelLayout('compact')
                            ->disk('public')
                            ->directory('catalogs')
                            ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file))
                            ->extraFieldWrapperAttributes(['class' => 'profile-primary-image-field'])
                            ->required()
                            ->columnSpanFull(),

                        // Значення поля — повний шлях відносно диска ('catalogs/xxxx.pdf'),
                        // як і в image. У БД (ArtCatalog::pdf_file) зберігається лише
                        // basename — конвертація в обидва боки відбувається на сторінках
                        // CreateCatalog/EditCatalog (див. ArtCatalogResource::pdf_url,
                        // що сам додає префікс 'catalogs/').
                        FileUpload::make('pdf_file')
                            ->label(__('profile_catalogs.fields.pdf_file'))
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('public')
                            ->directory('catalogs')
                            ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

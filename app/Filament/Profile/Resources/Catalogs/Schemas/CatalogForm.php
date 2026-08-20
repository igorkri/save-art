<?php

namespace App\Filament\Profile\Resources\Catalogs\Schemas;

use App\Models\ArtCategory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->columns(2)
                    ->schema([
                        TextInput::make('title.uk')
                            ->label(__('profile_catalogs.fields.title_uk'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('title.en')
                            ->label(__('profile_catalogs.fields.title_en'))
                            ->maxLength(255),

                        Select::make('art_category_id')
                            ->label(__('profile_catalogs.fields.art_category'))
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
                            ->searchable()
                            ->columnSpanFull(),

                        DatePicker::make('published_at')
                            ->label(__('profile_catalogs.fields.published_at')),

                        Toggle::make('is_primary')
                            ->label(__('profile_catalogs.fields.is_primary')),

                        FileUpload::make('image')
                            ->label(__('profile_catalogs.fields.image'))
                            ->image()
                            ->imageEditor()
                            ->imagePreviewHeight('400')
                            ->panelAspectRatio('1:1')
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

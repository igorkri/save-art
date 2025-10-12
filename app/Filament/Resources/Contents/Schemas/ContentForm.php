<?php

namespace App\Filament\Resources\Contents\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

class ContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('is_active')->required()->label('Активність')->default(true)->columnSpanFull(),
                TextInput::make('slug')->required()->label('Унікальний ідентифікатор')->unique(ignoreRecord: true)->columnSpanFull(),
                LanguageTabs::make([
                    TextInput::make('page_title')->label('Назва сторінки'),
                    TextInput::make('title')->label('Заголовок'),
                    RichEditor::make('content')->label('Контент')->columnSpanFull(),
                    TextInput::make('meta_title')->label('Мета заголовок'),
                    TextInput::make('meta_description')->label('Мета опис'),
                    TextInput::make('meta_keywords')->label('Мета ключові слова'),
                ])->columnSpanFull(),
            ]);
    }
}

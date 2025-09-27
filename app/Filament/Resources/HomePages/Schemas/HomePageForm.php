<?php

namespace App\Filament\Resources\HomePages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HomePageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('hero_title'),
                TextInput::make('hero_video_poster'),
                TextInput::make('hero_video_url')
                    ->url(),
                TextInput::make('donates_subtitle'),
                TextInput::make('donates_title'),
                TextInput::make('donates_text'),
                TextInput::make('total_collected')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('declared_projects')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('active_projects')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('completed_projects')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('sold_projects')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('partners_title'),
                TextInput::make('ad_first_title'),
                TextInput::make('ad_first_button_text'),
                FileUpload::make('ad_first_image')
                    ->image(),
                TextInput::make('ad_second_title'),
                TextInput::make('ad_second_button_text'),
                FileUpload::make('ad_second_image')
                    ->image(),
                TextInput::make('footer_expert_title'),
                TextInput::make('footer_expert_text'),
                TextInput::make('footer_expert_features'),
                TextInput::make('footer_expert_button_text'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}

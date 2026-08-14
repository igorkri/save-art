<?php

namespace App\Filament\Profile\Resources\UserPhotos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class UserPhotoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image')
                    ->label(__('profile_user_photos.fields.image'))
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('portfolio')
                    ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public')->delete($file))
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}

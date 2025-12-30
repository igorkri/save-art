<?php

namespace App\Filament\Resources\ProfileDocuments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProfileDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('file_path')
                    ->required(),
                TextInput::make('hash')
                    ->required(),
                TextInput::make('signed_file_path'),
                Select::make('sign_status')
                    ->options(['pending' => 'Pending', 'signed' => 'Signed', 'failed' => 'Failed'])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('signed_at'),
                Select::make('service')
                    ->options(['diia' => 'Diia', 'vchasno' => 'Vchasno', 'iit' => 'Iit'])
                    ->required(),
                Textarea::make('signature_base64')
                    ->columnSpanFull(),
            ]);
    }
}

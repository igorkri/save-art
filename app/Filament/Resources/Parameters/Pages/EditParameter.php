<?php

namespace App\Filament\Resources\Parameters\Pages;

use App\Filament\Resources\Parameters\ParameterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditParameter extends EditRecord
{
    protected static string $resource = ParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                $this->getFormContentComponent()->columnSpan(2),
                $this->getRelationManagersContentComponent()->columnSpan(1),
            ]);
    }
}

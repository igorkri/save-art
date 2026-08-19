<?php

namespace App\Filament\Profile\Resources\Catalogs\Pages;

use App\Filament\Profile\Resources\Catalogs\CatalogResource;
use App\Models\ArtCatalog;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCatalog extends EditRecord
{
    protected static string $resource = CatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['pdf_file'])) {
            $data['pdf_file'] = 'catalogs/'.$data['pdf_file'];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['pdf_file'])) {
            $data['pdf_file'] = basename($data['pdf_file']);
        }

        if (! empty($data['is_primary'])) {
            ArtCatalog::query()
                ->where('user_id', auth()->id())
                ->where('id', '!=', $this->record->id)
                ->update(['is_primary' => false]);
        }

        return $data;
    }
}

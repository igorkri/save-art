<?php

namespace App\Filament\Profile\Resources\Catalogs\Pages;

use App\Filament\Profile\Resources\Catalogs\CatalogResource;
use App\Models\ArtCatalog;
use Filament\Resources\Pages\CreateRecord;

class CreateCatalog extends CreateRecord
{
    protected static string $resource = CatalogResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        if (! empty($data['pdf_file'])) {
            $data['pdf_file'] = basename($data['pdf_file']);
        }

        if (! empty($data['is_primary'])) {
            ArtCatalog::query()
                ->where('user_id', auth()->id())
                ->update(['is_primary' => false]);
        }

        return $data;
    }
}

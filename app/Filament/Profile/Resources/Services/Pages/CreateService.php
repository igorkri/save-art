<?php

namespace App\Filament\Profile\Resources\Services\Pages;

use App\Filament\Profile\Resources\Services\ServiceResource;
use App\Models\Service;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['serviceable_type'] = User::class;
        $data['serviceable_id'] = auth()->id();
        $data['slug'] = $this->generateUniqueSlug($data['title']['uk'] ?? $data['title']['en'] ?? Str::random(8));

        return $data;
    }

    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (Service::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$count++;
        }

        return $slug;
    }
}

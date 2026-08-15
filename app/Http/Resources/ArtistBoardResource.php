<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistBoardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'titles' => $this->titles,
            'logo_museums' => $this->formatLogoMuseums(),
            'descriptions' => $this->descriptions,
            'data' => $this->formatArtistsData(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        return $data;
    }

    /**
     * Format logo museums with proper image URLs
     */
    private function formatLogoMuseums(): ?array
    {
        if (! $this->logo_museums) {
            return null;
        }

        return array_map(
            fn ($path) => $path ? asset('storage/'.$path) : null,
            $this->logo_museums
        );
    }

    /**
     * Format artists data with proper image URLs
     */
    private function formatArtistsData(): ?array
    {
        if (! $this->data) {
            return null;
        }

        return array_map(function ($artist) {
            // Format artist image
            if (isset($artist['image'])) {
                $artist['image'] = $artist['image'] ? asset('storage/'.$artist['image']) : null;
            }

            // Format works images
            if (isset($artist['works']) && is_array($artist['works'])) {
                $artist['works'] = array_map(function ($work) {
                    if (isset($work['image'])) {
                        $work['image'] = $work['image'] ? asset('storage/'.$work['image']) : null;
                    }

                    return $work;
                }, $artist['works']);
            }

            return $artist;
        }, $this->data);
    }
}

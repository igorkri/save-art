<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\User
 */
class ArtistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'avatar_url' => $this->avatar ? Storage::url($this->avatar) : null,

            // Публічні дані з профілю
            'profession' => $this->profilePersonal?->profession,
            'bio' => $this->profilePersonal?->bio,
            'city' => $this->profilePersonal?->city,
            'country' => $this->profilePersonal?->country,

            // Соціальні мережі
            'social' => $this->whenLoaded('profileSocial', function () {
                return [
                    'website' => $this->profileSocial?->website,
                    'facebook' => $this->profileSocial?->facebook,
                    'instagram' => $this->profileSocial?->instagram,
                    'youtube' => $this->profileSocial?->youtube,
                    'linkedin' => $this->profileSocial?->linkedin,
                ];
            }),

            // Статистика
            'projects_count' => $this->projects_count ?? $this->projects()->publicStatuses()->count(),
            'completed_projects_count' => $this->completed_projects_count ?? $this->projects()->completed()->count(),

            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

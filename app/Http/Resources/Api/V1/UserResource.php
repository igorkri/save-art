<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\ProfileLegalResource;
use App\Http\Resources\ProfilePersonalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
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
            'email' => $this->email,
            'slug' => $this->slug,
            'role' => $this->role?->value,
            'avatar_url' => $this->avatar ? Storage::url($this->avatar) : null,
            'email_verified_at' => $this->email_verified_at?->toISOString(),

            // Профілі (conditionally loaded)
            'profile_personal' => $this->whenLoaded('profilePersonal', function () {
                return new ProfilePersonalResource($this->profilePersonal);
            }),
            'profile_legal' => $this->whenLoaded('profileLegal', function () {
                return new ProfileLegalResource($this->profileLegal);
            }),
            'profile_social' => $this->whenLoaded('profileSocial', function () {
                return [
                    'website' => $this->profileSocial?->website,
                    'facebook' => $this->profileSocial?->facebook,
                    'instagram' => $this->profileSocial?->instagram,
                    'youtube' => $this->profileSocial?->youtube,
                    'tiktok' => $this->profileSocial?->tiktok,
                    'twitter' => $this->profileSocial?->twitter,
                    'linkedin' => $this->profileSocial?->linkedin,
                ];
            }),

            // Статистика для митців
            'projects_count' => $this->when(
                $this->relationLoaded('projects') || isset($this->projects_count),
                fn () => $this->projects_count ?? $this->projects->count()
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

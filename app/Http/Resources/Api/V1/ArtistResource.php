<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\User
 *
 * @OA\Schema(
 *     schema="Artist",
 *     title="Artist",
 *     description="Публічний профіль митця",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Іван Франко"),
 *     @OA\Property(property="slug", type="string", example="ivan-franko"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true),
 *     @OA\Property(property="profession", type="string", nullable=true),
 *     @OA\Property(property="bio", type="string", nullable=true),
 *     @OA\Property(property="city", type="string", nullable=true),
 *     @OA\Property(property="country", type="string", nullable=true),
 *     @OA\Property(property="social", type="object", nullable=true,
 *         @OA\Property(property="website", type="string", nullable=true),
 *         @OA\Property(property="facebook", type="string", nullable=true),
 *         @OA\Property(property="instagram", type="string", nullable=true),
 *         @OA\Property(property="youtube", type="string", nullable=true),
 *         @OA\Property(property="linkedin", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="projects_count", type="integer", example=5),
 *     @OA\Property(property="completed_projects_count", type="integer", example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
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
            'name' => $this->full_name ?? $this->name,
            'slug' => $this->slug,
            'avatar_url' => $this->avatar ? Storage::url($this->avatar) : null,

            // Публічні дані з профілю
            'profession' => $this->profession,
            'bio' => $this->description,
            'city' => $this->city,
            'country' => $this->country,

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
            'projects_count' => $this->projects_count ?? $this->projects()->whereIn('status', \App\Enums\ProjectStatus::publicStatuses())->count(),
            'completed_projects_count' => $this->completed_projects_count ?? $this->projects()->where('status', \App\Enums\ProjectStatus::Completed)->count(),

            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

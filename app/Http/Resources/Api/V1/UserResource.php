<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\ProfileLegalResource;
use App\Http\Resources\ProfilePersonalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\User
 *
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="Користувач",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Іван Франко"),
 *     @OA\Property(property="email", type="string", format="email", example="ivan@example.com"),
 *     @OA\Property(property="slug", type="string", nullable=true, example="ivan-franko"),
 *     @OA\Property(property="role", type="string", enum={"user", "mecenat", "owner", "moderator", "admin", "developer"}, example="user"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="profile_personal", ref="#/components/schemas/ProfilePersonal", nullable=true),
 *     @OA\Property(property="profile_legal", ref="#/components/schemas/ProfileLegal", nullable=true),
 *     @OA\Property(property="profile_social", type="object", nullable=true,
 *         @OA\Property(property="website", type="string", nullable=true),
 *         @OA\Property(property="facebook", type="string", nullable=true),
 *         @OA\Property(property="instagram", type="string", nullable=true),
 *         @OA\Property(property="youtube", type="string", nullable=true),
 *         @OA\Property(property="tiktok", type="string", nullable=true),
 *         @OA\Property(property="twitter", type="string", nullable=true),
 *         @OA\Property(property="linkedin", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="projects_count", type="integer", example=5),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
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
            'profile_type' => $this->profile_type?->value,
            'avatar_url' => $this->avatar ? Storage::url($this->avatar) : null,
            'email_verified_at' => $this->email_verified_at?->toISOString(),

            // Персональні дані (тепер в User)
            'profile_personal' => new ProfilePersonalResource($this->resource),
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

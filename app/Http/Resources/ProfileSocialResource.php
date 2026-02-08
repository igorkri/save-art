<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProfileSocial",
 *     title="ProfileSocial",
 *     description="Соціальні мережі профілю (03.7.3)",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="website", type="string", nullable=true, example="https://myart.com"),
 *     @OA\Property(property="facebook", type="string", nullable=true, example="https://facebook.com/myart"),
 *     @OA\Property(property="twitter", type="string", nullable=true, example="https://twitter.com/myart"),
 *     @OA\Property(property="instagram", type="string", nullable=true, example="https://instagram.com/myart"),
 *     @OA\Property(property="linkedin", type="string", nullable=true, example="https://linkedin.com/in/myart"),
 *     @OA\Property(property="youtube", type="string", nullable=true, example="https://youtube.com/@myart"),
 *     @OA\Property(property="pinterest", type="string", nullable=true, example="https://pinterest.com/myart"),
 *     @OA\Property(property="github", type="string", nullable=true, example="https://github.com/myart"),
 *     @OA\Property(property="telegram", type="string", nullable=true, example="https://t.me/myart"),
 *     @OA\Property(property="tiktok", type="string", nullable=true, example="https://tiktok.com/@myart"),
 *     @OA\Property(property="youtube_channel", type="string", nullable=true, example="https://youtube.com/channel/xxx"),
 *     @OA\Property(property="whatsapp", type="string", nullable=true, example="https://wa.me/380501234567"),
 *     @OA\Property(property="deviantart", type="string", nullable=true, example="https://deviantart.com/myart"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @mixin \App\Models\ProfileSocial
 */
class ProfileSocialResource extends JsonResource
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
            'user_id' => $this->user_id,
            'website' => $this->website,
            'facebook' => $this->facebook,
            'twitter' => $this->twitter,
            'instagram' => $this->instagram,
            'linkedin' => $this->linkedin,
            'youtube' => $this->youtube,
            'pinterest' => $this->pinterest,
            'github' => $this->github,
            'telegram' => $this->telegram,
            'tiktok' => $this->tiktok,
            'youtube_channel' => $this->youtube_channel,
            'whatsapp' => $this->whatsapp,
            'deviantart' => $this->deviantart,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\UserPhoto
 *
 * @OA\Schema(
 *     schema="UserPhoto",
 *     title="UserPhoto",
 *     description="Фото з портфоліо автора (митця чи організації)",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="image_url", type="string"),
 *     @OA\Property(property="likes_count", type="integer", example=12)
 * )
 */
class UserPhotoResource extends JsonResource
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
            'image_url' => Storage::url($this->image),
            'likes_count' => $this->likes_count,
        ];
    }
}

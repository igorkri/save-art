<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\LocalizesFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\Team
 *
 * @OA\Schema(
 *     schema="Team",
 *     title="Team",
 *     description="Публічний профіль команди",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="name", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true),
 *     @OA\Property(property="website", type="string", nullable=true),
 *     @OA\Property(property="country", ref="#/components/schemas/LocalizedString", nullable=true),
 *     @OA\Property(property="city", ref="#/components/schemas/LocalizedString", nullable=true),
 *     @OA\Property(property="description", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="social_links", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="members", type="array", @OA\Items(ref="#/components/schemas/TeamMember"))
 * )
 */
class TeamResource extends JsonResource
{
    use LocalizesFields;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $language = $this->getLanguage($request);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->localizeField($this->name, $language),
            'avatar_url' => $this->avatar ? Storage::url($this->avatar) : null,
            'website' => $this->website,
            'country' => $this->localizeField($this->country, $language),
            'city' => $this->localizeField($this->city, $language),
            'description' => $this->localizeField($this->description, $language),
            'social_links' => $this->social_links ?? [],
            'members' => TeamMemberResource::collection($this->whenLoaded('members')),
        ];
    }
}

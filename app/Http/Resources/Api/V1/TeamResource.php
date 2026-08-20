<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @mixin Team
 *
 * @OA\Schema(
 *     schema="Team",
 *     title="Team",
 *     description="Публічний профіль команди",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true),
 *     @OA\Property(property="website", type="string", nullable=true),
 *     @OA\Property(property="country", type="string", nullable=true),
 *     @OA\Property(property="city", type="string", nullable=true),
 *     @OA\Property(property="region", type="string", nullable=true),
 *     @OA\Property(property="zip", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="specialization", type="string", nullable=true),
 *     @OA\Property(property="social_links", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="members", type="array", @OA\Items(ref="#/components/schemas/TeamMember")),
 *     @OA\Property(property="is_owner", type="boolean", description="Чи є поточний авторизований користувач власником команди")
 * )
 */
class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'avatar_url' => $this->avatar ? Storage::url($this->avatar) : null,
            'website' => $this->website,
            'country' => $this->country,
            'city' => $this->city,
            'region' => $this->region,
            'zip' => $this->zip,
            'description' => $this->description,
            'specialization' => $this->specialization,
            'social_links' => $this->social_links ?? [],
            'members' => TeamMemberResource::collection($this->whenLoaded('members')),
            'is_owner' => $user ? $this->isOwnedBy($user) : false,
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\ProjectBonus
 *
 * @OA\Schema(
 *     schema="ProjectBonus",
 *     title="ProjectBonus",
 *     description="Бонус від автора за донат",
 *     type="object",
 *     required={"id", "order", "title", "min_donation"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order", type="integer", example=1),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="min_donation", type="number", format="float", example=500.00),
 *     @OA\Property(property="max_donation", type="number", format="float", nullable=true, example=1500.00),
 *     @OA\Property(property="quantity", type="integer", nullable=true, example=10),
 *     @OA\Property(property="quantity_claimed", type="integer", example=3),
 *     @OA\Property(property="remaining", type="integer", nullable=true, example=7),
 *     @OA\Property(property="is_available", type="boolean", example=true),
 *     @OA\Property(property="is_unlimited", type="boolean", example=false)
 * )
 */
class ProjectBonusResource extends JsonResource
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
            'order' => $this->order,

            'title' => $this->title,
            'description' => $this->description,

            'min_donation' => (float) $this->min_donation,
            'max_donation' => $this->max_donation !== null ? (float) $this->max_donation : null,

            'quantity' => $this->quantity,
            'quantity_claimed' => $this->quantity_claimed,
            'remaining' => $this->getRemaining(),

            'is_available' => $this->isAvailable(),
            'is_unlimited' => $this->quantity === null,
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProjectBonus
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

            'quantity' => $this->quantity,
            'quantity_claimed' => $this->quantity_claimed,
            'remaining' => $this->getRemaining(),

            'is_available' => $this->isAvailable(),
            'is_unlimited' => $this->quantity === null,
        ];
    }
}

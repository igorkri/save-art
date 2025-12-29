<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Donation
 */
class DonationResource extends JsonResource
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

            'project' => [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                'title' => $this->project->title,
            ],

            'amount' => (float) $this->amount,
            'currency' => $this->currency->value,

            'status' => $this->status,
            'status_label' => match ($this->status) {
                'pending' => 'Очікує',
                'paid' => 'Оплачено',
                'failed' => 'Помилка',
                'refunded' => 'Повернено',
                default => $this->status,
            },

            'is_anonymous' => $this->is_anonymous,
            'donor_name' => $this->is_anonymous ? null : $this->donor_name,

            'bonus' => $this->whenLoaded('bonus', function () {
                return [
                    'id' => $this->bonus->id,
                    'title' => $this->bonus->title,
                ];
            }),

            'message' => $this->message,

            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

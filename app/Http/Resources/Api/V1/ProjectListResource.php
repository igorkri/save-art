<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\Project
 */
class ProjectListResource extends JsonResource
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
            'slug' => $this->slug,

            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),

            'title' => $this->title,
            'short_description' => $this->short_description,
            'cover_url' => $this->cover ? Storage::url($this->cover) : null,

            'art_category' => $this->art_category?->value,
            'art_category_label' => $this->art_category?->getLabel(),
            'art_subcategory' => $this->art_subcategory,
            'art_subcategory_label' => $this->getArtSubcategoryLabel(),

            'currency' => $this->currency->value,
            'budget_goal' => (float) $this->budget_goal,
            'budget_collected' => (float) $this->budget_collected,
            'progress_percentage' => round($this->getProgressPercentage(), 2),

            'likes_count' => $this->likes_count,
            'donors_count' => $this->donors_count,

            'announced_at' => $this->announced_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'slug' => $this->user->slug ?? null,
                'avatar_url' => $this->user->avatar ? Storage::url($this->user->avatar) : null,
            ],

            'can_donate' => $this->canReceiveDonations(),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\Project
 */
class ProjectResource extends JsonResource
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
            'code' => $this->code,

            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'status_moderation' => $this->status_moderation->value,

            'title' => $this->title,
            'short_description' => $this->short_description,
            'cover_url' => $this->cover ? Storage::url($this->cover) : null,

            'art_category' => $this->art_category?->value,
            'art_category_label' => $this->art_category?->getLabel(),
            'art_subcategory' => $this->art_subcategory,
            'art_subcategory_label' => $this->getArtSubcategoryLabel(),

            'tags' => $this->tags,

            'currency' => $this->currency->value,
            'budget_goal' => (float) $this->budget_goal,
            'budget_collected' => (float) $this->budget_collected,
            'progress_percentage' => round($this->getProgressPercentage(), 2),

            'estimated_days' => $this->estimated_days,

            'likes_count' => $this->likes_count,
            'donors_count' => $this->donors_count,

            'is_liked' => $this->when(
                $request->user(),
                fn () => $this->likes()->where('user_id', $request->user()->id)->exists(),
                false
            ),

            'announced_at' => $this->announced_at?->toISOString(),
            'planned_completion_at' => $this->planned_completion_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'slug' => $this->user->slug ?? null,
                'avatar_url' => $this->user->avatar ? Storage::url($this->user->avatar) : null,
            ],

            'characteristics' => $this->characteristics,
            'budget_items' => $this->budget_items,
            'additional_info' => $this->additional_info,
            'final_result' => $this->final_result,

            'stages' => ProjectStageResource::collection($this->whenLoaded('stages')),
            'bonuses' => ProjectBonusResource::collection($this->whenLoaded('bonuses')),

            'can_edit' => $this->when(
                $request->user(),
                fn () => $request->user()->id === $this->user_id && $this->isEditable(),
                false
            ),
            'can_donate' => $this->canReceiveDonations(),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProjectStage
 */
class ProjectStageResource extends JsonResource
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

            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),

            'title' => $this->title,
            'description' => $this->description,

            'days_planned' => $this->days_planned,
            'budget_planned' => $this->budget_planned ? (float) $this->budget_planned : null,
            'budget_actual' => $this->budget_actual ? (float) $this->budget_actual : null,

            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'is_completed' => $this->isCompleted(),
            'is_in_progress' => $this->isInProgress(),
        ];
    }
}

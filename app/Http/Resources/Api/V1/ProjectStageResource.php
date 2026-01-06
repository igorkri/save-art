<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\ProjectStage
 *
 * @OA\Schema(
 *     schema="ProjectStage",
 *     title="ProjectStage",
 *     description="Етап проєкту",
 *     type="object",
 *     required={"id", "order", "status", "title"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order", type="integer", example=1),
 *     @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed"}, example="pending"),
 *     @OA\Property(property="status_label", type="string", example="Очікує"),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="description", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="days_planned", type="integer", nullable=true, example=30),
 *     @OA\Property(property="budget_planned", type="number", format="float", nullable=true, example=5000.00),
 *     @OA\Property(property="budget_actual", type="number", format="float", nullable=true, example=4800.00),
 *     @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="is_completed", type="boolean", example=false),
 *     @OA\Property(property="is_in_progress", type="boolean", example=false)
 * )
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

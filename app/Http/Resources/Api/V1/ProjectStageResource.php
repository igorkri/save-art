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
 *     @OA\Property(property="status", type="string", enum={"planned", "in_progress", "completed"}, example="planned"),
 *     @OA\Property(property="status_label", type="string", example="Заплановано"),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="description", ref="#/components/schemas/LocalizedString", nullable=true),
 *     @OA\Property(property="days_planned", type="integer", nullable=true, example=30),
 *     @OA\Property(property="budget_planned", type="number", format="float", nullable=true, example=5000.00),
 *     @OA\Property(property="budget_actual", type="number", format="float", nullable=true, example=4800.00),
 *     @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="is_completed", type="boolean", example=false),
 *     @OA\Property(property="is_in_progress", type="boolean", example=false),
 *     @OA\Property(
 *         property="documents",
 *         type="array",
 *         nullable=true,
 *         description="Документи/фото для підтвердження виконання етапу",
 *
 *         @OA\Items(ref="#/components/schemas/StageDocument")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StageDocument",
 *     title="StageDocument",
 *     description="Документ або фото етапу",
 *     type="object",
 *
 *     @OA\Property(property="type", type="string", enum={"photo", "document"}, example="photo", description="Тип: photo або document"),
 *     @OA\Property(property="file", type="string", example="projects/1/stages/1/receipt.jpg", description="Шлях до файлу"),
 *     @OA\Property(property="file_url", type="string", example="http://save-art.local/storage/projects/1/stages/1/receipt.jpg", description="Повний URL файлу"),
 *     @OA\Property(property="original_name", type="string", example="чек_матеріали.jpg", description="Оригінальна назва файлу"),
 *     @OA\Property(property="description", ref="#/components/schemas/LocalizedString", nullable=true, description="Опис документа (uk, en)"),
 *     @OA\Property(property="uploaded_at", type="string", format="date-time", description="Дата завантаження")
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

            'documents' => $this->documents,
        ];
    }
}

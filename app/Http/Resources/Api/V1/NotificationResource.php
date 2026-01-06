<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\Notification
 *
 * @OA\Schema(
 *     schema="Notification",
 *     title="Notification",
 *     description="Сповіщення користувача (03.2.4)",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"donation", "moderation", "message", "system", "project_approved", "project_rejected", "project_completed", "bonus_claimed"}, example="donation", description="Тип сповіщення"),
 *     @OA\Property(property="type_label", type="string", example="Донат", description="Назва типу українською"),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString", description="Заголовок сповіщення (мультимовне)"),
 *     @OA\Property(property="message", ref="#/components/schemas/LocalizedString", nullable=true, description="Текст повідомлення (мультимовне)"),
 *     @OA\Property(property="data", type="object", nullable=true, description="Додаткові дані (посилання, ID проекту тощо)",
 *         @OA\Property(property="project_id", type="integer", example=1),
 *         @OA\Property(property="project_slug", type="string", example="miy-proekt"),
 *         @OA\Property(property="donation_id", type="integer", example=5),
 *         @OA\Property(property="amount", type="number", example=500)
 *     ),
 *     @OA\Property(property="is_read", type="boolean", example=false, description="Чи прочитане"),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true, description="Дата прочитання"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Дата створення")
 * )
 */
class NotificationResource extends JsonResource
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
            'type' => $this->type->value,
            'type_label' => $this->type->getLabel(),
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}

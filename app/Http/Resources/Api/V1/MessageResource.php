<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\Message
 *
 * @OA\Schema(
 *     schema="Message",
 *     title="Message",
 *     description="Повідомлення в чаті з адміністрацією",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="content", type="string", example="Доброго дня! Хотів би уточнити..."),
 *     @OA\Property(property="subject", type="string", nullable=true, example="Питання щодо проєкту"),
 *     @OA\Property(property="direction", type="string", enum={"incoming", "outgoing"}, example="outgoing"),
 *     @OA\Property(property="is_from_admin", type="boolean", example=false),
 *     @OA\Property(property="is_read", type="boolean", example=true),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="admin", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string")
 *     ),
 *     @OA\Property(property="project", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *         @OA\Property(property="slug", type="string")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class MessageResource extends JsonResource
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
            'content' => $this->content,
            'subject' => $this->subject,
            'direction' => $this->direction,
            'is_from_admin' => $this->isFromAdmin(),
            'is_read' => $this->isRead(),
            'read_at' => $this->read_at?->toISOString(),

            // Адміністратор (якщо повідомлення від адміна)
            'admin' => $this->whenLoaded('admin', function () {
                return $this->admin ? [
                    'id' => $this->admin->id,
                    'name' => $this->admin->name,
                ] : null;
            }),

            // Проєкт (якщо пов'язаний)
            'project' => $this->whenLoaded('project', function () {
                return $this->project ? [
                    'id' => $this->project->id,
                    'title' => $this->project->title,
                    'slug' => $this->project->slug,
                ] : null;
            }),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

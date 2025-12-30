<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Message
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

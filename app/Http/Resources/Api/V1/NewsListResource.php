<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\News
 *
 * @OA\Schema(
 *     schema="NewsList",
 *     title="NewsList",
 *     description="Новина/подія у списку (скорочена інформація, без text_blocks)",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string", example="vystavka-suchasnogo-mystetstva-cymy-vyhidnymy"),
 *     @OA\Property(property="category", type="string", enum={"news", "event"}, example="news"),
 *     @OA\Property(property="category_label", type="string", example="Новини"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="date", type="string", example="12.01.2026", description="Дата публікації у форматі dd.mm.yyyy"),
 *     @OA\Property(property="published_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="main_image_url", type="string", nullable=true)
 * )
 */
class NewsListResource extends JsonResource
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

            'category' => $this->category->value,
            'category_label' => $this->category->getLabel('uk'),

            'title' => $this->title,

            'date' => $this->published_at?->format('d.m.Y'),
            'published_at' => $this->published_at?->toISOString(),

            'main_image_url' => $this->main_image ? asset('storage/'.$this->main_image) : null,
        ];
    }
}

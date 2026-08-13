<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\News
 *
 * @OA\Schema(
 *     schema="News",
 *     title="News",
 *     description="Новина/подія (повна інформація)",
 *     type="object",
 *     required={"id", "slug", "category", "title"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string", example="vystavka-suchasnogo-mystetstva-cymy-vyhidnymy"),
 *     @OA\Property(property="category", type="string", enum={"news", "event"}, example="news"),
 *     @OA\Property(property="category_label", type="string", example="Новини"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="date", type="string", example="12.01.2026", description="Дата публікації у форматі dd.mm.yyyy"),
 *     @OA\Property(property="published_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="main_image_url", type="string", nullable=true),
 *     @OA\Property(property="text_blocks", type="array", @OA\Items(
 *         type="object",
 *         @OA\Property(property="paragraphs", type="array", @OA\Items(type="string")),
 *         @OA\Property(property="image", type="string", nullable=true),
 *         @OA\Property(property="image_url", type="string", nullable=true)
 *     )),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class NewsResource extends JsonResource
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

            'text_blocks' => $this->formatTextBlocks($this->text_blocks),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Форматування text_blocks з додаванням URL зображень
     */
    private function formatTextBlocks(?array $textBlocks): array
    {
        if (! is_array($textBlocks)) {
            return [];
        }

        return array_map(function ($block) {
            if (! is_array($block)) {
                return $block;
            }

            return [
                'paragraphs' => $block['paragraphs'] ?? [],
                'image' => $block['image'] ?? null,
                'image_url' => isset($block['image']) ? asset('storage/'.$block['image']) : null,
            ];
        }, $textBlocks);
    }
}

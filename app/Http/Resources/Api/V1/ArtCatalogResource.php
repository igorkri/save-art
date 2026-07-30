<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\LocalizesFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\ArtCatalog
 *
 * @OA\Schema(
 *     schema="ArtCatalog",
 *     title="ArtCatalog",
 *     description="PDF-каталог робіт автора",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="image_url", type="string"),
 *     @OA\Property(property="published_at", type="string", format="date", nullable=true),
 *     @OA\Property(property="art_category", type="object", nullable=true,
 *         @OA\Property(property="slug", type="string"),
 *         @OA\Property(property="name", ref="#/components/schemas/LocalizedString"),
 *         @OA\Property(property="root_name", ref="#/components/schemas/LocalizedString")
 *     ),
 *     @OA\Property(property="likes_count", type="integer", example=10),
 *     @OA\Property(property="is_liked", type="boolean", example=false),
 *     @OA\Property(property="is_primary", type="boolean", example=false),
 *     @OA\Property(property="pdf_url", type="string", nullable=true),
 *     @OA\Property(property="author", type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="slug", type="string"),
 *         @OA\Property(property="avatar_url", type="string", nullable=true),
 *         @OA\Property(property="profession", type="string", nullable=true)
 *     )
 * )
 */
class ArtCatalogResource extends JsonResource
{
    use LocalizesFields;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $language = $this->getLanguage($request);

        return [
            'id' => $this->id,
            'title' => $this->localizeField($this->title, $language),
            // Сирий об'єкт {uk, en} незалежно від ?language — потрібен для передзаповнення форми редагування.
            'title_translations' => $this->title,
            'image_url' => Storage::url($this->image),
            'published_at' => $this->published_at?->toDateString(),
            'art_category' => $this->whenLoaded('artCategory', fn () => $this->artCategory ? [
                'slug' => $this->artCategory->slug,
                'name' => $this->localizeField($this->artCategory->name, $language),
                'root_name' => $this->localizeField(
                    ($this->artCategory->parent ?? $this->artCategory)->name,
                    $language
                ),
            ] : null),
            // Slug кореневої галузі та підкатегорії — для передзаповнення форми редагування каталогу.
            'art_category_slug' => $this->whenLoaded('artCategory', fn () => $this->artCategory?->getRootSlug()),
            'art_subcategory_slug' => $this->whenLoaded('artCategory', fn () => $this->artCategory?->getSubSlug()),
            'likes_count' => $this->likes_count,
            'is_liked' => $this->when(
                $request->user('sanctum'),
                fn () => $this->likes()->where('user_id', $request->user('sanctum')->id)->exists(),
                false
            ),
            'is_primary' => $this->is_primary,
            'pdf_url' => $this->pdf_file ? Storage::url('catalogs/'.$this->pdf_file) : null,
            'author' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'slug' => $this->user->slug,
                'avatar_url' => $this->user->avatar ? Storage::url($this->user->avatar) : null,
                'profession' => $this->localizeField($this->user->profession, $language),
            ]),
        ];
    }
}

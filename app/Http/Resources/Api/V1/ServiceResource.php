<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\LocalizesFields;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\Service
 *
 * @OA\Schema(
 *     schema="Service",
 *     title="Service",
 *     description="Послуга, яку пропонує митець або команда",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="description", ref="#/components/schemas/LocalizedString", nullable=true),
 *     @OA\Property(property="image_url", type="string", nullable=true),
 *     @OA\Property(property="art_category", type="object", nullable=true,
 *         @OA\Property(property="slug", type="string"),
 *         @OA\Property(property="name", ref="#/components/schemas/LocalizedString")
 *     ),
 *     @OA\Property(property="location", ref="#/components/schemas/LocalizedString", nullable=true),
 *     @OA\Property(property="price", type="number", format="float", nullable=true),
 *     @OA\Property(property="price_from", type="boolean", example=false),
 *     @OA\Property(property="currency", type="string", nullable=true, example="UAH"),
 *     @OA\Property(property="options", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="performer_type", type="string", enum={"artist", "organization", "team"}),
 *     @OA\Property(property="performer", type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="slug", type="string"),
 *         @OA\Property(property="avatar_url", type="string", nullable=true)
 *     )
 * )
 */
class ServiceResource extends JsonResource
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
        $performer = $this->serviceable;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->localizeField($this->title, $language),
            'description' => $this->localizeField($this->description, $language),
            'image_url' => $this->image ? Storage::url($this->image) : null,
            'art_category' => $this->whenLoaded('artCategory', fn () => $this->artCategory ? [
                'slug' => $this->artCategory->slug,
                'name' => $this->localizeField($this->artCategory->name, $language),
            ] : null),
            'location' => $this->localizeField($this->location, $language),
            'price' => $this->price !== null ? (float) $this->price : null,
            'price_from' => (bool) $this->price_from,
            'currency' => $this->currency?->value,
            'options' => collect($this->options ?? [])
                ->map(fn ($option) => $this->localizeField($option['name'] ?? null, $language))
                ->filter()
                ->values(),
            'performer_type' => $this->performerType($performer),
            'performer' => $performer ? [
                'id' => $performer->id,
                'name' => $performer instanceof Team ? $this->localizeField($performer->name, $language) : $performer->name,
                'slug' => $performer->slug,
                'avatar_url' => $performer->avatar ? Storage::url($performer->avatar) : null,
            ] : null,
        ];
    }

    private function performerType(mixed $performer): string
    {
        if ($performer instanceof Team) {
            return 'team';
        }

        if ($performer instanceof User && $performer->isOrganization()) {
            return 'organization';
        }

        return 'artist';
    }
}

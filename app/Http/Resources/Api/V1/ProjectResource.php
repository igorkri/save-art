<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\Project
 *
 * @OA\Schema(
 *     schema="Project",
 *     title="Project",
 *     description="Проєкт митця (повна інформація)",
 *     type="object",
 *     required={"id", "slug", "status", "title", "currency", "budget_goal"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string", example="miy-noviy-proekt-abc123"),
 *     @OA\Property(property="code", type="string", example="ABC12345"),
 *     @OA\Property(property="status", type="string", enum={"draft", "moderation", "announced", "in_progress", "paused", "completed", "sold", "rejected"}, example="announced"),
 *     @OA\Property(property="status_label", type="string", example="Оголошений"),
 *     @OA\Property(property="status_moderation", type="string", enum={"pending", "approved", "rejected"}, example="approved"),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="short_description", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="cover_url", type="string", nullable=true, example="http://save-art.local/storage/projects/covers/1.jpg"),
 *     @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}, example="visual"),
 *     @OA\Property(property="art_category_label", type="string", example="Візуальне мистецтво"),
 *     @OA\Property(property="art_subcategory", type="string", nullable=true, example="painting"),
 *     @OA\Property(property="art_subcategory_label", type="string", nullable=true, example="Живопис"),
 *     @OA\Property(property="tags", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
 *     @OA\Property(property="budget_goal", type="number", format="float", example=50000.00),
 *     @OA\Property(property="budget_collected", type="number", format="float", example=12500.00),
 *     @OA\Property(property="progress_percentage", type="number", format="float", example=25.00),
 *     @OA\Property(property="estimated_days", type="integer", nullable=true, example=90),
 *     @OA\Property(property="likes_count", type="integer", example=42),
 *     @OA\Property(property="donors_count", type="integer", example=15),
 *     @OA\Property(property="is_liked", type="boolean", example=false),
 *     @OA\Property(property="announced_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="planned_completion_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="author", ref="#/components/schemas/Author"),
 *     @OA\Property(property="characteristics", type="array", nullable=true, @OA\Items(type="object",
 *         @OA\Property(property="name", ref="#/components/schemas/LocalizedString"),
 *         @OA\Property(property="value", ref="#/components/schemas/LocalizedString")
 *     )),
 *     @OA\Property(property="budget_items", type="array", nullable=true, @OA\Items(type="object",
 *         @OA\Property(property="name", ref="#/components/schemas/LocalizedString"),
 *         @OA\Property(property="amount", type="number", example=15000)
 *     )),
 *     @OA\Property(property="final_result", ref="#/components/schemas/FinalResult", nullable=true),
 *     @OA\Property(property="stages", type="array", @OA\Items(ref="#/components/schemas/ProjectStage")),
 *     @OA\Property(property="bonuses", type="array", @OA\Items(ref="#/components/schemas/ProjectBonus")),
 *     @OA\Property(property="can_edit", type="boolean", example=false),
 *     @OA\Property(property="can_donate", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="FinalResult",
 *     title="FinalResult",
 *     description="Фінальний результат проєкту (03.4.4, 03.4.5). Типи: image (одне зображення), gallery (галерея), video (відео-файл), document (документ)",
 *     type="object",
 *     required={"type"},
 *
 *     @OA\Property(property="type", type="string", enum={"image", "gallery", "video", "document"}, example="gallery", description="Тип результату"),
 *     @OA\Property(property="description", ref="#/components/schemas/LocalizedString", nullable=true, description="Опис результату"),
 *     @OA\Property(property="file", type="object", nullable=true, description="Один файл (для type=image або document або video з одним файлом)",
 *         @OA\Property(property="path", type="string", example="projects/1/final-result/artwork.jpg"),
 *         @OA\Property(property="url", type="string", example="http://save-art.local/storage/projects/1/final-result/artwork.jpg"),
 *         @OA\Property(property="original_name", type="string", example="my-artwork.jpg"),
 *         @OA\Property(property="mime_type", type="string", example="image/jpeg"),
 *         @OA\Property(property="size", type="integer", example=1024000, description="Розмір у байтах")
 *     ),
 *     @OA\Property(property="files", type="array", nullable=true, description="Масив файлів (для type=gallery або кілька файлів)",
 *
 *         @OA\Items(type="object",
 *
 *             @OA\Property(property="path", type="string"),
 *             @OA\Property(property="url", type="string"),
 *             @OA\Property(property="original_name", type="string"),
 *             @OA\Property(property="mime_type", type="string"),
 *             @OA\Property(property="size", type="integer")
 *         )
 *     ),
 *     @OA\Property(property="uploaded_at", type="string", format="date-time", description="Дата завантаження")
 * )
 */
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Отримуємо мову з параметра запиту
        $language = $request->query('language');
        $supportedLanguages = ['uk', 'en'];
        if ($language && ! in_array($language, $supportedLanguages)) {
            $language = null;
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'code' => $this->code,

            'status' => $this->status->value,
            'status_label' => $this->status->getLabel($language ?? 'uk'),
            'status_moderation' => $this->status_moderation->value,

            'title' => $this->localizeField($this->title, $language),
            'short_description' => $this->localizeField($this->short_description, $language),
            'cover_url' => $this->cover ? Storage::url($this->cover) : null,

            'art_category' => $this->art_category?->value,
            'art_category_label' => $this->art_category?->getLabel($language ?? 'uk'),
            'art_subcategory' => $this->art_subcategory,
            'art_subcategory_label' => $this->getArtSubcategoryLabel($language ?? 'uk'),

            'tags' => $this->localizeField($this->tags, $language),

            'currency' => $this->currency->value,
            'budget_goal' => (float) $this->budget_goal,
            'budget_collected' => (float) $this->budget_collected,
            'progress_percentage' => round($this->getProgressPercentage(), 2),

            'estimated_days' => $this->estimated_days,

            'likes_count' => $this->likes_count,
            'donors_count' => $this->donors_count,

            'is_liked' => $this->when(
                $request->user(),
                fn () => $this->likes()->where('user_id', $request->user()->id)->exists(),
                false
            ),

            'announced_at' => $this->announced_at?->toISOString(),
            'planned_completion_at' => $this->planned_completion_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'slug' => $this->user->slug ?? null,
                'avatar_url' => $this->user->avatar ? Storage::url($this->user->avatar) : null,
            ],

            'characteristics' => $this->localizeArrayField($this->characteristics, $language),
            'budget_items' => $this->localizeArrayField($this->budget_items, $language),
            'additional_info' => $this->localizeField($this->additional_info, $language),
            'final_result' => $this->localizeFinalResult($this->final_result, $language),

            'stages' => ProjectStageResource::collection($this->whenLoaded('stages')),
            'bonuses' => ProjectBonusResource::collection($this->whenLoaded('bonuses')),

            'can_edit' => $this->when(
                $request->user(),
                fn () => $request->user()->id === $this->user_id && $this->isEditable(),
                false
            ),
            'can_donate' => $this->canReceiveDonations(),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Локалізація поля - повертає значення для конкретної мови або весь об'єкт
     */
    private function localizeField($field, ?string $language): mixed
    {
        if ($language === null) {
            return $field;
        }

        if (! is_array($field)) {
            return $field;
        }

        // Перевіряємо чи це об'єкт з мовами (має ключі uk/en)
        $hasLanguageKeys = isset($field['uk']) || isset($field['en']);

        if ($hasLanguageKeys) {
            // Це мультимовне поле - повертаємо значення для вказаної мови
            return $field[$language] ?? $field['uk'] ?? reset($field);
        }

        // Це звичайний масив (не мультимовний) - повертаємо як є
        return $field;
    }

    /**
     * Локалізація масиву об'єктів з вкладеними мовними полями
     */
    private function localizeArrayField($field, ?string $language): mixed
    {
        if ($language === null || ! is_array($field)) {
            return $field;
        }

        return array_map(function ($item) use ($language) {
            if (! is_array($item)) {
                return $item;
            }

            $localized = [];
            foreach ($item as $key => $value) {
                $localized[$key] = $this->localizeField($value, $language);
            }

            return $localized;
        }, $field);
    }

    /**
     * Локалізація final_result з вкладеним description
     */
    private function localizeFinalResult($finalResult, ?string $language): mixed
    {
        if ($language === null || ! is_array($finalResult)) {
            return $finalResult;
        }

        if (isset($finalResult['description'])) {
            $finalResult['description'] = $this->localizeField($finalResult['description'], $language);
        }

        return $finalResult;
    }
}

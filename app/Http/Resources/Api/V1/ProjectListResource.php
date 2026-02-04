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
 *     schema="ProjectList",
 *     title="ProjectList",
 *     description="Проєкт у списку (скорочена інформація)",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string", example="miy-noviy-proekt-abc123"),
 *     @OA\Property(property="status", type="string", example="announced"),
 *     @OA\Property(property="status_label", type="string", example="Оголошений"),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="short_description", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="cover_url", type="string", nullable=true),
 *     @OA\Property(property="art_category", type="string", example="visual"),
 *     @OA\Property(property="art_category_label", type="string", example="Візуальне мистецтво"),
 *     @OA\Property(property="currency", type="string", example="UAH"),
 *     @OA\Property(property="budget_goal", type="number", format="float", example=50000.00),
 *     @OA\Property(property="budget_collected", type="number", format="float", example=12500.00),
 *     @OA\Property(property="progress_percentage", type="number", format="float", example=25.00),
 *     @OA\Property(property="estimated_days", type="integer", nullable=true, example=90, description="Орієнтовна кількість днів на реалізацію"),
 *     @OA\Property(property="days_left", type="integer", nullable=true, example=30, description="Залишилось днів до планованого завершення"),
 *     @OA\Property(property="likes_count", type="integer", example=42),
 *     @OA\Property(property="donors_count", type="integer", example=15),
 *     @OA\Property(property="tags", type="array", nullable=true, @OA\Items(type="string"), example={"живопис", "сучасне мистецтво"}, description="Теги проєкту"),
 *     @OA\Property(property="author", ref="#/components/schemas/Author"),
 *     @OA\Property(property="announced_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="planned_completion_at", type="string", format="date-time", nullable=true, description="Планова дата завершення")
 * )
 */
class ProjectListResource extends JsonResource
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

            'status' => $this->status->value,
            'status_label' => $this->status->getLabel($language ?? 'uk'),

            'title' => $this->localizeField($this->title, $language),
            'short_description' => $this->localizeField($this->short_description, $language),
            'cover_url' => $this->cover ? Storage::url($this->cover) : null,

            'art_category' => $this->art_category?->value,
            'art_category_label' => $this->art_category?->getLabel($language ?? 'uk'),
            'art_subcategory' => $this->art_subcategory,
            'art_subcategory_label' => $this->getArtSubcategoryLabel($language ?? 'uk'),

            'currency' => $this->currency->value,
            'budget_goal' => (float) $this->budget_goal,
            'budget_collected' => (float) $this->budget_collected,
            'progress_percentage' => round($this->getProgressPercentage(), 2),

            'estimated_days' => $this->estimated_days,
            'days_left' => $this->getDaysLeft(),

            'likes_count' => $this->likes_count,
            'donors_count' => $this->donors_count,

            'tags' => $this->localizeField($this->tags, $language),

            'announced_at' => $this->announced_at?->toISOString(),
            'planned_completion_at' => $this->planned_completion_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'slug' => $this->user->slug ?? null,
                'avatar_url' => $this->user->avatar ? Storage::url($this->user->avatar) : null,
            ],

            'can_donate' => $this->canReceiveDonations(),
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
}

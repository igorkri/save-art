<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\UserType;
use App\Http\Resources\Api\V1\Concerns\BuildsProjectParameters;
use App\Http\Resources\Api\V1\Concerns\LocalizesFields;
use App\Models\User;
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
 *     @OA\Property(property="status_moderation", type="string", nullable=true, enum={"pending", "processing", "approved", "rejected"}, example="pending"),
 *     @OA\Property(property="sold_externally", type="boolean", example=false, description="Проєкт позначено проданим поза платформою (art-ua-info)"),
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
 *     @OA\Property(property="parameters", type="array", @OA\Items(ref="#/components/schemas/ProjectParameter")),
 *     @OA\Property(property="author", ref="#/components/schemas/Author"),
 *     @OA\Property(property="announced_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="planned_completion_at", type="string", format="date-time", nullable=true, description="Планова дата завершення")
 * )
 */
class ProjectListResource extends JsonResource
{
    use BuildsProjectParameters;
    use LocalizesFields;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $language = $this->getLanguage($request);

        $data = [
            'id' => $this->id,
            'slug' => $this->slug,

            'status' => $this->status->value,
            'status_label' => $this->status->getLabel($language ?? 'uk'),
            'status_moderation' => $this->status_moderation?->value,
            'sold_externally' => (bool) $this->sold_externally,

            'title' => $this->title,
            'short_description' => $this->short_description,
            'cover_url' => $this->cover ? Storage::url($this->cover) : null,

            'art_category' => $this->getArtCategorySlug(),
            'art_category_label' => $this->getArtCategoryLabel($language ?? 'uk'),
            'art_subcategory' => $this->getArtSubcategorySlug(),
            'art_subcategory_label' => $this->getArtSubcategoryLabel($language ?? 'uk'),

            'currency' => $this->currency->value,
            'budget_goal' => (float) $this->budget_goal,
            'budget_collected' => (float) $this->budget_collected,
            'progress_percentage' => round($this->getProgressPercentage(), 2),

            'estimated_days' => $this->estimated_days,
            'days_left' => $this->getDaysLeft(),

            'likes_count' => $this->likes_count,
            'donors_count' => $this->donors_count,

            'tags' => $this->tags,
            'parameters' => $this->buildParameters($language),

            'announced_at' => $this->announced_at?->toISOString(),
            'planned_completion_at' => $this->planned_completion_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'author' => $this->formatAuthor($this->user, $language),

            'can_donate' => $this->canReceiveDonations(),
        ];

        return $data;
    }

    /**
     * Форматувати дані автора залежно від типу (фіз. особа або юр. особа)
     *
     * @return array<string, mixed>
     */
    private function formatAuthor(User $user, ?string $language): array
    {
        if ($this->team_id && $this->team) {
            // Команда - дані з Team
            return [
                'id' => $this->team->id,
                'name' => $this->localizeField($this->team->name, $language),
                'slug' => $this->team->slug,
                'avatar_url' => $this->team->avatar ? Storage::url($this->team->avatar) : null,
                'profession' => null,
                'type' => UserType::Team,
            ];
        }

        // Перевіряємо чи це юридична особа за полем проєкту
        $isLegal = $this->user_type == UserType::Legal;

        if ($isLegal && $user->profileLegal) {
            // Юридична особа - дані з ProfileLegal
            return [
                'id' => $user->id,
                'name' => $user->profileLegal->name,
                'slug' => $user->slug ?? null,
                'avatar_url' => $user->profileLegal->logo ? Storage::url($user->profileLegal->logo) : null,
                'profession' => $user->profession,
                'type' => UserType::Legal,
            ];
        } else {
            // Фізична особа - дані з User
            return [
                'id' => $user->id,
                'name' => $user->full_name ?? $user->name,
                'slug' => $user->slug ?? null,
                'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
                'profession' => $user->profession,
                'type' => UserType::Personal,
            ];
        }
    }
}

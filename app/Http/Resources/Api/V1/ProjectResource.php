<?php

namespace App\Http\Resources\Api\V1;

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
 *     schema="Project",
 *     title="Project",
 *     description="Проєкт митця (повна інформація)",
 *     type="object",
 *     required={"id", "slug", "status", "title", "currency", "budget_goal"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string", example="miy-noviy-proekt-abc123"),
 *     @OA\Property(property="code", type="string", example="ABC12345"),
 *     @OA\Property(property="source", type="string", enum={"save_art", "art_ua_info"}, example="save_art"),
 *     @OA\Property(property="status", type="string", enum={"draft", "moderation", "announced", "in_progress", "paused", "completed", "sold", "rejected"}, example="announced"),
 *     @OA\Property(property="status_label", type="string", example="Оголошений"),
 *     @OA\Property(property="status_moderation", type="string", enum={"pending", "approved", "rejected"}, example="approved"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="short_description", type="string", nullable=true),
 *     @OA\Property(property="cover_url", type="string", nullable=true, example="http://save-art-web.ddev.site/storage/projects/covers/1.jpg"),
 *     @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}, example="visual"),
 *     @OA\Property(property="art_category_label", type="string", example="Візуальне мистецтво"),
 *     @OA\Property(property="art_subcategory", type="string", nullable=true, example="painting"),
 *     @OA\Property(property="art_subcategory_label", type="string", nullable=true, example="Живопис"),
 *     @OA\Property(property="tags", type="array", nullable=true, @OA\Items(type="string")),
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
 *     @OA\Property(property="budget_items", type="array", nullable=true, @OA\Items(type="object",
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="amount", type="number", example=15000)
 *     )),
 *     @OA\Property(property="content_blocks", type="array", nullable=true, description="Динамічні контент-блоки", @OA\Items(
 *         type="object",
 *         required={"type"},
 *         @OA\Property(property="type", type="string", enum={"heading", "paragraph", "image"}, example="heading"),
 *         @OA\Property(property="heading_level", type="string", enum={"h2", "h3", "h4", "h5", "h6"}, example="h2", description="Тільки для type=heading"),
 *         @OA\Property(property="heading_text", type="string", example="Про проєкт", description="Тільки для type=heading (локалізовано)"),
 *         @OA\Property(property="paragraph_text", type="string", example="Опис проєкту...", description="Тільки для type=paragraph (локалізовано)"),
 *         @OA\Property(property="image", type="string", example="projects/content-blocks/abc123.jpg", description="Тільки для type=image"),
 *         @OA\Property(property="image_url", type="string", example="http://localhost/storage/projects/content-blocks/abc123.jpg", description="Тільки для type=image"),
 *         @OA\Property(property="image_alt", type="string", example="Опис зображення", description="Тільки для type=image (локалізовано)"),
 *         @OA\Property(property="image_caption", type="string", nullable=true, example="Підпис", description="Тільки для type=image (локалізовано)")
 *     )),
 *     @OA\Property(property="final_result", ref="#/components/schemas/FinalResult", nullable=true),
 *     @OA\Property(property="stages", type="array", @OA\Items(ref="#/components/schemas/ProjectStage")),
 *     @OA\Property(property="bonuses", type="array", @OA\Items(ref="#/components/schemas/ProjectBonus")),
 *     @OA\Property(property="parameters", type="array", @OA\Items(ref="#/components/schemas/ProjectParameter")),
 *     @OA\Property(property="can_edit", type="boolean", example=false),
 *     @OA\Property(property="can_donate", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ProjectParameter",
 *     title="ProjectParameter",
 *     description="Значення характеристики проєкту (включно з незаповненими — value: null)",
 *     type="object",
 *
 *     @OA\Property(property="parameter_id", type="integer", example=5),
 *     @OA\Property(property="parameter", type="string", description="Назва характеристики", example="Жанр"),
 *     @OA\Property(property="type", type="string", enum={"list", "custom"}, example="list"),
 *     @OA\Property(property="value_id", type="integer", nullable=true, example=12),
 *     @OA\Property(property="value", type="string", nullable=true, description="Значення", example="Роман")
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
 *         @OA\Property(property="url", type="string", example="http://save-art-web.ddev.site/storage/projects/1/final-result/artwork.jpg"),
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

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'code' => $this->code,
            'source' => $this->source->value,
            'sold_externally' => (bool) $this->sold_externally,

            'status' => $this->status->value,
            'status_label' => $this->status->getLabel($language ?? 'uk'),
            'status_moderation' => $this->status_moderation->value,

            'title' => $this->title,
            'short_description' => $this->short_description,
            'cover_url' => $this->cover ? Storage::url($this->cover) : null,

            'art_category' => $this->getArtCategorySlug(),
            'art_category_label' => $this->getArtCategoryLabel($language ?? 'uk'),
            'art_subcategory' => $this->getArtSubcategorySlug(),
            'art_subcategory_label' => $this->getArtSubcategoryLabel($language ?? 'uk'),

            'tags' => $this->tags,

            'currency' => $this->currency?->value,
            'budget_goal' => $this->budget_goal ? (float) $this->budget_goal : null,
            'budget_collected' => (float) $this->budget_collected,
            'progress_percentage' => $this->budget_goal ? round($this->getProgressPercentage(), 2) : 0,

            'estimated_days' => $this->estimated_days,

            'likes_count' => $this->likes_count,
            'donors_count' => $this->donors_count,

            'is_liked' => $this->when(
                $request->user('sanctum'),
                fn () => $this->likes()->where('user_id', $request->user('sanctum')->id)->exists(),
                false
            ),

            'announced_at' => $this->announced_at?->toISOString(),
            'planned_completion_at' => $this->planned_completion_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'author' => $this->formatAuthor($this->user, $language),

            'budget_items' => $this->budget_items,
            'additional_info' => $this->localizeField($this->additional_info, $language),
            'content_blocks' => $this->content_blocks,
            'final_result' => $this->localizeFinalResult($this->final_result, $language),

            'stages' => ProjectStageResource::collection($this->whenLoaded('stages')),
            'bonuses' => ProjectBonusResource::collection($this->whenLoaded('bonuses')),
            'parameters' => $this->buildParameters($language),

            'can_edit' => $this->when(
                $request->user('sanctum'),
                fn () => $request->user('sanctum')->id === $this->user_id && ($this->isEditable() || $this->isPartiallyEditable()),
                false
            ),
            'can_donate' => $this->canReceiveDonations(),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Локалізація final_result з вкладеним description
     */
    private function localizeFinalResult($finalResult, ?string $language): mixed
    {
        if (! is_array($finalResult)) {
            return $finalResult;
        }

        // art-ua-info: final_result — список блоків роботи (type: image|link), а не
        // одиничний об'єкт { type, url, urls, description } як у save-art — конвертуємо
        // шляхи зображень в URL так само, як у content_blocks.
        if (array_is_list($finalResult)) {
            return array_map(function ($block) {
                if (is_array($block) && isset($block['image'])) {
                    $block['image'] = Storage::url($block['image']);
                    $block['image_url'] = $block['image'];
                }

                return $block;
            }, $finalResult);
        }

        if ($language === null) {
            return $finalResult;
        }

        if (isset($finalResult['description'])) {
            $finalResult['description'] = $this->localizeField($finalResult['description'], $language);
        }

        return $finalResult;
    }

    /**
     * Локалізація content_blocks з вкладеними мовними полями
     */
    private function localizeContentBlocks($contentBlocks, ?string $language): mixed
    {
        if (! is_array($contentBlocks)) {
            return $contentBlocks;
        }

        // Якщо мова не вказана - повертаємо як є, але з перетворенням image в URL
        if ($language === null) {
            return array_map(function ($block) {
                if (is_array($block) && isset($block['image'])) {
                    $block['image'] = Storage::url($block['image']);
                    $block['image_url'] = $block['image'];
                }

                return $block;
            }, $contentBlocks);
        }

        return array_map(function ($block) use ($language) {
            if (! is_array($block)) {
                return $block;
            }

            $localized = ['type' => $block['type'] ?? null];

            if ($block['type'] === 'heading') {
                $localized['heading_level'] = $block['heading_level'] ?? 'h2';
                $localized['heading_text'] = $this->localizeField($block['heading_text'] ?? null, $language);
            } elseif ($block['type'] === 'paragraph') {
                $localized['paragraph_text'] = $this->localizeField($block['paragraph_text'] ?? null, $language);
            } elseif ($block['type'] === 'image') {
                $localized['image'] = isset($block['image']) ? Storage::url($block['image']) : null;
                $localized['image_alt'] = $this->localizeField($block['image_alt'] ?? null, $language);
                $localized['image_caption'] = $this->localizeField($block['image_caption'] ?? null, $language);
            } elseif ($block['type'] === 'link') {
                $localized['url'] = $block['url'] ?? null;
            }

            return $localized;
        }, $contentBlocks);
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
                'type' => 'team',
            ];
        }

        // Перевіряємо чи це юридична особа за полем проєкту
        $isLegal = $this->is_legal;

        if ($isLegal && $user->profileLegal) {
            // Юридична особа - дані з ProfileLegal
            return [
                'id' => $user->id,
                'name' => $user->profileLegal->name,
                'slug' => $user->slug ?? null,
                'avatar_url' => $user->profileLegal->logo ? Storage::url($user->profileLegal->logo) : null,
                'profession' => $user->profession,
                'type' => 'legal',
            ];
        } else {
            // Фізична особа - дані з User
            return [
                'id' => $user->id,
                'name' => $user->full_name ?? $user->name,
                'slug' => $user->slug ?? null,
                'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
                'profession' => $user->profession,
                'type' => 'personal',
            ];
        }
    }
}

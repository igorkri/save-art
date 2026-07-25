<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\LocalizesFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\Donation
 *
 * @OA\Schema(
 *     schema="Donation",
 *     title="Donation",
 *     description="Донат на проєкт або платформу",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="donation_type", type="string", enum={"project", "platform"}, example="project", description="Тип донату: project - на проект, platform - на платформу"),
 *     @OA\Property(property="project", type="object", nullable=true, description="Проект (null для донатів на платформу)",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="slug", type="string", example="miy-proekt"),
 *         @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *         @OA\Property(property="cover_url", type="string", nullable=true, example="http://save-art-web.ddev.site/storage/projects/covers/1.jpg"),
 *         @OA\Property(property="author_name", type="string", nullable=true, example="Іван Петренко"),
 *         @OA\Property(property="author_avatar_url", type="string", nullable=true, example="http://save-art-web.ddev.site/storage/avatars/1.jpg")
 *     ),
 *     @OA\Property(property="amount", type="number", format="float", example=1000.00),
 *     @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
 *     @OA\Property(property="status", type="string", enum={"pending", "paid", "failed", "refunded"}, example="paid"),
 *     @OA\Property(property="status_label", type="string", example="Оплачено"),
 *     @OA\Property(property="is_anonymous", type="boolean", example=false),
 *     @OA\Property(property="is_public", type="boolean", example=true, description="Чи показувати донат на публічному профілі мецената"),
 *     @OA\Property(property="donor_name", type="string", nullable=true, example="Іван Петренко"),
 *     @OA\Property(property="bonus", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="title", ref="#/components/schemas/LocalizedString")
 *     ),
 *     @OA\Property(property="message", type="string", nullable=true, example="Успіхів!"),
 *     @OA\Property(property="paid_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class DonationResource extends JsonResource
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
            'donation_type' => $this->donation_type ?? 'project',

            'project' => $this->project ? [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                'title' => $this->localizeField($this->project->title, $language),
                'cover_url' => $this->project->cover ? Storage::url($this->project->cover) : null,
                'author_name' => $this->project->user?->name,
                'author_avatar_url' => $this->project->user?->avatar ? Storage::url($this->project->user->avatar) : null,
            ] : null,

            'amount' => (float) $this->amount,
            'currency' => $this->currency instanceof \App\Enums\Currency
                ? $this->currency->value
                : (string) $this->currency,

            'status' => $this->status,
            'status_label' => $this->getDonationStatusLabel($language),

            'is_anonymous' => $this->is_anonymous,
            'is_public' => $this->is_public,
            'donor_name' => $this->is_anonymous ? null : $this->donor_name,

            'bonus' => $this->whenLoaded('bonus', function () use ($language) {
                return [
                    'id' => $this->bonus->id,
                    'title' => $this->localizeField($this->bonus->title, $language),
                ];
            }),

            'message' => $this->message,

            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    /**
     * Отримати локалізовану назву статусу донату
     */
    private function getDonationStatusLabel(?string $language): string
    {
        $labels = [
            'pending' => ['uk' => 'Очікує', 'en' => 'Pending'],
            'paid' => ['uk' => 'Оплачено', 'en' => 'Paid'],
            'failed' => ['uk' => 'Помилка', 'en' => 'Failed'],
            'refunded' => ['uk' => 'Повернено', 'en' => 'Refunded'],
        ];

        $statusLabels = $labels[$this->status] ?? ['uk' => $this->status, 'en' => $this->status];

        return $statusLabels[$language ?? 'uk'];
    }
}

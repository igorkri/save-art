<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
 *         @OA\Property(property="title", ref="#/components/schemas/LocalizedString")
 *     ),
 *     @OA\Property(property="amount", type="number", format="float", example=1000.00),
 *     @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
 *     @OA\Property(property="status", type="string", enum={"pending", "paid", "failed", "refunded"}, example="paid"),
 *     @OA\Property(property="status_label", type="string", example="Оплачено"),
 *     @OA\Property(property="is_anonymous", type="boolean", example=false),
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
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'donation_type' => $this->donation_type ?? 'project',

            'project' => $this->project ? [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                'title' => $this->project->title,
            ] : null,

            'amount' => (float) $this->amount,
            'currency' => $this->currency instanceof \App\Enums\Currency
                ? $this->currency->value
                : (string) $this->currency,

            'status' => $this->status,
            'status_label' => match ($this->status) {
                'pending' => 'Очікує',
                'paid' => 'Оплачено',
                'failed' => 'Помилка',
                'refunded' => 'Повернено',
                default => $this->status,
            },

            'is_anonymous' => $this->is_anonymous,
            'donor_name' => $this->is_anonymous ? null : $this->donor_name,

            'bonus' => $this->whenLoaded('bonus', function () {
                return [
                    'id' => $this->bonus->id,
                    'title' => $this->bonus->title,
                ];
            }),

            'message' => $this->message,

            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

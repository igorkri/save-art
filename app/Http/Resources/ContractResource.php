<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @mixin \App\Models\Contract
 *
 * @OA\Schema(
 *     schema="Contract",
 *     title="Contract",
 *     description="Контракт митця з платформою (03.7.5 Profile Authorized - New - Contract)",
 *
 *     @OA\Property(property="id", type="integer", example=1, description="ID контракту"),
 *     @OA\Property(property="template_version", type="string", example="1.0", description="Версія шаблону контракту"),
 *     @OA\Property(property="status", type="string", enum={"pending", "signed", "rejected", "expired"}, example="pending", description="Статус контракту"),
 *     @OA\Property(property="status_label", type="string", example="Очікує підписання", description="Людиночитабельний статус"),
 *     @OA\Property(property="sign_service", type="string", nullable=true, enum={"diia", "vchasno", "iit", "manual"}, example="diia", description="Сервіс підпису (якщо підписано)"),
 *     @OA\Property(property="sign_service_label", type="string", nullable=true, example="Дія.Підпис", description="Назва сервісу підпису"),
 *     @OA\Property(property="is_pending", type="boolean", example=true, description="Чи очікує підписання"),
 *     @OA\Property(property="is_signed", type="boolean", example=false, description="Чи підписано"),
 *     @OA\Property(property="is_expired", type="boolean", example=false, description="Чи прострочено"),
 *     @OA\Property(property="signed_at", type="string", format="date-time", nullable=true, example="2025-01-05T12:00:00+00:00", description="Дата підписання"),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, example="2025-02-04T12:00:00+00:00", description="Дата закінчення терміну дії"),
 *     @OA\Property(property="file_url", type="string", nullable=true, example="https://example.com/storage/contracts/1_2025-01-05.pdf", description="URL файлу контракту"),
 *     @OA\Property(property="signed_file_url", type="string", nullable=true, example="https://example.com/storage/contracts/signed/1_signed.pdf", description="URL підписаного файлу"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-05T12:00:00+00:00", description="Дата створення"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-05T12:00:00+00:00", description="Дата оновлення")
 * )
 */
class ContractResource extends JsonResource
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
            'template_version' => $this->template_version,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'sign_service' => $this->sign_service?->value,
            'sign_service_label' => $this->sign_service?->label(),
            'is_pending' => $this->isPending(),
            'is_signed' => $this->isSigned(),
            'is_expired' => $this->isExpired(),
            'signed_at' => $this->signed_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'file_url' => $this->file_path ? Storage::url($this->file_path) : null,
            'signed_file_url' => $this->signed_file_path ? Storage::url($this->signed_file_path) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

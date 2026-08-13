<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProfileLegal",
 *     title="ProfileLegal",
 *     description="Юридичні дані профілю (03.7.1)",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Чи активний юридичний профіль"),
 *     @OA\Property(property="currency", type="string", nullable=true, example="UAH", description="Валюта"),
 *     @OA\Property(property="logo", type="string", nullable=true, example="logos/company.jpg", description="Логотип"),
 *     @OA\Property(property="name", type="string", nullable=true, description="Назва компанії", example="ТОВ Мистецтво"),
 *     @OA\Property(property="edrpou", type="string", nullable=true, example="12345678", description="Код ЄДРПОУ (8 цифр)"),
 *     @OA\Property(property="authorized_person", type="string", nullable=true, description="Уповноважена особа", example="Іван Петренко"),
 *     @OA\Property(property="address", type="string", nullable=true, description="Адреса", example="м. Київ, вул. Хрещатик, 1"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+380501234567", description="Телефон"),
 *     @OA\Property(property="email", type="string", nullable=true, example="company@example.com", description="Email"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ProfileLegalResource extends JsonResource
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
            'user_id' => $this->user_id,
            'is_active' => $this->is_active,
            'currency' => $this->currency,
            'logo' => $this->logo ? Storage::url(ltrim(preg_replace('#^/?storage/#', '', $this->logo), '/')) : null,
            'name' => $this->name,
            'edrpou' => $this->edrpou,
            'authorized_person' => $this->authorized_person,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

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
 *     @OA\Property(property="name", type="object", nullable=true, description="Назва компанії (мультимовне)",
 *         @OA\Property(property="uk", type="string", example="ТОВ Мистецтво"),
 *         @OA\Property(property="en", type="string", example="Art LLC")
 *     ),
 *     @OA\Property(property="edrpou", type="string", nullable=true, example="12345678", description="Код ЄДРПОУ (8 цифр)"),
 *     @OA\Property(property="authorized_person", type="object", nullable=true, description="Уповноважена особа (мультимовне)",
 *         @OA\Property(property="uk", type="string", example="Іван Петренко"),
 *         @OA\Property(property="en", type="string", example="Ivan Petrenko")
 *     ),
 *     @OA\Property(property="address", type="object", nullable=true, description="Адреса (мультимовне)",
 *         @OA\Property(property="uk", type="string", example="м. Київ, вул. Хрещатик, 1"),
 *         @OA\Property(property="en", type="string", example="Kyiv, Khreshchatyk str., 1")
 *     ),
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
            'logo' => $this->logo ? Storage::url($this->logo) : null,
            'name' => $this->name ?? ['en' => null, 'uk' => null],
            'edrpou' => $this->edrpou,
            'authorized_person' => $this->authorized_person ?? ['en' => null, 'uk' => null],
            'address' => $this->address ?? ['en' => null, 'uk' => null],
            'phone' => $this->phone,
            'email' => $this->email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

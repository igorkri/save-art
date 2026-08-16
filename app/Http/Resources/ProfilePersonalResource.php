<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProfilePersonal",
 *     title="ProfilePersonal",
 *     description="Персональні дані профілю (03.7.2)",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="avatar", type="string", nullable=true, example="avatars/user1.jpg", description="Шлях до аватара"),
 *     @OA\Property(property="full_name", type="string", nullable=true, description="Повне ім'я", example="Іван Петренко"),
 *     @OA\Property(property="profession", type="string", nullable=true, description="Професія", example="Художник"),
 *     @OA\Property(property="tags", type="string", nullable=true, description="Теги/спеціалізації", example="живопис, графіка"),
 *     @OA\Property(property="country", type="string", nullable=true, description="Країна", example="Україна"),
 *     @OA\Property(property="region", type="string", nullable=true, description="Регіон/область", example="Київська область"),
 *     @OA\Property(property="city", type="string", nullable=true, description="Місто", example="Київ"),
 *     @OA\Property(property="postal_code", type="string", nullable=true, example="01001", description="Поштовий індекс"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+380501234567", description="Персональний телефон"),
 *     @OA\Property(property="profile_type", type="string", nullable=true, example="artist", description="Тип профілю: artist або patron"),
 *     @OA\Property(property="profile_completed", type="boolean", example=false, description="Чи зберігав користувач обов'язкові поля профілю в кабінеті хоча б раз"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Опис/біографія", example="Український художник"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @mixin User
 */
class ProfilePersonalResource extends JsonResource
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
            'user_id' => $this->id,
            'avatar' => $this->avatar ? Storage::url($this->avatar) : null,
            'full_name' => $this->full_name,
            'profession' => $this->profession,
            'tags' => $this->tags,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'profile_type' => $this->profile_type?->value,
            'profile_completed' => $this->isProfileComplete(),
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

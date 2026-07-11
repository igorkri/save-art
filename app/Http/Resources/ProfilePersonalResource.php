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
 *     @OA\Property(property="full_name", type="object", nullable=true, description="Повне ім'я (мультимовне)",
 *         @OA\Property(property="uk", type="string", example="Іван Петренко"),
 *         @OA\Property(property="en", type="string", example="Ivan Petrenko")
 *     ),
 *     @OA\Property(property="profession", type="object", nullable=true, description="Професія (мультимовне)",
 *         @OA\Property(property="uk", type="string", example="Художник"),
 *         @OA\Property(property="en", type="string", example="Artist")
 *     ),
 *     @OA\Property(property="tags", type="object", nullable=true, description="Теги/спеціалізації (мультимовне)",
 *         @OA\Property(property="uk", type="string", example="живопис, графіка"),
 *         @OA\Property(property="en", type="string", example="painting, graphics")
 *     ),
 *     @OA\Property(property="country", type="object", nullable=true, description="Країна (мультимовне)",
 *         @OA\Property(property="uk", type="string", example="Україна"),
 *         @OA\Property(property="en", type="string", example="Ukraine")
 *     ),
 *     @OA\Property(property="region", type="object", nullable=true, description="Регіон/область (мультимовне)",
 *         @OA\Property(property="uk", type="string", example="Київська область"),
 *         @OA\Property(property="en", type="string", example="Kyiv region")
 *     ),
 *     @OA\Property(property="city", type="object", nullable=true, description="Місто (мультимовне)",
 *         @OA\Property(property="uk", type="string", example="Київ"),
 *         @OA\Property(property="en", type="string", example="Kyiv")
 *     ),
 *     @OA\Property(property="postal_code", type="string", nullable=true, example="01001", description="Поштовий індекс"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+380501234567", description="Персональний телефон"),
 *     @OA\Property(property="profile_type", type="string", nullable=true, example="artist", description="Тип профілю: artist або patron"),
 *     @OA\Property(property="description", type="object", nullable=true, description="Опис/біографія (мультимовне)",
 *         @OA\Property(property="uk", type="string", example="Український художник"),
 *         @OA\Property(property="en", type="string", example="Ukrainian artist")
 *     ),
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
            'full_name' => $this->full_name ?? ['en' => null, 'uk' => null],
            'profession' => $this->profession ?? ['en' => null, 'uk' => null],
            'tags' => $this->tags ?? ['en' => null, 'uk' => null],
            'country' => $this->country ?? ['en' => null, 'uk' => null],
            'region' => $this->region ?? ['en' => null, 'uk' => null],
            'city' => $this->city ?? ['en' => null, 'uk' => null],
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'profile_type' => $this->profile_type?->value,
            'description' => $this->description ?? ['en' => null, 'uk' => null],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

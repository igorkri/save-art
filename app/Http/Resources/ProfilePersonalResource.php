<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'user_id' => $this->user_id,
            'avatar' => $this->avatar,
            'full_name' => $this->full_name ?? ['en' => null, 'uk' => null],
            'profession' => $this->profession ?? ['en' => null, 'uk' => null],
            'tags' => $this->tags ?? ['en' => null, 'uk' => null],
            'country' => $this->country ?? ['en' => null, 'uk' => null],
            'region' => $this->region ?? ['en' => null, 'uk' => null],
            'city' => $this->city ?? ['en' => null, 'uk' => null],
            'postal_code' => $this->postal_code,
            'role' => $this->role,
            'description' => $this->description ?? ['en' => null, 'uk' => null],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

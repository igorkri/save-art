<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'currency' => $this->currency,
            'is_legal' => $this->is_legal,
            'logo' => $this->logo,
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

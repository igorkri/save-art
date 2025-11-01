<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProfileDocumentResource extends JsonResource
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
            'file_path' => $this->file_path,
            'file_url' => Storage::disk('public')->url($this->file_path),
            'hash' => $this->hash,
            'signed_file_path' => $this->signed_file_path,
            'signed_file_url' => $this->signed_file_path ? Storage::disk('public')->url($this->signed_file_path) : null,
            'sign_status' => $this->sign_status,
            'signed_at' => $this->signed_at?->toIso8601String(),
            'service' => $this->service,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

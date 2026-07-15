<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProfileDocument",
 *     title="ProfileDocument",
 *     description="Документ профілю",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="file_path", type="string", example="profile_documents/doc1.pdf"),
 *     @OA\Property(property="file_url", type="string", example="http://save-art-web.ddev.site/storage/profile_documents/doc1.pdf"),
 *     @OA\Property(property="hash", type="string", example="sha256hash...", description="Хеш файлу"),
 *     @OA\Property(property="signed_file_path", type="string", nullable=true, example="profile_documents/doc1_signed.pdf"),
 *     @OA\Property(property="signed_file_url", type="string", nullable=true),
 *     @OA\Property(property="sign_status", type="string", enum={"pending", "signed", "rejected"}, example="pending"),
 *     @OA\Property(property="signed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="service", type="string", enum={"diia", "vchasno", "iit", "manual"}, example="diia"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
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

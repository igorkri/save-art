<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\SignService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contract model represents a contract between an artist and the platform.
 * Corresponds to screens 03.7.5 and 03.7.5.1 from Figma specification.
 *
 * @property int $id
 * @property int $user_id
 * @property string $template_version
 * @property string|null $file_path
 * @property string|null $signed_file_path
 * @property ContractStatus $status
 * @property SignService|null $sign_service
 * @property string|null $signature_base64
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 */
class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_version',
        'file_path',
        'signed_file_path',
        'status',
        'sign_service',
        'signature_base64',
        'signed_at',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'sign_service' => SignService::class,
            'signed_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the contract is pending signature.
     */
    public function isPending(): bool
    {
        return $this->status === ContractStatus::Pending;
    }

    /**
     * Check if the contract is signed.
     */
    public function isSigned(): bool
    {
        return $this->status === ContractStatus::Signed;
    }

    /**
     * Check if the contract has expired.
     */
    public function isExpired(): bool
    {
        return $this->status === ContractStatus::Expired
            || ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Scope for pending contracts.
     */
    public function scopePending($query)
    {
        return $query->where('status', ContractStatus::Pending);
    }

    /**
     * Scope for signed contracts.
     */
    public function scopeSigned($query)
    {
        return $query->where('status', ContractStatus::Signed);
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}

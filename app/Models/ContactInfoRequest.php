<?php

namespace App\Models;

use App\Enums\ContactInfoRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $requester_id
 * @property int $owner_id
 * @property int|null $project_id
 * @property ContactInfoRequestStatus $status
 * @property \Carbon\Carbon|null $decided_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User $requester
 * @property-read \App\Models\User $owner
 * @property-read \App\Models\Project|null $project
 */
class ContactInfoRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'owner_id',
        'project_id',
        'status',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactInfoRequestStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

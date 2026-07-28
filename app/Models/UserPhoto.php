<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $image
 * @property int $likes_count
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User $user
 */
class UserPhoto extends Model
{
    /** @use HasFactory<\Database\Factories\UserPhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image',
        'likes_count',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'likes_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(UserPhotoLike::class);
    }
}

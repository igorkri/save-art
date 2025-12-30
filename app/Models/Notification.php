<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property NotificationType $type
 * @property string $title
 * @property string|null $message
 * @property array|null $data
 * @property \Carbon\Carbon|null $read_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User $user
 * @property-read bool $is_read
 */
class Notification extends Model
{
    use HasFactory;

    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /**
     * Користувач, якому належить сповіщення
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Перевірити, чи прочитане сповіщення
     */
    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Позначити як прочитане
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Позначити як непрочитане
     */
    public function markAsUnread(): void
    {
        if ($this->is_read) {
            $this->update(['read_at' => null]);
        }
    }

    /**
     * Scope для непрочитаних сповіщень
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope для прочитаних сповіщень
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope для сповіщень конкретного типу
     */
    public function scopeOfType($query, NotificationType $type)
    {
        return $query->where('type', $type);
    }
}

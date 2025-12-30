<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель для повідомлень між користувачем та адміністрацією
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $admin_id
 * @property int|null $project_id
 * @property string $content
 * @property string $direction
 * @property string|null $subject
 * @property \Carbon\Carbon|null $read_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read User|null $admin
 * @property-read Project|null $project
 */
class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'admin_id',
        'project_id',
        'content',
        'direction',
        'subject',
        'read_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Користувач (власник чату)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Адміністратор, який відправив/отримав повідомлення
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Проєкт, до якого відноситься повідомлення (опціонально)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Чи прочитане повідомлення
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Позначити як прочитане
     */
    public function markAsRead(): void
    {
        if (! $this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Чи повідомлення від користувача до адміна
     */
    public function isFromUser(): bool
    {
        return $this->direction === 'user_to_admin';
    }

    /**
     * Чи повідомлення від адміна до користувача
     */
    public function isFromAdmin(): bool
    {
        return $this->direction === 'admin_to_user';
    }

    /**
     * Scope для непрочитаних повідомлень
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Message>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Message>
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope для повідомлень від адміністрації
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Message>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Message>
     */
    public function scopeFromAdmin($query)
    {
        return $query->where('direction', 'admin_to_user');
    }

    /**
     * Scope для повідомлень від користувача
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Message>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Message>
     */
    public function scopeFromUser($query)
    {
        return $query->where('direction', 'user_to_admin');
    }
}

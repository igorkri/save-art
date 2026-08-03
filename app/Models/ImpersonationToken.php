<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Одноразовий короткоживучий грант, який видає адмінка (Filament) для входу
 * на фронтенд під обраним користувачем ("Увійти як"). Сам токен ніколи не
 * містить реального Sanctum-токена — фронтенд обмінює його на справжній
 * Bearer-токен через POST /v1/auth/impersonate/{token}/exchange.
 *
 * @property int $id
 * @property string $token
 * @property int $user_id
 * @property int $created_by
 * @property string|null $project_slug
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $used_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\User $admin
 */
class ImpersonationToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'user_id',
        'created_by',
        'project_slug',
        'target_app',
        'expires_at',
        'used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Користувач, під яким адмін хоче увійти
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Адміністратор, що видав грант
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Видати новий одноразовий грант для входу під користувачем.
     * Живе 2 хвилини — цього достатньо, щоб браузер встиг відкрити фронтенд
     * і одразу обміняти токен на справжню сесію.
     */
    public static function issue(User $target, User $admin, ?string $projectSlug = null, string $targetApp = 'save_art'): self
    {
        return self::create([
            'token' => Str::random(64),
            'user_id' => $target->id,
            'created_by' => $admin->id,
            'project_slug' => $projectSlug,
            'target_app' => $targetApp,
            'expires_at' => Carbon::now()->addMinutes(2),
        ]);
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    /**
     * Куди на фронтенді потрапити після обміну токена: одразу на проєкт
     * автора (якщо грант видано з картки проєкту) або в загальний кабінет.
     * Формат шляху залежить від фронтенду — save-art (SPA) та art-ua-info
     * (Next.js) мають різну структуру роутів кабінету.
     */
    public function redirectPath(): string
    {
        if ($this->target_app === 'art_ua_info') {
            return $this->project_slug
                ? "/profile/{$this->user->slug}/edit-project?edit={$this->project_slug}"
                : "/profile/{$this->user->slug}";
        }

        return $this->project_slug
            ? "/profile/private/{$this->project_slug}"
            : '/profile/private';
    }
}

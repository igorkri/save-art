<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Одноразовий короткоживучий грант для входу з Filament на фронтенд. Його
 * видає або адміністратор для входу під обраним користувачем ("Увійти як"),
 * або сам користувач зі свого кабінету. Токен ніколи не містить реального
 * Sanctum-токена — фронтенд обмінює його на Bearer-токен через
 * POST /v1/auth/impersonate/{token}/exchange.
 *
 * @property int $id
 * @property string $token
 * @property int $user_id
 * @property int $created_by
 * @property string|null $project_slug
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $used_at
 * @property-read User $user
 * @property-read User $admin
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
     * Формат шляху залежить від призначення — кабінет save-art, реальна
     * сторінка попереднього перегляду або кабінет art-ua-info.
     */
    public function redirectPath(): string
    {
        if ($this->target_app === 'save_art_project_preview') {
            return $this->project_slug
                ? "/project/{$this->project_slug}"
                : '/projects';
        }

        // Модератор заходить на art-ua-info під собою (не автором), одразу на публічну
        // сторінку роботи — щоб скористатись плаваючою панеллю модерації на сайті.
        if ($this->target_app === 'art_ua_info_project') {
            return $this->project_slug ? "/works/{$this->project_slug}" : '/';
        }

        if ($this->target_app === 'art_ua_info') {
            // /profile/{slug} без проєкту — теж лише SSO-редирект-заглушка
            // назад у Filament (art-ua-info/.../profile/[slug]/page.tsx),
            // тож без конкретного проєкту веде в нескінченний бумеранг.
            // /profile/{slug}/edit-project — справжня робоча сторінка.
            return $this->project_slug
                ? "/profile/{$this->user->slug}/edit-project?edit={$this->project_slug}"
                : '/';
        }

        // Особистий кабінет save-art (React SPA) переїхав у Filament-панель
        // /profile/private там більше не існує (matчить /profile/:slug і
        // 404-ить). Із проєктом ведемо на його публічну сторінку — вона
        // і раніше була єдиним робочим місцем перегляду проєкту у фронтенді.
        return $this->project_slug
            ? "/project/{$this->project_slug}"
            : '/';
    }
}

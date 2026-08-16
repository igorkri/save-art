<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Одноразовий короткоживучий грант для входу з SPA (save-art-web, Sanctum
 * Bearer-токен) у Filament-панель "profile" (сесійний web-guard) без
 * повторного вводу логіна/пароля. На відміну від ImpersonationToken (адмін
 * входить під іншим користувачем), тут користувач видає грант сам собі.
 *
 * @property int $id
 * @property string $token
 * @property int $user_id
 * @property string|null $redirect_path
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $used_at
 * @property-read User $user
 */
class ProfileSsoToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'user_id',
        'redirect_path',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Видати новий одноразовий грант. Живе 2 хвилини — цього достатньо, щоб
     * браузер встиг відкрити цільову сторінку одразу після переходу.
     */
    public static function issue(User $user, ?string $redirectPath = null): self
    {
        return self::create([
            'token' => Str::random(64),
            'user_id' => $user->id,
            'redirect_path' => $redirectPath,
            'expires_at' => Carbon::now()->addMinutes(2),
        ]);
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}

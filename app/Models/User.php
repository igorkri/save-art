<?php

namespace App\Models;

use App\Enums\ProfileType;
use App\Notifications\ResetPasswordNotification;
use App\Traits\LocalizesAttributes;
use App\UserRole;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Модель користувача
 *
 * @property int $id
 * @property string $email
 * @property string|null $slug
 * @property UserRole $role Системна роль (admin, moderator, user)
 * @property string|null $avatar Шлях до аватара
 * @property string|null $full_name ПІБ
 * @property string|null $profession Професія
 * @property string|null $tags Теги
 * @property string|null $country Країна
 * @property string|null $region Область/регіон
 * @property string|null $city Місто
 * @property string|null $postal_code Поштовий індекс
 * @property string|null $phone Персональний телефон
 * @property ProfileType|null $profile_type Тип профілю (artist/patron)
 * @property Carbon|null $profile_completed_at Коли користувач вперше зберіг обов'язкові поля профілю в кабінеті
 * @property string|null $description Опис/біографія
 * @property bool $is_blocked Чи заблокований
 * @property Carbon|null $blocked_until Дата закінчення блокування
 * @property Carbon|null $deletion_requested_at Дата запиту на видалення
 * @property Carbon|null $email_verified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, \Laravel\Sanctum\HasApiTokens, LocalizesAttributes, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'email_verified_at',
        'password',
        'role',
        'slug',
        'avatar',
        'full_name',
        'profession',
        'tags',
        'country',
        'region',
        'city',
        'postal_code',
        'phone',
        'profile_type',
        'description',
        'is_blocked',
        'blocked_until',
        'deletion_requested_at',
        'has_seen_new_project_hint',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<string>
     */
    protected $appends = [
        'display_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'profile_type' => ProfileType::class,
            'profile_completed_at' => 'datetime',
            'is_blocked' => 'boolean',
            'blocked_until' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'has_seen_new_project_hint' => 'boolean',
        ];
    }

    public function getTagsAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? implode(', ', $decoded) : $value;
    }

    public function setTagsAttribute(mixed $value): void
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if ($value !== null && ! is_array($value)) {
            $value = [$value];
        }

        if (is_array($value)) {
            $value = array_values(array_filter(
                array_map(static fn (mixed $tag): string => trim((string) $tag), $value),
                static fn (string $tag): bool => $tag !== '',
            ));
        }

        $this->attributes['tags'] = $value === null || $value === []
            ? null
            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Перевірити, чи має користувач вказану роль
     */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Перевірити, чи має користувач будь-яку з вказаних ролей
     *
     * @param  UserRole[]  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Перевірити, чи може користувач отримати доступ до Filament панелі
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->isAdmin();
        }

        if ($panel->getId() === 'profile') {
            return $this->isArtist();
        }

        return false;
    }

    /**
     * Перевірити, чи є користувач адміністратором
     */
    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    /**
     * Перевірити, чи може користувач модерувати контент
     */
    public function canModerate(): bool
    {
        return $this->role->canModerate();
    }

    /**
     * Отримати назву ролі українською
     */
    public function getRoleLabelAttribute(): string
    {
        return $this->role->getLabel();
    }

    /**
     * Юридичний профіль користувача
     */
    public function profileLegal(): HasOne
    {
        return $this->hasOne(ProfileLegal::class);
    }

    /**
     * Соціальні мережі користувача
     */
    public function profileSocial(): HasOne
    {
        return $this->hasOne(ProfileSocial::class);
    }

    /**
     * Документи профілю користувача
     */
    public function profileDocuments(): HasMany
    {
        return $this->hasMany(ProfileDocument::class);
    }

    /**
     * Перевірити, чи є користувач митцем
     */
    public function isArtist(): bool
    {
        return $this->profile_type === ProfileType::Artist;
    }

    /**
     * Перевірити, чи є користувач меценатом
     */
    public function isPatron(): bool
    {
        return $this->profile_type === ProfileType::Patron;
    }

    /**
     * Перевірити, чи є користувач організацією
     */
    public function isOrganization(): bool
    {
        return $this->profile_type === ProfileType::Organization;
    }

    /**
     * Чи заповнив користувач обов'язкові поля профілю в кабінеті хоча б раз
     * (App\Filament\Profile\Pages\Auth\EditProfile::handleRecordUpdate).
     * На відміну від profile_type (проставляється дефолтом при реєстрації),
     * це прапорець реальної дії користувача — саме на нього орієнтуються
     * фронтенди, показуючи повне меню кабінету.
     */
    public function isProfileComplete(): bool
    {
        return $this->profile_completed_at !== null;
    }

    /**
     * Команди, учасником яких є користувач
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')->withPivot('sort_order')->withTimestamps();
    }

    /**
     * Контракти користувача
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Активний (підписаний) контракт користувача
     */
    public function activeContract(): HasOne
    {
        return $this->hasOne(Contract::class)->signed()->latest();
    }

    /**
     * Перевірити, чи є у користувача підписаний контракт
     */
    public function hasSignedContract(): bool
    {
        return $this->contracts()->signed()->exists();
    }

    /**
     * Проєкти користувача
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Донати користувача (як донора)
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Повідомлення користувача (чат з адміном)
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Непрочитані повідомлення від адміністрації
     */
    public function unreadMessages(): HasMany
    {
        return $this->messages()->fromAdmin()->unread();
    }

    /**
     * Сповіщення користувача
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Чи реально заблокований користувач (з урахуванням дати закінчення блоку)
     */
    public function isActuallyBlocked(): bool
    {
        if (! $this->is_blocked) {
            return false;
        }
        if ($this->blocked_until === null) {
            return true;
        }

        return now()->lessThan($this->blocked_until);
    }

    /**
     * Accessor для is_actually_blocked
     */
    public function getIsActuallyBlockedAttribute(): bool
    {
        return $this->isActuallyBlocked();
    }

    /**
     * Отримати відображуване ім'я користувача
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: 'Не вказано';
    }

    /**
     * Аватар користувача у Filament замість згенерованих ініціалів.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if (blank($this->avatar)) {
            return null;
        }

        if (Str::startsWith($this->avatar, ['http://', 'https://', 'data:image/'])) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    /**
     * Отримати ім'я для авторизації (сумісність з Laravel Auth)
     * Laravel очікує поле name, тому створюємо accessor
     */
    public function getNameAttribute(): string
    {
        return $this->full_name ?: 'User #'.$this->id;
    }

    /**
     * Надсилає лист скидання пароля з брендованим шаблоном мовою поточного запиту
     * (App::setLocale виставляється в ForgotPasswordController за локаллю з фронтенду).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}

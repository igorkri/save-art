<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ProfileType;
use App\Traits\LocalizesAttributes;
use App\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Модель користувача
 *
 * @property int $id
 * @property string $email
 * @property string|null $slug
 * @property UserRole $role Системна роль (admin, moderator, user)
 * @property string|null $avatar Шлях до аватара
 * @property array|null $full_name ПІБ (мультимовне)
 * @property array|null $profession Професія (мультимовне)
 * @property array|null $tags Теги (мультимовне)
 * @property array|null $country Країна (мультимовне)
 * @property array|null $region Область/регіон (мультимовне)
 * @property array|null $city Місто (мультимовне)
 * @property string|null $postal_code Поштовий індекс
 * @property string|null $phone Персональний телефон
 * @property ProfileType|null $profile_type Тип профілю (artist/patron)
 * @property array|null $description Опис/біографія (мультимовне)
 * @property bool $is_blocked Чи заблокований
 * @property \Carbon\Carbon|null $blocked_until Дата закінчення блокування
 * @property \Carbon\Carbon|null $deletion_requested_at Дата запиту на видалення
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
            'full_name' => 'array',
            'profession' => 'array',
            'tags' => 'array',
            'country' => 'array',
            'region' => 'array',
            'city' => 'array',
            'description' => 'array',
            'profile_type' => ProfileType::class,
            'is_blocked' => 'boolean',
            'blocked_until' => 'datetime',
            'deletion_requested_at' => 'datetime',
        ];
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
     * Повертає full_name['uk'] або full_name['en']
     */
    public function getDisplayNameAttribute(): string
    {
        if (! $this->full_name || ! is_array($this->full_name)) {
            return 'Не вказано';
        }

        return $this->full_name['uk'] ?: ($this->full_name['en'] ?: 'Не вказано');
    }

    /**
     * Отримати ім'я для авторизації (сумісність з Laravel Auth)
     * Laravel очікує поле name, тому створюємо accessor
     */
    public function getNameAttribute(): string
    {
        if (! $this->full_name || ! is_array($this->full_name)) {
            return 'User #'.$this->id;
        }

        return $this->full_name['uk'] ?? $this->full_name['en'] ?? 'User #'.$this->id;
    }
}

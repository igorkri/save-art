<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, \Laravel\Sanctum\HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_blocked',
        'blocked_until',
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
            'is_blocked' => 'boolean',
            'blocked_until' => 'datetime',
        ];
    }

    /**
     * Проверить, имеет ли пользователь указанную роль
     */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Проверить, имеет ли пользователь любую из указанных ролей
     *
     * @param  UserRole[]  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Проверить, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    /**
     * Проверить, может ли пользователь модерировать контент
     */
    public function canModerate(): bool
    {
        return $this->role->canModerate();
    }

    /**
     * Получить название роли на русском языке
     */
    public function getRoleLabelAttribute(): string
    {
        return $this->role->getLabel();
    }

    public function profileLegal()
    {
        return $this->hasOne(ProfileLegal::class);
    }

    public function profilePersonal()
    {
        return $this->hasOne(ProfilePersonal::class);
    }

    public function profileSocial()
    {
        return $this->hasOne(ProfileSocial::class);
    }

    // profile_documents
    public function profileDocuments()
    {
        return $this->hasMany(ProfileDocument::class);
    }

    /**
     * Проєкти користувача
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Повідомлення користувача (чат з адміном)
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Непрочитані повідомлення від адміністрації
     */
    public function unreadMessages()
    {
        return $this->messages()->fromAdmin()->unread();
    }

    /**
     * Сповіщення користувача
     */
    public function notifications()
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
}

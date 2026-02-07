<?php

namespace App;

/**
 * Системні ролі користувачів (доступ до адмін-панелі)
 *
 * Примітка: для типу діяльності (митець/меценат) використовуйте App\Enums\ProfileType
 */
enum UserRole: string
{
    case Developer = 'developer';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case User = 'user';

    /**
     * Отримати назву ролі українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Developer => 'Розробник',
            self::Admin => 'Адміністратор',
            self::Moderator => 'Модератор',
            self::User => 'Користувач',
        };
    }

    /**
     * Отримати всі ролі з перекладами
     *
     * @return array<string, string>
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    /**
     * Перевірити, чи є роль адміністративною (доступ до адмін-панелі)
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::Developer, self::Admin]);
    }

    /**
     * Перевірити, чи може роль модерувати контент
     */
    public function canModerate(): bool
    {
        return in_array($this, [self::Developer, self::Admin, self::Moderator]);
    }
}

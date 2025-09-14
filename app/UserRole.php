<?php

namespace App;

enum UserRole: string
{
    case Developer = 'developer';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case Owner = 'owner';
    case Mecenat = 'mecenat';
    case User = 'user';

    /**
     * Получить название роли на русском языке
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Developer => 'Разработчик',
            self::Admin => 'Администратор',
            self::Moderator => 'Модератор',
            self::Owner => 'Владелец',
            self::Mecenat => 'Меценат',
            self::User => 'Пользователь',
        };
    }

    /**
     * Получить все роли с переводами
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
     * Проверить, является ли роль административной
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::Developer, self::Admin, self::Owner]);
    }

    /**
     * Проверить, может ли роль модерировать контент
     */
    public function canModerate(): bool
    {
        return in_array($this, [self::Developer, self::Admin, self::Owner, self::Moderator]);
    }
}

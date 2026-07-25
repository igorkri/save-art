<?php

namespace App\Enums;

enum NewsCategory: string
{
    case News = 'news';
    case Event = 'event';

    /**
     * Отримати назву категорії (підтримує мультимовність)
     */
    public function getLabel(?string $language = 'uk'): string
    {
        $labels = [
            'uk' => match ($this) {
                self::News => 'Новини',
                self::Event => 'Події',
            },
            'en' => match ($this) {
                self::News => 'News',
                self::Event => 'Events',
            },
        ];

        return $labels[$language] ?? $labels['uk'];
    }

    /**
     * Отримати всі категорії з перекладами
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
}
